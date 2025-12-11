<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['role'] !== 'pemilik') {
    echo 'Unauthorized';
    exit();
}

include '../config/db.php';

$id_pemilik = $_SESSION['user_id'];
$id_kost = $conn->real_escape_string($_GET['id_kost']);

// Check if kost belongs to pemilik
$sql_check = "SELECT id_kost FROM kost WHERE id_kost = '$id_kost' AND id_pemilik = '$id_pemilik'";
$result = $conn->query($sql_check);
if ($result->num_rows == 0) {
    echo '<p class="text-red-500">Kost not found or not owned by you</p>';
    exit();
}

$sql = "SELECT id_kamar, nama_kamar, harga, status FROM kamar WHERE id_kost = '$id_kost' ORDER BY nama_kamar";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $status_color = $row['status'] == 'tersedia' ? 'text-green-600' : 'text-red-600';
        echo '<div class="flex justify-between items-center p-2 border-b">';
        echo '<div>';
        echo '<span class="font-medium">' . htmlspecialchars($row['nama_kamar']) . '</span> - ';
        echo '<span class="text-purple-600">Rp ' . number_format($row['harga'], 0, ',', '.') . '</span> - ';
        echo '<span class="' . $status_color . '">' . ucfirst($row['status']) . '</span>';
        echo '</div>';
        echo '<button onclick="deleteKamar(' . $row['id_kamar'] . ')" class="text-red-600 hover:text-red-800"><i class="fas fa-trash"></i></button>';
        echo '</div>';
    }
} else {
    echo '<p class="text-gray-500">Belum ada kamar untuk kos ini.</p>';
}
?>
