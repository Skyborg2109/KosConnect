<?php
session_start();
header('Content-Type: application/json');

include '../config/db.php';

$response = ['success' => false, 'message' => 'Terjadi kesalahan yang tidak diketahui.'];

// Autentikasi: Pastikan hanya penyewa yang bisa mengakses
if (!isset($_SESSION['user_logged_in']) || $_SESSION['role'] !== 'penyewa') {
    $response['message'] = 'Akses tidak sah. Silakan login kembali.';
    http_response_code(403);
    echo json_encode($response);
    exit();
}

$id_penyewa = $_SESSION['user_id'];
$id_kost = filter_input(INPUT_POST, 'id_kost', FILTER_VALIDATE_INT);
$pesan = trim($_POST['pesan'] ?? '');

if (!$id_kost || empty($pesan)) {
    $response['message'] = 'Data yang dikirim tidak valid. Pastikan kos dan pesan keluhan diisi.';
    http_response_code(400);
    echo json_encode($response);
    exit();
}

// Validasi bahwa penyewa memiliki booking aktif di kos tersebut
$stmt_check = $conn->prepare("
    SELECT COUNT(*) as count FROM booking b
    INNER JOIN kamar k ON b.id_kamar = k.id_kamar
    WHERE b.id_penyewa = ? AND k.id_kost = ? AND b.status IN ('pending', 'dibayar', 'menunggu_pembayaran')
");
$stmt_check->bind_param("ii", $id_penyewa, $id_kost);
$stmt_check->execute();
$result_check = $stmt_check->get_result()->fetch_assoc();
$stmt_check->close();

if ($result_check['count'] == 0) {
    $response['message'] = 'Anda tidak memiliki booking aktif di kos ini.';
    http_response_code(403);
    echo json_encode($response);
    exit();
}

// Insert keluhan baru
$stmt = $conn->prepare("INSERT INTO complaint (id_penyewa, id_kost, pesan, status, created_at) VALUES (?, ?, ?, 'baru', NOW())");
$stmt->bind_param("iis", $id_penyewa, $id_kost, $pesan);

if ($stmt->execute()) {
    $response['success'] = true;
    $response['message'] = 'Keluhan Anda telah berhasil dikirim. Admin akan segera memprosesnya.';
} else {
    $response['message'] = 'Gagal mengirim keluhan. Silakan coba lagi.';
    http_response_code(500);
}

$stmt->close();
$conn->close();
echo json_encode($response);
?>
