<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['role'] !== 'penyewa') {
    header("Location: ../auth/loginForm.php");
    exit();
}

include '../config/db.php';

// =======================================================
// BAGIAN 1: PENGAMBILAN DATA (LOGIC)
// =======================================================

$id_penyewa = $_SESSION['user_id'];
$userName = htmlspecialchars($_SESSION['fullname'] ?? 'Penyewa Kos');
$userRole = htmlspecialchars($_SESSION['role'] ?? 'penyewa');
$userPhoto = $_SESSION['foto_profil'] ?? null;

$kost_details = null;
$available_rooms = [];

// --- Halaman ini sekarang hanya untuk detail kos ---
if (!isset($_GET['kostId']) || empty($_GET['kostId'])) {
    header("Location: ../dashboard/dashboarduser.php"); // Redirect jika tidak ada ID Kos
    exit();
}

    $id_kost = (int)$_GET['kostId'];

    // Ambil detail Kos
    try {
        $stmt_kost = $conn->prepare("SELECT nama_kost, alamat, deskripsi, fasilitas, gambar FROM kost WHERE id_kost = ?");
        $stmt_kost->bind_param("i", $id_kost);
        $stmt_kost->execute();
        $kost_details = $stmt_kost->get_result()->fetch_assoc();
        $stmt_kost->close();
    } catch (mysqli_sql_exception $e) {
        // Fallback jika kolom 'gambar' tidak ada
        $stmt_kost = $conn->prepare("SELECT nama_kost, alamat, deskripsi, fasilitas FROM kost WHERE id_kost = ?");
        $stmt_kost->bind_param("i", $id_kost);
        $stmt_kost->execute();
        $kost_details = $stmt_kost->get_result()->fetch_assoc();
        $stmt_kost->close();
    }

    // Ambil kamar yang tersedia untuk Kos tersebut
    if ($kost_details) {
        $stmt_rooms = $conn->prepare("SELECT id_kamar, nama_kamar, harga, status FROM kamar WHERE id_kost = ? AND status = 'tersedia'");
        $stmt_rooms->bind_param("i", $id_kost);
        $stmt_rooms->execute();
        $result_rooms = $stmt_rooms->get_result();
        while ($row = $result_rooms->fetch_assoc()) {
            $available_rooms[] = $row;
        }
        $stmt_rooms->close();
    } 


