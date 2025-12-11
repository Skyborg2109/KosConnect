<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || !in_array($_SESSION['role'], ['penyewa', 'admin'])) {
    header("Location: ../auth/loginForm.php");
    exit();
}

include '../config/db.php';
include '../config/SessionChecker.php';
include '../config/SessionManager.php';

// Validate multi-device session
if (!checkMultiDeviceSession($conn)) {
    session_destroy();
    header("Location: ../auth/loginForm.php");
    exit();
}

// Data User dari Sesi
$userName = htmlspecialchars($_SESSION['fullname'] ?? 'Penyewa Kos');
$userRole = htmlspecialchars($_SESSION['role'] ?? 'penyewa');
$id_penyewa = $_SESSION['user_id'];
$userEmail = $_SESSION['email'] ?? '';
$userPhoto = $_SESSION['foto_profil'] ?? null;

// Notification count (optional)
// Ambil jumlah notifikasi yang belum dibaca dari database
$stmt_notif = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE id_user = ? AND is_read = 0");
$stmt_notif->bind_param("i", $id_penyewa);
$stmt_notif->execute();
$notif_count = $stmt_notif->get_result()->fetch_assoc()['count'];
$stmt_notif->close();


// Statistik Dashboard
$total_booking_aktif = 0;
$total_complaint_diajukan = 0;
$total_review_diberikan = 0;

