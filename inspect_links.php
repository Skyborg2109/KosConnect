<?php
include 'config/db.php';
header('Content-Type: text/plain');

// Get all notification links
$result = $conn->query("SELECT id_notification, link FROM notifications WHERE id_user IN (SELECT id_user FROM user WHERE role='admin')");

echo "Current Admin Notification Links:\n";
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "ID " . $row['id_notification'] . ": " . $row['link'] . "\n";
    }
} else {
    echo "Error: " . $conn->error;
}
$conn->close();
?>
