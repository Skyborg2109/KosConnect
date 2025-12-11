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
$userEmail = $_SESSION['email'] ?? '';
$userPhoto = $_SESSION['foto_profil'] ?? null;
$id_penyewa = $_SESSION['user_id'];

// Ambil jumlah notifikasi yang belum dibaca dari database
$stmt_notif = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE id_user = ? AND is_read = 0");
$stmt_notif->bind_param("i", $id_penyewa);
$stmt_notif->execute();
$notif_count = $stmt_notif->get_result()->fetch_assoc()['count'];
$stmt_notif->close();

// Ambil data Kos untuk ditampilkan
$search = $_GET['search'] ?? '';
$list_kost = [];
$wishlist_kost = []; // Store wishlist items

try {
    // Coba query dengan kolom 'gambar'
    $sql = "SELECT id_kost, nama_kost, alamat, deskripsi, fasilitas, gambar, harga FROM kost WHERE status_kos = 'tersedia'";
    if (!empty($search)) {
        $sql .= " AND (nama_kost LIKE ? OR alamat LIKE ?)";
    }
    $sql .= " ORDER BY id_kost DESC";
    $stmt = $conn->prepare($sql);
} catch (mysqli_sql_exception $e) {
    // Fallback jika kolom 'gambar' tidak ada
    $sql = "SELECT id_kost, nama_kost, alamat, deskripsi, fasilitas, harga FROM kost WHERE status_kos = 'tersedia'";
    if (!empty($search)) {
        $sql .= " AND (nama_kost LIKE ? OR alamat LIKE ?)";
    }
    $sql .= " ORDER BY id_kost DESC";
    $stmt = $conn->prepare($sql);
}

if (!empty($search)) {
    $searchTerm = "%" . $search . "%";
    $stmt->bind_param("ss", $searchTerm, $searchTerm);
}

$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $list_kost[] = $row;
}
$stmt->close();

// Ambil wishlist user
try {
    $stmt_wishlist = $conn->prepare("SELECT id_kost FROM wishlist WHERE id_user = ?");
    $stmt_wishlist->bind_param("i", $id_penyewa);
    $stmt_wishlist->execute();
    $wishlist_result = $stmt_wishlist->get_result();
    while ($row = $wishlist_result->fetch_assoc()) {
        $wishlist_kost[] = $row['id_kost'];
    }
    $stmt_wishlist->close();
} catch (Exception $e) {
    // Silently fail if wishlist table doesn't exist yet
    $wishlist_kost = [];
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda - KosConnect</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        html { scroll-behavior: smooth; }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
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
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }
        
        @keyframes shimmer {
            0% { background-position: -1000px 0; }
            100% { background-position: 1000px 0; }
        }
        
        @keyframes fadeOut {
            from { opacity: 1; transform: scale(1); }
            to { opacity: 0; transform: scale(0.8); }
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
        
        /* Card Hover Effects */
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
            box-shadow: 0 20px 40px rgba(147, 51, 234, 0.15);
        }
        
        .card-hover:hover::before {
            left: 100%;
        }
        
        /* Hero Background */
        .hero-bg {
            background-image: linear-gradient(135deg, rgba(147, 51, 234, 0.8) 0%, rgba(79, 70, 229, 0.8) 100%), url('../img/kost4.png');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            position: relative;
        }
        
        .hero-bg::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at top right, rgba(255,255,255,0.1) 0%, transparent 60%);
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
            background: linear-gradient(90deg, #9333ea, #4f46e5);
            transition: width 0.3s ease;
        }
        
        nav a:hover::after,
        nav a.text-purple-600::after {
            width: 100%;
        }
        
        /* Buttons */
        button, .btn {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        button:hover:not(:disabled), .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }
        
        button:active:not(:disabled), .btn:active {
            transform: translateY(0);
        }
        
        /* Notification Badge */
        #notifBadge {
            animation: pulse 2s infinite;
        }
        
        /* Icon Bounce */
        .icon-bounce:hover i {
            animation: bounce 0.6s ease;
        }
        
        /* Search Input */
        .search-input {
            transition: all 0.3s ease;
        }
        
        .search-input:focus {
            transform: scale(1.02);
            box-shadow: 0 8px 30px rgba(147, 51, 234, 0.3);
        }
        
        /* Feature Cards */
        .feature-card {
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
        }
        
        .feature-card:hover .feature-icon {
            transform: scale(1.1) rotate(5deg);
        }
        
        .feature-icon {
            transition: all 0.3s ease;
        }
        
        /* Section Reveal */
        section {
            animation: fadeIn 0.6s ease-out;
        }
        
        /* Gradient Text */
        .gradient-text {
            background: linear-gradient(135deg, #9333ea 0%, #4f46e5 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #9333ea, #4f46e5);
            border-radius: 5px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #7e22ce, #4338ca);
        }
        
        /* Loading Skeleton */
        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 1000px 100%;
            animation: shimmer 2s infinite;
        }
        
        /* Mobile Menu */
        .mobile-menu {
            transform: translateX(100%);
            transition: transform 0.3s ease;
        }
        
        .mobile-menu.active {
            transform: translateX(0);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .card-hover:hover {
                transform: translateY(-4px) scale(1.01);
            }
            
            .hero-bg {
                background-attachment: scroll;
            }
        }

        /* Wishlist Button Styles */
        .wishlist-btn {
            font-size: 1.5em;
            transition: all 0.3s ease;
        }

        .wishlist-btn:hover {
            transform: scale(1.15);
        }

        .wishlist-btn.favorited {
            background-color: #fee2e2 !important;
            color: #dc2626 !important;
        }

        .wishlist-btn i {
            transition: all 0.3s ease;
        }

        .wishlist-btn.favorited i {
            font-weight: 900;
        }
    </style>
