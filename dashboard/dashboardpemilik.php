<?php
session_start();
// Autentikasi dan Redirect
if (!isset($_SESSION['user_logged_in']) || $_SESSION['role'] !== 'pemilik') { 
    $_SESSION['login_error'] = "Anda harus login terlebih dahulu untuk mengakses halaman ini.";
    header("Location: ../auth/loginForm.php");
    exit();
}

include_once '../config/db.php';
include_once '../config/SessionChecker.php';
// SessionManager.php is already included via SessionChecker.php

// Validate multi-device session
if (!checkMultiDeviceSession($conn)) {
    session_destroy();
    header("Location: ../auth/loginForm.php");
    exit();
}
 
// Persiapan Data Tampilan Sederhana untuk Header
$id_pemilik = $_SESSION['user_id'];
$fullName = $_SESSION['fullname'] ?? 'Pemilik Kos';
$userName = htmlspecialchars($fullName); // For compatibility with _user_profile_modal.php
$names = explode(' ', $fullName);
$initials = '';
foreach ($names as $name) { $initials .= strtoupper(substr($name, 0, 1)); }
$firstName = $names[0] ?? '';
$userEmail = $_SESSION['email'] ?? ''; // Ambil email dari sesi
$userPhoto = $_SESSION['foto_profil'] ?? null;
$userRole = $_SESSION['role'] ?? 'pemilik';

