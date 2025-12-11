<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "kosconnect";

$conn = mysqli_connect($host, $user, $pass, $dbname);

// Removed die() to prevent fatal errors that cause network issues
if (!$conn) {
    // Log error instead of dying
    error_log("Database connection failed: " . mysqli_connect_error());
}
?>
