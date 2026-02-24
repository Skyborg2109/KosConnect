<?php
session_start();
include '../config/db.php';
require_once __DIR__ . '/../config/cloudinary.php';
use Cloudinary\Api\Upload\UploadApi;

// Check if this is a Xendit/Midtrans request (needs JSON response)
$payment_method = $_POST['payment_method'] ?? 'manual';
$isGatewayRequest = in_array($payment_method, ['xendit']);

// Suppress errors for gateway requests
if ($isGatewayRequest) {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// 1. Autentikasi: Pastikan pengguna adalah penyewa yang sudah login
if (!isset($_SESSION['user_logged_in']) || $_SESSION['role'] !== 'penyewa') {
    if ($isGatewayRequest) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Akses tidak sah. Silakan login kembali.']);
        exit();
    }
    die("Akses tidak sah. Silakan login kembali.");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($isGatewayRequest) {
        header('Content-Type: application/json');
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Metode request tidak valid.']);
        exit();
    }
    die("Metode request tidak valid.");
}

$id_penyewa = $_SESSION['user_id'];
$id_booking = filter_var($_POST['id_booking'] ?? 0, FILTER_VALIDATE_INT);
$jumlah = filter_var($_POST['jumlah'] ?? 0, FILTER_VALIDATE_FLOAT);
$metode_pembayaran = trim($_POST['metode_pembayaran'] ?? '');

// 2. Validasi Input
if ($id_booking <= 0 || $jumlah <= 0 || empty($metode_pembayaran)) {
    if ($isGatewayRequest) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Data yang dikirim tidak lengkap atau tidak valid.']);
        exit();
    }
    die("Data yang dikirim tidak lengkap atau tidak valid.");
}

// 3. Validasi Kepemilikan Booking
$stmt_check = $conn->prepare("SELECT status FROM booking WHERE id_booking = ? AND id_penyewa = ?");
$stmt_check->bind_param("ii", $id_booking, $id_penyewa);
$stmt_check->execute();
$result_check = $stmt_check->get_result();
if ($result_check->num_rows === 0) {
    if ($isGatewayRequest) {
        header('Content-Type: application/json');
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Booking tidak ditemukan atau Anda tidak berhak mengaksesnya.']);
        exit();
    }
    die("Booking tidak ditemukan atau Anda tidak berhak mengaksesnya.");
}
$booking = $result_check->fetch_assoc();
if ($booking['status'] !== 'menunggu_pembayaran') {
    if ($isGatewayRequest) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Booking ini tidak lagi menunggu pembayaran.']);
        exit();
    }
    die("Booking ini tidak lagi menunggu pembayaran.");
}
$stmt_check->close();



// === PAYMENT GATEWAY (XENDIT) ===
if ($payment_method === 'xendit') {
    // Start output buffering to catch any unwanted output
    ob_start();
    
    try {
        // Load composer autoload for Xendit SDK
        if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
            ob_end_clean();
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Xendit SDK belum terinstall. Jalankan: composer install'
            ]);
            exit();
        }
        
        require_once __DIR__ . '/../vendor/autoload.php';
        require_once '../services/XenditService.php';
        require_once '../config/xendit.php';
        
        // Check if Xendit is configured
        if (!isXenditConfigured()) {
            ob_end_clean();
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Payment gateway belum dikonfigurasi. Silakan hubungi administrator.'
            ]);
            exit();
        }
        
        // Get customer details
        $stmt = $conn->prepare("SELECT nama_lengkap, email, no_telepon FROM user WHERE id_user = ?");
        $stmt->bind_param("i", $id_penyewa);
        $stmt->execute();
        $customer = $stmt->get_result()->fetch_assoc();
        
        // Map no_telepon to no_hp for Xendit
        $customer['no_hp'] = $customer['no_telepon'] ?? '+628123456789';
        $stmt->close();
        
        // Create Xendit invoice
        $xenditService = new XenditService($conn);
        $result = $xenditService->createInvoice($id_booking, $jumlah, $customer);
        
        // Clear any buffered output
        ob_end_clean();
        
        if ($result['success']) {
            // Return JSON response with invoice URL
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'invoice_url' => $result['invoice_url'],
                'invoice_id' => $result['invoice_id'],
                'external_id' => $result['external_id'],
            ]);
            exit();
        } else {
            // Error creating invoice
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $result['error'] ?? 'Gagal membuat invoice pembayaran'
            ]);
            exit();
        }
    } catch (Exception $e) {
        // Clear buffer and return error
        ob_end_clean();
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        exit();
    }
}

