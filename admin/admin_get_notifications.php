<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_logged_in']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

include '../config/db.php';

$adminID = $_SESSION['user_id'];

// If POST request, mark all notifications as read
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn->query("UPDATE notifications SET is_read = 1 WHERE id_user = $adminID");
    echo json_encode(['status' => 'success', 'message' => 'All notifications marked as read']);
    exit();
}

// Fetch notifications for admin (GET request)
$sql = "SELECT id_notification, pesan, link, is_read, 
        DATE_FORMAT(created_at, '%d/%m/%Y %H:%i') as created_at
        FROM notifications 
        WHERE id_user = ?
        ORDER BY created_at DESC 
        LIMIT 20";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $adminID);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

echo json_encode([
    'status' => 'success',
    'notifications' => $notifications
]);

$stmt->close();
$conn->close();
?>
