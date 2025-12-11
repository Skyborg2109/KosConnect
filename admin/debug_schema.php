<?php
include '../config/db.php';

$res = $conn->query("DESCRIBE booking");
if ($res) {
    echo "<table border=1><tr><th>Field</th><th>Type</th></tr>";
    while ($row = $res->fetch_assoc()) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "Error describing table: " . $conn->error;
}
?>
