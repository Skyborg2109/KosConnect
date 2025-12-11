<?php
session_start();
header('Content-Type: application/json');

include '../config/db.php';

$response = ['status' => 'error', 'message' => 'Akses tidak sah.'];

if (!isset($_SESSION['user_logged_in']) || $_SESSION['role'] !== 'pemilik') {
    http_response_code(403);
    echo json_encode($response);
    exit();
}

$id_pemilik = $_SESSION['user_id'];

// Jika metode POST, tandai semua notifikasi sebagai sudah dibaca
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id_user = ?");
    $stmt->bind_param("i", $id_pemilik);
    if ($stmt->execute()) {
        $response = ['status' => 'success', 'message' => 'Semua notifikasi ditandai terbaca.'];
    } else {
        $response['message'] = 'Gagal memperbarui notifikasi.';
    }
    echo json_encode($response);
    exit();
}

// Jika metode GET, ambil notifikasi yang belum dibaca
$stmt = $conn->prepare("SELECT pesan, link, is_read, DATE_FORMAT(created_at, '%d %b %Y %H:%i') as created_at FROM notifications WHERE id_user = ? ORDER BY created_at DESC LIMIT 15");
$stmt->bind_param("i", $id_pemilik);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

$response = [
    'status' => 'success',
    'notifications' => $notifications
];

echo json_encode($response);
?>