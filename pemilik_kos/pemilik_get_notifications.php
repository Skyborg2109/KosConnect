<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

session_start();
header('Content-Type: application/json');

include '../config/db.php';

$response = ['status' => 'error', 'message' => 'Akses tidak sah.'];

// ... (rest of logic) ...

try {
    if (!isset($_SESSION['user_logged_in']) || $_SESSION['role'] !== 'pemilik') {
         // Return 200 OK with error payload to avoid browser generic error pages for 403
         // or keep 403 but ensure valid JSON
         $response['message'] = 'Akses tidak sah. Silakan login kembali.';
         http_response_code(403);
         echo json_encode($response);
         exit(); // ob_end_clean not needed if exit called, but good practice
    }

    $id_pemilik = $_SESSION['user_id'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id_user = ?");
        if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
        
        $stmt->bind_param("i", $id_pemilik);
        if ($stmt->execute()) {
            $response = ['status' => 'success', 'message' => 'Semua notifikasi ditandai terbaca.'];
        } else {
            $response['message'] = 'Gagal memperbarui notifikasi: ' . $stmt->error;
        }
    } else {
        $stmt = $conn->prepare("SELECT pesan, link, is_read, DATE_FORMAT(created_at, '%d %b %Y %H:%i') as created_at FROM notifications WHERE id_user = ? ORDER BY created_at DESC LIMIT 15");
        if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);

        $stmt->bind_param("i", $id_pemilik);
        $stmt->execute();
        $result = $stmt->get_result();

        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }

        $response = [
            'status' => 'success',
            'notifications' => $notifications
        ];
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(500);
}

ob_end_clean();
echo json_encode($response);
?>