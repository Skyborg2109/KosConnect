<?php
include 'config/db.php';

// Clear old notifications
$result = $conn->query("DELETE FROM notifications");

if ($result) {
    echo "✅ Notifications table cleared successfully\n";
} else {
    echo "❌ Error clearing notifications: " . $conn->error . "\n";
}

$conn->close();
?>
