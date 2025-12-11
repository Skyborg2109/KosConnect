<?php
session_start();
header('Content-Type: application/json');

include '../config/db.php';

$response = ['status' => 'error', 'message' => 'Terjadi kesalahan yang tidak diketahui.'];

// 1. Autentikasi: Pastikan pengguna adalah pemilik yang sudah login
if (!isset($_SESSION['user_logged_in']) || $_SESSION['role'] !== 'pemilik') {
    $response['message'] = 'Akses tidak sah. Silakan login kembali.';
    http_response_code(403);
    echo json_encode($response);
    exit();
}

$id_pemilik = $_SESSION['user_id'];
$id_booking = filter_var($_POST['id_booking'] ?? 0, FILTER_VALIDATE_INT);
$action = $_POST['action'] ?? ''; // 'confirm' atau 'reject'

if ($id_booking <= 0 || !in_array($action, ['confirm', 'reject'])) {
    $response['message'] = 'Data yang dikirim tidak valid.';
    http_response_code(400);
    echo json_encode($response);
    exit();
}

$conn->begin_transaction();
try {
    // Ambil detail booking untuk verifikasi dan notifikasi
    $stmt_details = $conn->prepare("
        SELECT b.id_penyewa, b.status, k.nama_kamar, t.nama_kost
        FROM booking b
        JOIN kamar k ON b.id_kamar = k.id_kamar
        JOIN kost t ON k.id_kost = t.id_kost
        WHERE b.id_booking = ? AND t.id_pemilik = ? FOR UPDATE
    ");
    $stmt_details->bind_param("ii", $id_booking, $id_pemilik);
    $stmt_details->execute();
    $details = $stmt_details->get_result()->fetch_assoc();

    if (!$details) {
        throw new Exception("Booking tidak ditemukan atau Anda tidak berhak mengaksesnya.");
    }
    if ($details['status'] !== 'pending') {
        throw new Exception("Pesanan ini sudah diproses sebelumnya.");
    }

    $id_penyewa = $details['id_penyewa'];
    $nama_kamar = $details['nama_kamar'];
    $nama_kost = $details['nama_kost'];

    if ($action === 'confirm') {
        $new_status = 'menunggu_pembayaran';
        $pesan_notif = "Pesanan Anda untuk kamar '{$nama_kamar}' di '{$nama_kost}' telah dikonfirmasi. Silakan lanjutkan ke pembayaran.";
        $link_notif = "/KosConnect/user/user_dashboard.php";
        $response['message'] = 'Pesanan berhasil dikonfirmasi.';
    } else { // reject
        $new_status = 'ditolak';
        $pesan_notif = "Mohon maaf, pesanan Anda untuk kamar '{$nama_kamar}' di '{$nama_kost}' telah ditolak.";
        $link_notif = "/KosConnect/user/user_dashboard.php";
        $response['message'] = 'Pesanan berhasil ditolak.';

        // Kembalikan status kamar menjadi 'tersedia'
        $stmt_kamar = $conn->prepare("UPDATE kamar SET status = 'tersedia' WHERE id_kamar = (SELECT id_kamar FROM booking WHERE id_booking = ?)");
        $stmt_kamar->bind_param("i", $id_booking);
        $stmt_kamar->execute();
    }

    // Update status booking
    $stmt_update = $conn->prepare("UPDATE booking SET status = ? WHERE id_booking = ?");
    $stmt_update->bind_param("si", $new_status, $id_booking);
    $stmt_update->execute();

    // Kirim notifikasi ke penyewa
    $stmt_notif = $conn->prepare("INSERT INTO notifications (id_user, pesan, link) VALUES (?, ?, ?)");
    $stmt_notif->bind_param("iss", $id_penyewa, $pesan_notif, $link_notif);
    $stmt_notif->execute();

    $conn->commit();
    $response['status'] = 'success';

} catch (Exception $e) {
    $conn->rollback();
    $response['message'] = $e->getMessage();
    http_response_code(500);
}

$conn->close();
echo json_encode($response);
?>