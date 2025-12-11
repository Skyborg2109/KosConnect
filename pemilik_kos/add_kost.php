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

$nama_kost = trim($_POST['nama_kost'] ?? '');
if (empty($nama_kost)) {
    echo json_encode(['success' => false, 'message' => 'Nama kos tidak boleh kosong']);
    exit();
}

$alamat = trim($_POST['alamat'] ?? '');
if (empty($alamat)) {
    echo json_encode(['success' => false, 'message' => 'Alamat tidak boleh kosong']);
    exit();
}

$deskripsi = trim($_POST['deskripsi'] ?? '');
$fasilitas = trim($_POST['fasilitas'] ?? '');

$harga = trim($_POST['harga'] ?? '');
if (empty($harga) || !is_numeric($harga) || (float)$harga <= 0) {
    echo json_encode(['success' => false, 'message' => 'Harga harus berupa angka positif']);
    exit();
}

$sql = "INSERT INTO kost (id_pemilik, nama_kost, alamat, deskripsi, fasilitas, harga) VALUES (?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit();
}

$stmt->bind_param("isssss", $id_pemilik, $nama_kost, $alamat, $deskripsi, $fasilitas, $harga);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Execute failed: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