$stmt_stats = $conn->prepare("
    SELECT 
        (SELECT COUNT(id_booking) FROM booking WHERE id_penyewa = ? AND status IN ('pending', 'dibayar')) as total_booking,
        (SELECT COUNT(id_complaint) FROM complaint WHERE id_penyewa = ?) as total_complaint,
        (SELECT COUNT(id_feedback) FROM feedback WHERE id_penyewa = ?) as total_review
");
$stmt_stats->bind_param("iii", $id_penyewa, $id_penyewa, $id_penyewa);
$stmt_stats->execute();
$stats_result = $stmt_stats->get_result()->fetch_assoc();
if ($stats_result) {
    $total_booking_aktif = $stats_result['total_booking'];
    $total_complaint_diajukan = $stats_result['total_complaint'];
    $total_review_diberikan = $stats_result['total_review'];
}
$stmt_stats->close();

// Booking Terbaru
$stmt_latest_booking = $conn->prepare("
    SELECT b.tanggal_booking, b.status, k.nama_kamar, t.nama_kost
    FROM booking b
    INNER JOIN kamar k ON b.id_kamar = k.id_kamar
    INNER JOIN kost t ON k.id_kost = t.id_kost
    WHERE b.id_penyewa = ?
    ORDER BY b.tanggal_booking DESC
    LIMIT 1
");
$stmt_latest_booking->bind_param("i", $id_penyewa);
$stmt_latest_booking->execute();
$latest_booking = $stmt_latest_booking->get_result()->fetch_assoc();
$stmt_latest_booking->close();

// --- Logika Riwayat Booking (dipindahkan dari booking.php) ---
$booking_history = [
    'aktif' => [],
    'menunggu_proses' => [],
    'selesai' => [],
    'batal' => [],
    'ditolak' => [],
    'menunggu_pembayaran' => []
];

$stmt_history = $conn->prepare("
    SELECT 
        b.id_booking, b.tanggal_booking, b.status,
        k.nama_kamar, k.harga,
        t.nama_kost, t.alamat
    FROM booking b
    INNER JOIN kamar k ON b.id_kamar = k.id_kamar
    INNER JOIN kost t ON k.id_kost = t.id_kost
    WHERE b.id_penyewa = ?
    ORDER BY b.tanggal_booking DESC
");
$stmt_history->bind_param("i", $id_penyewa);
$stmt_history->execute();
$result_history = $stmt_history->get_result();

while ($row = $result_history->fetch_assoc()) {
    // Kelompokkan status 'dibayar' ke dalam 'aktif'
    if ($row['status'] === 'dibayar') {
        $booking_history['aktif'][] = $row;
    } elseif ($row['status'] === 'pending') {
        // Kelompokkan 'pending' ke dalam 'menunggu_proses'
        $booking_history['menunggu_proses'][] = $row;
    } elseif (array_key_exists($row['status'], $booking_history)) {
        // Untuk status lainnya, gunakan key yang sudah ada
        $booking_history[$row['status']][] = $row;
    }
}
$stmt_history->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - KosConnect</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideUp {
            from { 
                opacity: 0;
                transform: translateY(20px);
            }
            to { 
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        @keyframes shimmer {
            0% { background-position: -1000px 0; }
            100% { background-position: 1000px 0; }
        }
        
        @keyframes fadeOut {
            from { opacity: 1; transform: scale(1); }
            to { opacity: 0; transform: scale(0.8); }
        }
        
        /* User Info Box */
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
        
        /* Mobile Navigation Drawer */
        #mobileMenuDrawer {
            pointer-events: none;
        }
        
        #mobileMenuDrawer.active {
            pointer-events: auto;
        }
        
        #mobileMenuPanel {
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            will-change: transform;
        }
        
        #mobileMenuBackdrop {
            transition: opacity 0.3s ease;
        }
        
        /* Card Hover */
        .card-hover {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .card-hover::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.6s;
        }
        
        .card-hover:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px rgba(139, 92, 246, 0.15);
        }
        
        .card-hover:hover::before {
            left: 100%;
        }
        
        /* Status Badges */
        .status-badge { 
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem; 
            border-radius: 9999px; 
            font-size: 0.75rem; 
            font-weight: 700; 
            text-transform: uppercase;
            letter-spacing: 0.05em;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .status-badge i {
            margin-right: 0.5rem;
        }
        
        .status-dibayar { 
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
        }
        
        .status-pending { 
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
        }
        
        .status-menunggu_pembayaran { 
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1e40af;
        }
        
        .status-selesai { 
            background: linear-gradient(135deg, #e5e7eb 0%, #d1d5db 100%);
            color: #374151;
        }
        
        .status-ditolak { 
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
        }
        
        .status-batal { 
            background: linear-gradient(135deg, #ffedd5 0%, #fed7aa 100%);
            color: #9a3412;
        }

        /* Gradient Background */
        .gradient-bg {
            background: linear-gradient(135deg, #8B5CF6 0%, #6366F1 100%);
            position: relative;
            overflow: hidden;
        }
        
        .gradient-bg::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: pulse 15s ease-in-out infinite;
        }
        
        /* Navigation */
        nav {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95) !important;
            transition: all 0.3s ease;
        }
        
        nav a {
            position: relative;
            transition: all 0.3s ease;
        }
        
        nav a::after {
            content: '';
            position: absolute;
            bottom: -4px;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(90deg, #8B5CF6, #6366F1);
            transition: width 0.3s ease;
        }
        
        nav a:hover::after,
        nav a.text-purple-600::after {
            width: 100%;
        }
        
        /* Buttons */
        button, .btn-action {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        button:hover:not(:disabled), .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }
        
        /* Notification Badge */
        #notifBadge {
            animation: pulse 2s infinite;
        }
        
        /* Booking Card */
        .booking-card {
            transition: all 0.3s ease;
            border-left-width: 4px;
        }
        
        .booking-card:hover {
            transform: translateX(4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
        }
        
        /* Section Headers */
        .section-header {
            position: relative;
            padding-bottom: 1rem;
        }
        
        .section-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 4px;
            background: linear-gradient(90deg, #8B5CF6, #6366F1);
            border-radius: 2px;
        }
        
        /* Stats Card Icon */
        .stat-icon {
            transition: all 0.3s ease;
        }
        
        .card-hover:hover .stat-icon {
            transform: scale(1.2) rotate(5deg);
        }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 10px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #8B5CF6, #6366F1);
            border-radius: 5px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #7C3AED, #4F46E5);
        }
        
        /* Loading Animation */
        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 1000px 100%;
            animation: shimmer 2s infinite;
        }
        
        /* Page Animation */
        main {
            animation: fadeIn 0.6s ease-out;
        }

        /* Mobile Responsive Design */
        @media (max-width: 768px) {
            /* Navigation */
            nav {
                height: auto;
                padding: 0.5rem 0;
            }

            .text-2xl {
                font-size: 1.25rem !important;
            }

            /* Welcome Banner */
            .relative.overflow-hidden.bg-gradient-to-br {
                padding: 1.5rem !important;
                margin-bottom: 1rem !important;
                border-radius: 1rem !important;
            }

            .relative.overflow-hidden.bg-gradient-to-br h1 {
                font-size: 1.5rem !important;
                line-height: 1.8 !important;
            }

            .relative.overflow-hidden.bg-gradient-to-br p {
                font-size: 0.9rem !important;
                line-height: 1.4 !important;
            }

            /* Buttons in Banner */
            .flex.flex-col.sm\:flex-row.gap-3 button {
                padding: 0.75rem 1rem !important;
                font-size: 0.875rem !important;
                width: 100% !important;
                margin-bottom: 0.5rem !important;
            }

            /* Stats Cards */
            .grid.grid-cols-1.md\:grid-cols-3 {
                grid-template-columns: 1fr !important;
                gap: 1rem !important;
            }

            .card-hover {
                padding: 1.25rem !important;
                gap: 1.5rem !important;
                border-radius: 0.75rem !important;
            }

            .card-hover p {
                font-size: 1.5rem !important;
                margin: 0 !important;
            }

            .card-hover .stat-icon {
                width: 3.5rem !important;
                height: 3.5rem !important;
                min-width: 3.5rem !important;
                font-size: 1.75rem !important;
            }

            .card-hover .stat-icon i {
                font-size: 1.5rem !important;
            }

            /* Section Headers */
            .section-header {
                padding-bottom: 0.75rem !important;
                margin-bottom: 1rem !important;
            }

            .section-header h2 {
                font-size: 1.25rem !important;
                font-weight: 700 !important;
            }

            /* Booking Terbaru Section */
            .bg-white.p-6.rounded-xl.shadow-md.mb-8 {
                padding: 1.25rem !important;
                border-radius: 0.75rem !important;
                margin-bottom: 1rem !important;
            }

            .grid.grid-cols-1.md\:grid-cols-4 {
                grid-template-columns: 1fr !important;
                gap: 1rem !important;
            }

            .md\:col-span-2 {
                grid-column: span 1 !important;
            }

            .bg-gray-50.p-4.rounded-xl {
                padding: 1rem !important;
                border-radius: 0.5rem !important;
            }

            /* Booking Cards */
            .booking-card {
                padding: 1rem !important;
                margin-bottom: 0.75rem !important;
                border-radius: 0.75rem !important;
            }

            .booking-card h4 {
                font-size: 1rem !important;
                margin-bottom: 0.5rem !important;
            }

            .booking-card p {
                font-size: 0.875rem !important;
                line-height: 1.4 !important;
                margin-bottom: 0.25rem !important;
            }

            .status-badge {
                padding: 0.375rem 0.75rem !important;
                font-size: 0.7rem !important;
            }

            /* Tabs/Nav Items */
            .nav-tabs button,
            .nav-tabs a {
                padding: 0.75rem !important;
                font-size: 0.875rem !important;
                margin-right: 0.5rem !important;
            }

            /* Modal/Dialog */
            #profileModal {
                max-width: 95vw !important;
            }

            .modal-content {
                padding: 1.5rem !important;
                border-radius: 1rem !important;
            }

            /* Text Sizes */
            .text-gray-600 {
                font-size: 0.9rem !important;
            }

            .text-gray-500 {
                font-size: 0.85rem !important;
            }

            .text-sm {
                font-size: 0.8rem !important;
            }

            .text-xs {
                font-size: 0.75rem !important;
            }

            /* Spacing */
            .pt-24 {
                padding-top: 5rem !important;
            }

            .pb-12 {
                padding-bottom: 2rem !important;
            }

            .px-4 {
                padding-left: 1rem !important;
                padding-right: 1rem !important;
            }

            .px-6 {
                padding-left: 1rem !important;
                padding-right: 1rem !important;
            }

            .mx-auto {
                margin-left: auto !important;
                margin-right: auto !important;
            }

            /* Max Width */
            .max-w-7xl {
                max-width: 100% !important;
                padding: 0 0.5rem !important;
            }

            .max-w-2xl {
                max-width: 100% !important;
            }

            /* Flex Direction */
            .flex.flex-col.sm\:flex-row {
                flex-direction: column !important;
            }

            .flex.gap-6.sm\:gap-8 {
                gap: 1rem !important;
            }

            /* Table/List responsiveness */
            table {
                font-size: 0.85rem !important;
            }

            table th,
            table td {
                padding: 0.5rem !important;
            }

            /* User Info Box */
            .user-info-box {
                padding: 0.5rem 0.75rem !important;
            }

            .user-info-box .text-sm {
                display: none !important;
            }

            /* Mobile Menu */
            #mobileMenuPanel {
                width: 80vw !important;
                max-width: 320px !important;
            }

            /* Form elements */
            input, 
            textarea,
            select {
                font-size: 1rem !important;
                padding: 0.75rem !important;
                border-radius: 0.5rem !important;
            }

            /* Alerts */
            .bg-green-100,
            .bg-red-100,
            .bg-blue-100 {
                padding: 0.75rem !important;
                border-radius: 0.5rem !important;
                margin-bottom: 1rem !important;
            }

            .bg-green-100 i,
            .bg-red-100 i,
            .bg-blue-100 i {
                margin-right: 0.5rem !important;
            }

            .bg-green-100 p,
            .bg-red-100 p,
            .bg-blue-100 p {
                font-size: 0.9rem !important;
                line-height: 1.4 !important;
            }

            /* Buttons */
            button, .btn-action {
                padding: 0.75rem 1rem !important;
                font-size: 0.875rem !important;
                border-radius: 0.5rem !important;
            }

            /* Tab Navigation */
            .tab-nav {
                overflow-x: auto !important;
                -webkit-overflow-scrolling: touch !important;
            }

            .tab-nav button {
                white-space: nowrap !important;
                padding: 0.5rem 1rem !important;
            }

            /* Profile modal improvements */
            .modal-overlay {
                padding: 1rem !important;
            }

            /* Hero section */
            .gradient-bg {
                padding: 1.5rem 1rem !important;
                min-height: auto !important;
            }

            .gradient-bg h1 {
                font-size: 1.5rem !important;
            }

            .gradient-bg p {
                font-size: 0.9rem !important;
            }

            /* Responsive Images */
            img {
                max-width: 100% !important;
                height: auto !important;
            }

            /* Card Grid */
            .grid {
                gap: 0.75rem !important;
            }

            .gap-6 {
                gap: 0.75rem !important;
            }

            .gap-8 {
                gap: 1rem !important;
            }

            /* Overflow handling */
            .overflow-x-auto {
                -webkit-overflow-scrolling: touch !important;
            }
        }

        /* Extra small devices (< 640px) */
        @media (max-width: 640px) {
            .text-3xl {
                font-size: 1.25rem !important;
            }

            .text-2xl {
                font-size: 1.1rem !important;
            }

            .text-xl {
                font-size: 1rem !important;
            }

            .px-4 {
                padding-left: 0.75rem !important;
                padding-right: 0.75rem !important;
            }

            .pt-24 {
                padding-top: 4.5rem !important;
            }

            .relative.overflow-hidden.bg-gradient-to-br {
                padding: 1rem !important;
                margin-bottom: 0.75rem !important;
            }

            .relative.overflow-hidden.bg-gradient-to-br h1 {
                font-size: 1.25rem !important;
            }

            .card-hover .stat-icon {
                width: 2.5rem !important;
                height: 2.5rem !important;
            }

            .card-hover p {
                font-size: 1.5rem !important;
            }
        }
    </style>