$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>Detail Kos - KosConnect</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        html { scroll-behavior: smooth; }
        
        /* Reset Button Styles */
        button {
            all: unset;
            display: inline-block;
        }
        
        /* Animations */
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes slideInRight { from { opacity: 0; transform: translateX(30px); } to { opacity: 1; transform: translateX(0); } }
        @keyframes pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.05); } }
        @keyframes bounce { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-5px); } }
        @keyframes shimmer { 0% { background-position: -1000px 0; } 100% { background-position: 1000px 0; } }
        
        /* User Info Box */
        .user-info-box {
            display: flex;
            align-items: center;
            padding: 8px 12px;
            border-radius: 9999px;
            background-color: #f3f4f6;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .user-info-box:hover {
            background-color: #e5e7eb;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        /* Hero Section */
        .hero-section {
            position: relative;
            background: linear-gradient(135deg, rgba(147, 51, 234, 0.9) 0%, rgba(79, 70, 229, 0.9) 100%);
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at top right, rgba(255,255,255,0.1) 0%, transparent 60%);
        }
        
        /* Room Cards */
        .room-card {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            border: 1px solid #e5e7eb;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .room-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.6s;
            z-index: 1;
        }
        
        .room-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px rgba(147, 51, 234, 0.15);
            border-color: rgba(147, 51, 234, 0.2);
        }
        
        .room-card:hover::before {
            left: 100%;
        }
        
        .room-card:hover .room-image {
            transform: scale(1.1);
        }
        
        .room-image {
            transition: transform 0.6s ease;
            height: 200px;
            background: linear-gradient(135deg, #f3e8ff 0%, #e0e7ff 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #d8b4fe;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            animation: slideInRight 0.4s ease-out;
        }
        .status-aktif { 
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
        }
        .status-pending { 
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.2);
        }
        .status-menunggu_pembayaran { 
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1e40af;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
        }
        .status-selesai { 
            background: linear-gradient(135deg, #e5e7eb 0%, #d1d5db 100%);
            color: #374151;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        /* Price Tag */
        .price-tag {
            background: linear-gradient(135deg, #9333ea 0%, #7c3aed 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 1.75rem;
            font-weight: 800;
        }
        
        /* Booking Button */
        .book-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            background: linear-gradient(135deg, #9333ea 0%, #7c3aed 100%);
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
            padding: 0.6rem 1.2rem;
            border-radius: 0.5rem;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
            box-shadow: 0 2px 6px rgba(147, 51, 234, 0.2);
            text-decoration: none;
            white-space: nowrap;
        }
        
        .book-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.1);
            transition: left 0.3s ease;
            z-index: 1;
        }
        
        .book-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(147, 51, 234, 0.25);
            background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
        }
        
        .book-btn:hover::before {
            left: 100%;
        }
        
        .book-btn:active {
            transform: translateY(0);
            box-shadow: 0 1px 3px rgba(147, 51, 234, 0.1);
        }
        
        .book-btn i {
            font-size: 0.8rem;
        }
        
        .book-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        
        /* Button Width Utilities */
        button.w-full.book-btn {
            display: flex;
            width: 100%;
        }
        
        .room-card .book-btn {
            width: 100%;
        }
        
        /* Fasilitas */
        .facilities-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .facility-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            background: linear-gradient(135deg, #f3e8ff 0%, #e0e7ff 100%);
            border-radius: 0.75rem;
            border-left: 4px solid #9333ea;
            transition: all 0.3s ease;
        }
        
        .facility-item:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(147, 51, 234, 0.2);
        }
        
        .facility-icon {
            color: #9333ea;
            font-size: 1.5rem;
            margin-right: 1rem;
            min-width: 1.5rem;
        }
        
        /* Gradient Text */
        .gradient-text {
            background: linear-gradient(135deg, #9333ea 0%, #7c3aed 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        /* Section */
        section {
            animation: fadeIn 0.6s ease-out;
        }
        
        /* Back Button */
        .back-btn {
            transition: all 0.3s ease;
        }
        
        .back-btn:hover {
            transform: translateX(-5px);
        }
        
        /* No Rooms */
        .no-rooms-state {
            text-align: center;
            padding: 3rem 1rem;
        }
        
        .no-rooms-icon {
            font-size: 4rem;
            color: #d1d5db;
            margin-bottom: 1rem;
            animation: bounce 2s ease-in-out infinite;
        }
        
        /* Room Card Button Wrapper */
        .room-card .book-btn {
            margin-top: auto;
            width: 100%;
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
        
        /* Responsive */
        @media (max-width: 768px) {
            nav {
                padding: 0.5rem 0 !important;
            }

            nav .flex.justify-between {
                gap: 0.75rem !important;
            }

            h1 {
                font-size: 1.75rem !important;
            }

            h2 {
                font-size: 1.25rem !important;
            }

            h3 {
                font-size: 1.1rem !important;
            }

            p {
                font-size: 0.9rem !important;
            }

            .hidden.md\:flex {
                display: none !important;
            }

            #mobileMenuBtn {
                display: flex !important;
            }

            #mobileMenuPanel {
                width: 80vw !important;
                max-width: 320px !important;
                transform: translateX(100%);
            }

            .max-w-7xl {
                padding: 1rem !important;
            }

            .px-4 {
                padding-left: 1rem !important;
                padding-right: 1rem !important;
            }

            .px-6 {
                padding-left: 1rem !important;
                padding-right: 1rem !important;
            }

            .py-4 {
                padding-top: 0.75rem !important;
                padding-bottom: 0.75rem !important;
            }

            .py-6 {
                padding-top: 1rem !important;
                padding-bottom: 1rem !important;
            }

            .py-8 {
                padding-top: 1rem !important;
                padding-bottom: 1rem !important;
            }

            .gap-4 {
                gap: 0.75rem !important;
            }

            .gap-6 {
                gap: 0.75rem !important;
            }

            .space-y-4 > * + * {
                margin-top: 0.75rem !important;
            }

            .space-y-6 > * + * {
                margin-top: 1rem !important;
            }

            .space-x-4 > * + * {
                margin-left: 0.75rem !important;
            }

            .space-x-6 > * + * {
                margin-left: 0.75rem !important;
            }

            .hero-section {
                padding: 1.5rem 1rem !important;
            }

            .room-card {
                padding: 1rem !important;
                border-radius: 0.75rem !important;
            }

            .room-card:hover {
                transform: translateY(-4px) scale(1.01) !important;
                box-shadow: 0 10px 20px rgba(147, 51, 234, 0.1) !important;
            }

            .room-card img {
                height: 200px !important;
                object-fit: cover !important;
            }

            .room-card h3 {
                font-size: 1rem !important;
                line-height: 1.2 !important;
            }

            .room-card p {
                font-size: 0.85rem !important;
            }

            .price-tag {
                font-size: 1.3rem !important;
            }

            .facilities-container {
                grid-template-columns: 1fr !important;
                gap: 0.75rem !important;
            }

            .grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)) !important;
                gap: 0.75rem !important;
            }

            .grid.grid-cols-1 {
                grid-template-columns: 1fr !important;
            }

            .grid.grid-cols-2 {
                grid-template-columns: repeat(2, 1fr) !important;
            }

            .grid.grid-cols-3 {
                grid-template-columns: 1fr !important;
            }

            .text-sm {
                font-size: 0.75rem !important;
            }

            .text-lg {
                font-size: 1.1rem !important;
            }

            .text-xl {
                font-size: 1.25rem !important;
            }

            .text-2xl {
                font-size: 1.5rem !important;
            }

            .text-3xl {
                font-size: 1.75rem !important;
            }

            button {
                padding: 0.75rem 1rem !important;
                font-size: 0.9rem !important;
                width: auto !important;
            }

            .btn-primary,
            .bg-gradient-to-r.from-purple-600 {
                width: 100% !important;
                padding: 0.75rem 1rem !important;
            }

            input,
            textarea,
            select {
                padding: 0.75rem !important;
                font-size: 1rem !important;
                border-radius: 0.5rem !important;
            }

            .flex {
                flex-direction: row !important;
                flex-wrap: wrap !important;
                gap: 0.75rem !important;
            }

            .flex.flex-col {
                flex-direction: column !important;
                gap: 0.75rem !important;
            }

            .flex.justify-between {
                justify-content: space-between !important;
                gap: 0.5rem !important;
            }

            .flex.items-center {
                gap: 0.5rem !important;
            }

            .flex.space-x-4 {
                gap: 0.75rem !important;
            }

            .flex.space-x-6 {
                gap: 0.75rem !important;
            }

            .rounded-lg {
                border-radius: 0.5rem !important;
            }

            .rounded-xl {
                border-radius: 0.75rem !important;
            }

            .shadow-lg {
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08) !important;
            }

            .shadow-xl {
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08) !important;
            }

            .m-4 {
                margin: 0.75rem !important;
            }

            .m-6 {
                margin: 1rem !important;
            }

            .mt-4 {
                margin-top: 0.75rem !important;
            }

            .mt-6 {
                margin-top: 1rem !important;
            }

            .mb-4 {
                margin-bottom: 0.75rem !important;
            }

            .mb-6 {
                margin-bottom: 1rem !important;
            }

            .w-full {
                width: 100% !important;
            }

            .w-auto {
                width: auto !important;
            }

            table {
                font-size: 0.8rem !important;
            }

            th,
            td {
                padding: 0.5rem !important;
            }

            .badge,
            .tag {
                font-size: 0.75rem !important;
                padding: 0.25rem 0.5rem !important;
            }
        }

        @media (max-width: 640px) {
            h1 {
                font-size: 1.5rem !important;
            }

            h2 {
                font-size: 1.1rem !important;
            }

            h3 {
                font-size: 1rem !important;
            }

            p {
                font-size: 0.85rem !important;
            }

            .text-sm {
                font-size: 0.7rem !important;
            }

            .text-lg {
                font-size: 1rem !important;
            }

            .text-xl {
                font-size: 1.1rem !important;
            }

            .text-2xl {
                font-size: 1.25rem !important;
            }

            .text-3xl {
                font-size: 1.5rem !important;
            }

            .grid {
                grid-template-columns: 1fr !important;
                gap: 0.5rem !important;
            }

            .grid.grid-cols-2 {
                grid-template-columns: 1fr !important;
            }

            .room-card {
                padding: 0.75rem !important;
                border-radius: 0.5rem !important;
            }

            .room-card img {
                height: 150px !important;
            }

            .price-tag {
                font-size: 1.1rem !important;
            }

            .px-4,
            .px-6 {
                padding-left: 0.75rem !important;
                padding-right: 0.75rem !important;
            }

            .py-4,
            .py-6,
            .py-8 {
                padding-top: 0.5rem !important;
                padding-bottom: 0.5rem !important;
            }

            .gap-4,
            .gap-6 {
                gap: 0.5rem !important;
            }

            .space-y-4 > * + *,
            .space-y-6 > * + * {
                margin-top: 0.5rem !important;
            }

            .space-x-4 > * + *,
            .space-x-6 > * + * {
                margin-left: 0.5rem !important;
            }

            .flex {
                flex-direction: column !important;
                gap: 0.5rem !important;
            }

            button {
                padding: 0.65rem 0.9rem !important;
                font-size: 0.85rem !important;
            }

            input,
            textarea,
            select {
                padding: 0.65rem !important;
                font-size: 1rem !important;
            }

            #mobileMenuPanel {
                width: 90vw !important;
                max-width: 300px !important;
            }

            .rounded-lg {
                border-radius: 0.375rem !important;
            }

            .rounded-xl {
                border-radius: 0.5rem !important;
            }

            .m-4,
            .m-6 {
                margin: 0.5rem !important;
            }

            .mt-4,
            .mt-6 {
                margin-top: 0.5rem !important;
            }

            .mb-4,
            .mb-6 {
                margin-bottom: 0.5rem !important;
            }

            .shadow-lg,
            .shadow-xl {
                box-shadow: 0 3px 10px rgba(0, 0, 0, 0.06) !important;
            }

            table {
                font-size: 0.75rem !important;
            }

            th,
            td {
                padding: 0.375rem !important;
            }
        }
    </style>
