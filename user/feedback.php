<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['role'] !== 'penyewa') {
    header("Location: ../auth/loginForm.php");
    exit();
}

include '../config/db.php';

$id_penyewa = $_SESSION['user_id'];
$userName = htmlspecialchars($_SESSION['fullname'] ?? 'Penyewa Kos');
$userRole = htmlspecialchars($_SESSION['role'] ?? 'penyewa');
$userPhoto = $_SESSION['foto_profil'] ?? null;

// Ambil jumlah notifikasi yang belum dibaca
$stmt_notif = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE id_user = ? AND is_read = 0");
$stmt_notif->bind_param("i", $id_penyewa);
$stmt_notif->execute();
$notif_count = $stmt_notif->get_result()->fetch_assoc()['count'];
$stmt_notif->close();

// Ambil riwayat feedback penyewa
$stmt_feedback = $conn->prepare("
    SELECT f.id_feedback, f.pesan, f.created_at
    FROM feedback f
    WHERE f.id_penyewa = ?
    ORDER BY f.created_at DESC
");
$stmt_feedback->bind_param("i", $id_penyewa);
$stmt_feedback->execute();
$result_feedback = $stmt_feedback->get_result();
$user_feedback = [];
while ($row = $result_feedback->fetch_assoc()) {
    $user_feedback[] = $row;
}
$stmt_feedback->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Aplikasi - KosConnect</title>
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
        
        /* Navigation */
        nav {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95) !important;
            transition: all 0.3s ease;
            border-bottom: 1px solid rgba(209, 213, 219, 0.3);
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

        /* Navbar Button Styles */
        nav button i {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Notification Badge Animation */
        @keyframes pulse-custom {
            0%, 100% {
                opacity: 1;
                transform: scale(1);
            }
            50% {
                opacity: 0.7;
                transform: scale(0.95);
            }
        }

        .animate-pulse {
            animation: pulse-custom 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        /* Navbar Icon Button */
        nav .group {
            position: relative;
            overflow: hidden;
        }

        nav button.group::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(147, 51, 234, 0.1), transparent);
            transition: left 0.6s;
        }

        nav button.group:hover::before {
            left: 100%;
        }
        
        /* Buttons */
        button, .btn {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        button:hover:not(:disabled), .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }
        
        /* Textarea */
        textarea:focus {
            transform: scale(1.01);
            transition: all 0.3s ease;
        }
        
        /* Feedback Card */
        .feedback-card {
            transition: all 0.3s ease;
            border-left-width: 4px;
        }
        
        .feedback-card:hover {
            transform: translateX(4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
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
        
        /* Page Animation */
        main {
            animation: fadeIn 0.6s ease-out;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .card-hover:hover {
                transform: translateY(-4px) scale(1.01);
            }
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

        /* Comprehensive Mobile Responsive Design */
        @media (max-width: 768px) {
            /* Navigation */
            /* Navigation */
            nav {
                height: auto;
                /* Allow default padding or controlled padding */
                border-bottom: 1px solid rgba(209, 213, 219, 0.3) !important;
            }

            nav h1 {
                font-size: 1.1rem !important;
            }

            /* Navbar Container - Perfect alignment */
            .max-w-7xl.mx-auto > .flex {
                width: 100%;
                display: flex;
                align-items: center;
                justify-content: space-between;
                height: 4rem;
                /* Ensure side spacing */
                padding-left: 1rem !important;
                padding-right: 1rem !important;
            }
            
            /* If the html structure is nav > div > div.flex, we need to be careful. 
               The HTML is: 
               <nav ...>
                 <div class="max-w-7xl ...">
                   <div class="flex ..."> 
            */

             /* Targeting the outer container to ensure it doesn't have 0 padding if we override it */
            .max-w-7xl.mx-auto {
                padding-left: 0 !important;
                padding-right: 0 !important;
            }

            /* Logo section on left - Hidden in mobile */
            a.flex.items-center.group {
                display: none !important;
            }

            /* Mobile icons container - right aligned */
            .flex.md\:hidden.items-center.justify-end.gap-1.h-10 {
                display: flex !important;
                align-items: center !important;
                justify-content: flex-end !important;
                height: 4rem !important;
                gap: 0.5rem !important;
                padding-right: 1rem !important;
                margin-left: auto !important;
            }

            /* Mobile notification button */
            button#mobileNotifBtn {
                width: 2.5rem !important;
                height: 2.5rem !important;
                padding: 0.5rem !important;
                border-radius: 0.5rem !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                flex-shrink: 0 !important;
                position: relative !important;
            }

            button#mobileNotifBtn i {
                font-size: 1rem !important;
            }

            button#mobileNotifBtn #mobileNotifBadge {
                position: absolute !important;
                top: 0.125rem !important;
                right: 0.125rem !important;
            }

            /* Mobile menu button */
            button#mobileMenuBtn {
                width: 2.5rem !important;
                height: 2.5rem !important;
                padding: 0.5rem !important;
                border-radius: 0.5rem !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                flex-shrink: 0 !important;
            }

            button#mobileMenuBtn i {
                font-size: 1rem !important;
            }

            /* Hide Desktop Navigation */
            nav > div > div > nav {
                display: none !important;
            }

            /* Main Content */
            main {
                padding-top: 5rem !important;
                padding-bottom: 2rem !important;
            }

            .max-w-6xl,
            .max-w-4xl,
            .max-w-2xl {
                max-width: 100% !important;
                padding: 0 1rem !important;
            }

            /* Header Section */
            .relative.overflow-hidden.bg-gradient-to-br {
                padding: 1.5rem !important;
                min-height: 160px !important;
                border-radius: 1.75rem !important;
                margin-bottom: 1.5rem !important;
            }

            .relative.overflow-hidden.bg-gradient-to-br .relative.flex {
                flex-direction: column !important;
                gap: 1rem !important;
            }

            .relative.overflow-hidden.bg-gradient-to-br .w-20 {
                width: 3.5rem !important;
                height: 3.5rem !important;
                margin-right: 0 !important;
            }

            .relative.overflow-hidden.bg-gradient-to-br h1 {
                font-size: 1.75rem !important;
                margin-bottom: 0.5rem !important;
            }

            .relative.overflow-hidden.bg-gradient-to-br p {
                font-size: 0.95rem !important;
            }

            /* Page Title */
            h1 {
                font-size: 1.75rem !important;
                margin-bottom: 1rem !important;
            }

            h2 {
                font-size: 1.35rem !important;
                margin-bottom: 0.75rem !important;
            }

            h3 {
                font-size: 1.1rem !important;
                margin-bottom: 0.5rem !important;
            }

            /* Form Sections */
            .bg-white.rounded-3xl {
                padding: 1.5rem !important;
                border-radius: 1.5rem !important;
                margin-bottom: 1.25rem !important;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08) !important;
            }

            .space-y-6 {
                gap: 1.25rem !important;
            }

            .space-y-4 {
                gap: 0.75rem !important;
            }

            .space-y-6 > div,
            .space-y-4 > div {
                margin-bottom: 0 !important;
            }

            /* Form Inputs */
            input[type="text"],
            input[type="email"],
            input[type="password"],
            textarea,
            select {
                font-size: 1rem !important;
                padding: 0.875rem !important;
                border-radius: 1rem !important;
            }

            textarea {
                min-height: 150px !important;
            }

            label {
                font-size: 0.9rem !important;
                margin-bottom: 0.5rem !important;
                font-weight: 600 !important;
            }

            /* Buttons */
            button,
            .btn-action {
                padding: 0.875rem 1.25rem !important;
                font-size: 0.95rem !important;
                border-radius: 1rem !important;
                font-weight: 600 !important;
            }

            .flex.justify-between button,
            .flex.gap-4 button {
                width: 100% !important;
                margin-bottom: 0 !important;
            }

            /* Feedback Cards */
            .card-hover {
                padding: 1.25rem !important;
                border-radius: 1.5rem !important;
            }

            .feedback-card {
                padding: 1.25rem !important;
                border-radius: 1.25rem !important;
                gap: 1rem !important;
            }

            .feedback-card h4,
            .feedback-card h3 {
                font-size: 1rem !important;
                margin-bottom: 0.5rem !important;
            }

            .feedback-card p {
                font-size: 0.95rem !important;
                line-height: 1.5 !important;
            }

            /* Text Sizes */
            .text-sm {
                font-size: 0.85rem !important;
            }

            .text-xs {
                font-size: 0.75rem !important;
            }

            .text-base {
                font-size: 0.95rem !important;
            }

            .text-gray-600 {
                font-size: 0.9rem !important;
            }

            .text-gray-500 {
                font-size: 0.85rem !important;
            }

            /* Mobile Menu */
            #mobileMenuPanel {
                width: 80vw !important;
                max-width: 320px !important;
            }

            /* Responsive Spacing */
            .px-4 {
                padding-left: 1rem !important;
                padding-right: 1rem !important;
            }

            .px-6 {
                padding-left: 1rem !important;
                padding-right: 1rem !important;
            }

            .md\:p-8,
            .md\:p-10 {
                padding: 1.25rem !important;
            }

            /* Grid and Flex */
            .grid.grid-cols-1.md\:grid-cols-2,
            .grid.grid-cols-1.lg\:grid-cols-2,
            .grid.grid-cols-2 {
                grid-template-columns: 1fr !important;
                gap: 0.75rem !important;
            }

            .flex.justify-between {
                flex-direction: column !important;
                align-items: flex-start !important;
            }

            .flex.space-x-4,
            .flex.gap-4,
            .flex.gap-6 {
                flex-direction: column !important;
                gap: 0.75rem !important;
            }

            .flex.space-x-2 {
                flex-direction: column !important;
                gap: 0.5rem !important;
            }

            .flex.items-center {
                gap: 0.5rem !important;
            }

            /* List Items */
            .space-y-3 {
                gap: 0.75rem !important;
            }

            .space-y-3 > div {
                padding: 0.75rem !important;
            }

            /* Icon sizing */
            .w-14 {
                width: 3rem !important;
                height: 3rem !important;
            }

            .w-11 {
                width: 2.5rem !important;
                height: 2.5rem !important;
            }

            .w-20 {
                width: 4rem !important;
                height: 4rem !important;
            }

            /* Card Hover Effect */
            .card-hover:hover {
                transform: translateY(-2px) scale(1.005) !important;
                box-shadow: 0 8px 20px rgba(147, 51, 234, 0.08) !important;
            }
        }

        /* Drawer Animation */
        @media (max-width: 768px) {
            #mobileMenuPanel {
                transform: translateX(100%);
            }
        }

        /* Extra small devices (< 640px) */
        @media (max-width: 640px) {
            .max-w-6xl,
            .max-w-4xl,
            .max-w-2xl {
                padding: 0 0.75rem !important;
            }

            h1 {
                font-size: 1.5rem !important;
            }

            h2 {
                font-size: 1.15rem !important;
            }

            h3 {
                font-size: 1rem !important;
            }

            .relative.overflow-hidden.bg-gradient-to-br {
                padding: 1.25rem !important;
                min-height: 140px !important;
            }

            .relative.overflow-hidden.bg-gradient-to-br h1 {
                font-size: 1.5rem !important;
            }

            .relative.overflow-hidden.bg-gradient-to-br p {
                font-size: 0.9rem !important;
            }

            .px-4 {
                padding-left: 0.75rem !important;
                padding-right: 0.75rem !important;
            }

            nav .text-2xl {
                font-size: 1rem !important;
            }

            .flex.gap-4,
            .flex.gap-6 {
                gap: 0.5rem !important;
            }

            .bg-white.rounded-3xl {
                padding: 1rem !important;
                border-radius: 1.25rem !important;
            }

            .md\:p-8,
            .md\:p-10 {
                padding: 1rem !important;
            }

            input,
            textarea,
            select {
                font-size: 16px !important;
                padding: 0.75rem !important;
            }

            button {
                padding: 0.75rem 1rem !important;
                font-size: 0.9rem !important;
            }

            .feedback-card {
                padding: 1rem !important;
            }

            .w-14 {
                width: 2.5rem !important;
                height: 2.5rem !important;
            }

            .w-11 {
                width: 2rem !important;
                height: 2rem !important;
            }

            .space-y-6 {
                gap: 1rem !important;
            }
        }
    </style>
