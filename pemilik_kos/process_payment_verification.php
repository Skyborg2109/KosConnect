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
$id_payment = filter_var($_POST['id_payment'] ?? 0, FILTER_VALIDATE_INT);
$id_booking = filter_var($_POST['id_booking'] ?? 0, FILTER_VALIDATE_INT);
$action = $_POST['action'] ?? ''; // 'verify' atau 'reject'

if ($id_payment <= 0 || $id_booking <= 0 || !in_array($action, ['verify', 'reject'])) {
    $response['message'] = 'Data yang dikirim tidak valid.';
    http_response_code(400);
    echo json_encode($response);
    exit();
}

// 2. Verifikasi bahwa pembayaran ini milik salah satu kos dari pemilik yang login
$stmt_check = $conn->prepare("
    SELECT 
        p.id_payment, 
        b.id_penyewa,
        k.nama_kamar,
        t.nama_kost
    FROM pembayaran p
    JOIN booking b ON p.id_booking = b.id_booking
    JOIN kamar k ON b.id_kamar = k.id_kamar
    JOIN kost t ON k.id_kost = t.id_kost
    WHERE p.id_payment = ? AND t.id_pemilik = ? AND b.id_booking = ? AND p.status_pembayaran = 'menunggu'
");
$stmt_check->bind_param("iii", $id_payment, $id_pemilik, $id_booking);
$stmt_check->execute();
$result_check = $stmt_check->get_result();
$details = $result_check->fetch_assoc();

if ($result_check->num_rows === 0) {
    $response['message'] = 'Pembayaran tidak ditemukan, sudah diproses, atau Anda tidak memiliki akses.';
    http_response_code(404);
    echo json_encode($response);
    exit();
}

// 3. Lakukan Aksi
$conn->begin_transaction();
try {
    if ($action === 'verify') {
        // Update status pembayaran menjadi 'berhasil'
        $new_payment_status = 'berhasil';
        $stmt_payment = $conn->prepare("UPDATE pembayaran SET status_pembayaran = ? WHERE id_payment = ?");
        $stmt_payment->bind_param("si", $new_payment_status, $id_payment);
        $stmt_payment->execute();

        // Update status booking menjadi 'dibayar' untuk menandakan proses selesai
        $new_booking_status = 'dibayar';
        $stmt_booking = $conn->prepare("UPDATE booking SET status = ? WHERE id_booking = ?");
        $stmt_booking->bind_param("si", $new_booking_status, $id_booking);
        $stmt_booking->execute();

        // Kirim notifikasi ke penyewa
        $id_penyewa = $details['id_penyewa'];
        $pesan_notif = "Pembayaran Anda untuk kamar '{$details['nama_kamar']}' telah dikonfirmasi. Pesanan Anda sekarang aktif.";
        $link_notif = '/KosConnect/user/user_dashboard.php';
        $stmt_notif = $conn->prepare("INSERT INTO notifications (id_user, pesan, link) VALUES (?, ?, ?)");
        $stmt_notif->bind_param("iss", $id_penyewa, $pesan_notif, $link_notif);
        $stmt_notif->execute();

        $response['message'] = 'Pembayaran telah berhasil diverifikasi.';

    } else { // 'reject'
        // Update status pembayaran menjadi 'gagal'
        $new_payment_status = 'gagal';
        $stmt_payment = $conn->prepare("UPDATE pembayaran SET status_pembayaran = ? WHERE id_payment = ?");
        $stmt_payment->bind_param("si", $new_payment_status, $id_payment);
        $stmt_payment->execute();

        // Kembalikan status booking menjadi 'menunggu_pembayaran' agar penyewa bisa upload ulang
        $new_booking_status = 'menunggu_pembayaran';
        $stmt_booking = $conn->prepare("UPDATE booking SET status = ? WHERE id_booking = ?");
        $stmt_booking->bind_param("si", $new_booking_status, $id_booking);
        $stmt_booking->execute();

        // Kirim notifikasi ke penyewa
        $id_penyewa = $details['id_penyewa'];
        $pesan_notif = "Mohon maaf, pembayaran Anda untuk kamar '{$details['nama_kamar']}' ditolak. Silakan unggah ulang bukti pembayaran yang valid.";
        $link_notif = "/KosConnect/user/user_dashboard.php";
        $stmt_notif = $conn->prepare("INSERT INTO notifications (id_user, pesan, link) VALUES (?, ?, ?)");
        $stmt_notif->bind_param("iss", $id_penyewa, $pesan_notif, $link_notif);
        $stmt_notif->execute();

        $response['message'] = 'Pembayaran telah ditolak. Penyewa dapat mengunggah bukti baru.';
    }

    $conn->commit();
    $response['status'] = 'success';

} catch (Exception $e) {
    $conn->rollback();
    $response['message'] = 'Gagal memproses verifikasi: ' . $e->getMessage();
    http_response_code(500);
}

$conn->close();
echo json_encode($response);
?>