// === MANUAL PAYMENT (UPLOAD BUKTI) ===
else {
    // 4. Handle File Upload
    $bukti_nama = null;
    if (isset($_FILES['bukti_pembayaran']) && $_FILES['bukti_pembayaran']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['bukti_pembayaran'];
        $upload_dir = '../uploads/payments/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Validasi file
        $allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];
        $max_size = 2 * 1024 * 1024; // 2MB
        if (!in_array($file['type'], $allowed_types) || $file['size'] > $max_size) {
            die("Tipe file tidak valid atau ukuran terlalu besar. Hanya JPG, PNG, PDF (maks 2MB) yang diizinkan.");
        }

        // Generate unique name
        try {
            $uploadApi = new UploadApi();
            $uploadResult = $uploadApi->upload($file['tmp_name'], [
                'folder' => 'kosconnect/payments', 
                'public_id' => uniqid('payment_'),
                'resource_type' => 'auto' // 'auto' allows pdf or image
            ]);
            $bukti_nama = $uploadResult['secure_url'];
        } catch (Exception $e) {
            die("Gagal mengunggah bukti pembayaran ke Cloudinary: " . $e->getMessage());
        }
    } else {
        die("Anda harus mengunggah bukti pembayaran.");
    }

    // 5. Simpan ke Database
    $conn->begin_transaction();
    try {
        // Masukkan data ke tabel pembayaran (manual payment)
        $stmt_insert = $conn->prepare("
            INSERT INTO pembayaran 
            (id_booking, jumlah, metode_pembayaran, payment_gateway, status_pembayaran, bukti_pembayaran, tanggal_pembayaran) 
            VALUES (?, ?, ?, 'manual', 'menunggu', ?, NOW())
        ");
        $stmt_insert->bind_param("idss", $id_booking, $jumlah, $metode_pembayaran, $bukti_nama);
        $stmt_insert->execute();

        // Update status booking menjadi 'dibayar' (menunggu verifikasi admin)
        $stmt_update = $conn->prepare("UPDATE booking SET status = 'dibayar' WHERE id_booking = ?");
        $stmt_update->bind_param("i", $id_booking);
        $stmt_update->execute();

        // Ambil detail untuk notifikasi ke pemilik
        $stmt_details = $conn->prepare("
            SELECT t.id_pemilik, u.nama_lengkap, k.nama_kamar
            FROM booking b
            JOIN kamar k ON b.id_kamar = k.id_kamar
            JOIN kost t ON k.id_kost = t.id_kost
            JOIN user u ON b.id_penyewa = u.id_user
            WHERE b.id_booking = ?
        ");
        $stmt_details->bind_param("i", $id_booking);
        $stmt_details->execute();
        $details = $stmt_details->get_result()->fetch_assoc();

        // Kirim notifikasi ke pemilik
        $pesan_notif = "Pembayaran dari '{$details['nama_lengkap']}' untuk kamar '{$details['nama_kamar']}' telah diterima. Mohon segera verifikasi.";
        $link_notif = '/KosConnect/dashboard/dashboardpemilik.php?module=owner_manage_payments';
        $stmt_notif = $conn->prepare("INSERT INTO notifications (id_user, pesan, link) VALUES (?, ?, ?)");
        $stmt_notif->bind_param("iss", $details['id_pemilik'], $pesan_notif, $link_notif);
        $stmt_notif->execute();
        $stmt_notif->close();

        // Kirim notifikasi ke Admin (All Admins)
        $stmt_admin = $conn->prepare("SELECT id_user FROM user WHERE role = 'admin'");
        $stmt_admin->execute();
        $res_admin = $stmt_admin->get_result();
        
        $jumlahFormatted = number_format($jumlah, 0, ',', '.');
        $pesan_admin = "Pembayaran Manual Baru: {$details['nama_lengkap']} - {$details['nama_kamar']} (Rp {$jumlahFormatted}). Perlu verifikasi.";
        $link_admin = '/KosConnect/dashboard/dashboardadmin.php?module=admin_manage_transactions';
        
        $stmt_notif_admin = $conn->prepare("INSERT INTO notifications (id_user, pesan, link) VALUES (?, ?, ?)");
        
        while ($admin = $res_admin->fetch_assoc()) {
            $stmt_notif_admin->bind_param("iss", $admin['id_user'], $pesan_admin, $link_admin);
            $stmt_notif_admin->execute();
        }
        
        $stmt_notif_admin->close();
        $stmt_admin->close();

        $conn->commit();

        // Redirect ke halaman dashboard user dengan pesan sukses
        $_SESSION['payment_success'] = "Pembayaran Anda telah berhasil dilakukan. Silakan tunggu konfirmasi dari admin setelah mereka memverifikasi bukti pembayaran yang Anda upload.";
        header("Location: user_dashboard.php");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        die("Terjadi kesalahan database: " . $e->getMessage());
    }
}

$conn->close();
?>