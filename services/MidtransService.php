<?php
/**
 * Midtrans Service Class
 * Handle all Midtrans payment gateway operations
 */

class MidtransService {
    private $conn;
    
    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
        
        // Initialize Midtrans configuration
        require_once __DIR__ . '/../config/midtrans.php';
        initMidtransConfig();
    }
    
    /**
     * Create new payment transaction
     * 
     * @param int $bookingId - ID booking
     * @param float $amount - Jumlah pembayaran
     * @param array $customerDetails - Detail customer
     * @return array - Contains snap_token, snap_redirect_url, order_id
     */
    public function createTransaction($bookingId, $amount, $customerDetails) {
        try {
            // Generate unique order ID
            $orderId = 'KOS-' . $bookingId . '-' . time();
            
            // Prepare transaction details
            $transactionDetails = [
                'order_id' => $orderId,
                'gross_amount' => (int)$amount,
            ];
            
            // Prepare item details
            $itemDetails = [
                [
                    'id' => 'booking_' . $bookingId,
                    'price' => (int)$amount,
                    'quantity' => 1,
                    'name' => 'Pembayaran Kos - Booking #' . $bookingId,
                ]
            ];
            
            // Prepare customer details
            $customer = [
                'first_name' => $customerDetails['nama_lengkap'] ?? 'Customer',
                'email' => $customerDetails['email'] ?? 'customer@kosconnect.com',
                'phone' => $customerDetails['no_hp'] ?? '08123456789',
            ];
            
            // Prepare enabled payments
            $enabledPayments = MIDTRANS_ENABLED_PAYMENTS;
            
            // Prepare transaction parameters
            $params = [
                'transaction_details' => $transactionDetails,
                'item_details' => $itemDetails,
                'customer_details' => $customer,
                'enabled_payments' => $enabledPayments,
                'expiry' => [
                    'duration' => MIDTRANS_EXPIRY_DURATION,
                    'unit' => 'minutes'
                ],
                'callbacks' => [
                    'finish' => MIDTRANS_FINISH_URL,
                    'unfinish' => MIDTRANS_UNFINISH_URL,
                    'error' => MIDTRANS_ERROR_URL,
                ]
            ];
            
            // Create Snap transaction
            $snapToken = \Midtrans\Snap::getSnapToken($params);
            $snapRedirectUrl = \Midtrans\Snap::createTransaction($params)->redirect_url;
            
            // Save to database
            $stmt = $this->conn->prepare("
                INSERT INTO midtrans_transactions 
                (id_booking, order_id, gross_amount, snap_token, snap_redirect_url, transaction_status, created_at) 
                VALUES (?, ?, ?, ?, ?, 'pending', NOW())
            ");
            $stmt->bind_param("isdss", $bookingId, $orderId, $amount, $snapToken, $snapRedirectUrl);
            $stmt->execute();
            $transactionId = $stmt->insert_id;
            $stmt->close();
            
            return [
                'success' => true,
                'id_transaction' => $transactionId,
                'order_id' => $orderId,
                'snap_token' => $snapToken,
                'snap_redirect_url' => $snapRedirectUrl,
            ];
            
        } catch (Exception $e) {
            error_log("Midtrans createTransaction error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Get transaction status from Midtrans
     * 
     * @param string $orderId - Order ID
     * @return array - Transaction status data
     */
    public function getTransactionStatus($orderId) {
        try {
            $status = \Midtrans\Transaction::status($orderId);
            
            return [
                'success' => true,
                'order_id' => $status->order_id,
                'transaction_id' => $status->transaction_id ?? null,
                'transaction_status' => $status->transaction_status,
                'payment_type' => $status->payment_type ?? null,
                'transaction_time' => $status->transaction_time ?? null,
                'settlement_time' => $status->settlement_time ?? null,
                'gross_amount' => $status->gross_amount ?? 0,
                'fraud_status' => $status->fraud_status ?? null,
                'status_code' => $status->status_code ?? null,
                'status_message' => $status->status_message ?? null,
                'va_numbers' => $status->va_numbers ?? null,
                'bank' => $status->va_numbers[0]->bank ?? null,
            ];
            
        } catch (Exception $e) {
            error_log("Midtrans getTransactionStatus error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Handle notification from Midtrans webhook
     * 
     * @param object $notification - Notification data from Midtrans
     * @return array - Processing result
     */
    public function handleNotification($notification) {
        try {
            $notif = new \Midtrans\Notification();
            
            $orderId = $notif->order_id;
            $transactionStatus = $notif->transaction_status;
            $fraudStatus = $notif->fraud_status ?? 'accept';
            $paymentType = $notif->payment_type ?? null;
            $transactionId = $notif->transaction_id ?? null;
            $transactionTime = $notif->transaction_time ?? null;
            $settlementTime = $notif->settlement_time ?? null;
            $grossAmount = $notif->gross_amount ?? 0;
            $statusCode = $notif->status_code ?? null;
            $statusMessage = $notif->status_message ?? null;
            
            // Get VA number if available
            $vaNumber = null;
            $bank = null;
            if (isset($notif->va_numbers) && is_array($notif->va_numbers) && count($notif->va_numbers) > 0) {
                $vaNumber = $notif->va_numbers[0]->va_number ?? null;
                $bank = $notif->va_numbers[0]->bank ?? null;
            }
            
            // Determine final status
            $finalStatus = $this->determineFinalStatus($transactionStatus, $fraudStatus);
            
            // Update transaction in database
            $stmt = $this->conn->prepare("
                UPDATE midtrans_transactions 
                SET transaction_id = ?, 
                    payment_type = ?, 
                    transaction_status = ?, 
                    transaction_time = ?, 
                    settlement_time = ?,
                    va_number = ?,
                    bank = ?,
                    fraud_status = ?,
                    status_code = ?,
                    status_message = ?,
                    metadata = ?,
                    updated_at = NOW()
                WHERE order_id = ?
            ");
            
            $metadata = json_encode($notif);
            $stmt->bind_param(
                "ssssssssssss",
                $transactionId,
                $paymentType,
                $finalStatus,
                $transactionTime,
                $settlementTime,
                $vaNumber,
                $bank,
                $fraudStatus,
                $statusCode,
                $statusMessage,
                $metadata,
                $orderId
            );
            $stmt->execute();
            $stmt->close();
            
            // Get booking ID
            $stmt = $this->conn->prepare("SELECT id_booking FROM midtrans_transactions WHERE order_id = ?");
            $stmt->bind_param("s", $orderId);
            $stmt->execute();
            $result = $stmt->get_result();
            $transaction = $result->fetch_assoc();
            $bookingId = $transaction['id_booking'];
            $stmt->close();
            
            // Update booking status based on payment status
            $this->updateBookingStatus($bookingId, $finalStatus);
            
            return [
                'success' => true,
                'order_id' => $orderId,
                'status' => $finalStatus,
                'booking_id' => $bookingId,
                'gross_amount' => $grossAmount,
                'payment_type' => $paymentType,
            ];
            
        } catch (Exception $e) {
            error_log("Midtrans handleNotification error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Determine final transaction status
     */
    private function determineFinalStatus($transactionStatus, $fraudStatus) {
        if ($transactionStatus == 'capture') {
            return ($fraudStatus == 'accept') ? 'settlement' : 'deny';
        } elseif ($transactionStatus == 'settlement') {
            return 'settlement';
        } elseif ($transactionStatus == 'pending') {
            return 'pending';
        } elseif ($transactionStatus == 'deny') {
            return 'deny';
        } elseif ($transactionStatus == 'expire') {
            return 'expire';
        } elseif ($transactionStatus == 'cancel') {
            return 'cancel';
        } else {
            return $transactionStatus;
        }
    }
    
    /**
     * Update booking status based on payment status
     */
    private function updateBookingStatus($bookingId, $paymentStatus) {
        $bookingStatus = '';
        
        switch ($paymentStatus) {
            case 'settlement':
                $bookingStatus = 'dikonfirmasi'; // Payment successful, booking confirmed
                break;
            case 'pending':
                $bookingStatus = 'menunggu_pembayaran'; // Waiting for payment
                break;
            case 'expire':
            case 'cancel':
            case 'deny':
                $bookingStatus = 'dibatalkan'; // Payment failed, booking cancelled
                break;
            default:
                $bookingStatus = 'menunggu_pembayaran';
        }
        
        $stmt = $this->conn->prepare("UPDATE booking SET status = ? WHERE id_booking = ?");
        $stmt->bind_param("si", $bookingStatus, $bookingId);
        $stmt->execute();
        $stmt->close();
        
        // If payment successful, create pembayaran record
        if ($paymentStatus == 'settlement') {
            $this->createPembayaranRecord($bookingId);
        }
    }
    
    /**
     * Create pembayaran record when payment is successful
     */
    private function createPembayaranRecord($bookingId) {
        // Check if pembayaran already exists
        $stmt = $this->conn->prepare("SELECT id_pembayaran FROM pembayaran WHERE id_booking = ?");
        $stmt->bind_param("i", $bookingId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Already exists, update status
            $stmt->close();
            $stmt = $this->conn->prepare("
                UPDATE pembayaran 
                SET status_pembayaran = 'lunas' 
                WHERE id_booking = ?
            ");
            $stmt->bind_param("i", $bookingId);
            $stmt->execute();
            $stmt->close();
        } else {
            // Create new pembayaran record
            $stmt->close();
            
            // Get transaction details
            $stmt = $this->conn->prepare("
                SELECT mt.id_transaction, mt.gross_amount, mt.payment_type
                FROM midtrans_transactions mt
                WHERE mt.id_booking = ? AND mt.transaction_status = 'settlement'
                ORDER BY mt.created_at DESC
                LIMIT 1
            ");
            $stmt->bind_param("i", $bookingId);
            $stmt->execute();
            $result = $stmt->get_result();
            $transaction = $result->fetch_assoc();
            $stmt->close();
            
            if ($transaction) {
                $stmt = $this->conn->prepare("
                    INSERT INTO pembayaran 
                    (id_booking, jumlah, metode_pembayaran, payment_gateway, id_midtrans_transaction, status_pembayaran, tanggal_pembayaran) 
                    VALUES (?, ?, ?, 'midtrans', ?, 'lunas', NOW())
                ");
                $paymentType = $this->formatPaymentType($transaction['payment_type']);
                $stmt->bind_param("idsi", 
                    $bookingId, 
                    $transaction['gross_amount'], 
                    $paymentType,
                    $transaction['id_transaction']
                );
                $stmt->execute();
                $stmt->close();
            }
        }
    }
    
    /**
     * Format payment type for display
     */
    private function formatPaymentType($paymentType) {
        $types = [
            'bank_transfer' => 'Transfer Bank (Virtual Account)',
            'echannel' => 'Mandiri Bill',
            'gopay' => 'GoPay',
            'shopeepay' => 'ShopeePay',
            'qris' => 'QRIS',
            'credit_card' => 'Kartu Kredit',
            'cstore' => 'Convenience Store',
        ];
        
        return $types[$paymentType] ?? ucfirst(str_replace('_', ' ', $paymentType));
    }
    
    /**
     * Cancel transaction
     */
    public function cancelTransaction($orderId) {
        try {
            \Midtrans\Transaction::cancel($orderId);
            
            // Update database
            $stmt = $this->conn->prepare("
                UPDATE midtrans_transactions 
                SET transaction_status = 'cancel', updated_at = NOW() 
                WHERE order_id = ?
            ");
            $stmt->bind_param("s", $orderId);
            $stmt->execute();
            $stmt->close();
            
            return ['success' => true];
            
        } catch (Exception $e) {
            error_log("Midtrans cancelTransaction error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
?>