</head>
<body class="bg-gray-50 font-sans">

    <!-- Navigasi Konsisten -->
    <nav class="bg-white shadow-lg fixed w-full top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo (Left) -->
                <a href="../dashboard/dashboarduser.php" class="flex items-center group">
                    <div class="w-10 h-10 bg-gradient-to-br from-purple-600 to-indigo-600 rounded-lg flex items-center justify-center mr-2 group-hover:scale-110 transition-transform">
                        <i class="fas fa-home text-white"></i>
                    </div>
                    <h1 class="text-2xl font-bold bg-gradient-to-r from-purple-600 to-indigo-600 bg-clip-text text-transparent hidden sm:block">Kos<span class="text-purple-600">Connect</span></h1>
                </a>

                <!-- Desktop Navigation (Center) -->
                <div class="hidden md:flex items-center space-x-6">
                    <nav class="flex space-x-8">
                        <a href="../dashboard/dashboarduser.php" class="text-gray-700 font-medium hover:text-purple-600 py-2">Beranda</a>
                        <a href="user_dashboard.php" class="text-gray-700 font-medium hover:text-purple-600 py-2">Dashboard</a>
                        <a href="../dashboard/dashboarduser.php#pilihan-kos" class="text-gray-700 font-medium hover:text-purple-600 py-2">Pilihan Kos</a>
                        <a href="wishlist.php" class="text-gray-700 font-medium hover:text-purple-600 py-2">Favorit</a>
                        <a href="feedback.php" class="text-purple-600 font-semibold hover:text-purple-700 py-2">Feedback</a>
                        <a href="../dashboard/dashboarduser.php#kontak" class="text-gray-700 font-medium hover:text-purple-600 py-2 transition-colors">Kontak</a>
                    </nav>
                </div>

                <!-- Desktop Actions (Right) -->
                <div class="hidden md:flex items-center space-x-4 pl-6 border-l-2 border-gray-200">
                    <button id="desktopNotifBtn" onclick="showNotifications()" class="relative text-gray-600 hover:text-purple-600 p-2 rounded-lg hover:bg-purple-50 transition-all" aria-label="Notifikasi" title="Notifikasi">
                        <i class="fas fa-bell text-xl" aria-hidden="true"></i>
                        <?php if ($notif_count > 0): ?>
                            <span id="desktopNotifBadge" class="absolute top-0 right-0 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center shadow-lg"><?php echo $notif_count; ?></span>
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
                
                <!-- Mobile Menu Icons (Right) -->
                <div class="flex md:hidden items-center justify-end gap-1 h-10">
                    <button id="mobileNotifBtn" onclick="showNotifications()" class="relative text-gray-600 hover:text-purple-600 p-2 rounded-lg hover:bg-purple-50 transition-all flex items-center justify-center w-10 h-10" aria-label="Notifikasi" title="Notifikasi">
                        <i class="fas fa-bell text-sm sm:text-base" aria-hidden="true"></i>
                        <?php if ($notif_count > 0): ?>
                            <span id="mobileNotifBadge" class="absolute -top-1 -right-0.5 bg-red-500 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center shadow-md animate-pulse" style="font-size: 0.65rem;">
                                <span style="display:flex;align-items:center;justify-content:center;width:100%;height:100%;"><?php echo $notif_count; ?></span>
                            </span>
                        <?php endif; ?>
                    </button>
                    <button id="mobileMenuBtn" class="text-gray-600 hover:text-purple-600 hover:bg-purple-50 rounded-lg transition-colors flex items-center justify-center w-10 h-10" onclick="toggleMobileMenu()" title="Menu">
                        <i class="fas fa-bars text-sm sm:text-base"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Mobile Navigation Drawer -->
    <div id="mobileMenuDrawer" class="fixed inset-0 z-40 lg:hidden pointer-events-none" style="pointer-events: none;">
        <!-- Backdrop -->
        <div id="mobileMenuBackdrop" class="absolute inset-0 bg-black bg-opacity-50 transition-opacity duration-300 opacity-0 pointer-events-none" onclick="toggleMobileMenu()" style="pointer-events: none;"></div>
        
        <!-- Drawer -->
        <div class="absolute right-0 top-0 h-full w-64 bg-white shadow-2xl transform translate-x-full transition-transform duration-300 flex flex-col" id="mobileMenuPanel" style="pointer-events: auto;">
            <!-- Close Button -->
            <div class="flex items-center justify-between p-4 border-b border-gray-100">
                <h2 class="text-xl font-bold text-gray-800">Menu</h2>
                <button onclick="toggleMobileMenu()" class="p-2 text-gray-600 hover:text-purple-600 rounded-lg hover:bg-gray-100 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <!-- Navigation Links -->
            <nav class="p-4 space-y-1 flex-1">
                <a href="../dashboard/dashboarduser.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-purple-50 hover:text-purple-600 rounded-lg font-medium transition-colors mobile-menu-link">
                    <i class="fas fa-home mr-3 text-purple-600 w-5"></i>Beranda
                </a>
                <a href="user_dashboard.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-purple-50 hover:text-purple-600 rounded-lg font-medium transition-colors mobile-menu-link">
                    <i class="fas fa-chart-line mr-3 text-blue-600 w-5"></i>Dashboard
                </a>
                <a href="../dashboard/dashboarduser.php#pilihan-kos" class="flex items-center px-4 py-3 text-gray-700 hover:bg-purple-50 hover:text-purple-600 rounded-lg font-medium transition-colors mobile-menu-link">
                    <i class="fas fa-building mr-3 text-orange-600 w-5"></i>Pilihan Kos
                </a>
                <a href="wishlist.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-purple-50 hover:text-purple-600 rounded-lg font-medium transition-colors mobile-menu-link">
                    <i class="fas fa-heart mr-3 text-red-600 w-5"></i>Favorit
                </a>
                <a href="feedback.php" class="flex items-center px-4 py-3 text-purple-600 bg-purple-50 rounded-lg font-medium mobile-menu-link">
                    <i class="fas fa-comment mr-3 w-5"></i>Feedback
                </a>
                <a href="../dashboard/dashboarduser.php#kontak" class="flex items-center px-4 py-3 text-gray-700 hover:bg-purple-50 hover:text-purple-600 rounded-lg font-medium transition-colors mobile-menu-link">
                    <i class="fas fa-phone mr-3 text-cyan-600 w-5"></i>Kontak
                </a>
            </nav>
            
            <!-- Divider -->
            <div class="border-t border-gray-100"></div>
            
            <!-- User Actions -->
            <div class="p-4 space-y-3">
                <button id="mobileProfileBtn" class="w-full px-4 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-xl font-medium hover:from-purple-700 hover:to-indigo-700 transition-all flex items-center justify-center gap-2 shadow-md" type="button" style="pointer-events: auto; cursor: pointer;">
                    <i class="fas fa-user"></i>Profil
                </button>
                <button id="mobileLogoutBtn" class="w-full px-4 py-3 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-xl font-medium hover:from-red-600 hover:to-red-700 transition-all flex items-center justify-center gap-2 shadow-md" type="button" style="pointer-events: auto; cursor: pointer;">
                    <i class="fas fa-sign-out-alt"></i>Logout
                </button>
            </div>
        </div>
    </div>

    <main class="pt-24 pb-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Header -->
            <div class="relative overflow-hidden bg-gradient-to-br from-purple-600 via-purple-700 to-indigo-700 text-white rounded-3xl shadow-2xl p-8 md:p-10 mb-8" style="animation: slideUp 0.6s ease-out; min-height: 200px;">
                <!-- Decorative Elements -->
                <div class="absolute top-0 right-0 -mt-8 -mr-8 w-48 h-48 bg-white opacity-5 rounded-full blur-3xl"></div>
                <div class="absolute bottom-0 left-0 -mb-12 -ml-12 w-40 h-40 bg-indigo-400 opacity-10 rounded-full blur-3xl"></div>
                
                <div class="relative flex items-center gap-6">
                    <div class="w-20 h-20 bg-white bg-opacity-25 backdrop-blur-md rounded-2xl flex items-center justify-center flex-shrink-0 shadow-lg border border-white border-opacity-30">
                        <i class="fas fa-comments text-5xl"></i>
                    </div>
                    <div class="flex-grow">
                        <h1 class="text-3xl md:text-4xl font-bold mb-2 tracking-tight">Feedback Aplikasi</h1>
                        <p class="text-purple-100 text-base md:text-lg leading-relaxed">Berikan masukan dan saran untuk pengembangan KosConnect</p>
                    </div>
                </div>
            </div>

            <!-- Form Feedback -->
            <div class="bg-white rounded-3xl shadow-xl p-8 md:p-10 mb-8 card-hover border border-gray-200" style="animation: slideUp 0.8s ease-out;">
                <div class="flex items-center mb-7 pb-6 border-b-2 border-gray-100">
                    <div class="w-14 h-14 bg-gradient-to-br from-blue-100 to-blue-200 rounded-2xl flex items-center justify-center mr-5 shadow-md">
                        <i class="fas fa-edit text-2xl text-blue-600"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl md:text-3xl font-bold text-gray-800">Kirim Feedback</h2>
                        <p class="text-gray-500 text-sm mt-1">Bantu kami meningkatkan kualitas layanan</p>
                    </div>
                </div>

                <form id="feedbackForm" class="space-y-6">
                    <div>
                        <label for="pesan" class="flex items-center text-sm font-semibold text-gray-700 mb-3">
                            <i class="fas fa-comment-dots text-purple-600 mr-2.5"></i>
                            <span>Pesan Feedback</span>
                        </label>
                        <textarea id="pesan" name="pesan" rows="7" required
                            placeholder="Berikan masukan, saran, atau kritik konstruktif untuk pengembangan aplikasi KosConnect..."
                            class="w-full px-5 py-4 border-2 border-gray-200 rounded-2xl focus:ring-2 focus:ring-purple-500 focus:border-transparent resize-none transition-all font-medium text-gray-700 placeholder-gray-400"
                            maxlength="1000"></textarea>
                        <div class="flex justify-between items-center mt-3 px-1">
                            <p class="text-xs font-medium text-gray-500">
                                <i class="fas fa-info-circle mr-1.5"></i>Maksimal 1000 karakter
                            </p>
                            <p class="text-sm font-semibold text-gray-600" id="charCount">0 / 1000</p>
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-gradient-to-r from-blue-500 via-blue-600 to-blue-600 text-white py-4 px-6 rounded-2xl font-bold text-base md:text-lg hover:from-blue-600 hover:via-blue-700 hover:to-blue-700 shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center group">
                        <i class="fas fa-paper-plane mr-3 group-hover:translate-x-1 transition-transform duration-300"></i>
                        <span>Kirim Feedback</span>
                    </button>
                </form>
            </div>

            <!-- Riwayat Feedback -->
            <div class="bg-white rounded-3xl shadow-xl p-8 md:p-10 border border-gray-200" style="animation: slideUp 1s ease-out;">
                <div class="flex items-center justify-between mb-7 pb-6 border-b-2 border-gray-100">
                    <div class="flex items-center">
                        <div class="w-14 h-14 bg-gradient-to-br from-green-100 to-green-200 rounded-2xl flex items-center justify-center mr-5 shadow-md">
                            <i class="fas fa-history text-2xl text-green-600"></i>
                        </div>
                        <div>
                            <h2 class="text-2xl md:text-3xl font-bold text-gray-800">Riwayat Feedback</h2>
                            <p class="text-gray-500 text-sm mt-1">Daftar feedback yang telah Anda kirimkan</p>
                        </div>
                    </div>
                    <div class="bg-gradient-to-r from-purple-100 to-purple-50 text-purple-700 px-5 py-2.5 rounded-full font-bold text-sm border border-purple-200 shadow-sm">
                        <i class="fas fa-star mr-2"></i><?php echo count($user_feedback); ?> Feedback
                    </div>
                </div>

                <?php if (!empty($user_feedback)): ?>
                    <div class="space-y-4">
                        <?php foreach ($user_feedback as $index => $feedback): ?>
                        <div class="feedback-card border-l-4 border-blue-500 bg-gradient-to-r from-blue-50 via-blue-50 to-white p-6 rounded-2xl hover:shadow-lg hover:border-blue-600 transition-all duration-300" style="animation: slideUp <?php echo 1.1 + ($index * 0.08); ?>s ease-out;">
                            <div class="flex items-start gap-5">
                                <div class="w-11 h-11 bg-blue-100 rounded-xl flex items-center justify-center flex-shrink-0 mt-0.5 shadow-sm border border-blue-200">
                                    <i class="fas fa-comment text-blue-600 text-lg"></i>
                                </div>
                                <div class="flex-grow min-w-0">
                                    <p class="text-gray-800 leading-relaxed mb-4 font-medium break-words"><?php echo htmlspecialchars($feedback['pesan']); ?></p>
                                    <div class="flex items-center gap-2 text-xs font-medium text-gray-500 pt-2 border-t border-gray-200">
                                        <i class="far fa-calendar text-blue-500"></i>
                                        <span><?php echo date('d M Y', strtotime($feedback['created_at'])); ?></span>
                                        <span class="mx-1 text-gray-300">•</span>
                                        <i class="far fa-clock text-blue-500"></i>
                                        <span><?php echo date('H:i', strtotime($feedback['created_at'])); ?> WIB</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-16">
                        <div class="w-28 h-28 bg-gradient-to-br from-gray-100 to-gray-50 rounded-3xl flex items-center justify-center mx-auto mb-6 shadow-sm border border-gray-200">
                            <i class="fas fa-inbox text-6xl text-gray-300"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-800 mb-2">Belum Ada Feedback</h3>
                        <p class="text-gray-500 text-base mb-2">Anda belum pernah mengirim feedback.</p>
                        <p class="text-gray-400 text-sm">Feedback yang Anda kirim akan muncul di sini</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </main>

    <?php include '_user_profile_modal.php'; ?>

    <script>
        // Character counter for textarea
        const textarea = document.getElementById('pesan');
        const charCount = document.getElementById('charCount');
        
        textarea.addEventListener('input', function() {
            const count = this.value.length;
            charCount.textContent = `${count} / 1000`;
            
            if (count > 900) {
                charCount.classList.add('text-orange-500', 'font-semibold');
            } else {
                charCount.classList.remove('text-orange-500', 'font-semibold');
            }
        });

        // Update foto profil jika ada perubahan
        (function(){
            try {
                const newPhoto = localStorage.getItem('newProfilePhoto');
                if (newPhoto) {
                    const ts = Date.now();
                    const url = `../uploads/profiles/${newPhoto}?t=${ts}`;
                    const headerPhoto = document.getElementById('headerUserPhoto');
                    if (headerPhoto) {
                        if (headerPhoto.tagName === 'IMG') {
                            headerPhoto.src = url;
                        } else {
                            const img = document.createElement('img');
                            img.id = 'headerUserPhoto';
                            img.className = 'w-9 h-9 rounded-full object-cover ring-2 ring-purple-200';
                            img.src = url;
                            img.alt = 'Foto Profil';
                            headerPhoto.parentNode.replaceChild(img, headerPhoto);
                        }
                    }
                    const modalPhoto = document.getElementById('photoPreview');
                    if (modalPhoto) modalPhoto.src = url;
                    localStorage.removeItem('newProfilePhoto');
                }
            } catch (e) {
                console.error("Gagal memuat foto profil baru dari localStorage", e);
            }
        })();

        // Form submission
        document.getElementById('feedbackForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const message = formData.get('pesan').trim();

            if (message.length < 10) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Pesan Terlalu Pendek',
                    text: 'Feedback harus minimal 10 karakter.',
                    confirmButtonColor: '#8B5CF6',
                    customClass: {
                        popup: 'rounded-2xl'
                    }
                });
                return;
            }

            Swal.fire({
                title: '<span class="text-2xl font-bold">Mengirim Feedback...</span>',
                html: '<div class="py-4"><i class="fas fa-spinner fa-spin text-4xl text-purple-600"></i></div>',
                allowOutsideClick: false,
                showConfirmButton: false,
                customClass: {
                    popup: 'rounded-2xl'
                }
            });

            fetch('process_feedback.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '<span class="text-2xl font-bold text-gray-800">Berhasil!</span>',
                        html: '<p class="text-gray-600 text-lg">' + data.message + '</p>',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#10B981',
                        customClass: {
                            popup: 'rounded-2xl',
                            confirmButton: 'px-6 py-3 rounded-xl font-semibold shadow-lg'
                        }
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: '<span class="text-2xl font-bold text-gray-800">Gagal!</span>',
                        html: '<p class="text-gray-600">' + data.message + '</p>',
                        confirmButtonColor: '#EF4444',
                        customClass: {
                            popup: 'rounded-2xl',
                            confirmButton: 'px-6 py-3 rounded-xl font-semibold'
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: '<span class="text-2xl font-bold text-gray-800">Error!</span>',
                    html: '<p class="text-gray-600">Terjadi kesalahan saat mengirim feedback. Silakan coba lagi.</p>',
                    confirmButtonColor: '#EF4444',
                    customClass: {
                        popup: 'rounded-2xl',
                        confirmButton: 'px-6 py-3 rounded-xl font-semibold'
                    }
                });
            });
        });

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

        // Enhanced notifications
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
    </script>

    <!-- Mobile Menu Script -->
    <script>
        let mobileMenuActive = false;

        document.addEventListener('DOMContentLoaded', function() {
            const profileBtn = document.getElementById('mobileProfileBtn');
            const notifBtn = document.getElementById('mobileNotifBtn');
            const logoutBtn = document.getElementById('mobileLogoutBtn');
            
            if (profileBtn) {
                profileBtn.addEventListener('mousedown', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    closeMobileMenu();
                    setTimeout(() => {
                        if (typeof showProfileModal === 'function') {
                            showProfileModal();
                        }
                    }, 100);
                });
            }
            
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
    </script>

</body>
</html>
