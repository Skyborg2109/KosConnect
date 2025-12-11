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
$userPhoto = $_SESSION['foto_profil'] ?? null;
$id_penyewa = $_SESSION['user_id'];

// Ambil jumlah notifikasi yang belum dibaca dari database
$stmt_notif = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE id_user = ? AND is_read = 0");
$stmt_notif->bind_param("i", $id_penyewa);
$stmt_notif->execute();
$notif_count = $stmt_notif->get_result()->fetch_assoc()['count'];
$stmt_notif->close();

// Ambil wishlist user
$wishlist_items = [];
try {
    $sql = "SELECT k.id_kost, k.nama_kost, k.alamat, k.deskripsi, k.fasilitas, k.gambar, k.harga, k.status_kos, w.created_at as added_at
            FROM wishlist w
            JOIN kost k ON w.id_kost = k.id_kost
            WHERE w.id_user = ?
            ORDER BY w.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_penyewa);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $wishlist_items[] = $row;
    }
    $stmt->close();
} catch (Exception $e) {
    // Table might not exist yet
    $wishlist_items = [];
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Favorit - KosConnect</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        html { scroll-behavior: smooth; }
        
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
        
        /* Mobile Navigation Drawer */
        #mobileMenuDrawer {
            pointer-events: none;
        }
        
        #mobileMenuPanel {
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            will-change: transform;
        }
        
        #mobileMenuBackdrop {
            transition: opacity 0.3s ease;
        }
        
        .card-hover {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .card-hover:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px rgba(147, 51, 234, 0.15);
        }
        
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
        
        .gradient-text {
            background: linear-gradient(135deg, #9333ea 0%, #4f46e5 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
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

        .wishlist-btn.favorited i {
            font-weight: 900;
        }

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

            .px-8 {
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

            .grid.grid-cols-4 {
                grid-template-columns: repeat(2, 1fr) !important;
            }

            .card-hover {
                padding: 1rem !important;
                margin: 0 !important;
                border-radius: 0.75rem !important;
            }

            .card-hover:hover {
                transform: translateY(-4px) scale(1.01) !important;
                box-shadow: 0 10px 20px rgba(147, 51, 234, 0.1) !important;
            }

            .card-hover img {
                height: 200px !important;
                object-fit: cover !important;
            }

            .card-hover h3 {
                font-size: 1rem !important;
                line-height: 1.2 !important;
            }

            .card-hover p {
                font-size: 0.85rem !important;
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

            .text-4xl {
                font-size: 2rem !important;
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

            .inline-block {
                display: inline-block !important;
            }

            .block {
                display: block !important;
                width: 100% !important;
            }

            table {
                font-size: 0.8rem !important;
            }

            th,
            td {
                padding: 0.5rem !important;
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

            .mx-auto {
                margin-left: auto !important;
                margin-right: auto !important;
            }

            .w-full {
                width: 100% !important;
            }

            .w-auto {
                width: auto !important;
            }

            .wishlist-btn {
                font-size: 1.25em !important;
                padding: 0.5rem !important;
            }

            .wishlist-btn:hover {
                transform: scale(1.1) !important;
            }

            .gradient-text {
                font-size: 1.5rem !important;
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

            .text-4xl {
                font-size: 1.75rem !important;
            }

            .grid {
                grid-template-columns: 1fr !important;
                gap: 0.5rem !important;
            }

            .grid.grid-cols-2 {
                grid-template-columns: 1fr !important;
            }

            .card-hover {
                padding: 0.75rem !important;
                border-radius: 0.5rem !important;
            }

            .card-hover img {
                height: 150px !important;
            }

            .px-4,
            .px-6,
            .px-8 {
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

            .wishlist-btn {
                font-size: 1.1em !important;
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
                        <a href="../dashboard/dashboarduser.php" class="text-gray-700 font-medium hover:text-purple-600 py-2 relative">Beranda</a>
                        <a href="user_dashboard.php" class="text-gray-700 font-medium hover:text-purple-600 py-2 relative">Dashboard</a>
                        <a href="../dashboard/dashboarduser.php#pilihan-kos" class="text-gray-700 font-medium hover:text-purple-600 py-2 relative">Pilihan Kos</a>
                        <a href="wishlist.php" class="text-purple-600 font-semibold py-2 relative">Favorit</a>
                        <a href="feedback.php" class="text-gray-700 font-medium hover:text-purple-600 py-2 relative">Feedback</a>
                    </nav>
                    <div class="flex items-center space-x-4 pl-6 border-l-2 border-gray-200">
                        <button id="notifBtn" onclick="showNotifications()" class="relative text-gray-600 hover:text-purple-600 p-2 rounded-lg hover:bg-purple-50" title="Notifikasi">
                            <i class="fas fa-bell text-xl"></i>
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
                        <a href="../auth/logout.php" onclick="confirmLogout(event)" class="bg-gradient-to-r from-red-500 to-red-600 text-white px-4 py-2 rounded-lg font-medium text-sm hover:from-red-600 hover:to-red-700">
                            <i class="fas fa-sign-out-alt mr-1"></i>Logout
                        </a>
                    </div>
                </div>
                
                <!-- Mobile Menu Button di Navbar -->
                <div class="flex md:hidden items-center space-x-2">
                    <button id="notifBtn" onclick="showNotifications()" class="relative text-gray-600 hover:text-purple-600 p-2 rounded-lg hover:bg-purple-50 transition-all" aria-label="Notifikasi" title="Notifikasi">
                        <i class="fas fa-bell text-xl" aria-hidden="true"></i>
                        <?php if ($notif_count > 0): ?>
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

    <main class="pt-24">
        <!-- Header Section -->
        <section class="bg-gradient-to-r from-purple-600 to-indigo-600 text-white py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center mb-4">
                    <i class="fas fa-heart text-3xl mr-4"></i>
                    <h1 class="text-4xl font-bold">Kos Favorit Saya</h1>
                </div>
                <p class="text-purple-100">Kelola dan lihat semua kos yang telah Anda simpan sebagai favorit</p>
            </div>
        </section>

        <!-- Content Section -->
        <section class="py-12 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <?php if (!empty($wishlist_items)): ?>
                    <div class="mb-6 flex justify-between items-center">
                        <p class="text-gray-600">
                            <i class="fas fa-bookmark text-purple-600 mr-2"></i>
                            Anda memiliki <strong><?php echo count($wishlist_items); ?></strong> kos dalam favorit
                        </p>
                        <a href="../dashboard/dashboarduser.php#pilihan-kos" class="text-purple-600 hover:text-purple-700 font-medium flex items-center">
                            <i class="fas fa-plus mr-2"></i>Tambah Kos
                        </a>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                        <?php foreach ($wishlist_items as $index => $kost): ?>
                        <div class="bg-white rounded-2xl shadow-lg overflow-hidden card-hover border border-gray-100" style="animation: slideUp <?php echo 0.2 + ($index * 0.1); ?>s ease-out;">
                            <div class="h-56 w-full relative overflow-hidden group">
                                <?php 
                                    $cardImage = ($index % 2 == 0) ? 'kost4.jpg' : 'kost5.jpg';
                                ?>
                                <img src="../img/<?php echo $cardImage; ?>" alt="<?php echo htmlspecialchars($kost['nama_kost']); ?>" class="h-full w-full object-cover group-hover:scale-110 transition-transform duration-500">
                                <div class="absolute top-4 right-4 flex gap-2">
                                    <button onclick="toggleWishlist(<?php echo $kost['id_kost']; ?>, this)" class="wishlist-btn bg-white text-red-500 p-2 rounded-full shadow-lg hover:bg-red-50 transition-all duration-300 favorited" title="Hapus dari Favorit">
                                        <i class="fas fa-heart"></i>
                                    </button>
                                    <span class="bg-<?php echo $kost['status_kos'] == 'tersedia' ? 'green' : 'gray'; ?>-600 text-white px-3 py-1 rounded-full text-xs font-semibold shadow-lg">
                                        <i class="fas fa-check-circle mr-1"></i><?php echo ucfirst($kost['status_kos']); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="p-6 flex flex-col h-full">
                                <h3 class="text-xl font-bold text-gray-900 mb-2 hover:text-purple-600 transition-colors"><?php echo htmlspecialchars($kost['nama_kost']); ?></h3>
                                <p class="text-gray-500 text-sm flex items-center mb-3">
                                    <i class="fas fa-map-marker-alt mr-2 text-purple-500"></i>
                                    <?php echo htmlspecialchars($kost['alamat']); ?>
                                </p>
                                
                                <!-- Fasilitas Preview -->
                                <?php if (!empty($kost['fasilitas'])): ?>
                                <div class="mb-4">
                                    <div class="flex flex-wrap gap-2">
                                        <?php 
                                        $fasilitas = array_slice(explode(',', $kost['fasilitas']), 0, 3);
                                        foreach ($fasilitas as $f): 
                                        ?>
                                        <span class="bg-gray-100 text-gray-700 px-2 py-1 rounded-md text-xs">
                                            <i class="fas fa-check text-green-500 mr-1"></i><?php echo trim($f); ?>
                                        </span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <p class="text-gray-600 text-sm mb-4 flex-grow"><?php echo htmlspecialchars(substr($kost['deskripsi'], 0, 100)) . '...'; ?></p>
                                
                                <div class="pt-4 border-t border-gray-100 flex justify-between items-center gap-3">
                                    <div>
                                        <p class="text-xs text-gray-500 mb-1">Mulai dari</p>
                                        <p class="text-purple-600 font-bold text-2xl">
                                            Rp <?php echo number_format($kost['harga'], 0, ',', '.'); ?>
                                        </p>
                                        <p class="text-xs text-gray-400">per bulan</p>
                                    </div>
                                    <a href="booking.php?kostId=<?php echo $kost['id_kost']; ?>" class="bg-gradient-to-r from-purple-600 to-indigo-600 text-white px-4 py-3 rounded-xl font-semibold hover:from-purple-700 hover:to-indigo-700 transition-all shadow-lg text-sm text-center">
                                        Lihat Detail <i class="fas fa-arrow-right ml-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-20">
                        <div class="max-w-md mx-auto">
                            <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                                <i class="fas fa-heart-broken text-5xl text-gray-400"></i>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-800 mb-3">Belum Ada Favorit</h3>
                            <p class="text-gray-500 text-lg mb-6">Anda belum menambahkan kos apa pun ke favorit.</p>
                            <p class="text-gray-400 mb-8">Mulai jelajahi berbagai pilihan kos dan tambahkan ke favorit Anda!</p>
                            <a href="../dashboard/dashboarduser.php#pilihan-kos" class="inline-block bg-gradient-to-r from-purple-600 to-indigo-600 text-white px-8 py-3 rounded-xl font-semibold hover:from-purple-700 hover:to-indigo-700 transition-all shadow-lg">
                                <i class="fas fa-search mr-2"></i>Jelajahi Kos
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <footer class="bg-gradient-to-br from-gray-900 to-gray-800 text-white py-12 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
                <div class="md:col-span-2">
                    <h3 class="text-2xl font-bold mb-4">KosConnect</h3>
                    <p class="text-gray-400">Platform terpercaya untuk mencari dan menyewa kos di seluruh Indonesia.</p>
                </div>
                <div>
                    <h4 class="font-bold text-lg mb-4">Menu</h4>
                    <ul class="space-y-2">
                        <li><a href="../dashboard/dashboarduser.php" class="text-gray-400 hover:text-purple-400">Beranda</a></li>
                        <li><a href="user_dashboard.php" class="text-gray-400 hover:text-purple-400">Dashboard</a></li>
                        <li><a href="wishlist.php" class="text-gray-400 hover:text-purple-400">Favorit</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold text-lg mb-4">Bantuan</h4>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-400 hover:text-purple-400">FAQ</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-purple-400">Kontak</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-700 pt-8 text-center">
                <p class="text-gray-400">&copy; <?php echo date("Y"); ?> KosConnect. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <?php include './_user_profile_modal.php'; ?>

    <script>
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

        function confirmLogout(event) {
            event.preventDefault();
            Swal.fire({
                title: 'Konfirmasi Logout',
                html: '<p class="text-gray-600">Apakah Anda yakin ingin keluar?</p>',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Logout',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '../auth/logout.php';
                }
            });
        }

        function showNotifications() {
            fetch('user_get_notifications.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success' && data.notifications.length > 0) {
                        let notifHtml = '<div class="space-y-3 text-left max-h-96 overflow-y-auto">';
                        data.notifications.forEach(notif => {
                            notifHtml += `<div class="p-4 border rounded-xl">${notif.pesan}</div>`;
                        });
                        notifHtml += '</div>';
                        Swal.fire({
                            title: 'Notifikasi Anda',
                            html: notifHtml,
                            width: '600px'
                        });
                    }
                });
        }

        function toggleWishlist(kostId, button) {
            const formData = new FormData();
            formData.append('id_kost', kostId);
            formData.append('action', 'toggle');

            fetch('toggle_wishlist.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Dihapus dari Favorit',
                        text: data.message,
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2000
                    }).then(() => {
                        // Refresh halaman
                        location.reload();
                    });
                }
            });
        }
    </script>

</body>
</html>
