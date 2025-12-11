<?php
include '../config/db.php';
header('Content-Type: text/html');

echo "<h1>Deep Inspection: Pembayaran Data</h1>";

// 1. Check CONNECTION
if (!$conn) { die("Connection Failed: " . mysqli_connect_error()); }
echo "Status: Connected to " . $dbname . "<br><br>";

// 2. Check TABLE EXISTENCE
$tables = $conn->query("SHOW TABLES LIKE 'pembayaran'");
if ($tables->num_rows == 0) {
    die("CRITICAL: Table 'pembayaran' does not exist!");
} else {
    echo "Table 'pembayaran' found.<br>";
}

// 3. Check TOTAL ROWS
$count = $conn->query("SELECT COUNT(*) as c FROM pembayaran")->fetch_assoc()['c'];
echo "Total Rows in table: <b>$count</b><br><br>";

if ($count > 0) {
    // 4. Check DISTINCT STATUSES
    echo "<h3>Distinct Statuses:</h3>";
    $statuses = $conn->query("SELECT status_pembayaran, COUNT(*) as c FROM pembayaran GROUP BY status_pembayaran");
    echo "<table border=1 cellpadding=5><tr><th>Status</th><th>Count</th></tr>";
    while ($row = $statuses->fetch_assoc()) {
        echo "<tr><td>'{$row['status_pembayaran']}'</td><td>{$row['c']}</td></tr>";
    }
    echo "</table>";

    // 5. Check DATE RANGE
    echo "<h3>Date Range:</h3>";
    $dates = $conn->query("SELECT MIN(tanggal_pembayaran) as min_date, MAX(tanggal_pembayaran) as max_date FROM pembayaran")->fetch_assoc();
    echo "Earliest: {$dates['min_date']}<br>";
    echo "Latest: {$dates['max_date']}<br><br>";

    // 6. Dump LAST 10 ROWS
    echo "<h3>Latest 10 Rows (Raw):</h3>";
    $dump = $conn->query("SELECT * FROM pembayaran ORDER BY id_pembayaran DESC LIMIT 10");
    echo "<table border=1 cellpadding=5><tr>";
    // Headers
    $fields = $dump->fetch_fields();
    foreach ($fields as $field) { echo "<th>{$field->name}</th>"; }
    echo "</tr>";
    // Data
    while ($row = $dump->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $val) { echo "<td>$val</td>"; }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<h3>Possible Reasons for No Data:</h3>";
    echo "<ul>";
    echo "<li>User entered data into a different database?</li>";
    echo "<li>Insert queries failed silently?</li>";
    echo "<li>Data is in 'booking' table but not 'pembayaran'? (Let's check 'booking')</li>";
    echo "</ul>";
    
    // Check BOOKING table just in case
    $b_count = $conn->query("SELECT COUNT(*) as c FROM booking")->fetch_assoc()['c'];
    echo "Total Rows in 'booking' table: <b>$b_count</b>";
}
?>
