<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['role'] !== 'penyewa') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
    exit();
}

include '../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metode tidak diizinkan.']);
    exit();
}

$id_penyewa = $_SESSION['user_id'];
$pesan = trim($_POST['pesan'] ?? '');

// Validasi input
if (empty($pesan)) {
    echo json_encode(['success' => false, 'message' => 'Pesan feedback tidak boleh kosong.']);
    exit();
}

if (strlen($pesan) > 1000) {
    echo json_encode(['success' => false, 'message' => 'Pesan feedback maksimal 1000 karakter.']);
    exit();
}

// Simpan feedback ke database
$stmt = $conn->prepare("INSERT INTO feedback (id_penyewa, pesan, created_at) VALUES (?, ?, NOW())");
$stmt->bind_param("is", $id_penyewa, $pesan);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Feedback berhasil dikirim. Terima kasih atas masukan Anda!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal mengirim feedback. Silakan coba lagi.']);
}

$stmt->close();
$conn->close();
?>