</head>
<body class="bg-gray-50 font-sans">

    <!-- Navigasi Konsisten -->
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
                        <a href="user_dashboard.php" class="text-gray-700 font-medium hover:text-purple-600 py-2">Dashboard</a>
                        <a href="../dashboard/dashboarduser.php#pilihan-kos" class="text-gray-700 font-medium hover:text-purple-600 py-2">Pilihan Kos</a>
                        <a href="wishlist.php" class="text-gray-700 font-medium hover:text-purple-600 py-2">Favorit</a>
                        <a href="feedback.php" class="text-gray-700 font-medium hover:text-purple-600 py-2">Feedback</a>
                    </nav>
                    <div class="flex items-center space-x-4 pl-6 border-l-2 border-gray-200">
                        <button id="notifBtn" onclick="showNotifications()" class="relative text-gray-600 hover:text-purple-600 p-2 rounded-lg hover:bg-purple-50 transition-all" aria-label="Notifikasi" title="Notifikasi">
                            <i class="fas fa-bell text-xl" aria-hidden="true"></i>
                            <?php 
                            include '../config/db.php';
                            $stmt_notif = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE id_user = ? AND is_read = 0");
                            $stmt_notif->bind_param("i", $id_penyewa);
                            $stmt_notif->execute();
                            $notif_result = $stmt_notif->get_result()->fetch_assoc();
                            $notif_count = $notif_result['count'];
                            $stmt_notif->close();
                            $conn->close();
                            ?>
                            <?php if ($notif_count > 0): ?>
                                <span id="notifBadge" class="absolute top-0 right-0 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center shadow-lg"><?php echo $notif_count; ?></span>
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
                
                <!-- Mobile Menu Button di Navbar -->
                <div class="flex md:hidden items-center space-x-2">
                    <button id="notifBtn" onclick="showNotifications()" class="relative text-gray-600 hover:text-purple-600 p-2 rounded-lg hover:bg-purple-50 transition-all" aria-label="Notifikasi" title="Notifikasi">
                        <i class="fas fa-bell text-xl" aria-hidden="true"></i>
                        <?php 
                        if ($notif_count > 0): ?>
                            <span id="notifBadge" class="absolute top-0 right-0 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center shadow-lg"><?php echo $notif_count; ?></span>
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
            <div class="p-4 space-y-3" style="pointer-events: auto;">
                <button id="mobileProfileBtn" class="w-full px-4 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-lg font-medium hover:from-purple-700 hover:to-indigo-700 transition-all flex items-center justify-center" type="button" style="pointer-events: auto; cursor: pointer;">
                    <i class="fas fa-user mr-2"></i>Profil
                </button>
                <button id="mobileNotifBtn" class="w-full px-4 py-3 bg-blue-500 text-white rounded-lg font-medium hover:bg-blue-600 transition-all flex items-center justify-center" type="button" style="pointer-events: auto; cursor: pointer;">
                    <i class="fas fa-bell mr-2"></i>Notifikasi
                </button>
                <button id="mobileLogoutBtn" class="w-full px-4 py-3 bg-red-500 text-white rounded-lg font-medium hover:bg-red-600 transition-all text-center flex items-center justify-center" type="button" style="pointer-events: auto; cursor: pointer;">
                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                </button>
            </div>
        </div>
    </div>

    <main class="pt-20 pb-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <?php if ($kost_details): ?>
            
            <!-- Back Button -->
            <a href="../dashboard/dashboarduser.php#pilihan-kos" class="inline-flex items-center text-purple-600 hover:text-purple-700 mb-8 back-btn group">
                <i class="fas fa-arrow-left mr-2 group-hover:mr-3 transition-all"></i>
                <span class="font-medium">Kembali ke Daftar Kos</span>
            </a>

            <!-- Hero Section -->
            <section class="hero-section rounded-3xl shadow-2xl mb-16 p-12 text-white relative overflow-hidden" style="animation: slideUp 0.6s ease-out;">
                <div class="relative z-10">
                    <div class="inline-block bg-white bg-opacity-20 backdrop-blur-sm text-white px-4 py-2 rounded-full text-sm font-semibold mb-4 animation: slideInRight 0.8s ease-out;">
                        <i class="fas fa-star mr-1 text-yellow-300"></i>Pilihan Unggulan
                    </div>
                    <h1 class="text-5xl md:text-6xl font-extrabold mb-4 leading-tight" style="animation: slideUp 0.8s ease-out;">
                        <?php echo htmlspecialchars($kost_details['nama_kost']); ?>
                    </h1>
                    <p class="text-xl md:text-2xl text-purple-100 mb-6 flex items-center" style="animation: slideUp 1s ease-out;">
                        <i class="fas fa-map-marker-alt mr-3 text-2xl"></i>
                        <?php echo htmlspecialchars($kost_details['alamat']); ?>
                    </p>
                    
                    <!-- Quick Stats -->
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mt-8" style="animation: slideUp 1.2s ease-out;">
                        <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl p-4 border border-white border-opacity-20">
                            <div class="text-sm text-purple-100 mb-1">Kamar Tersedia</div>
                            <div class="text-3xl font-bold"><?php echo count($available_rooms); ?></div>
                        </div>
                        <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl p-4 border border-white border-opacity-20">
                            <div class="text-sm text-purple-100 mb-1">Rating</div>
                            <div class="text-3xl font-bold flex items-center">
                                <i class="fas fa-star text-yellow-300 mr-2"></i>4.8
                            </div>
                        </div>
                        <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl p-4 border border-white border-opacity-20 col-span-2 md:col-span-1">
                            <div class="text-sm text-purple-100 mb-1">Harga Mulai</div>
                            <div class="text-2xl font-bold">Rp <?php echo number_format(min(array_column($available_rooms, 'harga')), 0, ',', '.'); ?></div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Content Section -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-16">
                <!-- Main Content (2/3) -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Tentang Kos -->
                    <div class="bg-white rounded-2xl shadow-lg p-8 border border-gray-100" style="animation: slideUp 0.8s ease-out;">
                        <div class="flex items-center mb-6">
                            <div class="w-12 h-12 bg-gradient-to-br from-purple-400 to-purple-600 rounded-lg flex items-center justify-center text-white text-xl mr-4">
                                <i class="fas fa-info-circle"></i>
                            </div>
                            <h2 class="text-3xl font-bold text-gray-900">Tentang Kos Ini</h2>
                        </div>
                        <p class="text-gray-600 leading-relaxed text-lg">
                            <?php echo nl2br(htmlspecialchars($kost_details['deskripsi'])); ?>
                        </p>
                    </div>

                    <!-- Fasilitas -->
                    <div class="bg-white rounded-2xl shadow-lg p-8 border border-gray-100" style="animation: slideUp 1s ease-out;">
                        <div class="flex items-center mb-6">
                            <div class="w-12 h-12 bg-gradient-to-br from-indigo-400 to-indigo-600 rounded-lg flex items-center justify-center text-white text-xl mr-4">
                                <i class="fas fa-home"></i>
                            </div>
                            <h3 class="text-3xl font-bold text-gray-900">Fasilitas Utama</h3>
                        </div>
                        <div class="facilities-container">
                            <?php 
                            $fasilitas = array_filter(array_map('trim', explode(',', $kost_details['fasilitas'])));
                            $icons = ['WiFi' => 'fas fa-wifi', 'Listrik' => 'fas fa-plug', 'Air' => 'fas fa-droplet', 
                                     'Kamar Mandi' => 'fas fa-bath', 'Dapur' => 'fas fa-utensils', 'Parkir' => 'fas fa-car',
                                     'AC' => 'fas fa-snowflake', 'Ranjang' => 'fas fa-bed', 'Lemari' => 'fas fa-cabinet',
                                     'Meja' => 'fas fa-table', 'Kursi' => 'fas fa-chair', 'Pintu' => 'fas fa-door-open'];
                            $fasilitas_list = [];
                            foreach ($fasilitas as $f) {
                                foreach ($icons as $key => $icon) {
                                    if (stripos($f, $key) !== false) {
                                        $fasilitas_list[] = ['name' => $f, 'icon' => $icon];
                                        break;
                                    }
                                }
                                if (count($fasilitas_list) === count($fasilitas)) break;
                            }
                            foreach ($fasilitas_list as $item):
                            ?>
                            <div class="facility-item">
                                <i class="<?php echo $item['icon']; ?> facility-icon"></i>
                                <span class="text-gray-700 font-medium"><?php echo $item['name']; ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Sidebar (1/3) -->
                <div class="space-y-6" style="animation: slideInRight 0.8s ease-out;">
                    <!-- Price Card -->
                    <div class="bg-gradient-to-br from-purple-50 to-indigo-50 rounded-2xl shadow-lg p-8 border border-purple-100 sticky top-24">
                        <div class="text-center mb-6">
                            <p class="text-gray-600 text-sm mb-2">Harga Mulai Dari</p>
                            <p class="price-tag">Rp <?php echo number_format(min(array_column($available_rooms, 'harga')), 0, ',', '.'); ?></p>
                            <p class="text-gray-500 text-sm mt-1">per bulan</p>
                        </div>
                        
                        <!-- Quick Info -->
                        <div class="space-y-3 mb-6 pb-6 border-b-2 border-purple-200">
                            <div class="flex items-center text-sm text-gray-700">
                                <i class="fas fa-door-open text-purple-600 mr-3 w-5"></i>
                                <span><strong><?php echo count($available_rooms); ?></strong> kamar tersedia</span>
                            </div>
                            <div class="flex items-center text-sm text-gray-700">
                                <i class="fas fa-star text-yellow-400 mr-3 w-5"></i>
                                <span><strong>4.8</strong> rating</span>
                            </div>
                            <div class="flex items-center text-sm text-gray-700">
                                <i class="fas fa-users text-purple-600 mr-3 w-5"></i>
                                <span><strong>200+</strong> pengguna aktif</span>
                            </div>
                        </div>

                        <!-- CTA -->
                        <button onclick="document.getElementById('kamar-section').scrollIntoView({behavior: 'smooth'})" class="w-full book-btn">
                            <i class="fas fa-arrow-down"></i>Lihat Kamar
                        </button>
                    </div>

                    <!-- Info Box -->
                    <div class="bg-blue-50 rounded-2xl shadow-lg p-6 border border-blue-100">
                        <div class="flex items-start">
                            <i class="fas fa-lightbulb text-blue-600 text-2xl mr-4 mt-1 flex-shrink-0"></i>
                            <div>
                                <h4 class="font-bold text-blue-900 mb-2">Tanya Pemilik?</h4>
                                <p class="text-sm text-blue-800 mb-3">
                                    Hubungi pemilik kos untuk menanyakan detail lebih lanjut tentang kamar atau fasilitas.
                                </p>
                                <button class="text-blue-600 hover:text-blue-700 font-semibold text-sm flex items-center">
                                    <i class="fas fa-phone mr-2"></i>Hubungi Sekarang
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Available Rooms Section -->
            <section id="kamar-section" class="mb-12" style="animation: slideUp 1.2s ease-out;">
                <div class="mb-10">
                    <h2 class="text-4xl font-extrabold text-gray-900 mb-3">
                        <i class="fas fa-door-open text-purple-600 mr-3"></i>Kamar yang Tersedia
                    </h2>
                    <p class="text-gray-600 text-lg">Pilih kamar favorit Anda dan lakukan booking sekarang</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <?php if (!empty($available_rooms)): ?>
                        <?php foreach ($available_rooms as $index => $room): ?>
                        <div class="bg-white rounded-2xl shadow-lg overflow-hidden room-card" style="animation: slideUp <?php echo 0.8 + ($index * 0.15); ?>s ease-out;">
                            <!-- Room Image -->
                            <div class="room-image">
                                <i class="fas fa-bed"></i>
                            </div>

                            <!-- Room Info -->
                            <div class="p-6 flex flex-col h-full">
                                <div class="flex justify-between items-start mb-4">
                                    <div>
                                        <h3 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($room['nama_kamar']); ?></h3>
                                        <p class="text-sm text-gray-500 mt-1">
                                            <i class="fas fa-id-card mr-1"></i>ID: <?php echo $room['id_kamar']; ?>
                                        </p>
                                    </div>
                                    <span class="status-badge status-aktif">Tersedia</span>
                                </div>

                                <!-- Price -->
                                <div class="mb-4 pb-4 border-b-2 border-gray-100">
                                    <p class="text-gray-600 text-sm mb-1">Harga Sewa Per Bulan</p>
                                    <p class="price-tag">Rp <?php echo number_format($room['harga'], 0, ',', '.'); ?></p>
                                </div>

                                <!-- Features -->
                                <div class="space-y-2 mb-6 flex-grow">
                                    <div class="flex items-center text-sm text-gray-700">
                                        <i class="fas fa-check text-green-500 mr-2 w-4"></i>
                                        <span>Fasilitas lengkap</span>
                                    </div>
                                    <div class="flex items-center text-sm text-gray-700">
                                        <i class="fas fa-check text-green-500 mr-2 w-4"></i>
                                        <span>Lokasi strategis</span>
                                    </div>
                                    <div class="flex items-center text-sm text-gray-700">
                                        <i class="fas fa-check text-green-500 mr-2 w-4"></i>
                                        <span>Aman dan nyaman</span>
                                    </div>
                                </div>

                                <!-- Action Button Wrapper -->
                                <div class="mt-6 pt-4 border-t border-gray-100">
                                    <button onclick="bookRoom(<?php echo $room['id_kamar']; ?>)" class="w-full book-btn">
                                        <i class="fas fa-calendar-check"></i>Book Sekarang
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-rooms-state md:col-span-3">
                            <i class="fas fa-door-open no-rooms-icon"></i>
                            <h3 class="text-2xl font-bold text-gray-700 mb-2">Tidak Ada Kamar Tersedia</h3>
                            <p class="text-gray-500 text-lg mb-6">
                                Mohon maaf, saat ini belum ada kamar yang tersedia di kos ini.
                            </p>
                            <p class="text-gray-400 mb-6">
                                Silakan periksa kembali nanti atau lihat pilihan kos lainnya.
                            </p>
                            <a href="../dashboard/dashboarduser.php#pilihan-kos" class="inline-block bg-gradient-to-r from-purple-600 to-indigo-600 text-white px-8 py-3 rounded-xl font-bold hover:from-purple-700 hover:to-indigo-700 transition-all shadow-lg">
                                <i class="fas fa-search mr-2"></i>Lihat Kos Lain
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <?php endif; ?>

        </div>
    </main>

    <?php include '_user_profile_modal.php'; ?>

    <script>
            (function(){
                try {
                    const newPhoto = localStorage.getItem('newProfilePhoto');
                    if (newPhoto) {
                        const ts = Date.now();
                        const url = `../uploads/profiles/${newPhoto}?t=${ts}`;
                        document.querySelectorAll('img').forEach(img => {
                            try { if (img.src && img.src.indexOf('/uploads/profiles/') !== -1) img.src = url; } catch(e){}
                        });
                        const headerNode = document.getElementById('headerUserPhoto');
                        if (headerNode && headerNode.tagName !== 'IMG') {
                            const img = document.createElement('img');
                            img.id = 'headerUserPhoto';
                            img.className = 'w-8 h-8 rounded-full object-cover';
                            img.src = url;
                            img.alt = 'Foto Profil';
                            headerNode.parentNode.replaceChild(img, headerNode);
                        }
                        localStorage.removeItem('newProfilePhoto');
                    }
                } catch (e) {}
            })();
        
        // Mobile Menu Script
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

        function bookRoom(id_kamar) {
            Swal.fire({
                title: 'Konfirmasi Booking',
                text: 'Anda akan memesan kamar ini. Lanjutkan?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Book Sekarang',
                cancelButtonText: 'Batal',
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    // Kirim request AJAX ke backend untuk memproses booking
                    const formData = new FormData();
                    formData.append('id_kamar', id_kamar);

                    return fetch('process_booking.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                            // Jika server mengembalikan error (misal: kamar sudah dipesan)
                            return response.json().then(err => { throw new Error(err.message) });
                        }
                        return response.json();
                    })
                    .catch(error => {
                        Swal.showValidationMessage(`Request gagal: ${error}`);
                    });
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
                if (result.isConfirmed) {
                    // Jika booking berhasil, tampilkan notifikasi sukses dan redirect
                    Swal.fire({
                        title: '<strong class="text-2xl">🎉 Booking Berhasil!</strong>',
                        html: `
                            <div class="text-left space-y-4 p-4">
                                <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-lg">
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-info-circle text-blue-500 text-xl mt-1"></i>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm text-blue-800 font-medium">
                                                Pesanan Anda sedang menunggu konfirmasi dari pemilik kos
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-lg">
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-bell text-green-500 text-xl mt-1"></i>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm text-green-800 font-medium">
                                                Kami akan memberitahu Anda melalui notifikasi segera setelah pesanan dikonfirmasi
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-center pt-2">
                                    <p class="text-gray-600 text-sm">
                                        <i class="fas fa-clock mr-1"></i>
                                        Biasanya konfirmasi memakan waktu kurang dari 24 jam
                                    </p>
                                </div>
                            </div>
                        `,
                        icon: 'success',
                        confirmButtonText: '<i class="fas fa-history mr-2"></i>Lihat Riwayat Booking',
                        confirmButtonColor: '#9333ea',
                        customClass: {
                            popup: 'rounded-2xl',
                            confirmButton: 'rounded-xl font-semibold px-6 py-3',
                            htmlContainer: 'p-0'
                        },
                        showClass: {
                            popup: 'animate__animated animate__fadeInDown'
                        }
                    }).then(() => {
                        // Arahkan ke halaman riwayat booking
                        window.location.href = 'booking.php';
                    });
                }
            });
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
                    // Ambil URL dari elemen yang diklik dan arahkan ke sana
                    window.location.href = event.target.closest('a').href;
                }
            });
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
    </script>

</body>
</html>