// Ambil jumlah notifikasi yang belum dibaca
$notif_count = 0;
try {
    $stmt_notif = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE id_user = ? AND is_read = 0");
    if ($stmt_notif) {
        $stmt_notif->bind_param("i", $id_pemilik);
        $stmt_notif->execute();
        $notif_result = $stmt_notif->get_result();
        if ($notif_result) {
            $notif_count = $notif_result->fetch_assoc()['count'] ?? 0;
        }
        $stmt_notif->close();
    }
} catch (Throwable $e) {
    error_log("Notification count error: " . $e->getMessage());
    $notif_count = 0;
}
 
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title id="mainTitle">Dashboard Pemilik Kos - KosKu</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Chart.js - Using local file with relative path -->
    <script src="../assets/js/chart.min.js"></script>
    <script>
        window.addEventListener('load', function() {
            if (typeof Chart === 'undefined') {
                console.error('❌ Chart.js failed to load from ../assets/js/chart.min.js');
                // Try one more fallback path just in case
                var script = document.createElement('script');
                script.src = '/KosConnect/assets/js/chart.min.js';
                script.onload = function() { console.log('✅ Chart.js loaded via fallback path'); };
                document.head.appendChild(script);
            } else {
                console.log('✅ Chart.js loaded successfully!');
            }
        });
    </script>
    <style>
        /* Pastikan CSS ini konsisten */
        .dark-gradient {
            background: linear-gradient(135deg, #1e293b 0%, #334155 50%, #475569 100%);
            position: relative;
            overflow: hidden;
        }
        
        .dark-gradient::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(148, 163, 184, 0.1) 0%, transparent 70%);
            animation: pulse 15s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(10%, 10%) scale(1.1); }
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
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
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            transition: left 0.5s;
        }
        
        .card-hover:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        
        .card-hover:hover::before {
            left: 100%;
        }
        
        .sidebar-link {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        /* Left accent bar */
        .sidebar-link::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background: linear-gradient(180deg, #94a3b8, #64748b);
            transform: scaleY(0);
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        /* Gradient background overlay */
        .sidebar-link::after {
            content: '';
            position: absolute;
            left: -100%;
            top: 0;
            height: 100%;
            width: 100%;
            background: linear-gradient(90deg, 
                rgba(148, 163, 184, 0.15) 0%, 
                rgba(148, 163, 184, 0.25) 50%, 
                rgba(148, 163, 184, 0.15) 100%);
            transition: left 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 0;
        }
        
        .sidebar-link:hover {
            background: linear-gradient(90deg, 
                rgba(255, 255, 255, 0.08) 0%, 
                rgba(255, 255, 255, 0.15) 100%);
            padding-left: 32px;
            transform: translateX(4px);
            box-shadow: inset 0 0 20px rgba(148, 163, 184, 0.1),
                        0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .sidebar-link:hover::before {
            transform: scaleY(1);
        }
        
        .sidebar-link:hover::after {
            left: 100%;
        }
        
        /* Icon animation on hover */
        .sidebar-link:hover i {
            transform: scale(1.15) rotate(5deg);
            filter: drop-shadow(0 0 8px rgba(148, 163, 184, 0.6));
        }
        
        .sidebar-link i {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            z-index: 1;
        }
        
        /* Text animation on hover */
        .sidebar-link span {
            position: relative;
            z-index: 1;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .sidebar-link:hover span {
            letter-spacing: 0.5px;
        }
        
        /* Active state enhancement */
        .sidebar-link:active {
            transform: translateX(2px);
            transition: transform 0.1s ease;
        }
        
        .active-link {
            background: linear-gradient(90deg, 
                rgba(255, 255, 255, 0.12) 0%, 
                rgba(255, 255, 255, 0.18) 100%) !important;
            border-left-color: #94a3b8 !important;
            font-weight: 600;
            padding-left: 32px !important;
            box-shadow: inset 0 0 25px rgba(148, 163, 184, 0.25),
                        0 2px 8px rgba(0, 0, 0, 0.1);
            transform: translateX(4px);
        }
        
        .active-link::before {
            transform: scaleY(1) !important;
        }
        
        .active-link::after {
            content: '';
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 60%;
            background: linear-gradient(180deg, #94a3b8, #64748b);
            border-radius: 2px 0 0 2px;
            box-shadow: 0 0 10px rgba(148, 163, 184, 0.5);
        }
        
        .active-link i {
            transform: scale(1.1);
            filter: drop-shadow(0 0 6px rgba(148, 163, 184, 0.5));
        }
        
        .active-link span {
            letter-spacing: 0.3px;
        }

        
        /* Transisi untuk sidebar */
        .sidebar-transition {
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        /* Header enhancement */
        header {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95) !important;
            transition: all 0.3s ease;
        }
        
        /* Button animations */
        button {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        button:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        button:active:not(:disabled) {
            transform: translateY(0);
        }
        
        /* Modal animations */
        #profileModal,
        #kosModal,
        #kamarModal {
            animation: fadeIn 0.3s ease;
        }
        
        #profileModal > div,
        #kosModal > div,
        #kamarModal > div {
            animation: slideIn 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        /* Loading spinner */
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .fa-spinner {
            animation: spin 1s linear infinite;
        }
        
        /* Notification badge pulse */
        @keyframes badgePulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        #notifBadge {
            animation: badgePulse 2s ease-in-out infinite;
        }
        
        /* Content container fade in */
        #contentContainer {
            animation: fadeIn 0.5s ease;
        }
        
        /* Profile photo hover */
        #sidebarUserPhoto {
            transition: all 0.3s ease;
        }
        
        #sidebarUserPhoto:hover {
            transform: scale(1.1);
            box-shadow: 0 0 20px rgba(148, 163, 184, 0.5);
        }
        
        /* Input focus effects */
        input:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: #94a3b8 !important;
            box-shadow: 0 0 0 3px rgba(148, 163, 184, 0.1) !important;
            transition: all 0.3s ease;
        }
        
        /* Smooth scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #94a3b8;
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #64748b;
        }
        
        /* Overlay fade */
        #sidebarOverlay {
            transition: opacity 0.3s ease;
        }
        
        #sidebarOverlay.hidden {
            opacity: 0;
        }
        
        /* Form elements enhancement */
        .form-input {
            transition: all 0.3s ease;
        }
        
        .form-input:hover {
            border-color: #cbd5e1;
        }
        
        /* Badge animations */
        .badge {
            display: inline-block;
            animation: slideIn 0.3s ease;
        }
        
        /* Responsive improvements */
        @media (max-width: 768px) {
            .card-hover:hover {
                transform: translateY(-4px) scale(1.01);
            }
            
            header {
                padding: 0.75rem 1rem !important;
            }
            
            #pageTitle {
                font-size: 1.25rem !important;
            }
        }
        
        /* Glass morphism effect for modals */
        .glass-effect {
            backdrop-filter: blur(16px) saturate(180%);
            background-color: rgba(255, 255, 255, 0.75);
            border: 1px solid rgba(209, 213, 219, 0.3);
        }
        
        /* Gradient text */
        .gradient-text {
            background: linear-gradient(135deg, #1e293b 0%, #475569 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        /* Icon bounce on hover */
        .icon-bounce:hover i {
            animation: bounce 0.6s ease;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }
        
        /* Modal fade animation */
        .modal-fade {
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .modal-fade.show {
            opacity: 1;
        }
        
        @keyframes fadeOut {
            from { opacity: 1; transform: scale(1); }
            to { opacity: 0; transform: scale(0.8); }
        }
    </style>
</head>
<body class="bg-gray-50 font-sans">
 
    <header class="bg-white shadow-sm border-b border-gray-200 p-4 flex justify-between items-center sticky top-0 z-40 md:ml-64">
        <div class="flex items-center space-x-3">
             <button id="hamburgerBtn" class="md:hidden text-slate-600 hover:text-slate-800 mr-2 icon-bounce">
                 <i class="fas fa-bars text-xl"></i>
             </button>
            <h1 id="pageTitle" class="text-xl sm:text-2xl font-bold gradient-text">Dashboard</h1>
        </div>
        <div class="flex items-center space-x-3 sm:space-x-4">
            <button onclick="showNotifications()" class="relative text-slate-600 hover:text-slate-800 icon-bounce p-2 rounded-lg hover:bg-slate-100">
                <i class="fas fa-bell text-xl"></i>
                <?php if ($notif_count > 0): ?>
                <span id="notifBadge" class="absolute top-0 right-0 -mt-1 -mr-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center shadow-lg"><?php echo $notif_count; ?></span>
                <?php endif; ?>
            </button>
            <button class="bg-gradient-to-r from-slate-600 to-slate-700 hover:from-slate-700 hover:to-slate-800 text-white px-4 py-2 rounded-lg font-medium transition-all duration-300 hidden sm:flex items-center space-x-2 shadow-md" onclick="handleLogout()">
                <i class="fas fa-sign-out-alt"></i><span>Logout</span>
            </button>
            <!-- Mobile logout button -->
            <button class="sm:hidden text-slate-600 hover:text-slate-800 icon-bounce p-2 rounded-lg hover:bg-slate-100" onclick="handleLogout()">
                <i class="fas fa-sign-out-alt text-xl"></i>
            </button>
        </div>
    </header>
 
    <div id="sidebar" class="fixed left-0 top-0 h-full w-64 dark-gradient text-white shadow-2xl z-50 transform -translate-x-full md:translate-x-0 sidebar-transition">
        <div class="p-6 border-b border-white border-opacity-10 relative z-10">
            <div class="flex items-center space-x-3 mb-4">
                <div class="w-10 h-10 bg-white bg-opacity-20 rounded-lg flex items-center justify-center backdrop-blur-sm">
                    <i class="fas fa-home text-white text-xl"></i>
                </div>
                <h2 class="text-xl font-bold">Dashboard Pemilik</h2>
            </div>
            <button onclick="showProfileModal()" class="w-full flex items-center space-x-3 text-left hover:bg-white hover:bg-opacity-10 p-3 rounded-xl transition-all duration-300 group">
                <?php 
                    $userPhoto = trim($userPhoto ?? '');
                    $displayPhoto = false;
                    $profilePhotoUrl = '';

                    if (!empty($userPhoto)) {
                        if (strpos($userPhoto, 'http') === 0) {
                            $profilePhotoUrl = $userPhoto;
                            $displayPhoto = true;
                        } elseif (file_exists(__DIR__ . '/../uploads/profiles/' . $userPhoto)) {
                            $profilePhotoUrl = '../uploads/profiles/' . $userPhoto;
                            $displayPhoto = true;
                        }
                    }

                    if ($displayPhoto) {
                ?>
                    <img id="sidebarUserPhoto" src="<?php echo htmlspecialchars($profilePhotoUrl); ?>" alt="Foto Profil" onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($fullName); ?>&background=random';" class="w-12 h-12 rounded-full object-cover flex-shrink-0 ring-2 ring-white ring-opacity-30 group-hover:ring-opacity-60 transition-all">
                <?php } else { ?>
                    <div id="sidebarUserPhoto" class="w-12 h-12 bg-slate-600 rounded-full flex items-center justify-center text-slate-300 font-bold text-lg flex-shrink-0 ring-2 ring-white ring-opacity-30 group-hover:ring-opacity-60 transition-all"><?php echo $initials; ?></div>
                <?php } ?>
                <div class="flex-grow">
                    <p class="font-bold text-white group-hover:text-slate-100"><?php echo htmlspecialchars($fullName); ?></p>
                    <p class="text-sm text-slate-300 group-hover:text-slate-200"><?php echo htmlspecialchars(ucfirst($userRole)); ?></p>
                </div>
                <i class="fas fa-chevron-right text-slate-400 group-hover:text-white transition-all"></i>
            </button>
        </div>

        <nav class="mt-6 relative z-10">
            <a href="#" data-module="owner_dashboard_summary" onclick="loadContent('owner_dashboard_summary', event)" class="nav-item sidebar-link flex items-center px-6 py-4 text-white border-l-4 border-slate-400 active-link group">
                <i class="fas fa-tachometer-alt mr-3 text-lg group-hover:scale-110 transition-transform"></i>
                <span>Dashboard</span>
            </a>
            <a href="#" data-module="owner_manage_kost" onclick="loadContent('owner_manage_kost', event)" class="nav-item sidebar-link flex items-center px-6 py-4 text-slate-300 hover:text-white border-l-4 border-transparent group">
                <i class="fas fa-building mr-3 text-lg group-hover:scale-110 transition-transform"></i>
                <span>Manajemen Kos</span>
            </a>
            <a href="#" data-module="owner_manage_booking" onclick="loadContent('owner_manage_booking', event)" class="nav-item sidebar-link flex items-center px-6 py-4 text-slate-300 hover:text-white border-l-4 border-transparent group">
                <i class="fas fa-clipboard-list mr-3 text-lg group-hover:scale-110 transition-transform"></i>
                <span>Pesanan Masuk</span>
            </a>
            <a href="#" data-module="owner_manage_payments" onclick="loadContent('owner_manage_payments', event)" class="nav-item sidebar-link flex items-center px-6 py-4 text-slate-300 hover:text-white border-l-4 border-transparent group">
                <i class="fas fa-credit-card mr-3 text-lg group-hover:scale-110 transition-transform"></i>
                <span>Pembayaran</span>
            </a>
            <a href="#" data-module="owner_view_feedback" onclick="loadContent('owner_view_feedback', event)" class="nav-item sidebar-link flex items-center px-6 py-4 text-slate-300 hover:text-white border-l-4 border-transparent group">
                <i class="fas fa-exclamation-triangle mr-3 text-lg group-hover:scale-110 transition-transform"></i>
                <span>Keluhan Kos</span>
            </a>
            <button onclick="showProfileModal()" class="nav-item sidebar-link flex items-center w-full text-left px-6 py-4 text-slate-300 hover:text-white border-l-4 border-transparent group">
                <i class="fas fa-user-cog mr-3 text-lg group-hover:scale-110 transition-transform"></i>
                <span>Profil Saya</span>
            </button>
        </nav>
        
        <!-- Footer Sidebar -->
        <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-white border-opacity-10 relative z-10">
            <p class="text-xs text-slate-400 text-center">© 2025 KosConnect</p>
        </div>
    </div>
 
    <div class="md:ml-64 min-h-screen bg-gradient-to-br from-gray-50 to-slate-100">
        <div id="contentContainer" class="p-4 sm:p-6 lg:p-8">
            <!-- Loading state with skeleton -->
            <div class="flex items-center justify-center py-20">
                <div class="text-center">
                    <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-slate-300 border-t-slate-600 mb-4"></div>
                    <p class="text-slate-600 font-medium">Memuat dashboard...</p>
                </div>
            </div>
        </div> 
    </div>
    
    <!-- Overlay untuk mobile saat sidebar terbuka -->
    <div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-60 z-40 hidden md:hidden backdrop-blur-sm"></div>

    <?php include '../user/_user_profile_modal.php'; ?>

    <script>
        (function(){
            try {
                const newPhoto = localStorage.getItem('newProfilePhoto');
                if (newPhoto) {
                    const ts = Date.now();
                    const url = newPhoto.startsWith('http') ? newPhoto : `../uploads/profiles/${newPhoto}?t=${ts}`;
                    document.querySelectorAll('img').forEach(img => {
                        try { if (img.src && img.src.indexOf('/uploads/profiles/') !== -1) img.src = url; } catch(e){}
                    });
                    const sidebarNode = document.getElementById('sidebarUserPhoto');
                    if (sidebarNode && sidebarNode.tagName !== 'IMG') {
                        const img = document.createElement('img');
                        img.id = 'sidebarUserPhoto';
                        img.className = 'w-12 h-12 rounded-full object-cover flex-shrink-0';
                        img.src = url;
                        img.alt = 'Foto Profil';
                        sidebarNode.parentNode.replaceChild(img, sidebarNode);
                    }
                    localStorage.removeItem('newProfilePhoto');
                }
            } catch (e) {}
        })();
        // Variabel untuk menyimpan konten dashboard awal
        let dashboardContentCache = '';

        function loadContent(moduleName, event) {
            return new Promise((resolve, reject) => {
                // Sembunyikan sidebar di mobile setelah menu diklik
                sidebar.classList.add('-translate-x-full');
                sidebarOverlay.classList.add('hidden');

                if (event) event.preventDefault();
                
                const container = document.getElementById('contentContainer');
                const pageTitle = document.getElementById('pageTitle');
            
            // Update status aktif sidebar
            const navItems = document.querySelectorAll('.nav-item');
            navItems.forEach(item => {
                item.classList.remove('active-link');
                item.style.borderColor = 'transparent';
            });
            
            const activeLink = event ? event.currentTarget : document.querySelector(`[data-module="${moduleName}"]`);
            if (activeLink) {
                activeLink.classList.add('active-link');
                activeLink.style.borderColor = '#94a3b8'; // slate-400
            }

            pageTitle.textContent = activeLink.textContent.trim();
            
            // Tampilkan loading screen dengan animasi
            container.innerHTML = `
                <div class="flex items-center justify-center py-20">
                    <div class="text-center">
                        <div class="inline-block animate-spin rounded-full h-16 w-16 border-4 border-slate-300 border-t-slate-600 mb-4"></div>
                        <p class="text-slate-600 font-medium text-lg">Memuat ${activeLink.textContent.trim()}...</p>
                        <p class="text-slate-400 text-sm mt-2">Harap tunggu sebentar</p>
                    </div>
                </div>
            `;

            // Menggunakan endpoint API baru yang lebih efisien
            const url = `../pemilik_kos/pemilik_get_module.php?module=${moduleName}`;
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 15000); // Timeout 15 detik

            fetch(url, { signal: controller.signal })
                .then(response => {
                    clearTimeout(timeoutId);
                    if (!response.ok) {
                        throw new Error(`Gagal memuat modul (${response.status})`);
                    }
                    return response.text();
                })
                .then(html => {
                    // Create a temporary container to parse the HTML
                    const temp = document.createElement('div');
                    temp.innerHTML = html;
                    
                    // Extract scripts
                    const scripts = Array.from(temp.querySelectorAll('script'));
                    
                    // Remove scripts from HTML to prevent double execution (though innerHTML usually doesn't execute them anyway)
                    scripts.forEach(script => script.remove());
                    
                    // Set the HTML content
                    container.innerHTML = temp.innerHTML;
                    
                    // Execute scripts manually
                    scripts.forEach(script => {
                        const newScript = document.createElement('script');
                        if (script.src) {
                            newScript.src = script.src;
                        } else {
                            newScript.textContent = script.textContent;
                        }
                        document.body.appendChild(newScript);
                        // Optional: remove script after execution to keep DOM clean
                        // document.body.removeChild(newScript);
                    });

                    resolve();
                })
                .catch(error => {
                    clearTimeout(timeoutId);
                    const errorMessage = error.name === 'AbortError' 
                        ? `Timeout memuat ${activeLink.textContent.trim()} (lebih dari 15 detik).`
                        : error.message;
                    container.innerHTML = `
                        <div class="flex items-center justify-center py-20">
                            <div class="text-center max-w-md">
                                <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i class="fas fa-exclamation-triangle text-red-500 text-3xl"></i>
                                </div>
                                <h3 class="text-xl font-semibold text-gray-800 mb-2">Gagal Memuat Konten</h3>
                                <p class="text-red-600 mb-4">${errorMessage}</p>
                                <button onclick="location.reload()" class="bg-gradient-to-r from-slate-600 to-slate-700 text-white px-6 py-3 rounded-lg hover:from-slate-700 hover:to-slate-800 transition-all shadow-md">
                                    <i class="fas fa-redo mr-2"></i>Muat Ulang Halaman
                                </button>
                            </div>
                        </div>
                    `;
                    console.error('Load Content Error:', error);
                    reject(error);
                });
            });
        }

        // Logika untuk sidebar mobile
        const hamburgerBtn = document.getElementById('hamburgerBtn');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        hamburgerBtn.addEventListener('click', () => {
            sidebar.classList.toggle('-translate-x-full');
            sidebarOverlay.classList.toggle('hidden');
        });

        sidebarOverlay.addEventListener('click', () => {
            sidebar.classList.add('-translate-x-full');
            sidebarOverlay.classList.add('hidden');
        });

        // Tutup modal jika klik di luar area konten
        document.getElementById('profileModal').addEventListener('click', function(e) {
            if (e.target === this) closeProfileModal();
        });

        function handleLogout() {
            Swal.fire({
                title: '<strong>Konfirmasi Logout</strong>',
                html: '<p class="text-gray-600">Apakah Anda yakin ingin keluar dari dashboard?</p>',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-sign-out-alt mr-2"></i>Ya, Logout',
                cancelButtonText: '<i class="fas fa-times mr-2"></i>Batal',
                confirmButtonColor: '#475569',
                cancelButtonColor: '#94a3b8',
                customClass: {
                    popup: 'rounded-2xl',
                    confirmButton: 'rounded-lg px-6 py-3',
                    cancelButton: 'rounded-lg px-6 py-3'
                },
                showClass: {
                    popup: 'animate__animated animate__fadeInDown animate__faster'
                },
                hideClass: {
                    popup: 'animate__animated animate__fadeOutUp animate__faster'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading
                    Swal.fire({
                        title: 'Logging out...',
                        html: '<div class="py-4"><i class="fas fa-spinner fa-spin text-4xl text-slate-600"></i></div>',
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

        function showKosModal(mode, id = null) {
            const modal = document.getElementById('kosModal');
            const form = document.getElementById('kosForm');
            const title = document.getElementById('kosModalTitle');
            const imagePreview = document.getElementById('image_preview');
            const actionInput = document.getElementById('kosAction');
            const idInput = document.getElementById('id_kost');
            const errorBox = document.getElementById('kosModalError');

            form.reset();
            errorBox.classList.add('hidden');
            modal.classList.remove('hidden');
            document.getElementById('image_preview').innerHTML = `
                <i class="fas fa-image text-3xl mb-2"></i>
                <p class="text-sm">Klik atau tarik gambar ke sini</p>
            `;
            
            // Clear Room Types
            document.getElementById('roomTypesContainer').innerHTML = '';
            
            // Clear Gallery Previews
            document.querySelectorAll('.preview-container').forEach(el => el.innerHTML = '');
            
            // Clear all category previews
            document.querySelectorAll('.preview-container').forEach(el => el.innerHTML = '');
            // Reset visuals for all file inputs (optional but good UI)
            document.querySelectorAll('[name^="foto_"]').forEach(input => {
                const feedbackDiv = input.nextElementSibling;
                if(feedbackDiv) {
                     feedbackDiv.innerHTML = `
                        <i class="fas fa-images text-xl mb-1 group-hover:text-purple-500 transition-colors"></i>
                        <p class="text-xs font-medium text-slate-500">Klik untuk upload</p>
                    `;
                }
            });

            // Clear existing photos containers
            ['Bangunan', 'Kamar', 'Kamar Mandi', 'Fasilitas Bersama', 'Lainnya'].forEach(cat => {
                 const el = document.getElementById(`existing_photos_${cat}`);
                 if(el) el.innerHTML = '';
            });
            
            document.getElementById('gambar_lama').value = '';

            if (mode === 'add') {
                title.textContent = 'Tambah Kos Baru';
                actionInput.value = 'add';
                idInput.value = '';
                addRoomTypeInput(); // Add one empty room type input by default for new kos
            } else { // mode === 'edit'
                title.textContent = 'Edit Data Kos';
                actionInput.value = 'edit';
                idInput.value = id;

                // Ambil data kos via AJAX untuk diisi ke form
                fetch(`../pemilik_kos/process_kost.php?action=get_details&id_kost=${id}`)
                    .then(response => response.json())
                    .then(res => {
                        if (res.status === 'success') {
                            const kosData = res.data; // Use a consistent variable name
                            document.getElementById('nama_kost').value = kosData.nama_kost;
                            document.getElementById('alamat').value = kosData.alamat;
                            document.getElementById('harga').value = parseInt(kosData.harga);
                            document.getElementById('deskripsi').value = kosData.deskripsi;
                            document.getElementById('fasilitas').value = kosData.fasilitas;
                            document.getElementById('peraturan').value = kosData.peraturan || '';
                            document.getElementById('tipe_kost').value = kosData.tipe_kost;
                            document.getElementById('jenis_kamar').value = kosData.jenis_kamar || 'Kamar Mandi Dalam';
                            document.getElementById('gambar_lama').value = kosData.gambar;
                            if (kosData.gambar) {
                                let imgSrc = kosData.gambar;
                                if (!imgSrc.startsWith('http')) {
                                    imgSrc = `../uploads/kost/${imgSrc}`;
                                }
                                document.getElementById('image_preview').innerHTML = `<img src="${imgSrc}" class="h-full w-full object-contain">`;
                            }
                            
                            // Load Room Types
                            const rtContainer = document.getElementById('roomTypesContainer');
                            rtContainer.innerHTML = ''; // Clear
                            if (kosData.room_types && kosData.room_types.length > 0) {
                                kosData.room_types.forEach(rt => addRoomTypeInput(rt));
                            } else {
                                 // Add one empty by default if none? No, optional.
                            }
                            
                            // Load additional photos organized by category
                            if (kosData.photos && kosData.photos.length > 0) {
                                kosData.photos.forEach(photo => {
                                    let cat = photo.category || 'Lainnya';
                                    const container = document.getElementById(`existing_photos_${cat}`);
                                    if (container) {
                                        const photoDiv = document.createElement('div');
                                        photoDiv.className = 'relative group h-20 w-full rounded-lg overflow-hidden border border-gray-200';
                                        photoDiv.id = `photo-${photo.id_photo}`;
                                        let photoSrc = photo.file_name;
                                        if (!photoSrc.startsWith('http')) {
                                            photoSrc = `../uploads/kost/${photoSrc}`;
                                        }
                                        photoDiv.innerHTML = `
                                            <img src="${photoSrc}" class="h-full w-full object-cover">
                                            <button type="button" onclick="deletePhoto(${photo.id_photo}, ${id})" class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                                <i class="fas fa-times text-xs"></i>
                                            </button>
                                        `;
                                        container.appendChild(photoDiv);
                                    }
                                });
                            }
                        } else {
                            Swal.fire('Gagal', 'Gagal mengambil data kos: ' + res.message, 'error');
                            closeKosModal();
                        }
                    })
                    .catch(err => {
                        Swal.fire('Error', 'Terjadi kesalahan jaringan.', 'error');
                        console.error(err);
                        closeKosModal();
                    });
            }
        }

        // Fungsi untuk menampilkan notifikasi
        function showNotifications() {
            // Optimistic UI update: Sembunyikan badge segera
            const badge = document.getElementById('notifBadge');
            if (badge) {
                badge.style.animation = 'fadeOut 0.3s ease';
                setTimeout(() => badge.remove(), 300);
            }

            fetch('../pemilik_kos/pemilik_get_notifications.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success' && data.notifications.length > 0) {
                        let notifHtml = '<div class="space-y-3 text-left max-h-96 overflow-y-auto pr-2">';
                        data.notifications.forEach(notif => {
                            const readClass = notif.is_read == 1 ? 'opacity-60' : 'font-semibold border-l-4 border-blue-500';
                            const icon = notif.is_read == 1 ? 'fa-envelope-open' : 'fa-envelope';
                            notifHtml += `
                                <div class="p-4 border rounded-xl hover:bg-gray-50 ${readClass} transition-all duration-300 hover:shadow-md">
                                    <div class="flex items-start space-x-3">
                                        <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                                            <i class="fas ${icon} text-blue-600"></i>
                                        </div>
                                        <div class="flex-grow">
                                            <p class="text-sm text-gray-800">${notif.pesan}</p>
                                            <div class="flex justify-between items-center mt-2">
                                                <span class="text-xs text-gray-400">
                                                    <i class="far fa-clock mr-1"></i>${notif.created_at}
                                                </span>
                                                ${(() => {
                                                    if (!notif.link) return '';
                                                    let cleanLink = notif.link;
                                                    // Sanitize link for virtual host
                                                    if (window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
                                                        if (cleanLink.startsWith('/KosConnect/')) {
                                                            cleanLink = cleanLink.replace('/KosConnect/', '/');
                                                        }
                                                    }
                                                    return `<a href="${cleanLink}" class="text-xs text-blue-600 hover:text-blue-700 font-medium hover:underline">Lihat Detail →</a>`;
                                                })()}
                                            </div>
                                        </div>
                                    </div>
                                </div>`;
                        });
                        notifHtml += '</div>';

                        Swal.fire({
                            title: '<strong class="text-2xl">📬 Notifikasi Anda</strong>',
                            html: notifHtml,
                            width: '600px',
                            showConfirmButton: true,
                            confirmButtonText: '<i class="fas fa-check-double mr-2"></i>Tandai Semua Sudah Dibaca',
                            confirmButtonColor: '#475569',
                            customClass: {
                                popup: 'rounded-2xl',
                                confirmButton: 'rounded-lg px-6 py-3'
                            }
                        }).then(() => {
                            fetch('../pemilik_kos/pemilik_get_notifications.php', { method: 'POST' });
                        });
                    } else {
                        Swal.fire({
                            title: 'Notifikasi',
                            html: '<div class="text-center py-4"><i class="fas fa-inbox text-gray-300 text-5xl mb-4"></i><p class="text-gray-600">Tidak ada notifikasi baru.</p></div>',
                            icon: 'info',
                            confirmButtonColor: '#475569',
                            customClass: {
                                popup: 'rounded-2xl'
                            }
                        });
                    }
                });
        }

        // Fungsi untuk edit kos (dipanggil dari modul owner_manage_kost)
        function editKos(id_kost) {
            showKosModal('edit', id_kost);
        }

        function deletePhoto(id_photo, id_kost) { // Logic to delete specific photo
            Swal.fire({
                title: 'Hapus Foto?',
                text: "Foto ini akan dihapus permanen.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('action', 'delete_photo');
                    formData.append('id_photo', id_photo);
                    formData.append('id_kost', id_kost);

                    fetch('../pemilik_kos/process_kost.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            const elem = document.getElementById(`photo-${id_photo}`);
                            if (elem) elem.remove();
                            Swal.fire('Terhapus!', 'Foto berhasil dihapus.', 'success');
                        } else {
                            Swal.fire('Gagal', data.message, 'error');
                        }
                    })
                    .catch(e => {
                        console.error(e);
                        Swal.fire('Error', 'Gagal menghapus foto.', 'error');
                    });
                }
            });
        }

        function closeKosModal() { //
            document.getElementById('kosModal').classList.add('hidden');
        }

        function saveKos(event) { //
            event.preventDefault();
            const form = document.getElementById('kosForm');
            const formData = new FormData(form);
            const button = document.getElementById('saveKosButton');
            const errorBox = document.getElementById('kosModalError');

            button.disabled = true;
            button.textContent = 'Menyimpan...';
            errorBox.classList.add('hidden');

            fetch('../pemilik_kos/process_kost.php', {
                method: 'POST',
                body: formData
            })
            .then(async response => {
                const text = await response.text();
                try {
                    const res = JSON.parse(text);
                    if (res.status === 'success') {
                        Swal.fire('Berhasil', res.message, 'success');
                        closeKosModal();
                        loadContent('owner_manage_kost'); // Muat ulang konten manajemen kos
                    } else {
                        errorBox.textContent = res.message || 'Terjadi kesalahan pada server.';
                        errorBox.classList.remove('hidden');
                    }
                } catch (e) {
                    // Jika response bukan JSON, tampilkan teks yang diterima
                    errorBox.textContent = `Server response: ${text}`;
                    errorBox.classList.remove('hidden');
                    console.error('Save Kos non-JSON response:', text);
                }
            })
            .catch(async error => {
                // network-level or fetch error
                errorBox.textContent = `Terjadi kesalahan jaringan atau server tidak merespon. Cek console untuk detail.`;
                errorBox.classList.remove('hidden');
                console.error('Save Kos fetch error:', error);
            })
            .finally(() => {
                button.disabled = false;
                button.textContent = 'Simpan';
            });
        }

        function closeKamarModal() {
            document.getElementById('kamarModal').classList.add('hidden');
        }

        // Open modal to manage kamar for a specific kost
        function showKamarModal(id_kost) {
            const modal = document.getElementById('kamarModal');
            const content = document.getElementById('kamarModalContent');
            if (!modal || !content) return;

            // Show loading state
            content.innerHTML = `<div class="p-6 text-center text-gray-500"><i class="fas fa-spinner fa-spin mr-2"></i>Memuat daftar kamar...</div>`;
            modal.classList.remove('hidden');

            fetch(`../pemilik_kos/pemilik_manage_kamar_modal.php?id_kost=${id_kost}`)
                .then(async resp => {
                    const text = await resp.text();
                    if (!resp.ok) {
                        // show server response text when available
                        throw new Error(`Gagal memuat data kamar (HTTP ${resp.status}): ${text}`);
                    }
                    return text;
                })
                .then(html => {
                    content.innerHTML = html;
                    // After content loaded, any inline buttons/forms will call global functions (addKamar, deleteKamar)
                })
                .catch(err => {
                    content.innerHTML = `<div class="p-6 text-red-600">❌ ${err.message}</div>`;
                    console.error('showKamarModal error:', err);
                });
        }

        function addKamar(event, id_kost) {
            event.preventDefault();
            const form = document.getElementById('addKamarForm');
            const formData = new FormData(form);
            formData.append('action', 'add');
            formData.append('id_kost', id_kost);
            const button = document.getElementById('addKamarButton');
            const errorBox = document.getElementById('kamarFormError');
            button.disabled = true;
            button.textContent = 'Menambah...';
            errorBox.classList.add('hidden');

            fetch('../pemilik_kos/process_kamar.php', { method: 'POST', body: formData })
                .then(async resp => {
                    const text = await resp.text();
                    if (!resp.ok) {
                        throw new Error(`HTTP ${resp.status}: ${text}`);
                    }
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        throw new Error(`Non-JSON response: ${text}`);
                    }
                })
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire('Berhasil', data.message, 'success');
                        // Muat ulang konten modal kamar
                        showKamarModal(id_kost);
                    } else {
                        errorBox.textContent = data.message || 'Terjadi kesalahan server.';
                        errorBox.classList.remove('hidden');
                    }
                })
                .catch(err => {
                    console.error('Add Kamar Error:', err);
                    errorBox.textContent = 'Gagal menambah kamar. Detail: ' + err.message + '. Lihat console untuk detail.';
                    errorBox.classList.remove('hidden');
                })
                .finally(() => {
                    button.disabled = false;
                    button.textContent = 'Tambah Kamar';
                });
        }

        function editKamar(id, nama, tipe, harga, status, foto, fasilitas) {
            // Set form to edit mode with animation
            const formTitle = document.getElementById('formTitle');
            const saveButton = document.getElementById('saveKamarButton');
            
            document.getElementById('formTitle').innerHTML = '<i class="fas fa-edit mr-2 text-blue-600"></i>Edit Kamar: ' + nama;
            document.getElementById('kamarAction').value = 'edit';
            document.getElementById('kamarId').value = id;
            document.getElementById('nama_kamar').value = nama;
            document.getElementById('tipe_kamar').value = tipe;
            document.getElementById('harga_kamar').value = harga;
            document.getElementById('status_kamar').value = status;
            document.getElementById('fasilitas_kamar').value = fasilitas;
            
            // Set photo preview
            const photoPreview = document.getElementById('kamarPhotoPreview');
            // Pratinjau Foto
            const previewContainer = document.getElementById('kamarPhotoPreview');
            if (foto) {
                // Check if it's already a full URL or a relative path (starts with ../ or /)
                const isPath = foto.startsWith('http') || foto.startsWith('../') || foto.startsWith('/');
                const imgSrc = isPath ? foto : `../uploads/rooms/${foto}`;
                previewContainer.innerHTML = `<img src="${imgSrc}" class="w-full h-full object-cover rounded-xl shadow-sm">`;
            } else {
                previewContainer.innerHTML = '<i class="fas fa-image text-4xl text-gray-300"></i>';
            }
            
            // Animate title change
            formTitle.style.opacity = '0';
            setTimeout(() => {
                formTitle.innerHTML = '<i class="fas fa-edit mr-2 text-purple-600"></i>Edit Kamar';
                formTitle.style.opacity = '1';
            }, 150);
            
            saveButton.innerHTML = '<i class="fas fa-save mr-2"></i>Simpan Perubahan';
            document.getElementById('cancelEditButton').classList.remove('hidden');
            
            // Scroll to form smoothly (works for both layouts)
            const formElement = document.getElementById('kamarForm');
            if (formElement) {
                formElement.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }

        function cancelEdit() {
            // Reset form to add mode with animation
            const formTitle = document.getElementById('formTitle');
            const form = document.getElementById('kamarForm');
            
            form.reset();
            document.getElementById('kamarAction').value = 'add';
            document.getElementById('kamarId').value = '';
            
            // Animate title change
            formTitle.style.opacity = '0';
            setTimeout(() => {
                formTitle.innerHTML = '<i class="fas fa-plus-circle mr-2 text-purple-600"></i>Tambah Kamar Baru';
                formTitle.style.opacity = '1';
            }, 150);
            
            document.getElementById('saveKamarButton').innerHTML = '<i class="fas fa-save mr-2"></i>Simpan';
            document.getElementById('cancelEditButton').classList.add('hidden');
            document.getElementById('kamarFormError').classList.add('hidden');
            
            // Reset photo preview
            const photoPreview = document.getElementById('kamarPhotoPreview');
            if (photoPreview) {
                photoPreview.innerHTML = '<i class="fas fa-image text-purple-200 text-3xl"></i>';
            }
        }

        function previewKamarPhoto(input) {
            const preview = document.getElementById('kamarPhotoPreview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover">`;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function saveKamar(event, id_kost) {
            event.preventDefault();
            const form = document.getElementById('kamarForm');
            const formData = new FormData(form);
            formData.append('id_kost', id_kost);
            const button = document.getElementById('saveKamarButton');
            const errorBox = document.getElementById('kamarFormError');
            const originalText = button.innerHTML;

            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Menyimpan...';
            button.style.opacity = '0.7';
            errorBox.classList.add('hidden');

            fetch('../pemilik_kos/process_kamar.php', { method: 'POST', body: formData })
                .then(async resp => {
                    const text = await resp.text();
                    if (!resp.ok) {
                        throw new Error(`HTTP ${resp.status}: ${text}`);
                    }
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        throw new Error(`Non-JSON response: ${text}`);
                    }
                })
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: '<span class="text-2xl font-bold text-gray-800">Berhasil!</span>',
                            html: `<p class="text-gray-600">${data.message}</p>`,
                            confirmButtonColor: '#9333ea',
                            confirmButtonText: '<i class="fas fa-check mr-2"></i>OK',
                            customClass: {
                                popup: 'rounded-2xl',
                                confirmButton: 'rounded-xl font-semibold px-6 py-3'
                            }
                        });
                        // Reset form and reload modal
                        cancelEdit();
                        showKamarModal(id_kost);
                    } else {
                        errorBox.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>' + (data.message || 'Terjadi kesalahan server.');
                        errorBox.classList.remove('hidden');
                    }
                })
                .catch(err => {
                    console.error('Save Kamar Error:', err);
                    errorBox.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>Gagal menyimpan kamar. Detail: ' + err.message;
                    errorBox.classList.remove('hidden');
                })
                .finally(() => {
                    button.disabled = false;
                    button.innerHTML = originalText;
                    button.style.opacity = '1';
                });
        }

        function deleteKamar(id_kamar, id_kost) {
            Swal.fire({
                title: '<span class="text-2xl font-bold text-gray-800">Konfirmasi Hapus Kamar</span>',
                html: '<p class="text-gray-600">Yakin ingin menghapus kamar ini? Tindakan ini tidak dapat dibatalkan.</p>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-trash-alt mr-2"></i>Ya, Hapus',
                cancelButtonText: '<i class="fas fa-times mr-2"></i>Batal',
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                customClass: {
                    popup: 'rounded-2xl',
                    confirmButton: 'rounded-xl font-semibold px-6 py-3',
                    cancelButton: 'rounded-xl font-semibold px-6 py-3'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading
                    Swal.fire({
                        title: 'Menghapus...',
                        html: '<div class="py-4"><i class="fas fa-spinner fa-spin text-4xl text-purple-600"></i></div>',
                        showConfirmButton: false,
                        allowOutsideClick: false,
                        customClass: {
                            popup: 'rounded-2xl'
                        }
                    });
                    
                    const formData = new FormData();
                    formData.append('action', 'delete');
                    formData.append('id_kamar', id_kamar);

                    fetch('../pemilik_kos/process_kamar.php', { method: 'POST', body: formData })
                        .then(async resp => {
                            const text = await resp.text();
                            if (!resp.ok) {
                                // show server response when available
                                throw new Error(`HTTP ${resp.status}: ${text}`);
                            }
                            try {
                                return JSON.parse(text);
                            } catch (e) {
                                throw new Error(`Non-JSON response: ${text}`);
                            }
                        })
                        .then(data => {
                            if (data.status === 'success') {
                                // Animate removal
                                const elem = document.getElementById(`kamar-${id_kamar}`);
                                if (elem) {
                                    elem.style.transition = 'all 0.3s ease-out';
                                    elem.style.opacity = '0';
                                    elem.style.transform = 'translateX(-20px)';
                                    setTimeout(() => {
                                        elem.remove();
                                        // Update count
                                        const countElem = document.getElementById('totalKamarCount');
                                        if (countElem) {
                                            countElem.textContent = parseInt(countElem.textContent) - 1;
                                        }
                                    }, 300);
                                }
                                
                                Swal.fire({
                                    icon: 'success',
                                    title: '<span class="text-2xl font-bold text-gray-800">Berhasil Dihapus!</span>',
                                    html: `<p class="text-gray-600">${data.message}</p>`,
                                    confirmButtonColor: '#9333ea',
                                    confirmButtonText: '<i class="fas fa-check mr-2"></i>OK',
                                    timer: 2000,
                                    customClass: {
                                        popup: 'rounded-2xl',
                                        confirmButton: 'rounded-xl font-semibold px-6 py-3'
                                    }
                                });
                            } else {
                                Swal.fire('Gagal', 'Gagal menghapus: ' + (data.message || 'Unknown error'), 'error');
                            }
                        })
                        .catch(err => {
                            console.error('Delete Kamar Error:', err);
                            Swal.fire('Error', 'Gagal menghapus kamar. Detail: ' + err.message + '. Lihat console untuk detail.', 'error');
                        });
                }
            });
        }

        // Inisialisasi: Muat konten dashboard awal saat halaman siap
        document.addEventListener('DOMContentLoaded', function() {
            // Cek apakah ada parameter module di URL
            const urlParams = new URLSearchParams(window.location.search);
            const moduleParam = urlParams.get('module');
            
            // Tentukan modul yang akan dimuat (dari URL atau default)
            const moduleToLoad = moduleParam || 'owner_dashboard_summary';
            
            // Jika ada parameter status (untuk filter booking), tambahkan ke nama module untuk diproses loadContent
            // Perhatian: loadContent (dan pemilik_get_module.php) mungkin perlu penyesuaian jika mereka tidak mengharapkan string query di nama module
            // Namun, berdasarkan logika sebelumnya (onclick="loadContent('owner_manage_booking&status=pending')"), 
            // kita bisa meneruskan query string sebagai bagian dari moduleName atau menangani secara terpisah.
            // Solusi aman: Jika ada parameter lain, append manual ke moduleName agar diteruskan ke fetch URL.
            
            let moduleKey = moduleToLoad;
            
            // Kumpulkan parameter lain selain 'module'
            let extraParams = [];
            for (const [key, value] of urlParams) {
                if (key !== 'module') {
                    extraParams.push(`${key}=${value}`);
                }
            }
            
            if (extraParams.length > 0) {
                moduleKey += '&' + extraParams.join('&');
            }

            // Cari link di sidebar yang cocok untuk highlight
            // Kita cari yang data-module nya cocok dengan module dasar (tanpa query string)
            const activeLink = document.querySelector(`[data-module="${moduleToLoad}"]`);
            
            // Buat event palsu
            const fakeEvent = { 
                preventDefault: () => {}, 
                currentTarget: activeLink || document.querySelector('[data-module="owner_dashboard_summary"]') 
            };
            
            loadContent(moduleKey, fakeEvent);
        });

        // Fungsi untuk menginisialisasi grafik setelah konten dimuat
        function initializeCharts() {
            // Pastikan Chart.js sudah dimuat
            if (typeof Chart !== 'undefined') {
                // Grafik Pendapatan
                const incomeCtx = document.getElementById('incomeChart');
                if (incomeCtx && window.chartData) {
                    // Simpan instance chart untuk dihancurkan nanti
                    window.incomeChartInstance = new Chart(incomeCtx.getContext('2d'), {
                        type: 'line',
                        data: {
                            labels: window.chartData.labels,
                            datasets: [{
                                label: 'Pendapatan',
                                data: window.chartData.data,
                                backgroundColor: 'rgba(129, 140, 248, 0.2)',
                                borderColor: 'rgba(129, 140, 248, 1)',
                                borderWidth: 3,
                                pointBackgroundColor: 'rgba(129, 140, 248, 1)',
                                pointBorderColor: '#fff',
                                pointHoverBackgroundColor: '#fff',
                                pointHoverBorderColor: 'rgba(129, 140, 248, 1)',
                                tension: 0.3,
                                fill: true,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                }

                // Grafik Tingkat Hunian
                const occupancyCtx = document.getElementById('occupancyChart');
                if (occupancyCtx && window.occupancyData) {
                    window.occupancyChartInstance = new Chart(occupancyCtx.getContext('2d'), {
                        type: 'doughnut',
                        data: {
                            labels: window.occupancyData.labels,
                            datasets: [{
                                data: window.occupancyData.data,
                                backgroundColor: [
                                    'rgba(34, 197, 94, 0.8)', // Hijau untuk tersedia
                                    'rgba(239, 68, 68, 0.8)'  // Merah untuk terisi
                                ],
                                borderColor: [
                                    'rgba(34, 197, 94, 1)',
                                    'rgba(239, 68, 68, 1)'
                                ],
                                borderWidth: 2
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                }
                            }
                        }
                    });
                }
            }
        }

        // Global payment verification handler so buttons in owner_manage_payments.php work when injected
        function handlePaymentAction(id_payment, id_booking, action) {
            const titles = {
                verify: 'Verifikasi Pembayaran?',
                reject: 'Tolak Pembayaran?'
            };
            const texts = {
                verify: 'Pastikan Anda sudah memeriksa bukti pembayaran. Aksi ini tidak dapat dibatalkan.',
                reject: 'Status booking akan dikembalikan ke "Menunggu Pembayaran" agar penyewa bisa mengunggah ulang.'
            };

            Swal.fire({
                title: titles[action],
                text: texts[action],
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: (action === 'verify' ? '#3085d6' : '#d33'),
                confirmButtonText: 'Ya, lanjutkan!',
                cancelButtonText: 'Batal',
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    const formData = new FormData();
                    formData.append('id_payment', id_payment);
                    formData.append('id_booking', id_booking);
                    formData.append('action', action);

                    return fetch('../pemilik_kos/process_payment_verification.php', { method: 'POST', body: formData })
                        .then(async response => {
                            const text = await response.text();
                            try {
                                const data = JSON.parse(text);
                                if (!response.ok) throw new Error(data.message || `HTTP ${response.status}`);
                                return data;
                            } catch (e) {
                                throw new Error(`Invalid server response: ${text}`);
                            }
                        })
                        .catch(error => Swal.showValidationMessage(`Request gagal: ${error}`));
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
                if (result.isConfirmed && result.value && result.value.status === 'success') {
                    Swal.fire('Berhasil!', result.value.message, 'success').then(() => {
                        // Reload payments module
                        const fakeEvent = { currentTarget: document.querySelector('[data-module="owner_manage_payments"]') };
                        loadContent('owner_manage_payments', fakeEvent);
                    });
                }
            });
        }
    </script>
    <script>
        // Override loadContent untuk menginisialisasi grafik setelah konten dimuat
        const originalLoadContent = loadContent;
        loadContent = function(moduleName, event) {
            // Hapus chart instance yang lama jika ada, untuk mencegah memory leak
            if (window.incomeChartInstance) window.incomeChartInstance.destroy();
            if (window.occupancyChartInstance) window.occupancyChartInstance.destroy();

            // Panggil fungsi loadContent yang asli
            const fetchPromise = originalLoadContent(moduleName, event);

            // Inisialisasi grafik setelah fetch selesai
            if (fetchPromise instanceof Promise) {
                fetchPromise.then(() => {
                    if (moduleName === 'owner_dashboard_summary') {
                        // Beri sedikit waktu agar DOM diperbarui
                        setTimeout(initializeCharts, 100);
                    }
                }).catch(err => console.error("Gagal memuat modul:", err));
            }
        };

        // Booking action handlers (global) moved here so they work for modules loaded via AJAX
        function confirmBooking(id) {
            handleBookingAction(id, 'confirm', '✅ Konfirmasi Pesanan', 'Apakah Anda yakin ingin mengkonfirmasi pesanan ini?', 'Ya, Konfirmasi');
        }
        
        function rejectBooking(id) {
            handleBookingAction(id, 'reject', '❌ Tolak Pesanan', 'Apakah Anda yakin ingin menolak pesanan ini?', 'Ya, Tolak');
        }

        function handleBookingAction(id, action, title, text, confirmButtonText) {
            const isConfirm = action === 'confirm';
            const iconColor = isConfirm ? '#10b981' : '#ef4444';
            const iconBg = isConfirm ? 'bg-green-100' : 'bg-red-100';
            const iconClass = isConfirm ? 'fa-check-circle' : 'fa-times-circle';
            
            Swal.fire({
                title: `<strong class="text-2xl">${title}</strong>`,
                html: `
                    <div class="text-center py-4">
                        <div class="w-20 h-20 ${iconBg} rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas ${iconClass} text-4xl" style="color: ${iconColor}"></i>
                        </div>
                        <p class="text-gray-700 text-lg mb-2">${text}</p>
                        ${isConfirm 
                            ? '<p class="text-sm text-gray-500">Penyewa akan menerima notifikasi konfirmasi</p>' 
                            : '<p class="text-sm text-gray-500">Kamar akan kembali tersedia untuk penyewa lain</p>'
                        }
                    </div>
                `,
                icon: isConfirm ? 'question' : 'warning',
                showCancelButton: true,
                confirmButtonText: `<i class="fas ${isConfirm ? 'fa-check' : 'fa-times'} mr-2"></i>${confirmButtonText}`,
                cancelButtonText: '<i class="fas fa-arrow-left mr-2"></i>Batal',
                confirmButtonColor: isConfirm ? '#10b981' : '#ef4444',
                cancelButtonColor: '#6b7280',
                customClass: {
                    popup: 'rounded-2xl',
                    confirmButton: 'rounded-xl font-semibold px-6 py-3',
                    cancelButton: 'rounded-xl font-semibold px-6 py-3',
                    htmlContainer: 'p-0'
                },
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    const formData = new FormData();
                    formData.append('id_booking', id);
                    formData.append('action', action);

                    return fetch('../pemilik_kos/process_booking_action.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json().then(data => ({ok: response.ok, data})))
                    .then(({ok, data}) => {
                        if (!ok) throw new Error(data.message || 'Request gagal');
                        return data;
                    })
                    .catch(error => {
                        Swal.showValidationMessage(`
                            <div class="text-left">
                                <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>
                                <span class="text-red-600">Request gagal: ${error.message}</span>
                            </div>
                        `);
                    });
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
                if (result.isConfirmed && result.value.status === 'success') {
                    const successIcon = isConfirm ? 'fa-check-circle' : 'fa-ban';
                    const successColor = isConfirm ? 'green' : 'orange';
                    
                    Swal.fire({
                        title: '<strong class="text-2xl">🎉 Berhasil!</strong>',
                        html: `
                            <div class="text-center py-4">
                                <div class="w-20 h-20 bg-${successColor}-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i class="fas ${successIcon} text-4xl text-${successColor}-500"></i>
                                </div>
                                <p class="text-gray-700 text-lg font-medium mb-3">${result.value.message}</p>
                                ${isConfirm 
                                    ? `
                                        <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-lg mt-4">
                                            <div class="flex items-start">
                                                <i class="fas fa-info-circle text-green-500 text-xl mt-1 mr-3"></i>
                                                <div class="text-left">
                                                    <p class="text-sm text-green-800 font-medium">Penyewa telah menerima notifikasi konfirmasi</p>
                                                    <p class="text-xs text-green-600 mt-1">Silakan tunggu pembayaran dari penyewa</p>
                                                </div>
                                            </div>
                                        </div>
                                    ` 
                                    : `
                                        <div class="bg-orange-50 border-l-4 border-orange-500 p-4 rounded-lg mt-4">
                                            <div class="flex items-start">
                                                <i class="fas fa-info-circle text-orange-500 text-xl mt-1 mr-3"></i>
                                                <div class="text-left">
                                                    <p class="text-sm text-orange-800 font-medium">Pesanan telah dibatalkan</p>
                                                    <p class="text-xs text-orange-600 mt-1">Kamar kembali tersedia untuk penyewa lain</p>
                                                </div>
                                            </div>
                                        </div>
                                    `
                                }
                            </div>
                        `,
                        icon: 'success',
                        confirmButtonText: '<i class="fas fa-check mr-2"></i>OK, Mengerti',
                        confirmButtonColor: isConfirm ? '#10b981' : '#f59e0b',
                        customClass: {
                            popup: 'rounded-2xl',
                            confirmButton: 'rounded-xl font-semibold px-6 py-3',
                            htmlContainer: 'p-0'
                        },
                        timer: 3000,
                        timerProgressBar: true
                    }).then(() => {
                        // Muat ulang modul yang sedang aktif
                        const activeModule = document.querySelector('.sidebar-link.active-link')?.dataset.module || 'owner_dashboard_summary';
                        loadContent(activeModule, { currentTarget: document.querySelector(`[data-module="${activeModule}"]`) });
                    });
                }
            });
        }

        function previewImage(input, previewId) {
            const preview = document.getElementById(previewId);
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" class="h-full w-full object-contain rounded-lg">`;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Helper to preview images for a specific category input
        function previewCategory(input) {
            // Find the container for previews (assumed to be the next sibling or found by class)
            // In our HTML structure: input.parent -> next Sibling is .preview-container
            const container = input.parentElement.parentElement.querySelector('.preview-container'); 
            if(!container) return;
            
            container.innerHTML = ''; // Clear previous previews
            
            if (input.files) {
                Array.from(input.files).forEach(file => {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const imgDiv = document.createElement('div');
                        imgDiv.className = 'relative h-20 w-full rounded-lg overflow-hidden border border-slate-200';
                        imgDiv.innerHTML = `<img src="${e.target.result}" class="h-full w-full object-cover">`;
                        container.appendChild(imgDiv);
                    }
                    reader.readAsDataURL(file);
                });
                
                // Update label feedback
                const count = input.files.length;
                const feedbackDiv = input.nextElementSibling;
                if (feedbackDiv) {
                    if (count > 0) {
                        feedbackDiv.innerHTML = `
                             <i class="fas fa-check-circle text-3xl text-emerald-500 mb-2"></i>
                             <p class="text-sm font-bold text-slate-700">${count} Foto Dipilih</p>
                        `;
                    } else {
                        // Reset to default icon (we might need to store original HTML or just simplify)
                        // Simplified reset:
                         feedbackDiv.innerHTML = `
                            <i class="fas fa-images text-xl mb-1 group-hover:text-purple-500 transition-colors"></i>
                            <p class="text-xs font-medium text-slate-500">Klik untuk upload</p>
                        `;
                    }
                }
            }
        }

        // Function to add a new room type input field
        // Function to add a new room type input field
        function addRoomTypeInput(existingData = null) {
            const container = document.getElementById('roomTypesContainer');
            // We use simple array notation type_name[] which PHP handles automatically as arrays.
            // No need for explicit indexing [0], [1] unless keys matter. PHP re-indexes numerically.
            
            const div = document.createElement('div');
            div.className = 'bg-slate-50 p-4 rounded-xl border border-slate-200 relative';
            div.innerHTML = `
                <div class="absolute top-2 right-2 cursor-pointer text-slate-400 hover:text-red-500 transition-colors" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </div>
                <div class="grid grid-cols-1 gap-3">
                    <input type="hidden" name="type_id[]" value="${existingData ? existingData.id_tipe : 0}">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Nama Tipe Kamar</label>
                        <input type="text" name="type_name[]" value="${existingData ? existingData.nama_tipe : ''}" class="w-full text-sm border-slate-200 rounded-lg focus:ring-purple-200 p-2" placeholder="Ex: Deluxe, Standard, VIP" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Foto Tipe</label>
                        <div class="flex items-center gap-3">
                            <div class="h-12 w-12 bg-white rounded-lg border border-slate-200 flex items-center justify-center overflow-hidden shrink-0">
                                ${existingData && existingData.foto_tipe ? `<img src="${existingData.foto_tipe}" class="h-full w-full object-cover">` : '<i class="fas fa-image text-slate-300"></i>'}
                            </div>
                            <input type="file" name="type_image[]" class="text-xs text-slate-500 file:mr-2 file:py-1 file:px-2 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100" accept="image/*">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Peraturan Kamar (Opsional)</label>
                        <textarea name="type_peraturan[]" rows="2" class="w-full text-sm border-slate-200 rounded-lg focus:ring-purple-200 p-2" placeholder="Contoh: Maksimal 2 orang...">${existingData ? (existingData.peraturan_kamar || '') : ''}</textarea>
                    </div>
                </div>
            `;
            container.appendChild(div);
        }

        // Function to remove a room type input field
        function removeRoomTypeInput(button) {
            button.closest('.flex.items-center.space-x-2').remove();
            // Re-index inputs after removal to ensure correct array submission
            const container = document.getElementById('roomTypesContainer');
            Array.from(container.children).forEach((child, idx) => {
                child.querySelectorAll('input').forEach(input => {
                    if (input.name) {
                        input.name = input.name.replace(/\[\d+\]/, `[${idx}]`);
                    }
                });
            });
        }
    </script>

    <!-- Modal untuk Tambah/Edit Kos -->
    <div id="kosModal" class="fixed inset-0 bg-black bg-opacity-60 hidden flex items-center justify-center z-50 p-4 backdrop-blur-sm">
        <div class="bg-white rounded-2xl w-full max-w-xl mx-4 max-h-[90vh] overflow-y-auto shadow-2xl transition-all transform scale-100">
            <form id="kosForm" onsubmit="saveKos(event)">
                <div class="p-6 border-b bg-gradient-to-r from-slate-700 to-slate-800 text-white rounded-t-2xl flex justify-between items-center sticky top-0 z-10">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-white bg-opacity-10 rounded-lg flex items-center justify-center backdrop-blur-sm">
                            <i class="fas fa-building text-xl"></i>
                        </div>
                        <h3 id="kosModalTitle" class="text-xl font-bold tracking-wide">Tambah Kos Baru</h3>
                    </div>
                    <button type="button" onclick="closeKosModal()" class="text-white hover:bg-white hover:bg-opacity-20 rounded-full w-10 h-10 flex items-center justify-center transition-all text-xl">&times;</button>
                </div>

                <div class="p-8">
                    <div id="kosModalError" class="hidden bg-red-50 text-red-600 p-4 rounded-xl text-sm mb-6 border border-red-100 flex items-start">
                        <i class="fas fa-exclamation-circle mt-1 mr-3 flex-shrink-0"></i>
                        <span id="kosModalErrorText"></span>
                    </div>
                    <input type="hidden" name="action" id="kosAction" value="add">
                    <input type="hidden" name="id_kost" id="id_kost">
                    <input type="hidden" name="gambar_lama" id="gambar_lama">

                    <div class="space-y-6">
                        <!-- Nama Kos -->
                        <div>
                            <label for="nama_kost" class="block text-sm font-semibold text-slate-700 mb-2">
                                Nama Kos
                            </label>
                            <div class="relative">
                                <span class="absolute left-4 top-3.5 text-slate-400"><i class="fas fa-home"></i></span>
                                <input type="text" name="nama_kost" id="nama_kost" class="pl-11 block w-full bg-slate-50 border border-slate-200 rounded-xl shadow-sm p-3 focus:bg-white focus:border-slate-500 focus:ring-2 focus:ring-slate-200 transition-all font-medium" placeholder="Contoh: Kos Bahagia" required>
                            </div>
                        </div>

                        <!-- Harga dan Tipe -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="harga" class="block text-sm font-semibold text-slate-700 mb-2">
                                    Harga Sewa <span class="text-slate-400 font-normal">(per bulan)</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute left-4 top-3.5 text-slate-400"><i class="fas fa-tag"></i></span>
                                    <input type="number" name="harga" id="harga" class="pl-11 block w-full bg-slate-50 border border-slate-200 rounded-xl shadow-sm p-3 focus:bg-white focus:border-slate-500 focus:ring-2 focus:ring-slate-200 transition-all font-medium" placeholder="Contoh: 1500000" required>
                                </div>
                            </div>
                            <div>
                                <label for="tipe_kost" class="block text-sm font-semibold text-slate-700 mb-2">
                                    Tipe Kos
                                </label>
                                <div class="relative">
                                    <span class="absolute left-4 top-3.5 text-slate-400"><i class="fas fa-venus-mars"></i></span>
                                    <select name="tipe_kost" id="tipe_kost" class="pl-11 block w-full bg-slate-50 border border-slate-200 rounded-xl shadow-sm p-3 focus:bg-white focus:border-slate-500 focus:ring-2 focus:ring-slate-200 transition-all font-medium appearance-none" required>
                                        <option value="Campuran">🚻 Campuran</option>
                                        <option value="Putra">🚹 Putra</option>
                                        <option value="Putri">🚺 Putri</option>
                                    </select>
                                    <div class="absolute right-4 top-3.5 text-slate-400 pointer-events-none">
                                        <i class="fas fa-chevron-down text-xs"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Jenis Kamar -->
                        <div>
                            <label for="jenis_kamar" class="block text-sm font-semibold text-slate-700 mb-2">
                                Jenis/Fasilitas Utama Kamar
                            </label>
                            <div class="relative">
                                <span class="absolute left-4 top-3.5 text-slate-400"><i class="fas fa-bed"></i></span>
                                <select name="jenis_kamar" id="jenis_kamar" class="pl-11 block w-full bg-slate-50 border border-slate-200 rounded-xl shadow-sm p-3 focus:bg-white focus:border-slate-500 focus:ring-2 focus:ring-slate-200 transition-all font-medium appearance-none" required>
                                    <option value="Kamar Mandi Dalam">🚿 Kamar Mandi Dalam</option>
                                    <option value="Kamar Mandi Luar">🚪 Kamar Mandi Luar</option>
                                    <option value="AC + Kamar Mandi Dalam">❄️ AC + Kamar Mandi Dalam</option>
                                    <option value="AC + Kamar Mandi Luar">❄️ AC + Kamar Mandi Luar</option>
                                    <option value="VVIP (Lengkap)">💎 VVIP (Lengkap)</option>
                                </select>
                                <div class="absolute right-4 top-3.5 text-slate-400 pointer-events-none">
                                    <i class="fas fa-chevron-down text-xs"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Alamat -->
                        <div>
                            <label for="alamat" class="block text-sm font-semibold text-slate-700 mb-2">
                                Alamat Lengkap
                            </label>
                            <div class="relative">
                                <span class="absolute left-4 top-3.5 text-slate-400"><i class="fas fa-map-marker-alt"></i></span>
                                <textarea name="alamat" id="alamat" rows="2" class="pl-11 block w-full bg-slate-50 border border-slate-200 rounded-xl shadow-sm p-3 focus:bg-white focus:border-slate-500 focus:ring-2 focus:ring-slate-200 transition-all font-medium" placeholder="Jalan, Nomor, Kota..." required></textarea>
                            </div>
                        </div>

                        <!-- Deskripsi -->
                        <div>
                            <label for="deskripsi" class="block text-sm font-semibold text-slate-700 mb-2">
                                Deskripsi
                            </label>
                            <div class="relative">
                                <span class="absolute left-4 top-3.5 text-slate-400"><i class="fas fa-align-left"></i></span>
                                <textarea name="deskripsi" id="deskripsi" rows="3" class="pl-11 block w-full bg-slate-50 border border-slate-200 rounded-xl shadow-sm p-3 focus:bg-white focus:border-slate-500 focus:ring-2 focus:ring-slate-200 transition-all font-medium" placeholder="Jelaskan fasilitas dan keunggulan kos..." required></textarea>
                            </div>
                        </div>

                        <!-- Fasilitas -->
                        <div>
                            <label for="fasilitas" class="block text-sm font-semibold text-slate-700 mb-2">
                                Fasilitas <span class="text-slate-400 font-normal">(Pisahkan dengan koma)</span>
                            </label>
                            <div class="relative">
                                <span class="absolute left-4 top-3.5 text-slate-400"><i class="fas fa-list-ul"></i></span>
                                <input type="text" name="fasilitas" id="fasilitas" class="pl-11 block w-full bg-slate-50 border border-slate-200 rounded-xl shadow-sm p-3 focus:bg-white focus:border-slate-500 focus:ring-2 focus:ring-2 focus:ring-slate-200 transition-all font-medium" placeholder="WiFi, AC, Kamar Mandi Dalam..." required>
                            </div>
                        </div>

                        <!-- Peraturan Kos -->
                        <div>
                            <label for="peraturan" class="block text-sm font-semibold text-slate-700 mb-2">
                                Peraturan Kos <span class="text-slate-400 font-normal">(Opsional)</span>
                            </label>
                            <div class="relative">
                                <span class="absolute left-4 top-3.5 text-slate-400"><i class="fas fa-gavel"></i></span>
                                <textarea name="peraturan" id="peraturan" rows="3" class="pl-11 block w-full bg-slate-50 border border-slate-200 rounded-xl shadow-sm p-3 focus:bg-white focus:border-slate-500 focus:ring-2 focus:ring-slate-200 transition-all font-medium" placeholder="Contoh: Dilarang merokok, jam malam pukul 22.00, tidak boleh bawa hewan peliharaan..."></textarea>
                            </div>
                        </div>

                        <!-- Gambar Utama -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Gambar Utama</label>
                            <div class="border-2 border-dashed border-slate-300 rounded-xl p-4 text-center hover:bg-slate-50 transition-colors relative">
                                <input type="file" name="gambar" id="gambar" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" onchange="previewImage(this, 'image_preview')">
                                <div id="image_preview" class="h-48 w-full flex flex-col items-center justify-center text-slate-400 pointer-events-none">
                                    <i class="fas fa-image text-3xl mb-2"></i>
                                    <p class="text-sm">Klik atau tarik gambar ke sini</p>
                                </div>
                            </div>
                        </div>

                        <!-- Kategori Foto -->
                        <div class="space-y-6 border-t pt-4">
                            <h4 class="font-bold text-gray-800">Galeri Kos</h4>
                            
                            <!-- Foto Bangunan -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">Foto Bangunan (Tampak Depan/Luaran)</label>
                                <div id="existing_photos_Bangunan" class="grid grid-cols-4 gap-3 mb-3 empty:hidden"></div>
                                <div class="border-2 border-dashed border-slate-300 rounded-xl p-3 text-center hover:bg-slate-50 transition-colors relative group">
                                    <input type="file" name="foto_bangunan[]" multiple class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" onchange="previewCategory(this)">
                                    <div class="flex flex-col items-center justify-center text-slate-400 pointer-events-none">
                                        <i class="fas fa-building text-xl mb-1 group-hover:text-purple-500 transition-colors"></i>
                                        <p class="text-xs font-medium text-slate-500">Upload Foto Bangunan</p>
                                    </div>
                                </div>
                                <div class="mt-2 grid grid-cols-4 gap-2 preview-container"></div>
                            </div>

                            <!-- Foto Kamar -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">Foto Kamar</label>
                                <div id="existing_photos_Kamar" class="grid grid-cols-4 gap-3 mb-3 empty:hidden"></div>
                                <div class="border-2 border-dashed border-slate-300 rounded-xl p-3 text-center hover:bg-slate-50 transition-colors relative group">
                                    <input type="file" name="foto_kamar[]" multiple class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" onchange="previewCategory(this)">
                                    <div class="flex flex-col items-center justify-center text-slate-400 pointer-events-none">
                                        <i class="fas fa-bed text-xl mb-1 group-hover:text-purple-500 transition-colors"></i>
                                        <p class="text-xs font-medium text-slate-500">Upload Foto Kamar</p>
                                    </div>
                                </div>
                                <div class="mt-2 grid grid-cols-4 gap-2 preview-container"></div>
                            </div>

                            <!-- Foto Kamar Mandi -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">Foto Kamar Mandi</label>
                                <div id="existing_photos_Kamar Mandi" class="grid grid-cols-4 gap-3 mb-3 empty:hidden"></div>
                                <div class="border-2 border-dashed border-slate-300 rounded-xl p-3 text-center hover:bg-slate-50 transition-colors relative group">
                                    <input type="file" name="foto_kamar_mandi[]" multiple class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" onchange="previewCategory(this)">
                                    <div class="flex flex-col items-center justify-center text-slate-400 pointer-events-none">
                                        <i class="fas fa-bath text-xl mb-1 group-hover:text-purple-500 transition-colors"></i>
                                        <p class="text-xs font-medium text-slate-500">Upload Foto Kamar Mandi</p>
                                    </div>
                                </div>
                                <div class="mt-2 grid grid-cols-4 gap-2 preview-container"></div>
                            </div>

                            <!-- Foto Fasilitas Bersama -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">Foto Fasilitas Bersama (Dapur, Parkir, dll)</label>
                                <div id="existing_photos_Fasilitas Bersama" class="grid grid-cols-4 gap-3 mb-3 empty:hidden"></div>
                                <div class="border-2 border-dashed border-slate-300 rounded-xl p-3 text-center hover:bg-slate-50 transition-colors relative group">
                                    <input type="file" name="foto_fasilitas[]" multiple class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" onchange="previewCategory(this)">
                                    <div class="flex flex-col items-center justify-center text-slate-400 pointer-events-none">
                                        <i class="fas fa-users text-xl mb-1 group-hover:text-purple-500 transition-colors"></i>
                                        <p class="text-xs font-medium text-slate-500">Upload Foto Fasilitas</p>
                                    </div>
                                </div>
                                <div class="mt-2 grid grid-cols-4 gap-2 preview-container"></div>
                            </div>

                            <!-- Foto Lainnya -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">Foto Lainnya</label>
                                <div id="existing_photos_Lainnya" class="grid grid-cols-4 gap-3 mb-3 empty:hidden"></div>
                                <div class="border-2 border-dashed border-slate-300 rounded-xl p-3 text-center hover:bg-slate-50 transition-colors relative group">
                                    <input type="file" name="foto_lainnya[]" multiple class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" onchange="previewCategory(this)">
                                    <div class="flex flex-col items-center justify-center text-slate-400 pointer-events-none">
                                        <i class="fas fa-images text-xl mb-1 group-hover:text-purple-500 transition-colors"></i>
                                        <p class="text-xs font-medium text-slate-500">Upload Foto Lainnya</p>
                                    </div>
                                </div>
                                <div class="mt-2 grid grid-cols-4 gap-2 preview-container"></div>
                            </div>
                            <!-- Tipe Kamar Management -->
                            <div class="space-y-4 border-t pt-4">
                                <div class="flex justify-between items-center">
                                    <h4 class="font-bold text-gray-800">Tipe Kamar (Master Data)</h4>
                                    <button type="button" onclick="addRoomTypeInput()" class="text-xs bg-purple-100 text-purple-700 px-3 py-1.5 rounded-full font-bold hover:bg-purple-200 transition-colors">
                                        <i class="fas fa-plus mr-1"></i> Tambah Tipe
                                    </button>
                                </div>
                                <div id="roomTypesContainer" class="space-y-3">
                                    <!-- Dynamic Inputs -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="p-6 bg-gray-50 border-t rounded-b-2xl flex justify-end space-x-3">
                    <button type="button" onclick="closeKosModal()" class="bg-gray-200 text-gray-700 px-6 py-3 rounded-lg hover:bg-gray-300 transition-all shadow-md hover:shadow-lg">
                        <i class="fas fa-times mr-2"></i>Batal
                    </button>
                    <button id="saveKosButton" type="submit" class="bg-gradient-to-r from-purple-600 to-indigo-600 text-white px-8 py-3 rounded-lg hover:from-purple-700 hover:to-indigo-700 transition-all shadow-md hover:shadow-lg">
                        <i class="fas fa-save mr-2"></i>Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal untuk Kelola Kamar -->
    <div id="kamarModal" class="fixed inset-0 bg-black bg-opacity-60 hidden flex items-center justify-center z-50 p-4 backdrop-blur-sm">
        <div id="kamarModalContent" class="bg-white rounded-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto shadow-2xl transition-all transform scale-100">
            <!-- Konten dinamis akan dimuat di sini -->
        </div>
    </div>
</body>
</html>
