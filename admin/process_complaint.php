<?php
session_start();
header('Content-Type: application/json');

include '../config/db.php';

$response = ['success' => false, 'message' => 'Aksi tidak valid.'];

// Autentikasi: Pastikan hanya admin yang bisa mengakses
if (!isset($_SESSION['user_logged_in']) || $_SESSION['role'] !== 'admin') {
    $response['message'] = 'Akses ditolak. Anda tidak memiliki izin.';
    http_response_code(403);
    echo json_encode($response);
    exit();
}

$action = $_POST['action'] ?? '';
$id_complaint = filter_input(INPUT_POST, 'id_complaint', FILTER_VALIDATE_INT);

if (!$id_complaint) {
    $response['message'] = 'ID Keluhan tidak valid.';
    http_response_code(400);
    echo json_encode($response);
    exit();
}

switch ($action) {
    case 'resolve':
        $new_status = 'selesai';
        $stmt = $conn->prepare("UPDATE complaint SET status = ? WHERE id_complaint = ?");
        $stmt->bind_param("si", $new_status, $id_complaint);
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Keluhan berhasil ditandai sebagai selesai.';
        } else {
            $response['message'] = 'Gagal memperbarui status keluhan.';
        }
        $stmt->close();
        break;

    case 'process':
        $new_status = 'diproses';
        $stmt = $conn->prepare("UPDATE complaint SET status = ? WHERE id_complaint = ?");
        $stmt->bind_param("si", $new_status, $id_complaint);
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Keluhan berhasil ditandai sebagai diproses.';
        } else {
            $response['message'] = 'Gagal memperbarui status keluhan.';
        }
        $stmt->close();
        break;

    default:
        $response['message'] = 'Aksi tidak dikenali.';
        http_response_code(400);
        break;
}

$conn->close();
echo json_encode($response);
?>
