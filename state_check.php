<?php
session_start();
include 'config/db.php';

header('Content-Type: text/plain');

echo "=== DEBUG INFO ===\n";
echo "Session Status: " . (session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive') . "\n";
echo "Session ID: " . session_id() . "\n";
echo "Logged In: " . (isset($_SESSION['user_logged_in']) ? 'Yes' : 'No') . "\n";
echo "User ID (Session): " . ($_SESSION['user_id'] ?? 'Not Set') . "\n";
echo "Role (Session): " . ($_SESSION['role'] ?? 'Not Set') . "\n";

if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    
    // Check User in DB
    $res = $conn->query("SELECT * FROM user WHERE id_user = $uid");
    if ($res->num_rows > 0) {
        $user = $res->fetch_assoc();
        echo "DB User Found: " . $user['username'] . " (Role: " . $user['role'] . ")\n";
    } else {
        echo "DB User NOT Found for ID $uid\n";
    }
    
    // Check Notifications
    $notif_res = $conn->query("SELECT * FROM notifications WHERE id_user = $uid");
    echo "Notification Count in DB for ID $uid: " . $notif_res->num_rows . "\n";
    
    if ($notif_res->num_rows > 0) {
        echo "--- List --- \n";
        while ($row = $notif_res->fetch_assoc()) {
            echo "[" . $row['created_at'] . "] " . $row['pesan'] . "\n";
        }
    }
} else {
    echo "Cannot check DB notifications because User ID is missing from session.\n";
}

// Check if any admin exists
$admin_res = $conn->query("SELECT id_user, username FROM user WHERE role='admin'");
echo "\n--- All Admins in DB ---\n";
while($row = $admin_res->fetch_assoc()) {
    echo "ID: " . $row['id_user'] . ", Username: " . $row['username'] . "\n";
}

$conn->close();
?>
