<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>KosConnect Database Migration Helper</h1>";

include 'config/db.php';

function checkAndAddColumn($conn, $table, $column, $definition) {
    try {
        $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
        if ($result && $result->num_rows === 0) {
            echo "<p>Adding column <strong>$column</strong> to table <strong>$table</strong>... ";
            if ($conn->query("ALTER TABLE `$table` ADD COLUMN $column $definition")) {
                echo "<span style='color:green'>SUCCESS</span></p>";
            } else {
                echo "<span style='color:red'>FAILED: " . $conn->error . "</span></p>";
            }
        } else {
            echo "<p>Column <strong>$column</strong> in table <strong>$table</strong> already exists. <span style='color:blue'>SKIPPED</span></p>";
        }
    } catch (Exception $e) {
        echo "<p style='color:red'>Error checking/adding $column: " . $e->getMessage() . "</p>";
    }
}

// 1. Add tipe_kost to kost
checkAndAddColumn($conn, 'kost', 'tipe_kost', "ENUM('Putra', 'Putri', 'Campuran') DEFAULT 'Campuran' AFTER `harga`");

// 2. Add jenis_kamar to kost
checkAndAddColumn($conn, 'kost', 'jenis_kamar', "VARCHAR(100) DEFAULT 'Kamar Mandi Dalam' AFTER `tipe_kost`");

// 3. Add tipe_kamar to kamar
checkAndAddColumn($conn, 'kamar', 'tipe_kamar', "VARCHAR(100) DEFAULT 'Standard' AFTER `nama_kamar`");

// 4. Add category to kost_photos
checkAndAddColumn($conn, 'kost_photos', 'category', "VARCHAR(50) DEFAULT 'lainnya'");

echo "<h3>Migration Completed.</h3>";
echo "<p><a href='index.php'>Go to Home</a></p>";
?>
