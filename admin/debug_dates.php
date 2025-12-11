<?php
include '../config/db.php';

$six_months_ago = date('Y-m-01', strtotime("-5 months"));
echo "Checking for payments since: $six_months_ago<br>";

$sql = "SELECT id_pembayaran, tanggal_pembayaran, status_pembayaran 
        FROM pembayaran 
        WHERE tanggal_pembayaran >= '$six_months_ago'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "Found " . $result->num_rows . " payments in range.<br>";
    while ($row = $result->fetch_assoc()) {
        echo "ID: {$row['id_pembayaran']} - Date: {$row['tanggal_pembayaran']} - Status: {$row['status_pembayaran']}<br>";
    }
} else {
    echo "No payments found in the last 6 months (regardless of status).<br>";
    
    // Check old data
    $old = $conn->query("SELECT COUNT(*) as c FROM pembayaran")->fetch_assoc()['c'];
    echo "Total rows in table: $old";
}
?>
