<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_logged_in']) || !in_array($_SESSION['role'], ['penyewa', 'admin'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

include '../config/db.php';

$id_user = $_SESSION['user_id'];
$id_kost = isset($_POST['id_kost']) ? (int)$_POST['id_kost'] : 0;
$action = isset($_POST['action']) ? $_POST['action'] : 'toggle'; // toggle, check, or remove

if ($id_kost <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid kost ID']);
    exit();
}

try {
    if ($action === 'check') {
        // Check if already in wishlist
        $stmt = $conn->prepare("SELECT id_wishlist FROM wishlist WHERE id_user = ? AND id_kost = ?");
        $stmt->bind_param("ii", $id_user, $id_kost);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        
        echo json_encode([
            'status' => 'success',
            'is_favorited' => $exists
        ]);
    } 
    elseif ($action === 'toggle') {
        // Check if already exists
        $stmt = $conn->prepare("SELECT id_wishlist FROM wishlist WHERE id_user = ? AND id_kost = ?");
        $stmt->bind_param("ii", $id_user, $id_kost);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        
        if ($exists) {
            // Remove from wishlist
            $stmt = $conn->prepare("DELETE FROM wishlist WHERE id_user = ? AND id_kost = ?");
            $stmt->bind_param("ii", $id_user, $id_kost);
            $stmt->execute();
            $stmt->close();
            
            echo json_encode([
                'status' => 'success',
                'action' => 'removed',
                'message' => 'Kos dihapus dari wishlist'
            ]);
        } else {
            // Add to wishlist
            $stmt = $conn->prepare("INSERT INTO wishlist (id_user, id_kost) VALUES (?, ?)");
            $stmt->bind_param("ii", $id_user, $id_kost);
            $stmt->execute();
            $stmt->close();
            
            echo json_encode([
                'status' => 'success',
                'action' => 'added',
                'message' => 'Kos ditambahkan ke wishlist'
            ]);
        }
    }
    elseif ($action === 'remove') {
        // Remove from wishlist
        $stmt = $conn->prepare("DELETE FROM wishlist WHERE id_user = ? AND id_kost = ?");
        $stmt->bind_param("ii", $id_user, $id_kost);
        $stmt->execute();
        $stmt->close();
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Kos dihapus dari wishlist'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>
