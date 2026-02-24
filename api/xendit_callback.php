<?php
/**
 * Xendit Callback Handler
 * Endpoint untuk menerima webhook callback dari Xendit
 */

// Disable output buffering
ob_start();

// Set header
header('Content-Type: application/json');

// Include dependencies
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/xendit.php';
require_once __DIR__ . '/../services/XenditService.php';

// Log incoming request
$rawInput = file_get_contents('php://input');
error_log("Xendit Callback Received: " . $rawInput);

// Get callback data
$callbackData = json_decode($rawInput, true);

try {
    // Verify callback token (optional but recommended)
    $callbackToken = $_SERVER['HTTP_X_CALLBACK_TOKEN'] ?? '';
    $expectedToken = hash_hmac('sha256', $rawInput, XENDIT_SECRET_KEY);
    
    // For now, we'll process without strict verification
    // In production, uncomment this:
    // if ($callbackToken !== $expectedToken) {
    //     throw new Exception('Invalid callback token');
    // }
    
    // Initialize Xendit service
    $xenditService = new XenditService($conn);
    
    // Handle callback
    $result = $xenditService->handleCallback($callbackData);
    
    if ($result['success']) {
        // Log success
        error_log("Xendit Callback Processed Successfully: External ID " . $result['external_id']);
        
        // Determine path prefix for notification links
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
        $is_virtual_host = strpos($host, 'localhost') === false && strpos($host, '127.0.0.1') === false;
        $path_prefix = $is_virtual_host ? '' : '/KosConnect';
        
        // Send notification to user and owner
        $bookingId = $result['booking_id'];
        $status = $result['status'];
        
        // Get booking details
        $stmt = $conn->prepare("
            SELECT b.id_penyewa, t.id_pemilik, u.nama_lengkap, k.nama_kamar, t.nama_kost
            FROM booking b
            JOIN kamar k ON b.id_kamar = k.id_kamar
            JOIN kost t ON k.id_kost = t.id_kost
            JOIN user u ON b.id_penyewa = u.id_user
            WHERE b.id_booking = ?
        ");
        $stmt->bind_param("i", $bookingId);
        $stmt->execute();
        $booking = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($booking) {
            // Notification based on status
            if ($status == 'PAID' || $status == 'SETTLED') {
                // Notify user - payment success
                $pesanUser = "Pembayaran Anda untuk {$booking['nama_kamar']} di {$booking['nama_kost']} telah berhasil! Booking Anda telah dikonfirmasi.";
                $linkUser = $path_prefix . '/user/user_dashboard.php#riwayat';
                $stmtNotif = $conn->prepare("INSERT INTO notifications (id_user, pesan, link) VALUES (?, ?, ?)");
                $stmtNotif->bind_param("iss", $booking['id_penyewa'], $pesanUser, $linkUser);
                $stmtNotif->execute();
                $stmtNotif->close();
                
                // Notify owner - new confirmed booking
                $pesanOwner = "Pembayaran dari {$booking['nama_lengkap']} untuk {$booking['nama_kamar']} telah diterima. Booking telah dikonfirmasi.";
                $linkOwner = $path_prefix . '/dashboard/dashboardpemilik.php?module=owner_manage_bookings';
                $stmtNotif = $conn->prepare("INSERT INTO notifications (id_user, pesan, link) VALUES (?, ?, ?)");
                $stmtNotif->bind_param("iss", $booking['id_pemilik'], $pesanOwner, $linkOwner);
                $stmtNotif->execute();
                $stmtNotif->close();
                
            } elseif ($status == 'PENDING') {
                // Notify user - payment pending
                $pesanUser = "Invoice pembayaran untuk {$booking['nama_kamar']} di {$booking['nama_kost']} telah dibuat. Silakan selesaikan pembayaran.";
                $linkUser = $path_prefix . '/user/user_dashboard.php#riwayat';
                $stmtNotif = $conn->prepare("INSERT INTO notifications (id_user, pesan, link) VALUES (?, ?, ?)");
                $stmtNotif->bind_param("iss", $booking['id_penyewa'], $pesanUser, $linkUser);
                $stmtNotif->execute();
                $stmtNotif->close();
                
            } elseif ($status == 'EXPIRED') {
                // Notify user - payment expired
                $pesanUser = "Invoice pembayaran untuk {$booking['nama_kamar']} di {$booking['nama_kost']} telah kadaluarsa. Silakan buat booking baru.";
                $linkUser = $path_prefix . '/user/user_dashboard.php#riwayat';
                $stmtNotif = $conn->prepare("INSERT INTO notifications (id_user, pesan, link) VALUES (?, ?, ?)");
                $stmtNotif->bind_param("iss", $booking['id_penyewa'], $pesanUser, $linkUser);
                $stmtNotif->execute();
                $stmtNotif->close();
            }
        }
        
        // Return success response to Xendit
        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'message' => 'Callback processed successfully'
        ]);
        
    } else {
        // Log error
        error_log("Xendit Callback Processing Failed: " . $result['error']);
        
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => $result['error']
        ]);
    }
    
} catch (Exception $e) {
    error_log("Xendit Callback Exception: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

$conn->close();
ob_end_flush();
?>
