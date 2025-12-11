<?php
include 'config/db.php';

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$sql = "ALTER TABLE kost
  ADD COLUMN status_kos ENUM('tersedia','tidak_tersedia') DEFAULT 'tersedia' AFTER fasilitas,
  ADD COLUMN gambar VARCHAR(255) DEFAULT NULL AFTER status_kos;";

if (mysqli_query($conn, $sql)) {
    echo "Migration successful: Columns added to kost table.";
} else {
    echo "Error: " . mysqli_error($conn);
}

mysqli_close($conn);
?>