</head>
<body class="bg-gray-50 font-sans">

    <nav class="bg-white shadow-lg fixed w-full top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <a href="../dashboard/dashboarduser.php" class="flex items-center group">
                    <div class="w-10 h-10 bg-gradient-to-br from-purple-600 to-indigo-600 rounded-lg flex items-center justify-center mr-2 group-hover:scale-110 transition-transform">
                        <i class="fas fa-home text-white"></i>
                    </div>
                    <h1 class="text-2xl font-bold bg-gradient-to-r from-purple-600 to-indigo-600 bg-clip-text text-transparent">Kos<span class="text-purple-600">Connect</span></h1>
                </a>
                <div class="hidden md:flex items-center space-x-6">
                    <nav class="flex space-x-8">
                        <a href="../dashboard/dashboarduser.php" class="text-gray-700 font-medium hover:text-purple-600 py-2">Beranda</a>
                        <a href="user_dashboard.php" class="text-purple-600 font-semibold hover:text-purple-700 py-2">Dashboard</a>
                        <a href="../dashboard/dashboarduser.php#pilihan-kos" class="text-gray-700 font-medium hover:text-purple-600 py-2">Pilihan Kos</a>
                        <a href="wishlist.php" class="text-gray-700 font-medium hover:text-purple-600 py-2">Favorit</a>
                        <a href="feedback.php" class="text-gray-700 font-medium hover:text-purple-600 py-2">Feedback</a>
                        <a href="../dashboard/dashboarduser.php#kontak" class="text-gray-700 font-medium hover:text-purple-600 py-2 transition-colors">Kontak</a>
                    </nav>
                    <div class="flex items-center space-x-4 pl-6 border-l-2 border-gray-200">
                        <button id="notifBtn" onclick="showNotifications()" class="relative text-gray-600 hover:text-purple-600 p-2 rounded-lg hover:bg-purple-50 transition-all" aria-label="Notifikasi" title="Notifikasi">
                            <i class="fas fa-bell text-xl" aria-hidden="true"></i>
                            <?php if (!empty($notif_count) && $notif_count > 0): ?>
                                <span id="notifBadge" class="absolute top-0 right-0 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center shadow-lg" aria-live="polite"><?php echo ($notif_count > 99) ? '99+' : $notif_count; ?></span>
                            <?php endif; ?>
                        </button>
                        <button onclick="showProfileModal()" class="user-info-box">
                            <?php if ($userPhoto): ?>
                                <img id="headerUserPhoto" src="../uploads/profiles/<?php echo htmlspecialchars($userPhoto); ?>" alt="Foto Profil" class="w-9 h-9 rounded-full object-cover ring-2 ring-purple-200">
                            <?php else: ?>
                                <div id="headerUserPhoto" class="w-9 h-9 rounded-full bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center text-white font-bold shadow-md">
                                    <?php echo strtoupper(substr($userName, 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                            <div class="text-sm text-gray-800 ml-3">
                                <span class="block font-semibold"><?php echo $userName; ?></span>
                                <span class="block text-xs text-gray-500"><?php echo htmlspecialchars(ucfirst($userRole)); ?></span>
                            </div>
                            <i class="fas fa-chevron-down text-gray-400 ml-2 text-xs"></i>
                        </button>
                        <a href="../auth/logout.php" onclick="confirmLogout(event)" class="bg-gradient-to-r from-red-500 to-red-600 text-white px-4 py-2 rounded-lg font-medium text-sm hover:from-red-600 hover:to-red-700 shadow-md hover:shadow-lg cursor-pointer">
                            <i class="fas fa-sign-out-alt mr-1"></i>Logout
                        </a>
                    </div>
                </div>
                
                <!-- Mobile Menu Button di Navbar -->
                <div class="flex md:hidden items-center space-x-2">
                    <button id="notifBtn" onclick="showNotifications()" class="relative text-gray-600 hover:text-purple-600 p-2 rounded-lg hover:bg-purple-50 transition-all" aria-label="Notifikasi" title="Notifikasi">
                        <i class="fas fa-bell text-xl" aria-hidden="true"></i>
                        <?php if (!empty($notif_count) && $notif_count > 0): ?>
                            <span id="notifBadge" class="absolute top-0 right-0 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center shadow-lg" aria-live="polite"><?php echo ($notif_count > 99) ? '99+' : $notif_count; ?></span>
                        <?php endif; ?>
                    </button>
                    <button id="mobileMenuBtn" class="p-2 text-gray-600 hover:text-purple-600 hover:bg-purple-50 rounded-lg transition-colors" onclick="toggleMobileMenu()" title="Menu">
                        <i class="fas fa-bars text-xl"></i>
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
                <a href="../dashboard/dashboarduser.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-purple-50 hover:text-purple-600 rounded-lg font-medium transition-colors mobile-menu-link" onclick="handleMobileMenuClick(event)">
                    <i class="fas fa-home mr-3 text-purple-600"></i>Beranda
                </a>
                <a href="user_dashboard.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-purple-50 hover:text-purple-600 rounded-lg font-medium transition-colors mobile-menu-link" onclick="handleMobileMenuClick(event)">
                    <i class="fas fa-chart-line mr-3 text-blue-600"></i>Dashboard
                </a>
                <a href="../dashboard/dashboarduser.php#pilihan-kos" class="flex items-center px-4 py-3 text-gray-700 hover:bg-purple-50 hover:text-purple-600 rounded-lg font-medium transition-colors mobile-menu-link" onclick="handleMobileMenuClick(event)">
                    <i class="fas fa-building mr-3 text-orange-600"></i>Pilihan Kos
                </a>
                <a href="wishlist.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-purple-50 hover:text-purple-600 rounded-lg font-medium transition-colors mobile-menu-link" onclick="handleMobileMenuClick(event)">
                    <i class="fas fa-heart mr-3 text-red-600"></i>Favorit
                </a>
                <a href="feedback.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-purple-50 hover:text-purple-600 rounded-lg font-medium transition-colors mobile-menu-link" onclick="handleMobileMenuClick(event)">
                    <i class="fas fa-comment mr-3 text-green-600"></i>Feedback
                </a>
                <a href="../dashboard/dashboarduser.php#kontak" class="flex items-center px-4 py-3 text-gray-700 hover:bg-purple-50 hover:text-purple-600 rounded-lg font-medium transition-colors mobile-menu-link" onclick="handleMobileMenuClick(event)">
                    <i class="fas fa-phone mr-3 text-cyan-600"></i>Kontak
                </a>
            </nav>
            
            <!-- Divider -->
            <div class="border-t border-gray-100 my-4"></div>
            
            <!-- User Actions -->
            <div class="p-4 space-y-3 relative z-50">
                <button id="mobileProfileBtn" class="w-full px-4 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-lg font-medium hover:from-purple-700 hover:to-indigo-700 transition-all flex items-center justify-center cursor-pointer" type="button" data-action="profile">
                    <i class="fas fa-user mr-2"></i>Profil
                </button>
                <button id="mobileNotifBtn" class="w-full px-4 py-3 bg-blue-500 text-white rounded-lg font-medium hover:bg-blue-600 transition-all flex items-center justify-center cursor-pointer" type="button" data-action="notif">
                    <i class="fas fa-bell mr-2"></i>Notifikasi
                </button>
                <button id="mobileLogoutBtn" class="w-full px-4 py-3 bg-red-500 text-white rounded-lg font-medium hover:bg-red-600 transition-all text-center flex items-center justify-center cursor-pointer" type="button" data-action="logout">
                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                </button>
            </div>
        </div>
    </div>

    <main class="pt-24 pb-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Welcome Banner -->
            <div class="relative overflow-hidden bg-gradient-to-br from-purple-600 via-indigo-600 to-purple-700 text-white rounded-2xl shadow-lg p-8 mb-6">
                <!-- Decorative Elements -->
                <div class="absolute top-0 right-0 -mt-4 -mr-4 w-40 h-40 bg-white opacity-5 rounded-full blur-3xl"></div>
                <div class="absolute bottom-0 left-0 -mb-8 -ml-8 w-32 h-32 bg-indigo-400 opacity-10 rounded-full blur-2xl"></div>
                
                <div class="relative flex flex-col gap-6">
                    <div class="space-y-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-12 h-12 bg-white bg-opacity-20 backdrop-blur-sm rounded-full flex items-center justify-center animate-pulse flex-shrink-0">
                                <i class="fas fa-hand-sparkles text-2xl"></i>
                            </div>
                            <h1 class="text-2xl sm:text-3xl font-bold break-words">Selamat Datang, <?php echo htmlspecialchars(explode(' ', $userName)[0]); ?>!</h1>
                        </div>
                        <p class="text-purple-100 text-base">Berikut adalah ringkasan aktivitas Anda di KosConnect</p>
                        <div class="flex flex-col sm:flex-row gap-4 sm:gap-6 pt-2">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-calendar-check text-purple-200"></i>
                                <span class="text-sm text-purple-100"><?php echo $total_booking_aktif; ?> Booking Aktif</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-star text-purple-200"></i>
                                <span class="text-sm text-purple-100"><?php echo $total_review_diberikan; ?> Review</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <button onclick="window.location.href='../dashboard/dashboarduser.php#pilihan-kos'" class="bg-white text-purple-600 px-6 py-3 rounded-lg font-semibold hover:bg-purple-50 shadow-lg hover:shadow-lg transition-all hover:scale-105 flex items-center justify-center group">
                            <i class="fas fa-search mr-2 group-hover:rotate-12 transition-transform"></i>Cari Kos
                        </button>
                        <button onclick="showProfileModal()" class="bg-purple-500 bg-opacity-30 backdrop-blur-sm border-2 border-white border-opacity-30 text-white px-6 py-3 rounded-lg font-semibold hover:bg-opacity-40 shadow-lg transition-all hover:scale-105 flex items-center justify-center group">
                            <i class="fas fa-user-circle mr-2 group-hover:rotate-12 transition-transform"></i>Profil Saya
                        </button>
                    </div>
                </div>
            </div>

            <!-- Payment Success Notification -->
            <?php if (isset($_SESSION['payment_success'])): ?>
                <div id="paymentNotification" class="bg-green-100 border-l-4 border-green-500 text-green-800 p-4 rounded-md mb-8" role="alert">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-500 mr-3"></i>
                        <div>
                            <p class="font-semibold">Pembayaran Berhasil!</p>
                            <p><?php echo htmlspecialchars($_SESSION['payment_success']); ?></p>
                        </div>
                    </div>
                </div>
                <?php unset($_SESSION['payment_success']); ?>
            <?php endif; ?>

            <!-- Kartu Statistik -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <a href="#riwayat" class="block group">
                    <div class="card-hover bg-white p-6 rounded-xl shadow-md border border-gray-100 hover:border-blue-200 flex items-center space-x-6 h-full relative overflow-hidden hover:shadow-lg transition-all">
                        <div class="absolute top-0 right-0 w-24 h-24 bg-blue-50 rounded-full -mr-8 -mt-8 opacity-50 group-hover:scale-150 transition-transform duration-500"></div>
                        <div class="stat-icon w-16 h-16 bg-gradient-to-br from-blue-400 to-blue-600 rounded-2xl flex items-center justify-center shadow-lg flex-shrink-0">
                            <i class="fas fa-calendar-check text-2xl text-white"></i>
                        </div>
                        <div class="relative z-10">
                            <p class="text-4xl font-bold text-gray-800 mb-1"><?php echo $total_booking_aktif; ?></p>
                            <p class="text-gray-600 font-medium">Booking Aktif</p>
                        </div>
                    </div>
                </a>
                <a href="complaint.php" class="block group">
                    <div class="card-hover bg-white p-6 rounded-xl shadow-md border border-gray-100 hover:border-red-200 flex items-center space-x-6 h-full relative overflow-hidden hover:shadow-lg transition-all">
                        <div class="absolute top-0 right-0 w-24 h-24 bg-red-50 rounded-full -mr-8 -mt-8 opacity-50 group-hover:scale-150 transition-transform duration-500"></div>
                        <div class="stat-icon w-16 h-16 bg-gradient-to-br from-red-400 to-red-600 rounded-2xl flex items-center justify-center shadow-lg flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-2xl text-white"></i>
                        </div>
                        <div class="relative z-10">
                            <p class="text-4xl font-bold text-gray-800 mb-1"><?php echo $total_complaint_diajukan; ?></p>
                            <p class="text-gray-600 font-medium">Keluhan Diajukan</p>
                        </div>
                    </div>
                </a>
                <a href="feedback.php" class="block group">
                    <div class="card-hover bg-white p-6 rounded-xl shadow-md border border-gray-100 hover:border-green-200 flex items-center space-x-6 h-full relative overflow-hidden hover:shadow-lg transition-all">
                        <div class="absolute top-0 right-0 w-24 h-24 bg-green-50 rounded-full -mr-8 -mt-8 opacity-50 group-hover:scale-150 transition-transform duration-500"></div>
                        <div class="stat-icon w-16 h-16 bg-gradient-to-br from-green-400 to-green-600 rounded-2xl flex items-center justify-center shadow-lg flex-shrink-0">
                            <i class="fas fa-star text-2xl text-white"></i>
                        </div>
                        <div class="relative z-10">
                            <p class="text-4xl font-bold text-gray-800 mb-1"><?php echo $total_review_diberikan; ?></p>
                            <p class="text-gray-600 font-medium">Review Diberikan</p>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Booking Terbaru -->
            <div class="bg-white p-6 rounded-xl shadow-md mb-8 border border-gray-100 hover:shadow-lg transition-shadow">
                <div class="flex items-center mb-6 pb-4 border-b border-gray-100">
                    <div class="w-12 h-12 bg-gradient-to-br from-purple-100 to-purple-200 rounded-xl flex items-center justify-center mr-4 flex-shrink-0">
                        <i class="fas fa-history text-2xl text-purple-600"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-800 truncate">Status Booking Terbaru Anda</h3>
                </div>
                <?php if ($latest_booking): ?>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="md:col-span-2 bg-gray-50 p-4 rounded-xl">
                        <p class="text-sm font-medium text-gray-500 mb-2">Nama Kos & Kamar</p>
                        <p class="text-lg font-bold text-gray-900 truncate"><?php echo htmlspecialchars($latest_booking['nama_kost']); ?></p>
                        <p class="text-base font-semibold text-purple-600 truncate"><?php echo htmlspecialchars($latest_booking['nama_kamar']); ?></p>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-xl">
                        <p class="text-sm font-medium text-gray-500 mb-2">Tanggal Booking</p>
                        <p class="text-lg font-semibold text-gray-900 flex items-center">
                            <i class="fas fa-calendar-alt text-purple-500 mr-2 flex-shrink-0"></i>
                            <span class="truncate"><?php echo date('d M Y', strtotime($latest_booking['tanggal_booking'])); ?></span>
                        </p>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-xl">
                        <p class="text-sm font-medium text-gray-500 mb-2">Status</p>
                        <div class="mt-1">
                            <span class="status-badge <?php echo 'status-' . strtolower($latest_booking['status']); ?> text-sm">
                                <i class="fas fa-circle text-xs mr-1"></i>
                                <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $latest_booking['status']))); ?>
                            </span>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="text-center py-16">
                    <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-inbox text-4xl text-gray-400"></i>
                    </div>
                    <p class="text-gray-500 text-lg">Anda belum memiliki riwayat booking.</p>
                    <button onclick="window.location.href='../dashboard/dashboarduser.php#pilihan-kos'" class="mt-4 bg-purple-600 text-white px-6 py-2.5 rounded-lg hover:bg-purple-700 transition-colors text-base font-medium">
                        Mulai Booking
                    </button>
                </div>
                <?php endif; ?>
            </div>

            <!-- ======================================================= -->
            <!-- TAMPILAN RIWAYAT BOOKING (dipindahkan dari booking.php) -->
            <!-- ======================================================= -->
            <div id="riwayat" class="pt-6 sm:pt-8 lg:pt-8">
                <div class="mb-6 sm:mb-8">
                    <h1 class="text-3xl sm:text-4xl font-bold bg-gradient-to-r from-purple-600 to-indigo-600 bg-clip-text text-transparent mb-1 sm:mb-2 truncate">Riwayat Booking Anda</h1>
                    <p class="text-gray-600 text-sm sm:text-base">Pantau semua booking Anda dengan mudah</p>
                </div>

                <!-- Booking Aktif -->
                <div class="mb-8 sm:mb-10">
                    <div class="flex items-center mb-4 sm:mb-6 section-header">
                        <div class="w-10 h-10 bg-gradient-to-br from-green-400 to-green-600 rounded-xl flex items-center justify-center mr-3 shadow-md flex-shrink-0">
                            <i class="fas fa-check-circle text-xl text-white"></i>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-800">Booking Aktif</h2>
                        <?php if (!empty($booking_history['aktif'])): ?>
                            <span class="ml-3 bg-green-100 text-green-700 text-sm font-semibold px-3 py-1 rounded-full"><?php echo count($booking_history['aktif']); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($booking_history['aktif'])): ?>
                        <div class="grid gap-4">
                        <?php foreach ($booking_history['aktif'] as $booking): ?>
                            <div class="booking-card bg-white p-6 rounded-xl shadow-md border-l-4 border-green-500 hover:shadow-lg transition-all">
                                <div class="flex justify-between items-start mb-4">
                                    <div class="flex-grow">
                                        <div class="flex items-start space-x-3">
                                            <div class="w-12 h-12 bg-green-50 rounded-lg flex items-center justify-center flex-shrink-0">
                                                <i class="fas fa-home text-green-600 text-xl"></i>
                                            </div>
                                            <div>
                                                <p class="font-bold text-xl text-gray-900 mb-1"><?php echo htmlspecialchars($booking['nama_kost']); ?></p>
                                                <p class="text-purple-600 font-semibold"><?php echo htmlspecialchars($booking['nama_kamar']); ?></p>
                                                <p class="text-sm text-gray-500 mt-2 flex items-center">
                                                    <i class="fas fa-calendar-alt mr-2"></i>
                                                    Dibooking pada: <?php echo date('d M Y', strtotime($booking['tanggal_booking'])); ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <span class="status-badge status-dibayar flex items-center">
                                        <i class="fas fa-check-circle mr-1"></i>Aktif
                                    </span>
                                </div>
                                <div class="mt-4 pt-4 border-t border-gray-100 flex justify-end">
                                    <a href="complaint.php" class="bg-red-50 text-red-600 hover:bg-red-100 px-4 py-2 rounded-lg text-sm font-semibold transition-colors flex items-center group">
                                        <i class="fas fa-exclamation-circle mr-2 group-hover:scale-110 transition-transform"></i>Ajukan Keluhan
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="bg-gray-50 border-2 border-dashed border-gray-200 rounded-lg sm:rounded-xl p-6 sm:p-8 text-center">
                            <i class="fas fa-calendar-times text-4xl sm:text-5xl text-gray-300 mb-2 sm:mb-3"></i>
                            <p class="text-gray-500 text-sm sm:text-lg">Tidak ada booking yang sedang aktif.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Booking Pending -->
                <div class="mb-8 sm:mb-10">
                    <div class="flex items-center mb-4 sm:mb-6 section-header">
                        <div class="w-10 h-10 bg-gradient-to-br from-yellow-400 to-yellow-600 rounded-xl flex items-center justify-center mr-3 shadow-md flex-shrink-0">
                            <i class="fas fa-hourglass-half text-xl text-white"></i>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-800">Menunggu Proses</h2>
                        <?php if (!empty($booking_history['menunggu_proses']) || !empty($booking_history['menunggu_pembayaran'])): ?>
                            <span class="ml-3 bg-yellow-100 text-yellow-700 text-sm font-semibold px-3 py-1 rounded-full">
                                <?php echo count($booking_history['menunggu_proses']) + count($booking_history['menunggu_pembayaran']); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($booking_history['menunggu_proses']) || !empty($booking_history['menunggu_pembayaran'])): ?>
                        <div class="grid gap-4">
                        <?php foreach ($booking_history['menunggu_proses'] as $booking): ?> 
                            <div class="booking-card bg-white p-6 rounded-xl shadow-md border-l-4 border-yellow-500 hover:shadow-lg transition-all">
                                <div class="flex justify-between items-start mb-4">
                                    <div class="flex-grow">
                                        <div class="flex items-start space-x-3">
                                            <div class="w-12 h-12 bg-yellow-50 rounded-lg flex items-center justify-center flex-shrink-0">
                                                <i class="fas fa-clock text-yellow-600 text-xl"></i>
                                            </div>
                                            <div>
                                                <p class="font-bold text-xl text-gray-900 mb-1"><?php echo htmlspecialchars($booking['nama_kost']); ?></p>
                                                <p class="text-purple-600 font-semibold"><?php echo htmlspecialchars($booking['nama_kamar']); ?></p>
                                                <p class="text-sm text-gray-500 mt-2 flex items-center">
                                                    <i class="fas fa-calendar-alt mr-2"></i>
                                                    Dibooking pada: <?php echo date('d M Y', strtotime($booking['tanggal_booking'])); ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <span class="status-badge status-pending flex items-center">
                                        <i class="fas fa-clock mr-1"></i>Menunggu Konfirmasi
                                    </span>
                                </div>
                                <div class="mt-4 pt-4 border-t border-gray-100 flex justify-end items-center">
                                    <button onclick="cancelBooking(<?php echo $booking['id_booking']; ?>)" class="bg-red-50 text-red-600 hover:bg-red-100 px-4 py-2 rounded-lg text-sm font-semibold transition-colors flex items-center group">
                                        <i class="fas fa-times-circle mr-2 group-hover:scale-110 transition-transform"></i>Batalkan Pesanan
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?> 
                        <?php foreach ($booking_history['menunggu_pembayaran'] as $booking): ?>
                            <div class="booking-card bg-white p-6 rounded-xl shadow-md border-l-4 border-blue-500 hover:shadow-lg transition-all">
                                <div class="flex justify-between items-start mb-4">
                                    <div class="flex-grow">
                                        <div class="flex items-start space-x-3">
                                            <div class="w-12 h-12 bg-blue-50 rounded-lg flex items-center justify-center flex-shrink-0">
                                                <i class="fas fa-wallet text-blue-600 text-xl"></i>
                                            </div>
                                            <div>
                                                <p class="font-bold text-xl text-gray-900 mb-1"><?php echo htmlspecialchars($booking['nama_kost']); ?></p>
                                                <p class="text-purple-600 font-semibold"><?php echo htmlspecialchars($booking['nama_kamar']); ?></p>
                                                <p class="text-sm text-gray-500 mt-2 flex items-center">
                                                    <i class="fas fa-calendar-alt mr-2"></i>
                                                    Dibooking pada: <?php echo date('d M Y', strtotime($booking['tanggal_booking'])); ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <span class="status-badge status-menunggu_pembayaran flex items-center">
                                        <i class="fas fa-credit-card mr-1"></i>Menunggu Pembayaran
                                    </span>
                                </div>
                                <div class="mt-4 pt-4 border-t border-gray-100 flex justify-end items-center gap-3">
                                    <button onclick="cancelBooking(<?php echo $booking['id_booking']; ?>)" class="bg-red-50 text-red-600 hover:bg-red-100 px-4 py-2 rounded-lg text-sm font-semibold transition-colors flex items-center group">
                                        <i class="fas fa-times-circle mr-2 group-hover:scale-110 transition-transform"></i>Batalkan
                                    </button>
                                    <a href="payment.php?booking_id=<?php echo $booking['id_booking']; ?>" class="bg-purple-600 text-white hover:bg-purple-700 px-4 py-2 rounded-lg font-semibold text-center transition-colors flex items-center justify-center group">
                                        <i class="fas fa-credit-card mr-2 group-hover:scale-110 transition-transform"></i>Bayar
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="bg-gray-50 border-2 border-dashed border-gray-200 rounded-lg sm:rounded-xl p-6 sm:p-8 text-center">
                            <i class="fas fa-inbox text-4xl sm:text-5xl text-gray-300 mb-2 sm:mb-3"></i>
                            <p class="text-gray-500 text-sm sm:text-lg">Tidak ada booking yang menunggu proses.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Riwayat Lainnya -->
                <div class="mb-8 sm:mb-10">
                    <div class="flex items-center mb-4 sm:mb-6 section-header">
                        <div class="w-10 h-10 bg-gradient-to-br from-gray-400 to-gray-600 rounded-xl flex items-center justify-center mr-3 shadow-md flex-shrink-0">
                            <i class="fas fa-archive text-xl text-white"></i>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-800">Riwayat Lainnya</h2>
                        <?php if (!empty($booking_history['selesai']) || !empty($booking_history['ditolak']) || !empty($booking_history['batal'])): ?>
                            <span class="ml-3 bg-gray-100 text-gray-700 text-sm font-semibold px-3 py-1 rounded-full">
                                <?php echo count($booking_history['selesai']) + count($booking_history['ditolak']) + count($booking_history['batal']); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($booking_history['selesai']) || !empty($booking_history['ditolak']) || !empty($booking_history['batal'])): ?>
                        <div class="grid gap-4">
                        <?php foreach ($booking_history['selesai'] as $booking): ?> 
                            <div class="booking-card bg-white p-5 rounded-xl shadow-sm border-l-4 border-gray-400 opacity-80 hover:opacity-100 transition-all">
                                <div class="flex justify-between items-center">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-check-double text-gray-600"></i>
                                        </div>
                                        <div>
                                            <p class="font-bold text-lg text-gray-700"><?php echo htmlspecialchars($booking['nama_kost']); ?></p>
                                            <p class="text-sm text-gray-500 flex items-center mt-1">
                                                <i class="fas fa-door-closed mr-2"></i>
                                                <?php echo htmlspecialchars($booking['nama_kamar']); ?>
                                            </p>
                                            <p class="text-xs text-gray-400 mt-1">Selesai pada: <?php echo date('d M Y', strtotime($booking['tanggal_booking'])); ?></p>
                                        </div>
                                    </div>
                                    <span class="status-badge status-selesai flex items-center">
                                        <i class="fas fa-flag-checkered mr-1"></i>Selesai
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php foreach ($booking_history['ditolak'] as $booking): ?> 
                            <div class="booking-card bg-white p-5 rounded-xl shadow-sm border-l-4 border-red-500 opacity-80 hover:opacity-100 transition-all">
                                <div class="flex justify-between items-center">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-times text-red-600"></i>
                                        </div>
                                        <div>
                                            <p class="font-bold text-lg text-gray-700"><?php echo htmlspecialchars($booking['nama_kost']); ?></p>
                                            <p class="text-sm text-gray-500 flex items-center mt-1">
                                                <i class="fas fa-door-closed mr-2"></i>
                                                <?php echo htmlspecialchars($booking['nama_kamar']); ?>
                                            </p>
                                            <p class="text-xs text-gray-400 mt-1">Ditolak pada: <?php echo date('d M Y', strtotime($booking['tanggal_booking'])); ?></p>
                                        </div>
                                    </div>
                                    <span class="status-badge status-ditolak flex items-center">
                                        <i class="fas fa-ban mr-1"></i>Ditolak
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php foreach ($booking_history['batal'] as $booking): ?> 
                            <div class="booking-card bg-white p-5 rounded-xl shadow-sm border-l-4 border-orange-500 opacity-80 hover:opacity-100 transition-all">
                                <div class="flex justify-between items-center">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-ban text-orange-600"></i>
                                        </div>
                                        <div>
                                            <p class="font-bold text-lg text-gray-700"><?php echo htmlspecialchars($booking['nama_kost']); ?></p>
                                            <p class="text-sm text-gray-500 flex items-center mt-1">
                                                <i class="fas fa-door-closed mr-2"></i>
                                                <?php echo htmlspecialchars($booking['nama_kamar']); ?>
                                            </p>
                                            <p class="text-xs text-gray-400 mt-1">Dibatalkan pada: <?php echo date('d M Y', strtotime($booking['tanggal_booking'])); ?></p>
                                        </div>
                                    </div>
                                    <span class="status-badge status-batal flex items-center">
                                        <i class="fas fa-times-circle mr-1"></i>Dibatalkan
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="bg-gray-50 border-2 border-dashed border-gray-200 rounded-lg sm:rounded-xl p-6 sm:p-8 text-center">
                            <i class="fas fa-history text-4xl sm:text-5xl text-gray-300 mb-2 sm:mb-3"></i>
                            <p class="text-gray-500 text-sm sm:text-lg">Tidak ada riwayat booking lainnya.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <?php include '_user_profile_modal.php'; ?>

    <script>
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="user_dashboard.php#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const targetId = this.getAttribute('href').split('#')[1];
                const target = document.getElementById(targetId);
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth' });
                }
            });
        });

        // Mobile menu toggle with smooth animation
        let mobileMenuActive = false;

        // Setup button event listeners when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            const profileBtn = document.getElementById('mobileProfileBtn');
            const notifBtn = document.getElementById('mobileNotifBtn');
            const logoutBtn = document.getElementById('mobileLogoutBtn');
            
            if (profileBtn) {
                profileBtn.addEventListener('mousedown', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    closeMobileMenu();
                    setTimeout(() => showProfileModal(), 100);
                });
            }
            
            if (notifBtn) {
                notifBtn.addEventListener('mousedown', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    closeMobileMenu();
                    setTimeout(() => showNotifications(), 100);
                });
            }
            
            if (logoutBtn) {
                logoutBtn.addEventListener('mousedown', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    closeMobileMenu();
                    setTimeout(() => confirmLogout(new Event('click')), 100);
                });
            }
        });
        
        function toggleMobileMenu() {
            const drawer = document.getElementById('mobileMenuDrawer');
            const panel = document.getElementById('mobileMenuPanel');
            const backdrop = document.getElementById('mobileMenuBackdrop');
            
            if (mobileMenuActive) {
                // Close menu
                panel.style.transform = 'translateX(100%)';
                backdrop.style.opacity = '0';
                backdrop.style.pointerEvents = 'none';
                drawer.style.pointerEvents = 'none';
                setTimeout(() => {
                    mobileMenuActive = false;
                }, 300);
            } else {
                // Open menu
                mobileMenuActive = true;
                drawer.style.pointerEvents = 'auto';
                panel.style.transform = 'translateX(0)';
                backdrop.style.opacity = '1';
                backdrop.style.pointerEvents = 'auto';
            }
        }

        // Handle mobile menu item click
        function handleMobileMenuClick(event) {
            // Jangan prevent default untuk link navigation
            // Hanya close menu
            const panel = document.getElementById('mobileMenuPanel');
            const backdrop = document.getElementById('mobileMenuBackdrop');
            
            if (panel && backdrop) {
                panel.style.transform = 'translateX(100%)';
                backdrop.style.opacity = '0';
                backdrop.style.pointerEvents = 'none';
                setTimeout(() => {
                    mobileMenuActive = false;
                }, 300);
            }
        }

        // Close menu when clicking outside
        document.addEventListener('click', (e) => {
            const drawer = document.getElementById('mobileMenuDrawer');
            const menuBtn = document.getElementById('mobileMenuBtn');
            
            // Don't close if clicking on the drawer or menu button
            if (!drawer || !mobileMenuActive) return;
            if (drawer.contains(e.target) || e.target.closest('#mobileMenuBtn')) return;
            
            // Close menu when clicking outside
            toggleMobileMenu();
        });

        // Helper function to close mobile menu
        function closeMobileMenu() {
            const panel = document.getElementById('mobileMenuPanel');
            const backdrop = document.getElementById('mobileMenuBackdrop');
            const drawer = document.getElementById('mobileMenuDrawer');
            
            if (panel && backdrop) {
                panel.style.transform = 'translateX(100%)';
                backdrop.style.opacity = '0';
                backdrop.style.pointerEvents = 'none';
                drawer.style.pointerEvents = 'none';
                setTimeout(() => {
                    mobileMenuActive = false;
                }, 300);
            }
        }

        // Enhanced notification display
        function showNotifications() {
            fetch('user_get_notifications.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success' && data.notifications.length > 0) {
                        let notifHtml = '<div class="space-y-3 text-left max-h-96 overflow-y-auto">';
                        data.notifications.forEach(notif => {
                            const readClass = notif.is_read == 1 ? 'opacity-60' : '';
                            const iconClass = notif.is_read == 1 ? 'fa-envelope-open' : 'fa-envelope';
                            notifHtml += `
                                <div class="p-4 border-l-4 border-purple-500 rounded-lg hover:bg-purple-50 transition-colors ${readClass} bg-white shadow-sm">
                                    <div class="flex items-start space-x-3">
                                        <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center flex-shrink-0">
                                            <i class="fas ${iconClass} text-purple-600"></i>
                                        </div>
                                        <div class="flex-grow">
                                            <p class="text-sm text-gray-800 ${notif.is_read == 1 ? '' : 'font-semibold'}">${notif.pesan}</p>
                                            <div class="flex justify-between items-center mt-2">
                                                <span class="text-xs text-gray-400 flex items-center">
                                                    <i class="far fa-clock mr-1"></i>${notif.created_at}
                                                </span>
                                                ${notif.link ? `<a href="${notif.link}" class="text-xs text-purple-600 hover:text-purple-700 font-semibold">Lihat Detail →</a>` : ''}
                                            </div>
                                        </div>
                                    </div>
                                </div>`;
                        });
                        notifHtml += '</div>';

                        Swal.fire({
                            title: '<span class="text-2xl font-bold text-gray-800">📬 Notifikasi Anda</span>',
                            html: notifHtml,
                            width: '600px',
                            showConfirmButton: true,
                            confirmButtonText: '<i class="fas fa-check-double mr-2"></i>Tandai Semua Sudah Dibaca',
                            confirmButtonColor: '#8B5CF6',
                            customClass: {
                                popup: 'rounded-2xl',
                                confirmButton: 'px-6 py-3 rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all'
                            }
                        }).then(() => {
                            fetch('user_get_notifications.php', { method: 'POST' }).then(() => {
                                const badge = document.getElementById('notifBadge');
                                if (badge) badge.remove();
                            });
                        });
                    } else {
                        Swal.fire({
                            icon: 'info',
                            title: 'Notifikasi',
                            text: 'Tidak ada notifikasi baru.',
                            confirmButtonColor: '#8B5CF6',
                            customClass: {
                                popup: 'rounded-2xl',
                                confirmButton: 'px-6 py-3 rounded-xl font-semibold'
                            }
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Gagal memuat notifikasi.',
                        confirmButtonColor: '#8B5CF6'
                    });
                });
        }

        // Enhanced logout confirmation
        function confirmLogout(event) {
            event.preventDefault();
            Swal.fire({
                title: '<span class="text-2xl font-bold text-gray-800">Konfirmasi Logout</span>',
                html: '<p class="text-gray-600">Apakah Anda yakin ingin keluar dari akun Anda?</p>',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-sign-out-alt mr-2"></i>Ya, Logout',
                cancelButtonText: '<i class="fas fa-times mr-2"></i>Batal',
                confirmButtonColor: '#EF4444',
                cancelButtonColor: '#6B7280',
                customClass: {
                    popup: 'rounded-2xl',
                    confirmButton: 'px-6 py-3 rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all',
                    cancelButton: 'px-6 py-3 rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '../auth/logout.php';
                }
            });
        }

        // Enhanced cancel booking
        function cancelBooking(id_booking) {
            Swal.fire({
                title: '<span class="text-2xl font-bold text-gray-800">Batalkan Pesanan?</span>',
                html: '<p class="text-gray-600">Apakah Anda yakin ingin membatalkan pesanan ini?<br><span class="text-red-600 font-semibold">Aksi ini tidak dapat diurungkan.</span></p>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#EF4444',
                cancelButtonColor: '#6B7280',
                confirmButtonText: '<i class="fas fa-times-circle mr-2"></i>Ya, Batalkan',
                cancelButtonText: '<i class="fas fa-arrow-left mr-2"></i>Tidak',
                showLoaderOnConfirm: true,
                customClass: {
                    popup: 'rounded-2xl',
                    confirmButton: 'px-6 py-3 rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all',
                    cancelButton: 'px-6 py-3 rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all'
                },
                preConfirm: () => {
                    const formData = new FormData();
                    formData.append('id_booking', id_booking);

                    return fetch('process_cancel_booking.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(async response => {
                        const text = await response.text();
                        try {
                            const data = JSON.parse(text);
                            if (!response.ok) throw new Error(data.message || `HTTP ${response.status}`);
                            return data;
                        } catch (e) {
                            if (!response.ok) {
                                const msg = text && text.trim() ? text.trim() : `Server error (status ${response.status})`;
                                throw new Error(msg);
                            }
                            throw new Error(`Invalid server response: ${text}`);
                        }
                    })
                    .catch(error => {
                        Swal.showValidationMessage(`Request gagal: ${error}`);
                    });
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        icon: 'success',
                        title: '<span class="text-2xl font-bold text-gray-800">Berhasil!</span>',
                        html: '<p class="text-gray-600">Pesanan Anda telah berhasil dibatalkan.</p>',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#10B981',
                        customClass: {
                            popup: 'rounded-2xl',
                            confirmButton: 'px-6 py-3 rounded-xl font-semibold shadow-lg'
                        }
                    }).then(() => {
                        window.location.reload();
                    });
                }
            });
        }

        // Auto hide payment success notification
        setTimeout(() => {
            const paymentNotif = document.getElementById('paymentNotification');
            if (paymentNotif) {
                paymentNotif.style.transition = 'all 0.5s ease-out';
                paymentNotif.style.opacity = '0';
                paymentNotif.style.transform = 'translateY(-20px)';
                setTimeout(() => paymentNotif.remove(), 500);
            }
        }, 5000);
    </script>
</body>
</html>