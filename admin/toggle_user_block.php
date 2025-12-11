<?php
session_start();
header('Content-Type: application/json');
include '../config/db.php';

$response = ['success' => false, 'message' => 'Terjadi kesalahan.'];

// 1. Autentikasi: Pastikan hanya admin yang bisa mengakses
if (!isset($_SESSION['user_logged_in']) || $_SESSION['role'] !== 'admin') {
    $response['message'] = 'Akses ditolak. Anda tidak memiliki izin.';
    http_response_code(403);
    echo json_encode($response);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

// 2. Validasi Input
$user_id = filter_var($data['user_id'] ?? null, FILTER_VALIDATE_INT);
$is_blocked = filter_var($data['is_blocked'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 1]]);

if (!$user_id || $is_blocked === false) {
    $response['message'] = 'Data yang dikirim tidak valid.';
    http_response_code(400);
    echo json_encode($response);
    exit();
}

// 3. Jangan biarkan admin memblokir dirinya sendiri
if ($user_id === $_SESSION['user_id']) {
    $response['message'] = 'Anda tidak dapat memblokir akun Anda sendiri.';
    echo json_encode($response);
    exit();
}

// 4. Update Database
$stmt = $conn->prepare("UPDATE user SET is_blocked = ? WHERE id_user = ?");
$stmt->bind_param("ii", $is_blocked, $user_id);

if ($stmt->execute()) {
    $response['success'] = true;
    $response['message'] = 'Status akun pengguna berhasil diperbarui.';
} else {
    $response['message'] = 'Gagal memperbarui status akun di database.';
}

$stmt->close();
$conn->close();
echo json_encode($response);