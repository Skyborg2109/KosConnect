<?php
include '../config/db.php';
// Check connection
if (!$conn) { die("DB Connection Failed"); }

echo "<h3>Check Pembayaran Table</h3>";
$sql = "SELECT * FROM pembayaran ORDER BY id_pembayaran DESC LIMIT 10";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<table border=1><tr><th>ID</th><th>Tgl</th><th>Jumlah</th><th>Status</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id_pembayaran']}</td>";
        echo "<td>{$row['tanggal_pembayaran']}</td>";
        echo "<td>{$row['jumlah']}</td>";
        echo "<td>{$row['status_pembayaran']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Tabel pembayaran KOSONG (0 rows).";
}
?>
