<?php
include 'config/db.php';

$sql = "ALTER TABLE `kost` ADD COLUMN `tipe_kost` ENUM('Putra', 'Putri', 'Campuran') DEFAULT 'Campuran' AFTER `harga`";

try {
    if ($conn->query($sql)) {
        echo "Migration successful: Column 'tipe_kost' added successfully.\n";
    } else {
        if ($conn->errno === 1060) {
            echo "Migration skipped: Column 'tipe_kost' already exists.\n";
        } else {
            echo "Migration failed: " . $conn->error . "\n";
        }
    }
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Migration skipped: Column 'tipe_kost' already exists.\n";
    } else {
        echo "Migration failed: " . $e->getMessage() . "\n";
    }
}

$conn->close();
?>
