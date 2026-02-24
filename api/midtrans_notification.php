<?php
/**
 * Midtrans Notification Handler
 * Endpoint untuk menerima webhook notification dari Midtrans
 */

// Disable output buffering
ob_start();

// Set header
header('Content-Type: application/json');

// Include dependencies
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/MidtransService.php';

// Log incoming request
$rawInput = file_get_contents('php://input');
error_log("Midtrans Notification Received: " . $rawInput);

try {
    // Initialize Midtrans service
    $midtransService = new MidtransService($conn);
    
    // Handle notification
    $result = $midtransService->handleNotification($_POST);
    
    if ($result['success']) {
        // Log success
        error_log("Midtrans Notification Processed Successfully: Order ID " . $result['order_id']);
        
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
            if ($status == 'settlement') {
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
                
                // Notify Admin - New Payment Received (All Admins)
                $stmtAdmin = $conn->prepare("SELECT id_user FROM user WHERE role = 'admin'");
                $stmtAdmin->execute();
                $resAdmin = $stmtAdmin->get_result();
                
                $amountFormatted = number_format($result['gross_amount'] ?? 0, 0, ',', '.');
                $pesanAdmin = "Pembayaran baru diterima: Booking #{$bookingId} sebesar Rp {$amountFormatted}";
                $linkAdmin = $path_prefix . '/dashboard/dashboardadmin.php?module=admin_manage_transactions';
                
                $stmtNotifAdmin = $conn->prepare("INSERT INTO notifications (id_user, pesan, link) VALUES (?, ?, ?)");
                
                while ($admin = $resAdmin->fetch_assoc()) {
                    $stmtNotifAdmin->bind_param("iss", $admin['id_user'], $pesanAdmin, $linkAdmin);
                    $stmtNotifAdmin->execute();
                }
                
                $stmtNotifAdmin->close();
                $stmtAdmin->close();
                
            } elseif ($status == 'pending') {
                // Notify user - payment pending
                $pesanUser = "Pembayaran Anda untuk {$booking['nama_kamar']} di {$booking['nama_kost']} sedang diproses. Silakan selesaikan pembayaran.";
                $linkUser = $path_prefix . '/user/user_dashboard.php#riwayat';
                $stmtNotif = $conn->prepare("INSERT INTO notifications (id_user, pesan, link) VALUES (?, ?, ?)");
                $stmtNotif->bind_param("iss", $booking['id_penyewa'], $pesanUser, $linkUser);
                $stmtNotif->execute();
                $stmtNotif->close();
                
            } elseif (in_array($status, ['expire', 'cancel', 'deny'])) {
                // Notify user - payment failed
                $pesanUser = "Pembayaran Anda untuk {$booking['nama_kamar']} di {$booking['nama_kost']} gagal atau dibatalkan. Silakan coba lagi.";
                $linkUser = $path_prefix . '/user/user_dashboard.php#riwayat';
                $stmtNotif = $conn->prepare("INSERT INTO notifications (id_user, pesan, link) VALUES (?, ?, ?)");
                $stmtNotif->bind_param("iss", $booking['id_penyewa'], $pesanUser, $linkUser);
                $stmtNotif->execute();
                $stmtNotif->close();
            }
        }
        
        // Return success response to Midtrans
        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'message' => 'Notification processed successfully'
        ]);
        
    } else {
        // Log error
        error_log("Midtrans Notification Processing Failed: " . $result['error']);
        
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => $result['error']
        ]);
    }
    
} catch (Exception $e) {
    error_log("Midtrans Notification Exception: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

$conn->close();
ob_end_flush();
?>
