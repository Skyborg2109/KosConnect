<?php
session_start();

// Autentikasi dan validasi dasar
if (!isset($_SESSION['user_logged_in']) || $_SESSION['role'] !== 'pemilik') {
    http_response_code(403); // Forbidden
    die('<p class="text-red-500 p-4">Akses tidak sah. Sesi tidak valid.</p>');
}

if (!isset($_GET['module'])) {
    http_response_code(400); // Bad Request
    die('<p class="text-red-500 p-4">Error: Nama modul tidak disediakan.</p>');
}

include '../config/db.php';

// Variabel ini dibutuhkan oleh beberapa modul yang akan dimuat
$id_pemilik = (int)$_SESSION['user_id'];

$allowed_modules = ['owner_dashboard_summary', 'owner_manage_kost', 'owner_manage_booking', 'owner_manage_payments', 'owner_view_feedback'];
$module_to_load = $_GET['module'];

if (in_array($module_to_load, $allowed_modules)) {
    $module_file = $module_to_load . '.php';
    if (file_exists(__DIR__ . '/' . $module_file)) {
        include $module_file; // Muat file modul yang diminta
    } else {
        echo "<p class='text-red-500 p-4'>Error: File modul '$module_file' tidak ditemukan.</p>";
    }
}

// Close database connection only if the module doesn't need it
// Some modules may need to keep the connection open for further operations
if ($module_to_load !== 'owner_view_feedback') {
    $conn->close();
}
