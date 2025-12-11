<?php
include '../config/db.php';

echo "<h2>Debug Payment Data</h2>";

// Check total payments count
$result = $conn->query("SELECT COUNT(*) as count FROM pembayaran");
$row = $result->fetch_assoc();
echo "Total rows in pembayaran: " . $row['count'] . "<br>";

// Check payments in the last 6 months (regardless of status)
$six_months_ago = date('Y-m-01', strtotime("-5 months"));
echo "Checking data since: $six_months_ago<br>";

$sql = "SELECT id_pembayaran, jumlah, tanggal_pembayaran, status_pembayaran 
        FROM pembayaran 
        WHERE tanggal_pembayaran >= '$six_months_ago' 
        ORDER BY tanggal_pembayaran DESC LIMIT 10";
$res = $conn->query($sql);

if ($res && $res->num_rows > 0) {
    echo "<table border='1'><tr><th>ID</th><th>Jumlah</th><th>Tanggal</th><th>Status</th></tr>";
    while ($r = $res->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$r['id_pembayaran']}</td>";
        echo "<td>{$r['jumlah']}</td>";
        echo "<td>{$r['tanggal_pembayaran']}</td>";
        echo "<td>{$r['status_pembayaran']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No payments found since $six_months_ago.<br>";
}

// Check if there are ANY payments with status 'disetujui'
$check_status = $conn->query("SELECT COUNT(*) as count FROM pembayaran WHERE status_pembayaran = 'disetujui'");
$row_status = $check_status->fetch_assoc();
echo "Total 'disetujui' payments (all time): " . $row_status['count'] . "<br>";

?>
