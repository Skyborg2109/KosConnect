<?php
// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// We want to avoid the die() with HTML from db.php if possible for CLI, 
// but since we need the connection, let's just use the credentials directly to be safe in CLI.
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "kosconnect";

echo "Connecting to $dbname...\n";
$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . "\n");
}
echo "Connected.\n";

// Check if column exists
$result = $conn->query("SHOW COLUMNS FROM `kost` LIKE 'tipe_kost'");
if ($result->num_rows > 0) {
    echo "Column 'tipe_kost' already exists. Skipping.\n";
} else {
    $sql = "ALTER TABLE `kost` ADD COLUMN `tipe_kost` ENUM('Putra', 'Putri', 'Campuran') DEFAULT 'Campuran' AFTER `harga`";
    if ($conn->query($sql)) {
        echo "Migration successful: Column 'tipe_kost' added.\n";
    } else {
        echo "Error adding column: " . $conn->error . "\n";
    }
}

$conn->close();
?>
