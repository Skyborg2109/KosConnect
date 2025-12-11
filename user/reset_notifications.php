<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['role'] !== 'penyewa') {
    http_response_code(403);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Reset notifikasi count umum
    $_SESSION['notif_count'] = 0;

    // Jika ada pesan spesifik yang diminta untuk dihapus (misal: 'payment_success')
    if (isset($_POST['specific'])) {
        unset($_SESSION[$_POST['specific']]);
    }

    echo json_encode(['success' => true]);
} else {
    http_response_code(405);
}
?>
