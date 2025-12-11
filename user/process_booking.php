<?php
session_start();
header('Content-Type: application/json');

include '../config/db.php';

$response = ['status' => 'error', 'message' => 'Terjadi kesalahan yang tidak diketahui.'];

// 1. Autentikasi: Pastikan pengguna adalah penyewa yang sudah login
if (!isset($_SESSION['user_logged_in']) || $_SESSION['role'] !== 'penyewa') {
    $response['message'] = 'Akses tidak sah. Silakan login kembali.';
    http_response_code(403); // Forbidden
    echo json_encode($response);
    exit();
}

$id_penyewa = $_SESSION['user_id'];
$id_kamar = filter_var($_POST['id_kamar'] ?? 0, FILTER_VALIDATE_INT);

if ($id_kamar <= 0) {
    $response['message'] = 'ID Kamar tidak valid.';
    http_response_code(400); // Bad Request
    echo json_encode($response);
    exit();
}

// 2. Gunakan Transaksi Database untuk memastikan integritas data
$conn->begin_transaction();

try {
    // 3. Ambil detail kamar dan pemilik, kunci baris untuk mencegah race condition
    $stmt_check = $conn->prepare("SELECT status FROM kamar WHERE id_kamar = ? FOR UPDATE");
    $stmt_check->bind_param("i", $id_kamar);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows === 0) {
        throw new Exception("Kamar tidak ditemukan.");
    }

    $kamar = $result_check->fetch_assoc();

    // 4. Pastikan kamar masih tersedia
    if ($kamar['status'] !== 'tersedia') {
        throw new Exception("Maaf, kamar ini baru saja dipesan oleh orang lain.");
    }

    // Ambil detail untuk notifikasi
    $stmt_details = $conn->prepare("
        SELECT k.nama_kamar, t.nama_kost, t.id_pemilik 
        FROM kamar k 
        JOIN kost t ON k.id_kost = t.id_kost 
        WHERE k.id_kamar = ?
    ");
    $stmt_details->bind_param("i", $id_kamar);
    $stmt_details->execute();
    $details = $stmt_details->get_result()->fetch_assoc();
    $id_pemilik = $details['id_pemilik'];
    $nama_kamar = $details['nama_kamar'];
    $nama_kost = $details['nama_kost'];
    $stmt_details->close();

    // 5. Buat data booking baru
    $stmt_insert = $conn->prepare("INSERT INTO booking (id_penyewa, id_kamar, tanggal_booking, status) VALUES (?, ?, NOW(), 'pending')");
    $stmt_insert->bind_param("ii", $id_penyewa, $id_kamar);
    $stmt_insert->execute();

    // 6. Update status kamar menjadi 'dipesan'
    $stmt_update = $conn->prepare("UPDATE kamar SET status = 'dipesan' WHERE id_kamar = ?");
    $stmt_update->bind_param("i", $id_kamar);
    $stmt_update->execute();

    // 7. Buat notifikasi untuk pemilik kos
    $pesan_notif = "Pesanan baru untuk kamar '{$nama_kamar}' di '{$nama_kost}' telah masuk. Mohon segera dikonfirmasi.";
    $link_notif = '/KosConnect/dashboard/dashboardpemilik.php?module=owner_manage_booking&status=pending';
    $stmt_notif = $conn->prepare("INSERT INTO notifications (id_user, pesan, link) VALUES (?, ?, ?)");
    $stmt_notif->bind_param("iss", $id_pemilik, $pesan_notif, $link_notif);
    $stmt_notif->execute();
    
    // 8. Buat notifikasi untuk penyewa (user yang booking)
    $pesan_notif_penyewa = "Booking Anda untuk kamar '{$nama_kamar}' di '{$nama_kost}' berhasil dibuat. Silakan tunggu konfirmasi dari pemilik kos.";
    $link_notif_penyewa = '/KosConnect/user/user_dashboard.php';
    $stmt_notif_penyewa = $conn->prepare("INSERT INTO notifications (id_user, pesan, link) VALUES (?, ?, ?)");
    $stmt_notif_penyewa->bind_param("iss", $id_penyewa, $pesan_notif_penyewa, $link_notif_penyewa);
    $stmt_notif_penyewa->execute();

    // 7. Jika semua berhasil, commit transaksi
    $conn->commit();

    $response['status'] = 'success';
    $response['message'] = 'Booking berhasil dibuat! Pesanan Anda sedang menunggu konfirmasi dari pemilik kos. Kami akan memberitahu Anda segera setelah pesanan dikonfirmasi.';

} catch (Exception $e) {
    // 8. Jika ada kesalahan, rollback semua perubahan
    $conn->rollback();
    $response['message'] = $e->getMessage();
    http_response_code(500); // Internal Server Error
} finally {
    if (isset($stmt_check)) $stmt_check->close();
    if (isset($stmt_notif)) $stmt_notif->close();
    if (isset($stmt_notif_penyewa)) $stmt_notif_penyewa->close();
    if (isset($stmt_insert)) $stmt_insert->close();
    if (isset($stmt_update)) $stmt_update->close();
}

$conn->close();
echo json_encode($response);
?>