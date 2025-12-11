<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['role'] !== 'pemilik') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (!isset($_SESSION['user_id']) || !is_numeric($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid user session']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

include '../config/db.php';

$id_pemilik = (int)$_SESSION['user_id'];

$id_kost = trim($_POST['id_kost'] ?? '');
if (empty($id_kost) || !is_numeric($id_kost)) {
    echo json_encode(['success' => false, 'message' => 'Invalid kost ID']);
    exit();
}
$id_kost = (int)$id_kost;

// Check if kost belongs to pemilik
$sql_check = "SELECT id_kost FROM kost WHERE id_kost = ? AND id_pemilik = ?";
$stmt_check = $conn->prepare($sql_check);
if (!$stmt_check) {
    echo json_encode(['success' => false, 'message' => 'Prepare check failed: ' . $conn->error]);
    exit();
}
$stmt_check->bind_param("ii", $id_kost, $id_pemilik);
$stmt_check->execute();
$result = $stmt_check->get_result();
if ($result->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Kost not found or not owned by you']);
    $stmt_check->close();
    $conn->close();
    exit();
}
$stmt_check->close();

$nama_kamar = trim($_POST['nama_kamar'] ?? '');
if (empty($nama_kamar)) {
    echo json_encode(['success' => false, 'message' => 'Nama kamar tidak boleh kosong']);
    exit();
}

$harga = trim($_POST['harga'] ?? '');
if (empty($harga) || !is_numeric($harga) || (float)$harga <= 0) {
    echo json_encode(['success' => false, 'message' => 'Harga harus berupa angka positif']);
    exit();
}
$harga = (float)$harga;

$status = trim($_POST['status'] ?? '');
if (!in_array($status, ['tersedia', 'terisi'])) {
    echo json_encode(['success' => false, 'message' => 'Status tidak valid']);
    exit();
}

$sql = "INSERT INTO kamar (id_kost, nama_kamar, harga, status) VALUES (?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit();
}

$stmt->bind_param("isds", $id_kost, $nama_kamar, $harga, $status);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Execute failed: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