</head>
<body class="bg-gray-50 font-sans">

    <!-- Navigasi Konsisten -->
    <nav class="bg-white shadow-lg fixed w-full top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <a href="dashboarduser.php" class="flex items-center group">
                    <div class="w-10 h-10 bg-gradient-to-br from-purple-600 to-indigo-600 rounded-lg flex items-center justify-center mr-2 group-hover:scale-110 transition-transform">
                        <i class="fas fa-home text-white"></i>
                    </div>
                    <h1 class="text-2xl font-bold bg-gradient-to-r from-purple-600 to-indigo-600 bg-clip-text text-transparent">Kos<span class="text-purple-600">Connect</span></h1>
                </a>
                <div class="hidden md:flex items-center space-x-6">
                    <nav class="flex space-x-8">
                        <a href="dashboarduser.php" class="text-purple-600 font-semibold hover:text-purple-700 py-2">Beranda</a>
                        <a href="../user/user_dashboard.php" class="text-gray-700 font-medium hover:text-purple-600 py-2">Dashboard</a>
                        <a href="#pilihan-kos" class="text-gray-700 font-medium hover:text-purple-600 py-2">Pilihan Kos</a>
                        <a href="../user/wishlist.php" class="text-gray-700 font-medium hover:text-purple-600 py-2">Favorit</a>
                        <a href="../user/feedback.php" class="text-gray-700 font-medium hover:text-purple-600 py-2">Feedback</a>
                        <a href="#kontak" class="text-gray-700 font-medium hover:text-purple-600 py-2 transition-colors">Kontak</a>
                    </nav>
                    <div class="flex items-center space-x-4 pl-6 border-l-2 border-gray-200">
                        <button id="notifBtn" onclick="showNotifications()" class="relative text-gray-600 hover:text-purple-600 p-2 rounded-lg hover:bg-purple-50 transition-all" aria-label="Notifikasi" title="Notifikasi">
                            <i class="fas fa-bell text-xl" aria-hidden="true"></i>
                            <?php if ($notif_count > 0): ?>
                                <span id="notifBadge" class="absolute top-0 right-0 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center shadow-lg"><?php echo $notif_count; ?></span>
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
                <div class="flex md:hidden items-center gap-0.5">
                    <button id="notifBtn" onclick="showNotifications()" class="relative text-gray-600 hover:text-purple-600 px-1.5 py-2 rounded-lg hover:bg-purple-50 transition-all h-full flex items-center justify-center" aria-label="Notifikasi" title="Notifikasi">
                        <i class="fas fa-bell text-sm sm:text-base" aria-hidden="true"></i>
                        <?php if ($notif_count > 0): ?>
                            <span id="notifBadge" class="absolute -top-1 -right-0.5 bg-red-500 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center shadow-md animate-pulse" style="font-size: 0.65rem;"><span style="display:flex;align-items:center;justify-content:center;width:100%;height:100%;"><?php echo $notif_count; ?></span></span>
                        <?php endif; ?>
                    </button>
                    <button id="mobileMenuBtn" class="px-1.5 py-2 text-gray-600 hover:text-purple-600 hover:bg-purple-50 rounded-lg transition-colors h-full flex items-center justify-center" onclick="toggleMobileMenu()" title="Menu">
                        <i class="fas fa-bars text-sm sm:text-base"></i>
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
                <a href="dashboarduser.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-purple-50 hover:text-purple-600 rounded-lg font-medium transition-colors mobile-menu-link" onclick="handleMobileMenuClick(event)">
                    <i class="fas fa-home mr-3 text-purple-600"></i>Beranda
                </a>
                <a href="../user/user_dashboard.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-purple-50 hover:text-purple-600 rounded-lg font-medium transition-colors mobile-menu-link" onclick="handleMobileMenuClick(event)">
                    <i class="fas fa-chart-line mr-3 text-blue-600"></i>Dashboard
                </a>
                <a href="dashboarduser.php#pilihan-kos" class="flex items-center px-4 py-3 text-gray-700 hover:bg-purple-50 hover:text-purple-600 rounded-lg font-medium transition-colors mobile-menu-link" onclick="handleMobileMenuClick(event)">
                    <i class="fas fa-building mr-3 text-orange-600"></i>Pilihan Kos
                </a>
                <a href="../user/wishlist.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-purple-50 hover:text-purple-600 rounded-lg font-medium transition-colors mobile-menu-link" onclick="handleMobileMenuClick(event)">
                    <i class="fas fa-heart mr-3 text-red-600"></i>Favorit
                </a>
                <a href="../user/feedback.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-purple-50 hover:text-purple-600 rounded-lg font-medium transition-colors mobile-menu-link" onclick="handleMobileMenuClick(event)">
                    <i class="fas fa-comment mr-3 text-green-600"></i>Feedback
                </a>
                <a href="dashboarduser.php#kontak" class="flex items-center px-4 py-3 text-gray-700 hover:bg-purple-50 hover:text-purple-600 rounded-lg font-medium transition-colors mobile-menu-link" onclick="handleMobileMenuClick(event)">
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

    <main>
        <!-- Hero Section -->
        <section class="hero-bg pt-24 sm:pt-32 lg:pt-40 pb-16 sm:pb-20 lg:pb-28 text-white text-center relative overflow-hidden">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 relative z-10">
                <div class="mb-4 sm:mb-6">
                    <span class="inline-block bg-white bg-opacity-20 backdrop-blur-sm text-white px-3 sm:px-4 py-1.5 sm:py-2 rounded-full text-xs sm:text-sm font-medium mb-3 sm:mb-4">
                        ✨ Platform Pencarian Kos Terpercaya
                    </span>
                </div>
                <h1 class="text-3xl sm:text-5xl lg:text-6xl font-extrabold mb-4 sm:mb-6 leading-tight" style="animation: slideUp 0.8s ease-out;">
                    Temukan Kos Impian Anda
                </h1>
                <p class="text-base sm:text-xl lg:text-2xl text-purple-100 mb-8 sm:mb-10 max-w-2xl mx-auto" style="animation: slideUp 1s ease-out;">
                    Cari dan booking kos idaman dengan mudah di seluruh penjuru kota. Proses cepat, aman, dan terpercaya.
                </p>
                <form action="dashboarduser.php#pilihan-kos" method="GET" class="max-w-2xl mx-auto" style="animation: slideUp 1.2s ease-out;">
                    <div class="relative group">
                        <input type="search" name="search" placeholder="Cari nama kos atau lokasi..." value="<?php echo htmlspecialchars($search); ?>" class="w-full p-3 sm:p-4 lg:p-5 pr-16 sm:pr-20 lg:pr-24 rounded-full sm:rounded-full text-gray-900 focus:outline-none focus:ring-4 focus:ring-purple-300 search-input shadow-2xl text-sm sm:text-base">
                        <button type="submit" class="absolute top-0 right-0 h-full px-4 sm:px-6 lg:px-8 text-white bg-gradient-to-r from-purple-600 to-indigo-600 rounded-full hover:from-purple-700 hover:to-indigo-700 transition-all shadow-lg text-sm sm:text-base">
                            <i class="fas fa-search mr-1 sm:mr-2"></i><span class="hidden sm:inline">Cari</span>
                        </button>
                    </div>
                </form>
                
                <!-- Stats -->
                <div class="grid grid-cols-3 gap-2 sm:gap-4 max-w-2xl mx-auto mt-8 sm:mt-12" style="animation: slideUp 1.4s ease-out;">
                    <div class="text-center">
                        <div class="text-2xl sm:text-3xl font-bold mb-0.5 sm:mb-1"><?php echo count($list_kost); ?>+</div>
                        <div class="text-purple-200 text-xs sm:text-sm">Kos Tersedia</div>
                    </div>
                    <div class="text-center border-l border-r border-purple-400 border-opacity-30">
                        <div class="text-2xl sm:text-3xl font-bold mb-0.5 sm:mb-1">1000+</div>
                        <div class="text-purple-200 text-xs sm:text-sm">Pengguna Aktif</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl sm:text-3xl font-bold mb-0.5 sm:mb-1">24/7</div>
                        <div class="text-purple-200 text-xs sm:text-sm">Layanan Support</div>
                    </div>
                </div>
            </div>
            
            <!-- Decorative elements -->
            <div class="absolute top-20 left-10 w-20 h-20 bg-white bg-opacity-10 rounded-full blur-xl"></div>
            <div class="absolute bottom-20 right-10 w-32 h-32 bg-purple-300 bg-opacity-20 rounded-full blur-2xl"></div>
        </section>

        <!-- How It Works Section -->
        <section id="cara-kerja" class="py-16 sm:py-20 lg:py-24 bg-gradient-to-b from-white to-purple-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-10 sm:mb-14 lg:mb-16">
                    <span class="inline-block bg-purple-100 text-purple-600 px-3 sm:px-4 py-1.5 sm:py-2 rounded-full text-xs sm:text-sm font-semibold mb-3 sm:mb-4">
                        <i class="fas fa-lightbulb mr-1 sm:mr-2"></i>Cara Kerja
                    </span>
                    <h2 class="text-3xl sm:text-4xl font-bold text-gray-800 mb-2 sm:mb-4">Bagaimana Caranya?</h2>
                    <p class="text-gray-600 text-sm sm:text-base lg:text-lg max-w-2xl mx-auto">Hanya 3 langkah mudah untuk mendapatkan kos impian Anda.</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8 lg:gap-10">
                    <div class="feature-card text-center p-6 sm:p-8 bg-white rounded-xl sm:rounded-2xl shadow-md lg:shadow-lg hover:shadow-lg lg:hover:shadow-2xl transition-all">
                        <div class="relative inline-block mb-4 sm:mb-6">
                            <div class="feature-icon flex items-center justify-center h-20 sm:h-24 w-20 sm:w-24 mx-auto bg-gradient-to-br from-purple-400 to-purple-600 text-white rounded-lg sm:rounded-2xl text-3xl sm:text-4xl shadow-lg lg:shadow-xl">
                                <i class="fas fa-search-location"></i>
                            </div>
                            <div class="absolute -top-2 -right-2 w-7 sm:w-8 h-7 sm:h-8 bg-purple-600 text-white rounded-full flex items-center justify-center font-bold text-xs sm:text-sm shadow-lg">
                                1
                            </div>
                        </div>
                        <h3 class="text-lg sm:text-2xl font-bold mb-2 sm:mb-3 text-gray-800">Cari Kos</h3>
                        <p class="text-gray-600 leading-relaxed text-sm sm:text-base">Gunakan fitur pencarian untuk menemukan kos yang sesuai dengan kriteria dan budget Anda.</p>
                    </div>
                    <div class="feature-card text-center p-6 sm:p-8 bg-white rounded-xl sm:rounded-2xl shadow-md lg:shadow-lg hover:shadow-lg lg:hover:shadow-2xl transition-all">
                        <div class="relative inline-block mb-4 sm:mb-6">
                            <div class="feature-icon flex items-center justify-center h-20 sm:h-24 w-20 sm:w-24 mx-auto bg-gradient-to-br from-blue-400 to-blue-600 text-white rounded-lg sm:rounded-2xl text-3xl sm:text-4xl shadow-lg lg:shadow-xl">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div class="absolute -top-2 -right-2 w-7 sm:w-8 h-7 sm:h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold text-xs sm:text-sm shadow-lg">
                                2
                            </div>
                        </div>
                        <h3 class="text-lg sm:text-2xl font-bold mb-2 sm:mb-3 text-gray-800">Booking Kamar</h3>
                        <p class="text-gray-600 leading-relaxed text-sm sm:text-base">Pilih kamar yang Anda inginkan dan lakukan booking secara online dengan mudah dan cepat.</p>
                    </div>
                    <div class="feature-card text-center p-6 sm:p-8 bg-white rounded-xl sm:rounded-2xl shadow-md lg:shadow-lg hover:shadow-lg lg:hover:shadow-2xl transition-all">
                        <div class="relative inline-block mb-4 sm:mb-6">
                            <div class="feature-icon flex items-center justify-center h-20 sm:h-24 w-20 sm:w-24 mx-auto bg-gradient-to-br from-green-400 to-green-600 text-white rounded-lg sm:rounded-2xl text-3xl sm:text-4xl shadow-lg lg:shadow-xl">
                                <i class="fas fa-credit-card"></i>
                            </div>
                            <div class="absolute -top-2 -right-2 w-7 sm:w-8 h-7 sm:h-8 bg-green-600 text-white rounded-full flex items-center justify-center font-bold text-xs sm:text-sm shadow-lg">
                                3
                            </div>
                        </div>
                        <h3 class="text-lg sm:text-2xl font-bold mb-2 sm:mb-3 text-gray-800">Bayar & Huni</h3>
                        <p class="text-gray-600 leading-relaxed text-sm sm:text-base">Lakukan pembayaran dan kamar siap untuk Anda huni. Sesederhana itu!</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Pilihan Kos Section -->
        <section id="pilihan-kos" class="py-16 sm:py-20 lg:py-24 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-10 sm:mb-12 lg:mb-16">
                    <span class="inline-block bg-purple-100 text-purple-600 px-3 sm:px-4 py-1.5 sm:py-2 rounded-full text-xs sm:text-sm font-semibold mb-3 sm:mb-4">
                        <i class="fas fa-home mr-1 sm:mr-2"></i>Pilihan Terbaik
                    </span>
                    <h2 class="text-3xl sm:text-4xl lg:text-4xl font-bold text-gray-800 mb-2 sm:mb-4">Pilihan Kos Terbaik Untuk Anda</h2>
                    <p class="text-gray-600 text-sm sm:text-base lg:text-lg max-w-2xl mx-auto">Jelajahi berbagai pilihan kos yang nyaman, aman, dan terjangkau.</p>
                </div>

                <?php if (!empty($search)): ?>
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border-l-4 border-blue-500 p-3 sm:p-4 lg:p-5 rounded-lg sm:rounded-xl mb-8 sm:mb-10 shadow-sm" role="alert">
                        <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4">
                            <i class="fas fa-search text-blue-500 text-xl sm:text-2xl flex-shrink-0"></i>
                            <div class="flex-grow min-w-0">
                                <p class="text-blue-800 font-medium text-sm sm:text-base">Menampilkan hasil pencarian untuk: <strong class="font-bold">"<?php echo htmlspecialchars($search); ?>"</strong></p>
                                <p class="text-blue-600 text-xs sm:text-sm mt-1">Ditemukan <?php echo count($list_kost); ?> kos yang sesuai</p>
                            </div>
                            <a href="dashboarduser.php#pilihan-kos" class="bg-blue-500 text-white px-3 sm:px-4 py-1.5 sm:py-2 rounded-lg hover:bg-blue-600 transition-colors text-xs sm:text-sm font-medium whitespace-nowrap">
                                <i class="fas fa-times mr-1"></i>Hapus Filter
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5 lg:gap-6">
                    <?php if (!empty($list_kost)): ?>
                        <?php foreach ($list_kost as $index => $kost): ?>
                        <div class="bg-white rounded-xl sm:rounded-2xl shadow-md lg:shadow-lg overflow-hidden card-hover flex flex-col border border-gray-100 hover:shadow-lg transition-all duration-300" style="animation: slideUp <?php echo 0.2 + ($index * 0.1); ?>s ease-out;">
                            <div class="h-40 sm:h-48 lg:h-56 w-full relative overflow-hidden group">
                                <?php 
                                    // Alternating images for visual variety
                                    $cardImage = ($index % 2 == 0) ? 'kost4.jpg' : 'kost5.jpg';
                                ?>
                                <img src="../img/<?php echo $cardImage; ?>" alt="<?php echo htmlspecialchars($kost['nama_kost']); ?>" class="h-full w-full object-cover group-hover:scale-110 transition-transform duration-500">
                                <div class="absolute top-2 sm:top-3 lg:top-4 right-2 sm:right-3 lg:right-4 flex gap-1 sm:gap-2">
                                    <button onclick="toggleWishlist(<?php echo $kost['id_kost']; ?>, this)" class="wishlist-btn bg-white text-red-500 p-1.5 sm:p-2 rounded-full shadow-lg hover:bg-red-50 transition-all duration-300 <?php echo in_array($kost['id_kost'], $wishlist_kost) ? 'favorited' : ''; ?>" title="Tambah ke Favorit">
                                        <i class="fas fa-heart text-sm sm:text-base" style="font-size: 1em;"></i>
                                    </button>
                                    <span class="bg-purple-600 text-white px-2 sm:px-3 py-0.5 sm:py-1 rounded-full text-xs font-semibold shadow-lg flex items-center text-xxs sm:text-xs">
                                        <i class="fas fa-check-circle mr-0.5 sm:mr-1"></i>Tersedia
                                    </span>
                                </div>
                            </div>
                            <div class="p-3 sm:p-4 lg:p-6 flex-grow flex flex-col">
                                <h3 class="text-base sm:text-lg lg:text-xl font-bold text-gray-900 mb-1 sm:mb-2 hover:text-purple-600 transition-colors truncate"><?php echo htmlspecialchars($kost['nama_kost']); ?></h3>
                                <p class="text-gray-500 text-xs sm:text-sm flex items-center mb-2 sm:mb-3 truncate">
                                    <i class="fas fa-map-marker-alt mr-1 sm:mr-2 text-purple-500 flex-shrink-0"></i>
                                    <span class="truncate"><?php echo htmlspecialchars($kost['alamat']); ?></span>
                                </p>
                                
                                <!-- Fasilitas Preview -->
                                <?php if (!empty($kost['fasilitas'])): ?>
                                <div class="mb-2 sm:mb-4">
                                    <div class="flex flex-wrap gap-1 sm:gap-2">
                                        <?php 
                                        $fasilitas = array_slice(explode(',', $kost['fasilitas']), 0, 3);
                                        foreach ($fasilitas as $f): 
                                        ?>
                                        <span class="bg-gray-100 text-gray-700 px-1.5 sm:px-2 py-0.5 sm:py-1 rounded-md text-xs truncate">
                                            <i class="fas fa-check text-green-500 mr-0.5 sm:mr-1"></i><?php echo trim($f); ?>
                                        </span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="flex-grow"></div>
                                <div class="pt-2 sm:pt-3 lg:pt-4 border-t border-gray-100 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2 sm:gap-3">
                                    <div class="min-w-0">
                                        <p class="text-xs text-gray-500 mb-0.5 sm:mb-1">Mulai dari</p>
                                        <p class="text-purple-600 font-bold text-lg sm:text-xl lg:text-2xl truncate">
                                            Rp <?php echo number_format($kost['harga'], 0, ',', '.'); ?>
                                        </p>
                                        <p class="text-xs text-gray-400">per bulan</p>
                                    </div>
                                    <a href="../user/booking.php?kostId=<?php echo $kost['id_kost']; ?>" class="bg-gradient-to-r from-purple-600 to-indigo-600 text-white px-4 sm:px-5 lg:px-6 py-2 sm:py-2.5 lg:py-3 rounded-lg sm:rounded-xl font-semibold hover:from-purple-700 hover:to-indigo-700 transition-all shadow-md lg:shadow-lg hover:shadow-lg text-xs sm:text-sm lg:text-sm whitespace-nowrap">
                                        Lihat Detail <i class="fas fa-arrow-right ml-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="sm:col-span-2 lg:col-span-3 text-center py-16 sm:py-20">
                            <div class="max-w-md mx-auto px-4">
                                <div class="w-16 sm:w-20 h-16 sm:h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4 sm:mb-6">
                                    <i class="fas fa-house-damage text-4xl sm:text-5xl text-gray-400"></i>
                                </div>
                                <h3 class="text-xl sm:text-2xl font-bold text-gray-800 mb-2 sm:mb-3">Tidak Ada Kos Ditemukan</h3>
                                <p class="text-gray-500 text-sm sm:text-base lg:text-lg mb-4 sm:mb-6">Oops! Kami tidak menemukan kos yang sesuai dengan pencarian Anda.</p>
                                <p class="text-gray-400 text-xs sm:text-sm mb-6">Coba kata kunci lain atau hapus filter pencarian Anda.</p>
                                <a href="dashboarduser.php#pilihan-kos" class="inline-block bg-gradient-to-r from-purple-600 to-indigo-600 text-white px-6 sm:px-8 py-2 sm:py-3 rounded-lg sm:rounded-xl font-semibold hover:from-purple-700 hover:to-indigo-700 transition-all shadow-md lg:shadow-lg text-sm sm:text-base">
                                    <i class="fas fa-redo mr-1 sm:mr-2"></i>Lihat Semua Kos
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Contact Section -->
        <section id="kontak" class="bg-gradient-to-br from-purple-600 to-indigo-600 py-16 sm:py-20 lg:py-24 relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-full">
                <div class="absolute top-10 left-10 w-40 h-40 bg-white bg-opacity-10 rounded-full blur-3xl"></div>
                <div class="absolute bottom-10 right-10 w-60 h-60 bg-purple-300 bg-opacity-20 rounded-full blur-3xl"></div>
            </div>
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
                <div class="bg-white bg-opacity-10 backdrop-blur-sm rounded-2xl sm:rounded-3xl p-8 sm:p-12 shadow-2xl">
                    <div class="w-16 sm:w-20 h-16 sm:h-20 bg-white bg-opacity-20 rounded-full flex items-center justify-center mx-auto mb-4 sm:mb-6">
                        <i class="fas fa-headset text-white text-2xl sm:text-3xl"></i>
                    </div>
                    <h2 class="text-3xl sm:text-4xl font-bold text-white mb-3 sm:mb-4">Butuh Bantuan?</h2>
                    <p class="text-purple-100 text-sm sm:text-base lg:text-lg mb-6 sm:mb-8 max-w-2xl mx-auto">
                        Tim customer support kami siap membantu Anda menemukan kos yang tepat. Hubungi kami kapan saja, kami akan dengan senang hati membantu!
                    </p>
                    <div class="flex flex-col sm:flex-row gap-3 sm:gap-4 justify-center">
                        <a href="mailto:support@kosconnect.com" class="inline-flex items-center justify-center bg-white text-purple-600 px-6 sm:px-8 py-3 sm:py-4 rounded-lg sm:rounded-xl font-bold hover:bg-purple-50 transition-all shadow-lg hover:shadow-xl text-sm sm:text-base">
                            <i class="fas fa-envelope mr-2 sm:mr-3 text-lg sm:text-xl"></i>
                            <span>Email Kami</span>
                        </a>
                        <a href="tel:+62123456789" class="inline-flex items-center justify-center bg-purple-500 bg-opacity-30 backdrop-blur-sm text-white px-6 sm:px-8 py-3 sm:py-4 rounded-lg sm:rounded-xl font-bold hover:bg-opacity-40 transition-all border-2 border-white border-opacity-30 text-sm sm:text-base">
                            <i class="fas fa-phone mr-2 sm:mr-3 text-lg sm:text-xl"></i>
                            <span>Telepon Kami</span>
                        </a>
                    </div>
                    
                    <!-- Contact Info -->
                    <div class="mt-8 sm:mt-10 pt-6 sm:pt-8 border-t border-white border-opacity-20 grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6 text-white">
                        <div>
                            <i class="fas fa-clock text-xl sm:text-2xl mb-2 opacity-80"></i>
                            <p class="text-xs sm:text-sm opacity-90">Senin - Sabtu</p>
                            <p class="font-semibold text-sm sm:text-base">08:00 - 20:00 WIB</p>
                        </div>
                        <div>
                            <i class="fas fa-envelope text-xl sm:text-2xl mb-2 opacity-80"></i>
                            <p class="text-xs sm:text-sm opacity-90">Email</p>
                            <p class="font-semibold text-sm sm:text-base">support@kosconnect.com</p>
                        </div>
                        <div>
                            <i class="fas fa-phone text-xl sm:text-2xl mb-2 opacity-80"></i>
                            <p class="text-xs sm:text-sm opacity-90">Telepon</p>
                            <p class="font-semibold text-sm sm:text-base">+62 123 456 789</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="bg-gradient-to-br from-gray-900 to-gray-800 text-white">
        <div class="max-w-7xl mx-auto py-8 sm:py-10 lg:py-12 px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 sm:gap-8 mb-6 sm:mb-8">
                <div class="sm:col-span-2 lg:col-span-2">
                    <div class="flex items-center mb-3 sm:mb-4">
                        <div class="w-10 h-10 bg-gradient-to-br from-purple-600 to-indigo-600 rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-home text-white"></i>
                        </div>
                        <h3 class="text-xl sm:text-2xl font-bold">KosConnect</h3>
                    </div>
                    <p class="text-gray-400 mb-3 sm:mb-4 max-w-md text-sm sm:text-base">
                        Platform terpercaya untuk mencari dan menyewa kos di seluruh Indonesia. Proses mudah, cepat, dan aman.
                    </p>
                    <div class="flex space-x-4">
                        <a href="#" class="w-10 h-10 bg-gray-700 rounded-full flex items-center justify-center hover:bg-purple-600 transition-colors">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-gray-700 rounded-full flex items-center justify-center hover:bg-purple-600 transition-colors">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-gray-700 rounded-full flex items-center justify-center hover:bg-purple-600 transition-colors">
                            <i class="fab fa-twitter"></i>
                        </a>
                    </div>
                </div>
                <div>
                    <h4 class="font-bold text-base sm:text-lg mb-3 sm:mb-4">Menu Cepat</h4>
                    <ul class="space-y-2 text-sm sm:text-base">
                        <li><a href="dashboarduser.php" class="text-gray-400 hover:text-purple-400 transition-colors">Beranda</a></li>
                        <li><a href="../user/user_dashboard.php" class="text-gray-400 hover:text-purple-400 transition-colors">Dashboard</a></li>
                        <li><a href="#pilihan-kos" class="text-gray-400 hover:text-purple-400 transition-colors">Pilihan Kos</a></li>
                        <li><a href="../user/feedback.php" class="text-gray-400 hover:text-purple-400 transition-colors">Feedback</a></li>
                        <li><a href="#kontak" class="text-gray-400 hover:text-purple-400 transition-colors">Kontak</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold text-base sm:text-lg mb-3 sm:mb-4">Bantuan</h4>
                    <ul class="space-y-2 text-sm sm:text-base">
                        <li><a href="#cara-kerja" class="text-gray-400 hover:text-purple-400 transition-colors">Cara Kerja</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-purple-400 transition-colors">FAQ</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-purple-400 transition-colors">Syarat & Ketentuan</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-purple-400 transition-colors">Kebijakan Privasi</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-700 pt-6 sm:pt-8 text-center">
                <p class="text-gray-400 text-xs sm:text-sm">
                    &copy; <?php echo date("Y"); ?> KosConnect. All rights reserved. Made with 
                    <i class="fas fa-heart text-red-500"></i> for better living.
                </p>
            </div>
        </div>
    </footer>

    <?php include '../user/_user_profile_modal.php'; ?>

    <script>
        // --- LOGIKA UNTUK MENAMPILKAN FOTO PROFIL BARU SETELAH UPDATE ---
        (function(){
            try {
                const newPhoto = localStorage.getItem('newProfilePhoto');
                if (newPhoto) {
                    const ts = Date.now();
                    const url = `../uploads/profiles/${newPhoto}?t=${ts}`;
                    
                    // Update foto di header
                    const headerPhoto = document.getElementById('headerUserPhoto');
                    if (headerPhoto) {
                        if (headerPhoto.tagName === 'IMG') {
                            headerPhoto.src = url;
                        } else { // Jika placeholder div
                            const img = document.createElement('img');
                            img.id = 'headerUserPhoto';
                            img.className = 'w-8 h-8 rounded-full object-cover';
                            img.src = url;
                            img.alt = 'Foto Profil';
                            headerPhoto.parentNode.replaceChild(img, headerPhoto);
                        }
                    }

                    // Update foto di modal
                    const modalPhoto = document.getElementById('photoPreview');
                    if (modalPhoto) modalPhoto.src = url;

                    localStorage.removeItem('newProfilePhoto');
                }
            } catch (e) {
                console.error("Gagal memuat foto profil baru dari localStorage", e);
            }
        })();

        // --- LOGIKA UMUM ---
        function confirmLogout(event) {
            event.preventDefault();
            Swal.fire({
                title: '<strong>Konfirmasi Logout</strong>',
                html: '<p class="text-gray-600">Apakah Anda yakin ingin keluar dari KosConnect?</p>',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-sign-out-alt mr-2"></i>Ya, Logout',
                cancelButtonText: '<i class="fas fa-times mr-2"></i>Batal',
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                customClass: {
                    popup: 'rounded-2xl',
                    confirmButton: 'rounded-lg px-6 py-3',
                    cancelButton: 'rounded-lg px-6 py-3'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Logging out...',
                        html: '<div class="py-4"><i class="fas fa-spinner fa-spin text-4xl text-purple-600"></i></div>',
                        showConfirmButton: false,
                        allowOutsideClick: false,
                        customClass: {
                            popup: 'rounded-2xl'
                        }
                    });
                    setTimeout(() => {
                        window.location.href = '../auth/logout.php';
                    }, 500);
                }
            });
        }

        function showNotifications() {
            fetch('../user/user_get_notifications.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success' && data.notifications.length > 0) {
                        let notifHtml = '<div class="space-y-2 sm:space-y-3 text-left max-h-96 overflow-y-auto px-2 sm:px-0">';
                        data.notifications.forEach(notif => {
                            const readClass = notif.is_read == 1 ? 'opacity-60' : 'font-semibold border-l-4 border-purple-500';
                            const icon = notif.is_read == 1 ? 'fa-envelope-open' : 'fa-envelope';
                            // Perbaiki path link - jika sudah mengandung 'user/' atau 'dashboard/', langsung gunakan
                            let link = '#';
                            if (notif.link) {
                                // Jika link sudah lengkap dengan folder, gunakan langsung
                                if (notif.link.includes('/')) {
                                    link = `../${notif.link}`;
                                } else {
                                    // Jika hanya nama file, asumsikan di folder user
                                    link = `../user/${notif.link}`;
                                }
                            }
                            
                            notifHtml += `
                                <div class="p-3 sm:p-4 border rounded-lg sm:rounded-xl hover:bg-gray-50 ${readClass} transition-all duration-300 hover:shadow-md cursor-pointer" onclick="window.location.href='${link}'">
                                    <div class="flex items-start gap-2 sm:gap-3">
                                        <div class="w-9 sm:w-10 h-9 sm:h-10 bg-purple-100 rounded-full flex items-center justify-center flex-shrink-0">
                                            <i class="fas ${icon} text-purple-600 text-xs sm:text-sm"></i>
                                        </div>
                                        <div class="flex-grow min-w-0">
                                            <p class="text-xs sm:text-sm text-gray-800 break-words">${notif.pesan}</p>
                                            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-1 sm:gap-2 mt-1.5 sm:mt-2">
                                                <span class="text-xs text-gray-400 whitespace-nowrap">
                                                    <i class="far fa-clock mr-1"></i>${notif.created_at}
                                                </span>
                                                ${notif.link ? `<span class="text-xs text-purple-600 font-medium flex items-center whitespace-nowrap"><i class="fas fa-arrow-right mr-1"></i>Lihat Detail</span>` : ''}
                                            </div>
                                        </div>
                                    </div>
                                </div>`;
                        });
                        notifHtml += '</div>';

                        // Responsive width untuk mobile dan desktop
                        const isMobile = window.innerWidth < 640;
                        const swalWidth = isMobile ? '95%' : '600px';

                        Swal.fire({
                            title: '<strong class="text-xl sm:text-2xl">📬 Notifikasi Anda</strong>',
                            html: notifHtml,
                            width: swalWidth,
                            showConfirmButton: true,
                            confirmButtonText: '<i class="fas fa-check-double mr-1 sm:mr-2"></i><span class="text-xs sm:text-sm">Tandai Semua Dibaca</span>',
                            confirmButtonColor: '#9333ea',
                            customClass: {
                                popup: 'rounded-2xl',
                                confirmButton: 'rounded-lg px-4 sm:px-6 py-2.5 sm:py-3 text-xs sm:text-sm'
                            }
                        }).then(() => {
                            fetch('../user/user_get_notifications.php', { method: 'POST' }).then(() => {
                                const badge = document.getElementById('notifBadge');
                                if (badge) {
                                    badge.style.animation = 'fadeOut 0.3s ease';
                                    setTimeout(() => badge.remove(), 300);
                                }
                            });
                        });
                    } else {
                        Swal.fire({
                            title: '<span class="text-lg sm:text-xl">Notifikasi</span>',
                            html: '<div class="text-center py-4 sm:py-6"><i class="fas fa-inbox text-gray-300 text-3xl sm:text-5xl mb-3 sm:mb-4"></i><p class="text-gray-600 text-sm sm:text-base">Tidak ada notifikasi baru.</p></div>',
                            icon: 'info',
                            confirmButtonColor: '#9333ea',
                            customClass: {
                                popup: 'rounded-2xl',
                                confirmButton: 'rounded-lg px-4 sm:px-6 py-2.5 sm:py-3 text-xs sm:text-sm'
                            }
                        });
                    }
                });
        }

        // Mobile menu toggle with smooth animation
        let mobileMenuActive = false;

        // Setup button event listeners when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM Loaded - Setting up button listeners');
            
            // Direct event listener untuk mobile action buttons menggunakan ID
            const profileBtn = document.getElementById('mobileProfileBtn');
            const notifBtn = document.getElementById('mobileNotifBtn');
            const logoutBtn = document.getElementById('mobileLogoutBtn');
            
            console.log('Profile button found:', profileBtn ? 'Yes' : 'No');
            console.log('Notif button found:', notifBtn ? 'Yes' : 'No');
            console.log('Logout button found:', logoutBtn ? 'Yes' : 'No');
            
            // Add click listeners dengan mousedown untuk lebih responsif
            if (profileBtn) {
                profileBtn.addEventListener('mousedown', function(e) {
                    console.log('Profile button clicked');
                    e.preventDefault();
                    e.stopPropagation();
                    closeMobileMenu();
                    setTimeout(() => {
                        if (typeof showProfileModal === 'function') {
                            showProfileModal();
                        } else {
                            console.log('showProfileModal not available');
                        }
                    }, 100);
                });
            }
            
            if (notifBtn) {
                notifBtn.addEventListener('mousedown', function(e) {
                    console.log('Notification button clicked');
                    e.preventDefault();
                    e.stopPropagation();
                    closeMobileMenu();
                    setTimeout(() => {
                        if (typeof showNotifications === 'function') {
                            showNotifications();
                        } else {
                            console.log('showNotifications not available');
                        }
                    }, 100);
                });
            }
            
            if (logoutBtn) {
                logoutBtn.addEventListener('mousedown', function(e) {
                    console.log('Logout button clicked');
                    e.preventDefault();
                    e.stopPropagation();
                    closeMobileMenu();
                    setTimeout(() => {
                        if (typeof confirmLogout === 'function') {
                            const evt = new Event('click');
                            confirmLogout(evt);
                        } else {
                            window.location.href = '../auth/logout.php';
                        }
                    }, 100);
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

        // --- WISHLIST FUNCTIONS ---
        function toggleWishlist(kostId, button) {
            const formData = new FormData();
            formData.append('id_kost', kostId);
            formData.append('action', 'toggle');

            fetch('../user/toggle_wishlist.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Toggle button state
                    button.classList.toggle('favorited');
                    
                    // Show toast notification
                    Swal.fire({
                        icon: 'success',
                        title: data.action === 'added' ? 'Ditambahkan ke Favorit' : 'Dihapus dari Favorit',
                        html: data.message,
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true,
                        customClass: {
                            popup: 'rounded-lg'
                        }
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        html: data.message || 'Gagal mengupdate wishlist',
                        customClass: {
                            popup: 'rounded-2xl'
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    html: 'Terjadi kesalahan saat mengupdate wishlist',
                    customClass: {
                        popup: 'rounded-2xl'
                    }
                });
            });
        }
    </script>

</body>
</html>