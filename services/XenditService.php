<?php
/**
 * Xendit Service Class
 * Handle all Xendit payment gateway operations
 */

class XenditService {
    private $conn;
    
    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
        
        // Initialize Xendit configuration
        require_once __DIR__ . '/../config/xendit.php';
        initXenditConfig();
    }
    
    /**
     * Create new invoice
     * 
     * @param int $bookingId - ID booking
     * @param float $amount - Jumlah pembayaran
     * @param array $customerData - Detail customer
     * @return array - Contains invoice_url, external_id, invoice_id
     */
    public function createInvoice($bookingId, $amount, $customerData) {
        try {
            // Generate unique external ID
            $externalId = generateXenditExternalId($bookingId);
            
            // Get booking details for description
            $stmt = $this->conn->prepare("
                SELECT k.nama_kamar, t.nama_kost 
                FROM booking b
                JOIN kamar k ON b.id_kamar = k.id_kamar
                JOIN kost t ON k.id_kost = t.id_kost
                WHERE b.id_booking = ?
            ");
            $stmt->bind_param("i", $bookingId);
            $stmt->execute();
            $booking = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            $description = "Pembayaran Kos - {$booking['nama_kamar']} di {$booking['nama_kost']}";
            
            // Prepare invoice parameters for SDK v7
            $create_invoice_request = new \Xendit\Invoice\CreateInvoiceRequest([
                'external_id' => $externalId,
                'amount' => (float)$amount,
                'description' => $description,
                'invoice_duration' => XENDIT_INVOICE_EXPIRY,
                'customer' => [
                    'given_names' => $customerData['nama_lengkap'] ?? 'Customer',
                    'email' => $customerData['email'] ?? 'customer@kosconnect.com',
                    'mobile_number' => $customerData['no_hp'] ?? '+628123456789',
                ],
                'customer_notification_preference' => [
                    'invoice_created' => ['email'],
                    'invoice_reminder' => ['email'],
                    'invoice_paid' => ['email'],
                ],
                'success_redirect_url' => XENDIT_SUCCESS_URL,
                'failure_redirect_url' => XENDIT_FAILURE_URL,
                'currency' => 'IDR',
                'items' => [
                    [
                        'name' => $description,
                        'quantity' => 1,
                        'price' => (float)$amount,
                    ]
                ]
            ]);
            
            // Create invoice API instance
            $apiInstance = new \Xendit\Invoice\InvoiceApi();
            $invoice = $apiInstance->createInvoice($create_invoice_request);
            
            // Calculate expiry date
            $expiredAt = date('Y-m-d H:i:s', time() + XENDIT_INVOICE_EXPIRY);
            
            // Save to database
            $stmt = $this->conn->prepare("
                INSERT INTO xendit_invoices 
                (id_booking, external_id, invoice_id, invoice_url, amount, status, expired_at, created_at) 
                VALUES (?, ?, ?, ?, ?, 'PENDING', ?, NOW())
            ");
            $stmt->bind_param("isssds", 
                $bookingId, 
                $externalId, 
                $invoice['id'], 
                $invoice['invoice_url'], 
                $amount,
                $expiredAt
            );
            $stmt->execute();
            $invoiceDbId = $stmt->insert_id;
            $stmt->close();
            
            return [
                'success' => true,
                'id_invoice' => $invoiceDbId,
                'external_id' => $externalId,
                'invoice_id' => $invoice['id'],
                'invoice_url' => $invoice['invoice_url'],
                'expired_at' => $expiredAt,
            ];
            
        } catch (\Xendit\XenditSdkException $e) {
            error_log("Xendit SDK Error: " . $e->getMessage());
            error_log("Full Error Response: " . $e->getFullError());
            return [
                'success' => false,
                'error' => 'Xendit Error: ' . $e->getMessage(),
            ];
        } catch (Exception $e) {
            error_log("Xendit createInvoice error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Get invoice details
     * 
     * @param string $invoiceId - Xendit invoice ID
     * @return array - Invoice data
     */
    public function getInvoice($invoiceId) {
        try {
            $apiInstance = new \Xendit\Invoice\InvoiceApi();
            $invoice = $apiInstance->getInvoiceById($invoiceId);
            
            return [
                'success' => true,
                'invoice_id' => $invoice['id'],
                'external_id' => $invoice['external_id'],
                'status' => $invoice['status'],
                'amount' => $invoice['amount'],
                'paid_amount' => $invoice['paid_amount'] ?? 0,
                'invoice_url' => $invoice['invoice_url'],
                'payment_method' => $invoice['payment_method'] ?? null,
                'payment_channel' => $invoice['payment_channel'] ?? null,
            ];
            
        } catch (\Xendit\XenditSdkException $e) {
            error_log("Xendit SDK Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        } catch (Exception $e) {
            error_log("Xendit getInvoice error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Handle callback from Xendit webhook
     * 
     * @param array $callbackData - Data from Xendit callback
     * @return array - Processing result
     */
    public function handleCallback($callbackData) {
        try {
            $externalId = $callbackData['external_id'] ?? null;
            $invoiceId = $callbackData['id'] ?? null;
            $status = $callbackData['status'] ?? null;
            $paidAmount = $callbackData['paid_amount'] ?? 0;
            $paymentMethod = $callbackData['payment_method'] ?? null;
            $paymentChannel = $callbackData['payment_channel'] ?? null;
            $paidAt = $callbackData['paid_at'] ?? null;
            
            if (!$externalId || !$invoiceId) {
                throw new Exception('Invalid callback data');
            }
            
            // Update invoice in database
            $stmt = $this->conn->prepare("
                UPDATE xendit_invoices 
                SET invoice_id = ?,
                    status = ?,
                    payment_method = ?,
                    payment_channel = ?,
                    paid_amount = ?,
                    paid_at = ?,
                    callback_data = ?,
                    updated_at = NOW()
                WHERE external_id = ?
            ");
            
            $callbackJson = json_encode($callbackData);
            $paidAtFormatted = $paidAt ? date('Y-m-d H:i:s', strtotime($paidAt)) : null;
            
            $stmt->bind_param("ssssdsss",
                $invoiceId,
                $status,
                $paymentMethod,
                $paymentChannel,
                $paidAmount,
                $paidAtFormatted,
                $callbackJson,
                $externalId
            );
            $stmt->execute();
            $stmt->close();
            
            // Get booking ID
            $stmt = $this->conn->prepare("SELECT id_booking FROM xendit_invoices WHERE external_id = ?");
            $stmt->bind_param("s", $externalId);
            $stmt->execute();
            $result = $stmt->get_result();
            $invoice = $result->fetch_assoc();
            $bookingId = $invoice['id_booking'];
            $stmt->close();
            
            // Update booking status based on payment status
            $this->updateBookingStatus($bookingId, $status);
            
            return [
                'success' => true,
                'external_id' => $externalId,
                'status' => $status,
                'booking_id' => $bookingId,
            ];
            
        } catch (Exception $e) {
            error_log("Xendit handleCallback error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Update booking status based on payment status
     */
    private function updateBookingStatus($bookingId, $paymentStatus) {
        $bookingStatus = '';
        
        switch ($paymentStatus) {
            case 'PAID':
            case 'SETTLED':
                $bookingStatus = 'dibayar'; // Payment successful, status dibayar matching dashboard
                break;
            case 'PENDING':
                $bookingStatus = 'menunggu_pembayaran'; // Waiting for payment
                break;
            case 'EXPIRED':
                $bookingStatus = 'dibatalkan'; // Payment expired, booking cancelled
                break;
            default:
                $bookingStatus = 'menunggu_pembayaran';
        }
        
        $stmt = $this->conn->prepare("UPDATE booking SET status = ? WHERE id_booking = ?");
        $stmt->bind_param("si", $bookingStatus, $bookingId);
        $stmt->execute();
        $stmt->close();
        
        // If payment successful, create pembayaran record
        if ($paymentStatus == 'PAID' || $paymentStatus == 'SETTLED') {
            $this->createPembayaranRecord($bookingId);
        }
    }
    
    /**
     * Sync invoice status from Xendit to Database manually
     * Useful for redirect handling where callback might be delayed or unavailable (localhost)
     */
    public function syncInvoiceStatus($externalId) {
        try {
            // Get invoice from DB to get ID
            $stmt = $this->conn->prepare("SELECT invoice_id, id_booking FROM xendit_invoices WHERE external_id = ?");
            $stmt->bind_param("s", $externalId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                return ['success' => false, 'error' => 'Invoice not found'];
            }
            
            $dbInvoice = $result->fetch_assoc();
            $invoiceId = $dbInvoice['invoice_id'];
            $bookingId = $dbInvoice['id_booking'];
            $stmt->close();
            
            // Fetch from Xendit
            $apiInstance = new \Xendit\Invoice\InvoiceApi();
            $xenditInvoice = $apiInstance->getInvoiceById($invoiceId);
            
            // Map Xendit Invoice object to array for handleCallback
            $callbackData = [
                'id' => $xenditInvoice['id'],
                'external_id' => $xenditInvoice['external_id'],
                'status' => $xenditInvoice['status'],
                'merchant_name' => $xenditInvoice['merchant_name'],
                'merchant_profile_picture_url' => $xenditInvoice['merchant_profile_picture_url'],
                'amount' => $xenditInvoice['amount'],
                'payer_email' => $xenditInvoice['payer_email'],
                'description' => $xenditInvoice['description'],
                'expiry_date' => $xenditInvoice['expiry_date'],
                'invoice_url' => $xenditInvoice['invoice_url'],
                'should_exclude_credit_card' => $xenditInvoice['should_exclude_credit_card'],
                'should_send_email' => $xenditInvoice['should_send_email'],
                'created' => $xenditInvoice['created'],
                'updated' => $xenditInvoice['updated'],
                'currency' => $xenditInvoice['currency'],
                'payment_method' => $xenditInvoice['payment_method'] ?? null,
                'payment_channel' => $xenditInvoice['payment_channel'] ?? null,
                'paid_amount' => $xenditInvoice['paid_amount'] ?? 0,
                'paid_at' => $xenditInvoice['paid_at'] ?? null,
            ];
            
            // Reuse handleCallback logic to update DB
            return $this->handleCallback($callbackData);
            
        } catch (Exception $e) {
            error_log("Sync Invoice Error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
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
            
            // Get invoice details
            $stmt = $this->conn->prepare("
                SELECT id_invoice, amount, payment_method, payment_channel
                FROM xendit_invoices
                WHERE id_booking = ? AND status IN ('PAID', 'SETTLED')
                ORDER BY created_at DESC
                LIMIT 1
            ");
            $stmt->bind_param("i", $bookingId);
            $stmt->execute();
            $result = $stmt->get_result();
            $invoice = $result->fetch_assoc();
            $stmt->close();
            
            if ($invoice) {
                $paymentMethod = $this->formatPaymentMethod($invoice['payment_method'], $invoice['payment_channel']);
                
                $stmt = $this->conn->prepare("
                    INSERT INTO pembayaran 
                    (id_booking, jumlah, metode_pembayaran, payment_gateway, id_xendit_invoice, status_pembayaran, tanggal_pembayaran) 
                    VALUES (?, ?, ?, 'xendit', ?, 'lunas', NOW())
                ");
                $stmt->bind_param("idsi", 
                    $bookingId, 
                    $invoice['amount'], 
                    $paymentMethod,
                    $invoice['id_invoice']
                );
                $stmt->execute();
                $stmt->close();
            }
        }
    }
    
    /**
     * Format payment method for display
     */
    private function formatPaymentMethod($method, $channel) {
        if ($channel) {
            return strtoupper($channel) . ' - ' . ucfirst(str_replace('_', ' ', $method));
        }
        return ucfirst(str_replace('_', ' ', $method));
    }
    
    /**
     * Expire invoice
     */
    public function expireInvoice($invoiceId) {
        try {
            $apiInstance = new \Xendit\Invoice\InvoiceApi();
            $invoice = $apiInstance->expireInvoice($invoiceId);
            
            // Update database
            $stmt = $this->conn->prepare("
                UPDATE xendit_invoices 
                SET status = 'EXPIRED', updated_at = NOW() 
                WHERE invoice_id = ?
            ");
            $stmt->bind_param("s", $invoiceId);
            $stmt->execute();
            $stmt->close();
            
            return ['success' => true];
            
        } catch (\Xendit\XenditSdkException $e) {
            error_log("Xendit SDK Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        } catch (Exception $e) {
            error_log("Xendit expireInvoice error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
?>
