<?php
include 'config/db.php';
$result = $conn->query('DESCRIBE kost');
echo "Kost table structure:\n";
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}
