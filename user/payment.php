<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['role'] !== 'penyewa') {
    header("Location: ../auth/loginForm.php");
    exit();
}

$userName = htmlspecialchars($_SESSION['fullname'] ?? 'Penyewa Kos');
$userRole = htmlspecialchars($_SESSION['role'] ?? 'penyewa');
$userEmail = $_SESSION['email'] ?? '';
$userPhoto = $_SESSION['foto_profil'] ?? null;

include '../config/db.php';

$id_penyewa = $_SESSION['user_id'];
$id_booking = filter_var($_GET['booking_id'] ?? 0, FILTER_VALIDATE_INT);
$booking_details = null;
$error_message = '';

if ($id_booking <= 0) {
    $error_message = "ID Booking tidak valid.";
} else {
    // Ambil detail booking untuk ditampilkan, pastikan booking ini milik user yang login dan statusnya pending
    $stmt = $conn->prepare("
        SELECT 
            b.id_booking, b.status,
            k.nama_kamar, k.harga,
            t.nama_kost, t.alamat
        FROM booking b
        INNER JOIN kamar k ON b.id_kamar = k.id_kamar
        INNER JOIN kost t ON k.id_kost = t.id_kost
        WHERE b.id_booking = ? AND b.id_penyewa = ?
    ");
    $stmt->bind_param("ii", $id_booking, $id_penyewa);
    $stmt->execute();
    $result = $stmt->get_result();
    $booking_details = $result->fetch_assoc();
    $stmt->close();

    if (!$booking_details) {
        $error_message = "Booking tidak ditemukan atau Anda tidak memiliki akses.";
    } elseif ($booking_details['status'] !== 'menunggu_pembayaran') {
        $error_message = "Booking ini tidak lagi menunggu pembayaran. Status saat ini: " . ucfirst($booking_details['status']);
        $booking_details = null; // Jangan tampilkan form jika status tidak sesuai
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Booking - KosConnect</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
        }
        
        .user-info-box { 
            display: flex; 
            align-items: center; 
            padding: 10px 16px; 
            border-radius: 9999px; 
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .user-info-box:hover { 
            background: linear-gradient(135deg, #e5e7eb 0%, #d1d5db 100%);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        nav {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95) !important;
            transition: all 0.3s ease;
        }
        
        .payment-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.1);
            animation: slideUp 0.6s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .price-display {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 16px;
            text-align: center;
            margin: 20px 0;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        
        .instruction-card {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            border-left: 4px solid #2196f3;
            position: relative;
            overflow: hidden;
        }
        
        .instruction-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: radial-gradient(circle, rgba(33, 150, 243, 0.1) 0%, transparent 70%);
            border-radius: 50%;
            transform: translate(30px, -30px);
        }
        
        .form-group {
            margin-bottom: 24px;
            position: relative;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 16px 20px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: white;
        }
        
        .form-group select optgroup {
            font-weight: 600;
            color: #374151;
            background: #f9fafb;
        }
        
        .form-group select option {
            padding: 10px 20px;
            background: white;
            color: #374151;
        }
        
        /* Custom Select with Logos */
        .custom-select-wrapper {
            position: relative;
            width: 100%;
        }
        
        .custom-select {
            width: 100%;
            padding: 16px 20px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 16px;
            background: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.3s ease;
        }
        
        .custom-select:hover,
        .custom-select.active {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .custom-select-text {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .custom-select-logo {
            width: 24px;
            height: 24px;
            object-fit: contain;
        }
        
        .custom-select-arrow {
            transition: transform 0.3s ease;
        }
        
        .custom-select.active .custom-select-arrow {
            transform: rotate(180deg);
        }
        
        .custom-options {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            margin-top: 8px;
            max-height: 400px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .custom-options.show {
            display: block;
        }
        
        .custom-option-group {
            padding: 8px 0;
        }
        
        .custom-option-group:not(:last-child) {
            border-bottom: 1px solid #e5e7eb;
        }
        
        .custom-option-group-label {
            padding: 8px 20px;
            font-weight: 600;
            color: #6b7280;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: #f9fafb;
        }
        
        .custom-option {
            padding: 12px 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.2s ease;
        }
        
        .custom-option:hover {
            background: #f3f4f6;
        }
        
        .custom-option.selected {
            background: #ede9fe;
            color: #7c3aed;
        }
        
        .custom-option-logo {
            width: 28px;
            height: 28px;
            object-fit: contain;
            flex-shrink: 0;
        }
        
        .custom-option-text {
            flex: 1;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
        }
        
        .file-upload {
            position: relative;
            display: inline-block;
            width: 100%;
        }
        
        .file-upload input[type="file"] {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        
        .file-upload-label {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            border: 2px dashed #d1d5db;
            border-radius: 12px;
            background: #f9fafb;
            cursor: pointer;
            transition: all 0.3s ease;
            min-height: 80px;
        }
        
        .file-upload:hover .file-upload-label {
            border-color: #667eea;
            background: #f0f4ff;
        }
        
        .submit-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 18px 32px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            width: 100%;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        
        .submit-btn:active {
            transform: translateY(0);
        }
        
        .bank-info {
            background: white;
            padding: 16px;
            border-radius: 8px;
            margin: 8px 0;
            border-left: 4px solid #2196f3;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .floating-shapes {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }
        
        .shape {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            animation: float 6s ease-in-out infinite;
        }
        
        .shape:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }
        
        .shape:nth-child(2) {
            width: 60px;
            height: 60px;
            top: 20%;
            right: 10%;
            animation-delay: 2s;
        }
        
        .shape:nth-child(3) {
            width: 100px;
            height: 100px;
            bottom: 20%;
            left: 20%;
            animation-delay: 4s;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        
        .icon-pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        /* Mobile Menu Drawer Styles */
        #mobileMenuPanel {
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            will-change: transform;
        }

        #mobileMenuBackdrop {
            transition: opacity 0.3s ease;
        }

        .mobile-menu-link {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .mobile-menu-link:hover {
            background-color: #f3f4f6;
            padding-left: 24px;
        }

        @media (max-width: 768px) {
            #mobileMenuPanel {
                transform: translateX(100%);
            }

            body {
                font-size: 14px;
            }

            .text-5xl {
                font-size: 28px !important;
            }

            .text-3xl {
                font-size: 24px !important;
            }

            .payment-card {
                padding: 20px !important;
                margin: 0 12px;
                border-radius: 16px;
            }

            .price-display {
                padding: 16px !important;
                margin: 16px 0 !important;
                border-radius: 12px;
            }

            .price-display .text-4xl {
                font-size: 32px;
            }

            .instruction-card {
                padding: 16px !important;
                border-radius: 12px;
            }

            .form-group input,
            .form-group select {
                padding: 14px 16px !important;
                font-size: 14px;
            }

            .file-upload-label {
                padding: 16px !important;
                min-height: 70px;
            }

            .file-upload-label .text-center i {
                font-size: 40px !important;
            }

            .submit-btn {
                padding: 16px 24px !important;
                font-size: 14px;
                border-radius: 10px;
            }

            .bank-info {
                padding: 12px !important;
                margin: 6px 0 !important;
            }

            .bank-info .text-sm {
                font-size: 12px !important;
            }

            .bank-info .font-semibold {
                font-size: 14px;
            }

            #qrcode {
                width: 100% !important;
                display: flex !important;
                justify-content: center !important;
            }

            #qrcode canvas {
                max-width: 200px !important;
                height: auto !important;
            }

            .payment-method-info {
                max-height: none !important;
            }

            .text-gray-600 {
                font-size: 13px !important;
            }

            .text-gray-500 {
                font-size: 12px !important;
            }

            .text-sm {
                font-size: 12px !important;
            }

            .text-xs {
                font-size: 11px !important;
            }

            .mt-4, .mb-4, .my-4 {
                margin-top: 12px !important;
                margin-bottom: 12px !important;
            }

            .mt-8 {
                margin-top: 20px !important;
            }

            .pt-24 {
                padding-top: 72px !important;
            }

            .pb-12 {
                padding-bottom: 20px !important;
            }

            .px-4 {
                padding-left: 12px !important;
                padding-right: 12px !important;
            }

            .max-w-2xl {
                max-width: 100% !important;
            }

            nav .text-2xl {
                font-size: 18px !important;
            }

            nav .text-purple-600 {
                font-size: 13px !important;
            }

            .space-y-3 > * + * {
                margin-top: 12px !important;
            }

            .space-y-1 > * + * {
                margin-top: 4px !important;
            }

            .flex.items-center.justify-between {
                gap: 8px;
            }

            .icon-pulse {
                width: 50px !important;
                height: 50px !important;
            }

            .w-16 {
                width: 50px !important;
            }

            .h-16 {
                height: 50px !important;
            }

            .text-center.mb-8 {
                margin-bottom: 20px !important;
            }

            .text-center.mb-8 p {
                font-size: 13px !important;
                line-height: 1.4;
            }
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">

    <nav class="bg-white shadow-lg fixed w-full top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <a href="../dashboard/dashboarduser.php" class="flex items-center group">
                    <div class="w-10 h-10 bg-gradient-to-br from-purple-600 to-indigo-600 rounded-lg flex items-center justify-center mr-2 group-hover:scale-110 transition-transform">
                        <i class="fas fa-home text-white"></i>
                    </div>
                    <h1 class="text-2xl font-bold bg-gradient-to-r from-purple-600 to-indigo-600 bg-clip-text text-transparent hidden sm:block">Kos<span class="text-purple-600">Connect</span></h1>
                </a>
                <div class="hidden md:flex items-center space-x-6">
                    <a href="user_dashboard.php#riwayat" class="text-purple-600 hover:underline font-semibold">&larr; Kembali ke Dashboard</a>
                    <div class="flex items-center space-x-4 pl-6 border-l-2 border-gray-200">
                        <button id="notifBtn" onclick="showNotifications()" class="relative text-gray-600 hover:text-purple-600 p-2 rounded-lg hover:bg-purple-50 transition-all">
                            <i class="fas fa-bell text-xl"></i>
                            <?php if (isset($_SESSION['notif_count']) && $_SESSION['notif_count'] > 0): ?>
                                <span id="notifBadge" class="absolute top-0 right-0 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center"><?php echo $_SESSION['notif_count']; ?></span>
                            <?php endif; ?>
                        </button>
                        <button onclick="showProfileModal()" class="flex items-center space-x-2 p-2 rounded-full hover:bg-gray-100 transition">
                            <?php if ($userPhoto): ?>
                                <img id="headerUserPhoto" src="../uploads/profiles/<?php echo htmlspecialchars($userPhoto); ?>" alt="Foto Profil" class="w-9 h-9 rounded-full object-cover">
                            <?php else: ?>
                                <div id="headerUserPhoto" class="w-9 h-9 rounded-full bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center text-white font-bold">
                                    <?php echo strtoupper(substr($userName, 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                        </button>
                        <a href="../auth/logout.php" onclick="confirmLogout(event)" class="bg-gradient-to-r from-red-500 to-red-600 text-white px-4 py-2 rounded-lg font-medium text-sm hover:from-red-600 hover:to-red-700 shadow-md hover:shadow-lg cursor-pointer">
                            <i class="fas fa-sign-out-alt mr-1"></i>Logout
                        </a>
                    </div>
                </div>
                
                <!-- Mobile Menu Button -->
                <div class="flex md:hidden items-center space-x-1">
                    <button id="notifBtn" onclick="showNotifications()" class="relative text-gray-600 hover:text-purple-600 p-2 rounded-lg hover:bg-purple-50 transition-all">
                        <i class="fas fa-bell text-lg"></i>
                        <?php if (isset($_SESSION['notif_count']) && $_SESSION['notif_count'] > 0): ?>
                            <span id="notifBadge" class="absolute top-0 right-0 bg-red-500 text-white text-xs rounded-full h-4 w-4 flex items-center justify-center text-xs"><?php echo $_SESSION['notif_count']; ?></span>
                        <?php endif; ?>
                    </button>
                    <button id="mobileMenuBtn" class="p-2 text-gray-600 hover:text-purple-600 hover:bg-purple-50 rounded-lg transition-colors" onclick="toggleMobileMenu()" title="Menu">
                        <i class="fas fa-bars text-lg"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Mobile Navigation Drawer -->
    <div id="mobileMenuDrawer" class="fixed inset-0 z-40 md:hidden pointer-events-none" style="pointer-events: none;">
        <!-- Backdrop -->
        <div id="mobileMenuBackdrop" class="absolute inset-0 bg-black bg-opacity-50 transition-opacity duration-300 opacity-0 pointer-events-none" onclick="toggleMobileMenu()" style="pointer-events: none;"></div>
        
        <!-- Drawer -->
        <div class="absolute right-0 top-0 h-full w-64 bg-white shadow-2xl transform translate-x-full transition-transform duration-300" id="mobileMenuPanel" style="pointer-events: auto;">
            <!-- Close Button -->
            <div class="flex items-center justify-between p-4 border-b border-gray-100">
                <h2 class="text-xl font-bold text-gray-800">Menu</h2>
                <button onclick="toggleMobileMenu()" class="p-2 text-gray-600 hover:text-purple-600 rounded-lg">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <!-- Navigation Links -->
            <nav class="p-4 space-y-1">
                <a href="user_dashboard.php#riwayat" class="flex items-center px-4 py-3 text-gray-700 hover:bg-purple-50 hover:text-purple-600 rounded-lg font-medium transition-colors mobile-menu-link" onclick="handleMobileMenuClick(event)">
                    <i class="fas fa-arrow-left mr-3"></i>Kembali ke Dashboard
                </a>
            </nav>
            <!-- Divider -->
            <div class="border-t border-gray-100 my-4"></div>
            
            <!-- User Actions -->
            <div class="p-4 space-y-3" style="pointer-events: auto;">
                <button id="mobileNotifBtn" class="w-full px-4 py-3 bg-blue-500 text-white rounded-lg font-medium hover:bg-blue-600 transition-all flex items-center justify-center" type="button" style="pointer-events: auto; cursor: pointer;">
                    <i class="fas fa-bell mr-2"></i>Notifikasi
                </button>
                <button id="mobileLogoutBtn" class="w-full px-4 py-3 bg-red-500 text-white rounded-lg font-medium hover:bg-red-600 transition-all text-center flex items-center justify-center" type="button" style="pointer-events: auto; cursor: pointer;">
                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                </button>
            </div>
        </div>
    </div>

    <main class="pt-24 pb-12 relative">
        <div class="floating-shapes">
            <div class="shape"></div>
            <div class="shape"></div>
            <div class="shape"></div>
        </div>
        
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-8">
                <h1 class="text-4xl sm:text-5xl font-bold text-white mb-3 sm:mb-4" style="text-shadow: 0 2px 4px rgba(0,0,0,0.3);">
                    💳 Konfirmasi Pembayaran
                </h1>
                <p class="text-purple-100 text-sm sm:text-lg">Lengkapi pembayaran untuk menyelesaikan booking kos Anda</p>
            </div>

            <?php if ($error_message): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-6 mb-6 rounded-xl shadow-lg" role="alert">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle text-red-500 text-2xl mr-3"></i>
                        <div>
                            <p class="font-bold text-lg">Error</p>
                            <p><?php echo $error_message; ?></p>
                        </div>
                    </div>
                </div>
            <?php elseif ($booking_details): ?>
                <div class="payment-card rounded-2xl p-8">
                    <!-- Property Info Header -->
                    <div class="text-center mb-6 sm:mb-8">
                        <div class="w-12 sm:w-16 h-12 sm:h-16 bg-gradient-to-br from-purple-400 to-purple-600 rounded-full flex items-center justify-center mx-auto mb-3 sm:mb-4 icon-pulse">
                            <i class="fas fa-home text-white text-xl sm:text-2xl"></i>
                        </div>
                        <h2 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-1 sm:mb-2"><?php echo htmlspecialchars($booking_details['nama_kost']); ?></h2>
                        <p class="text-gray-600 text-sm sm:text-lg"><?php echo htmlspecialchars($booking_details['nama_kamar']); ?></p>
                        <p class="text-gray-500 text-xs sm:text-sm"><?php echo htmlspecialchars($booking_details['alamat']); ?></p>
                    </div>

                    <!-- Price Display -->
                    <div class="price-display">
                        <div class="text-sm opacity-90 mb-2">Total Tagihan</div>
                        <div class="text-4xl font-bold mb-1">Rp <?php echo number_format($booking_details['harga'], 0, ',', '.'); ?></div>
                        <div class="text-sm opacity-90">Untuk 1 Bulan Pertama</div>
                    </div>

                    <!-- Payment Instructions -->
                    <div class="instruction-card p-6 rounded-xl mb-8">
                        <div class="flex items-center mb-4">
                            <i class="fas fa-info-circle text-blue-600 text-xl mr-3"></i>
                            <h3 class="font-bold text-blue-800 text-lg">Instruksi Pembayaran</h3>
                        </div>
                        <p class="text-blue-700 mb-4">Silakan pilih metode pembayaran yang Anda inginkan dan ikuti instruksi pembayaran yang ditampilkan.</p>
                        
                        <!-- Payment Method Instructions (Hidden by default) -->
                        <div id="bankTransferInfo" class="payment-method-info hidden space-y-3">
                            <p class="text-blue-700 font-semibold mb-3">Transfer ke salah satu rekening bank berikut:</p>
                            <div class="space-y-3">
                                <div class="bank-info">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="font-semibold text-gray-800">Bank BCA</div>
                                            <div class="text-sm text-gray-600">123-456-7890</div>
                                            <div class="text-sm text-gray-600">a/n KosConnect Admin</div>
                                        </div>
                                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/5/5c/Bank_Central_Asia.svg/512px-Bank_Central_Asia.svg.png" alt="BCA" style="width: 50px; height: 50px; object-fit: contain;">
                                    </div>
                                </div>
                                
                                <div class="bank-info">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="font-semibold text-gray-800">Bank Mandiri</div>
                                            <div class="text-sm text-gray-600">098-765-4321</div>
                                            <div class="text-sm text-gray-600">a/n KosConnect Admin</div>
                                        </div>
                                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/a/ad/Bank_Mandiri_logo_2016.svg/512px-Bank_Mandiri_logo_2016.svg.png" alt="Mandiri" style="width: 50px; height: 50px; object-fit: contain;">
                                    </div>
                                </div>

                                <div class="bank-info">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="font-semibold text-gray-800">Bank BRI</div>
                                            <div class="text-sm text-gray-600">002-345-6789</div>
                                            <div class="text-sm text-gray-600">a/n KosConnect Admin</div>
                                        </div>
                                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/2/2e/BRI_2020.svg/512px-BRI_2020.svg.png" alt="BRI" style="width: 50px; height: 50px; object-fit: contain;">
                                    </div>
                                </div>

                                <div class="bank-info">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="font-semibold text-gray-800">Bank BNI</div>
                                            <div class="text-sm text-gray-600">004-567-8901</div>
                                            <div class="text-sm text-gray-600">a/n KosConnect Admin</div>
                                        </div>
                                        <img src="https://www.bni.co.id/Portals/1/BNI-logo.png" alt="BNI" style="width: 50px; height: 50px; object-fit: contain;">
                                    </div>
                                </div>

                                <div class="bank-info">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="font-semibold text-gray-800">Bank CIMB Niaga</div>
                                            <div class="text-sm text-gray-600">763-123-4567</div>
                                            <div class="text-sm text-gray-600">a/n KosConnect Admin</div>
                                        </div>
                                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/3/38/CIMB_Niaga_logo.svg/1280px-CIMB_Niaga_logo.svg.png" alt="CIMB Niaga" style="width: 50px; height: 50px; object-fit: contain;">
                                    </div>
                                </div>

                                <div class="bank-info">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="font-semibold text-gray-800">Bank Danamon</div>
                                            <div class="text-sm text-gray-600">011-234-5678</div>
                                            <div class="text-sm text-gray-600">a/n KosConnect Admin</div>
                                        </div>
                                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/a/a1/Danamon_%282024%29.svg/1200px-Danamon_%282024%29.svg.png" alt="Danamon" style="width: 50px; height: 50px; object-fit: contain; background: white; padding: 5px; border-radius: 8px;" onerror="this.onerror=null; this.outerHTML='<div style=\'width:50px;height:50px;background:#003D7A;border-radius:8px;display:flex;align-items:center;justify-content:center;font-weight:bold;color:white;font-size:9px;text-align:center;\'>Danamon</div>';">
                                    </div>
                                </div>

                                <div class="bank-info">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="font-semibold text-gray-800">Bank Permata</div>
                                            <div class="text-sm text-gray-600">013-345-6789</div>
                                            <div class="text-sm text-gray-600">a/n KosConnect Admin</div>
                                        </div>
                                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/f/ff/Permata_Bank_%282024%29.svg/640px-Permata_Bank_%282024%29.svg.png" alt="Permata" style="width: 50px; height: 50px; object-fit: contain; background: white; padding: 5px; border-radius: 8px;" onerror="this.onerror=null; this.outerHTML='<div style=\'width:50px;height:50px;background:#00A651;border-radius:8px;display:flex;align-items:center;justify-content:center;font-weight:bold;color:white;font-size:9px;text-align:center;\'>Permata</div>';">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Virtual Account Info -->
                        <div id="vaInfo" class="payment-method-info hidden space-y-3">
                            <p class="text-blue-700 font-semibold mb-3">Gunakan Virtual Account (VA) berikut:</p>
                            <div class="space-y-3">
                                <div class="bank-info">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="font-semibold text-gray-800">BCA Virtual Account</div>
                                            <div class="text-sm text-gray-600">VA: 50012345678901</div>
                                            <div class="text-sm text-gray-600">Atas nama: KosConnect</div>
                                        </div>
                                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/5/5c/Bank_Central_Asia.svg/512px-Bank_Central_Asia.svg.png" alt="BCA" style="width: 50px; height: 50px; object-fit: contain;">
                                    </div>
                                </div>
                                
                                <div class="bank-info">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="font-semibold text-gray-800">Mandiri Virtual Account</div>
                                            <div class="text-sm text-gray-600">VA: 70012345678901</div>
                                            <div class="text-sm text-gray-600">Atas nama: KosConnect</div>
                                        </div>
                                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/a/ad/Bank_Mandiri_logo_2016.svg/512px-Bank_Mandiri_logo_2016.svg.png" alt="Mandiri" style="width: 50px; height: 50px; object-fit: contain;">
                                    </div>
                                </div>

                                <div class="bank-info">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="font-semibold text-gray-800">BRI Virtual Account</div>
                                            <div class="text-sm text-gray-600">VA: 60012345678901</div>
                                            <div class="text-sm text-gray-600">Atas nama: KosConnect</div>
                                        </div>
                                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/2/2e/BRI_2020.svg/512px-BRI_2020.svg.png" alt="BRI" style="width: 50px; height: 50px; object-fit: contain;">
                                    </div>
                                </div>

                                <div class="bank-info">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="font-semibold text-gray-800">BNI Virtual Account</div>
                                            <div class="text-sm text-gray-600">VA: 80012345678901</div>
                                            <div class="text-sm text-gray-600">Atas nama: KosConnect</div>
                                        </div>
                                        <img src="https://www.bni.co.id/Portals/1/BNI-logo.png" alt="BNI" style="width: 50px; height: 50px; object-fit: contain;" onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNTAiIGhlaWdodD0iNTAiIHZpZXdCb3g9IjAgMCA1MCA1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iNTAiIGhlaWdodD0iNTAiIGZpbGw9IiNGNDc5MjAiIHJ4PSI4Ii8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxMiIgZm9udC13ZWlnaHQ9ImJvbGQiIGZpbGw9IndoaXRlIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkeT0iLjNlbSI+Qk5JPC90ZXh0Pjwvc3ZnPg==';">

                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- E-Wallet Info -->
                        <div id="eWalletInfo" class="payment-method-info hidden space-y-3">
                            <p class="text-blue-700 font-semibold mb-3">Transfer ke nomor atau akun di aplikasi e-wallet berikut:</p>
                            <div class="space-y-3">
                                <div class="bank-info">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="font-semibold text-gray-800">OVO</div>
                                            <div class="text-sm text-gray-600">Nomor: 0812-3456-7890</div>
                                            <div class="text-sm text-gray-600">Atas nama: KosConnect</div>
                                        </div>
                                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/e/eb/Logo_ovo_purple.svg/512px-Logo_ovo_purple.svg.png" alt="OVO" style="width: 40px; height: 40px; object-fit: contain;">
                                    </div>
                                </div>
                                
                                <div class="bank-info">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="font-semibold text-gray-800">DANA</div>
                                            <div class="text-sm text-gray-600">Nomor: 0812-3456-7891</div>
                                            <div class="text-sm text-gray-600">Atas nama: KosConnect</div>
                                        </div>
                                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/7/72/Logo_dana_blue.svg/512px-Logo_dana_blue.svg.png" alt="DANA" style="width: 40px; height: 40px; object-fit: contain;">
                                    </div>
                                </div>

                                <div class="bank-info">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="font-semibold text-gray-800">GoPay</div>
                                            <div class="text-sm text-gray-600">Nomor: 0812-3456-7892</div>
                                            <div class="text-sm text-gray-600">Atas nama: KosConnect</div>
                                        </div>
                                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/8/86/Gopay_logo.svg/512px-Gopay_logo.svg.png" alt="GoPay" style="width: 40px; height: 40px; object-fit: contain;">
                                    </div>
                                </div>

                                <div class="bank-info">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="font-semibold text-gray-800">LinkAja</div>
                                            <div class="text-sm text-gray-600">Nomor: 0812-3456-7893</div>
                                            <div class="text-sm text-gray-600">Atas nama: KosConnect</div>
                                        </div>
                                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/8/85/LinkAja.svg/512px-LinkAja.svg.png" alt="LinkAja" style="width: 40px; height: 40px; object-fit: contain;">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- QRIS Info -->
                        <div id="qrisInfo" class="payment-method-info hidden space-y-3">
                            <p class="text-blue-700 font-semibold mb-3 text-sm sm:text-base">Scan QRIS KosConnect dengan aplikasi perbankan atau e-wallet Anda:</p>
                            <div class="bg-white p-4 sm:p-6 rounded-lg text-center">
                                <div id="qrcode" class="mx-auto mb-4 flex items-center justify-center"></div>
                                <p class="text-gray-600 text-xs sm:text-sm">Scan dengan aplikasi perbankan atau dompet digital manapun</p>
                            </div>
                        </div>
                        
                        <div class="mt-4 p-3 bg-blue-50 rounded-lg">
                            <p class="text-sm text-blue-700 flex items-center">
                                <i class="fas fa-lightbulb text-yellow-500 mr-2"></i>
                                Setelah transfer/pembayaran, unggah bukti pembayaran pada form di bawah ini.
                            </p>
                        </div>
                    </div>

                    <!-- Payment Form -->
                    <form id="paymentForm" action="process_payment.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="id_booking" value="<?php echo $id_booking; ?>">
                        <input type="hidden" name="jumlah" value="<?php echo $booking_details['harga']; ?>">
                        
                        <div class="form-group">
                            <label for="metode_pembayaran" class="text-sm sm:text-base">
                                <i class="fas fa-credit-card mr-2 text-purple-600"></i>Metode Pembayaran
                            </label>
                            
                            <!-- Hidden input for form submission -->
                            <input type="hidden" name="metode_pembayaran" id="metode_pembayaran" required>
                            
                            <!-- Custom Select -->
                            <div class="custom-select-wrapper">
                                <div class="custom-select" id="customSelect">
                                    <div class="custom-select-text">
                                        <span id="selectedText">-- Pilih Metode Pembayaran --</span>
                                    </div>
                                    <i class="fas fa-chevron-down custom-select-arrow"></i>
                                </div>
                                
                                <div class="custom-options" id="customOptions">
                                    <!-- Transfer Bank -->
                                    <div class="custom-option-group">
                                        <div class="custom-option-group-label">🏦 Transfer Bank</div>
                                        <div class="custom-option" data-value="Transfer_Bank">
                                            <i class="fas fa-university custom-option-logo" style="font-size: 24px; color: #667eea;"></i>
                                            <span class="custom-option-text">Transfer Bank</span>
                                        </div>
                                    </div>
                                    
                                    <!-- Virtual Account -->
                                    <div class="custom-option-group">
                                        <div class="custom-option-group-label">💳 Virtual Account</div>
                                        <div class="custom-option" data-value="Virtual_Account">
                                            <i class="fas fa-credit-card custom-option-logo" style="font-size: 24px; color: #667eea;"></i>
                                            <span class="custom-option-text">Virtual Account</span>
                                        </div>
                                    </div>
                                    
                                    <!-- E-Wallet -->
                                    <div class="custom-option-group">
                                        <div class="custom-option-group-label">📱 E-Wallet / Dompet Digital</div>
                                        <div class="custom-option" data-value="OVO">
                                            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/e/eb/Logo_ovo_purple.svg/512px-Logo_ovo_purple.svg.png" alt="OVO" class="custom-option-logo">
                                            <span class="custom-option-text">OVO</span>
                                        </div>
                                        <div class="custom-option" data-value="DANA">
                                            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/7/72/Logo_dana_blue.svg/512px-Logo_dana_blue.svg.png" alt="DANA" class="custom-option-logo">
                                            <span class="custom-option-text">DANA</span>
                                        </div>
                                        <div class="custom-option" data-value="GoPay">
                                            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/8/86/Gopay_logo.svg/512px-Gopay_logo.svg.png" alt="GoPay" class="custom-option-logo">
                                            <span class="custom-option-text">GoPay</span>
                                        </div>
                                        <div class="custom-option" data-value="LinkAja">
                                            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/8/85/LinkAja.svg/512px-LinkAja.svg.png" alt="LinkAja" class="custom-option-logo">
                                            <span class="custom-option-text">LinkAja</span>
                                        </div>
                                    </div>
                                    
                                    <!-- QRIS -->
                                    <div class="custom-option-group">
                                        <div class="custom-option-group-label">📲 QRIS</div>
                                        <div class="custom-option" data-value="QRIS">
                                            <i class="fas fa-qrcode custom-option-logo" style="font-size: 24px; color: #667eea;"></i>
                                            <span class="custom-option-text">Scan QRIS</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>



                        <div class="form-group">
                            <label for="bukti_pembayaran" class="text-sm sm:text-base">
                                <i class="fas fa-cloud-upload-alt mr-2 text-purple-600"></i>Upload Bukti Pembayaran
                            </label>
                            <div class="file-upload">
                                <input type="file" name="bukti_pembayaran" id="bukti_pembayaran" required accept=".jpg,.jpeg,.png,.pdf">
                                <div class="file-upload-label">
                                    <div class="text-center">
                                        <i class="fas fa-cloud-upload-alt text-2xl sm:text-3xl text-gray-400 mb-2"></i>
                                        <div class="text-gray-600 font-medium text-sm">Klik untuk memilih file</div>
                                        <div class="text-xs text-gray-500">atau drag & drop di sini</div>
                                    </div>
                                </div>
                            </div>
                            <p class="text-xs text-gray-500 mt-2 flex items-center">
                                <i class="fas fa-info-circle mr-1"></i>
                                Format: JPG, PNG, PDF. Maks: 2MB.
                            </p>
                        </div>

                        <div class="mt-8">
                            <button type="submit" class="submit-btn">
                                <i class="fas fa-check-circle mr-2"></i>Konfirmasi Pembayaran
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include '_user_profile_modal.php'; ?>

    <script>
        // QR Code untuk QRIS
        let qrCode = null;

        function generateQRCode() {
            const qrcodeDiv = document.getElementById('qrcode');
            const amount = document.querySelector('input[name="jumlah"]').value;
            const formattedAmount = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(amount);

            // Tampilkan Gambar QRIS Statis dari User
            qrcodeDiv.innerHTML = `
                <div class="text-center">
                    <img src="../assets/images/qris_standard.png" alt="QRIS Scan" class="mx-auto border-4 border-gray-800 rounded-xl" style="width: 100%; max-width: 400px; height: auto;">
                    <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <p class="text-sm font-bold text-yellow-800 text-left">
                            <i class="fas fa-exclamation-circle mr-1"></i>PENTING:
                        </p>
                        <p class="text-xs text-yellow-800 text-left mt-1">
                            QRIS ini bersifat statis. Mohon input nominal sebesar <br>
                            <span class="text-lg font-black text-red-600">${formattedAmount}</span> <br>
                            secara MANUAL di aplikasi pembayaran Anda.
                        </p>
                    </div>
                </div>
            `;
        }
        
        // Fallback function tidak diperlukan lagi karena pakai gambar
        function generateQRCodeFallback() {}

        // Update payment info based on selected method
        function updatePaymentInfo() {
            const method = document.getElementById('metode_pembayaran').value;
            
            // Hide all payment method infos
            const bankTransferInfo = document.getElementById('bankTransferInfo');
            const vaInfo = document.getElementById('vaInfo');
            const eWalletInfo = document.getElementById('eWalletInfo');
            const qrisInfo = document.getElementById('qrisInfo');
            
            if (bankTransferInfo) bankTransferInfo.classList.add('hidden');
            if (vaInfo) vaInfo.classList.add('hidden');
            if (eWalletInfo) eWalletInfo.classList.add('hidden');
            if (qrisInfo) qrisInfo.classList.add('hidden');
            
            // Show selected method info
            if (method === 'Transfer_Bank') {
                if (bankTransferInfo) bankTransferInfo.classList.remove('hidden');
            } else if (method === 'Virtual_Account') {
                if (vaInfo) vaInfo.classList.remove('hidden');
            } else if (['OVO', 'DANA', 'GoPay', 'LinkAja'].includes(method)) {
                if (eWalletInfo) eWalletInfo.classList.remove('hidden');
            } else if (method === 'QRIS') {
                if (qrisInfo) qrisInfo.classList.remove('hidden');
                // Generate QR code ketika QRIS dipilih
                setTimeout(generateQRCode, 100);
            }
        }

        function showNotifications() {
            const paymentSuccessMessage = <?php echo isset($_SESSION['payment_success']) ? json_encode($_SESSION['payment_success']) : 'null'; ?>;
            const notifCount = <?php echo isset($_SESSION['notif_count']) ? (int)$_SESSION['notif_count'] : 0; ?>;

            if (paymentSuccessMessage) {
                Swal.fire({
                    title: 'Pembayaran Berhasil!',
                    text: paymentSuccessMessage,
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then(() => {
                    // Hapus pesan spesifik dan reset notifikasi umum
                    fetch('reset_notifications.php', { method: 'POST', body: new URLSearchParams({ specific: 'payment_success' }) })
                        .then(() => { location.reload(); }); // Reload untuk membersihkan state
                });
            } else if (notifCount > 0) {
                Swal.fire({
                    title: 'Notifikasi Baru',
                    text: 'Anda memiliki ' + notifCount + ' notifikasi belum dibaca.',
                    icon: 'info',
                    confirmButtonText: 'OK'
                }).then(() => {
                    fetch('reset_notifications.php', { method: 'POST' }).then(() => document.getElementById('notifBadge')?.remove());
                });
            } else {
                Swal.fire('Notifikasi', 'Tidak ada notifikasi baru.', 'info');
            }
        }

        function confirmLogout(event) {
            event.preventDefault();
            Swal.fire({
                title: 'Konfirmasi Logout',
                text: 'Apakah Anda yakin ingin logout?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Logout',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '../auth/logout.php';
                }
            });
        }

        // Custom Select Dropdown Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const customSelect = document.getElementById('customSelect');
            const customOptions = document.getElementById('customOptions');
            const selectedText = document.getElementById('selectedText');
            const hiddenInput = document.getElementById('metode_pembayaran');
            const options = document.querySelectorAll('.custom-option');

            // Toggle dropdown
            customSelect.addEventListener('click', function(e) {
                e.stopPropagation();
                customSelect.classList.toggle('active');
                customOptions.classList.toggle('show');
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function() {
                customSelect.classList.remove('active');
                customOptions.classList.remove('show');
            });

            // Handle option selection
            options.forEach(option => {
                option.addEventListener('click', function(e) {
                    e.stopPropagation();
                    
                    // Remove selected class from all options
                    options.forEach(opt => opt.classList.remove('selected'));
                    
                    // Add selected class to clicked option
                    this.classList.add('selected');
                    
                    // Get value and text
                    const value = this.getAttribute('data-value');
                    const text = this.querySelector('.custom-option-text').textContent;
                    const logo = this.querySelector('.custom-option-logo').cloneNode(true);
                    
                    // Update hidden input
                    hiddenInput.value = value;
                    
                    // Update selected text with logo
                    selectedText.innerHTML = '';
                    selectedText.appendChild(logo.cloneNode(true));
                    const textSpan = document.createElement('span');
                    textSpan.textContent = text;
                    textSpan.style.marginLeft = '10px';
                    selectedText.appendChild(textSpan);
                    
                    // Close dropdown
                    customSelect.classList.remove('active');
                    customOptions.classList.remove('show');
                    
                    // Trigger payment info update
                    updatePaymentInfo(value);
                });
            });
        });

        // Update payment info based on selected method
        function updatePaymentInfo(method) {
            const methodValue = method || document.getElementById('metode_pembayaran').value;
            
            // Hide all payment method info sections
            document.getElementById('bankTransferInfo').classList.add('hidden');
            document.getElementById('vaInfo').classList.add('hidden');
            document.getElementById('eWalletInfo').classList.add('hidden');
            document.getElementById('qrisInfo').classList.add('hidden');

            // Show relevant payment method info
            if (methodValue === 'Transfer_Bank') {
                document.getElementById('bankTransferInfo').classList.remove('hidden');
            } else if (methodValue === 'Virtual_Account') {
                document.getElementById('vaInfo').classList.remove('hidden');
            } else if (['OVO', 'DANA', 'GoPay', 'LinkAja'].includes(methodValue)) {
                document.getElementById('eWalletInfo').classList.remove('hidden');
            } else if (methodValue === 'QRIS') {
                document.getElementById('qrisInfo').classList.remove('hidden');
                generateQRCode();
            }
        }

        // File upload preview and drag & drop functionality
        document.addEventListener('DOMContentLoaded', function() {
            const fileInput = document.getElementById('bukti_pembayaran');
            const fileUploadLabel = document.querySelector('.file-upload-label');
            const originalText = fileUploadLabel.innerHTML;

            // Drag and drop functionality
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                fileUploadLabel.addEventListener(eventName, preventDefaults, false);
            });

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            ['dragenter', 'dragover'].forEach(eventName => {
                fileUploadLabel.addEventListener(eventName, highlight, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                fileUploadLabel.addEventListener(eventName, unhighlight, false);
            });

            function highlight(e) {
                fileUploadLabel.classList.add('bg-purple-50', 'border-purple-300');
                fileUploadLabel.classList.remove('border-gray-300');
            }

            function unhighlight(e) {
                fileUploadLabel.classList.remove('bg-purple-50', 'border-purple-300');
                fileUploadLabel.classList.add('border-gray-300');
            }

            fileUploadLabel.addEventListener('drop', handleDrop, false);

            function handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;
                fileInput.files = files;
                handleFileSelect(files[0]);
            }

            fileInput.addEventListener('change', function(e) {
                handleFileSelect(e.target.files[0]);
            });

            function handleFileSelect(file) {
                if (file) {
                    const fileSize = (file.size / 1024 / 1024).toFixed(2);
                    const fileName = file.name.length > 20 ? file.name.substring(0, 17) + '...' : file.name;
                    
                    fileUploadLabel.innerHTML = `
                        <div class="text-center">
                            <i class="fas fa-file-alt text-3xl text-green-500 mb-2"></i>
                            <div class="text-gray-800 font-medium">${fileName}</div>
                            <div class="text-sm text-gray-500">${fileSize} MB</div>
                        </div>
                    `;
                } else {
                    fileUploadLabel.innerHTML = originalText;
                }
            }

            // Form validation
            document.getElementById('paymentForm').addEventListener('submit', function(e) {
                const method = document.getElementById('metode_pembayaran').value;
                const file = document.getElementById('bukti_pembayaran').files[0];

                if (!method) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Pilih Metode Pembayaran',
                        text: 'Silakan pilih bank tujuan transfer terlebih dahulu.',
                        icon: 'warning',
                        confirmButtonText: 'OK'
                    });
                    return;
                }

                if (!file) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Upload Bukti Pembayaran',
                        text: 'Silakan upload bukti pembayaran Anda.',
                        icon: 'warning',
                        confirmButtonText: 'OK'
                    });
                    return;
                }

                // Show loading state
                const submitBtn = this.querySelector('.submit-btn');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Mengirim...';
                submitBtn.disabled = true;

                // Re-enable after 10 seconds as fallback
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }, 10000);
            });

            // Mobile Menu Script
            let mobileMenuActive = false;

            document.addEventListener('DOMContentLoaded', function() {
                const notifBtn = document.getElementById('mobileNotifBtn');
                const logoutBtn = document.getElementById('mobileLogoutBtn');
                
                if (notifBtn) {
                    notifBtn.addEventListener('mousedown', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        closeMobileMenu();
                        setTimeout(() => {
                            if (typeof showNotifications === 'function') {
                                showNotifications();
                            }
                        }, 100);
                    });
                }
                
                if (logoutBtn) {
                    logoutBtn.addEventListener('mousedown', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        closeMobileMenu();
                        setTimeout(() => {
                            if (typeof confirmLogout === 'function') {
                                confirmLogout(e);
                            }
                        }, 100);
                    });
                }

                const drawer = document.getElementById('mobileMenuDrawer');
                const backdrop = document.getElementById('mobileMenuBackdrop');
                
                if (drawer && backdrop) {
                    backdrop.addEventListener('click', function() {
                        if (mobileMenuActive) {
                            toggleMobileMenu();
                        }
                    });
                }
            });

            function toggleMobileMenu() {
                const drawer = document.getElementById('mobileMenuDrawer');
                const panel = document.getElementById('mobileMenuPanel');
                const backdrop = document.getElementById('mobileMenuBackdrop');
                
                if (!drawer || !panel || !backdrop) return;

                if (mobileMenuActive) {
                    panel.style.transform = 'translateX(100%)';
                    backdrop.style.opacity = '0';
                    drawer.style.pointerEvents = 'none';
                    setTimeout(() => {
                        mobileMenuActive = false;
                    }, 300);
                } else {
                    mobileMenuActive = true;
                    drawer.style.pointerEvents = 'auto';
                    panel.style.transform = 'translateX(0)';
                    backdrop.style.opacity = '1';
                }
            }

            function closeMobileMenu() {
                const drawer = document.getElementById('mobileMenuDrawer');
                const panel = document.getElementById('mobileMenuPanel');
                const backdrop = document.getElementById('mobileMenuBackdrop');
                
                if (!drawer || !panel || !backdrop) return;

                if (mobileMenuActive) {
                    panel.style.transform = 'translateX(100%)';
                    backdrop.style.opacity = '0';
                    drawer.style.pointerEvents = 'none';
                    setTimeout(() => {
                        mobileMenuActive = false;
                    }, 300);
                }
            }

            function handleMobileMenuClick(event) {
                if (mobileMenuActive) {
                    closeMobileMenu();
                }
            }
        });
    </script>
</body>
</html>