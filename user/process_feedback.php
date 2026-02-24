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
    
    // [NEW] Notifikasi ke Admin (All Admins)
    $stmt_admin = $conn->prepare("SELECT id_user FROM user WHERE role = 'admin'");
    $stmt_admin->execute();
    $res_admin = $stmt_admin->get_result();
    
    $preview_pesan = (strlen($pesan) > 40) ? substr($pesan, 0, 40) . '...' : $pesan;
    $pesan_notif = "Feedback baru dari user ID {$id_penyewa}: {$preview_pesan}";
    $link_notif = '/KosConnect/dashboard/dashboardadmin.php?module=admin_manage_complaints';
    
    $stmt_notif = $conn->prepare("INSERT INTO notifications (id_user, pesan, link) VALUES (?, ?, ?)");
    
    while ($data_admin = $res_admin->fetch_assoc()) {
        $id_admin = $data_admin['id_user'];
        $stmt_notif->bind_param("iss", $id_admin, $pesan_notif, $link_notif);
        $stmt_notif->execute();
    }
    
    $stmt_notif->close();
    $stmt_admin->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal mengirim feedback. Silakan coba lagi.']);
}

$stmt->close();
$conn->close();
?>
