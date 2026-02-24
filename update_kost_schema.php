<?php
// Since config/db.php might die() with HTML, let's replicate the connection here for a cleaner CLI output
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "kosconnect";

echo "Attempting to connect to database '$dbname' as user '$user'...\n";
$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . "\n");
}

echo "Connected successfully.\n";

$sql = "ALTER TABLE `kost` ADD COLUMN `tipe_kost` ENUM('Putra', 'Putri', 'Campuran') DEFAULT 'Campuran' AFTER `harga`";

if ($conn->query($sql)) {
    echo "Migration successful: Column 'tipe_kost' added successfully.\n";
} else {
    echo "Migration failed or already applied: " . $conn->error . "\n";
}

$conn->close();
?>
