<?php
session_start();
// Autentikasi dan Redirect
if (!isset($_SESSION['user_logged_in']) || $_SESSION['role'] !== 'pemilik') { //
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
 
// Persiapan Data Tampilan Sederhana untuk Header
$id_pemilik = $_SESSION['user_id'];
$fullName = $_SESSION['fullname'] ?? 'Pemilik Kos';
$names = explode(' ', $fullName);
$initials = '';
foreach ($names as $name) { $initials .= strtoupper(substr($name, 0, 1)); }
$firstName = $names[0] ?? '';
$userEmail = $_SESSION['email'] ?? ''; // Ambil email dari sesi
$userPhoto = $_SESSION['foto_profil'] ?? null;
$userRole = $_SESSION['role'] ?? 'pemilik';

// Ambil jumlah notifikasi yang belum dibaca
$stmt_notif = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE id_user = ? AND is_read = 0");
$stmt_notif->bind_param("i", $id_pemilik);
$stmt_notif->execute();
$notif_count = $stmt_notif->get_result()->fetch_assoc()['count'];
$stmt_notif->close();
 
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
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .sidebar-link::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background: linear-gradient(180deg, #94a3b8, #64748b);
            transform: scaleY(0);
            transition: transform 0.3s ease;
        }
        
        .sidebar-link:hover {
            background: rgba(255, 255, 255, 0.12);
            padding-left: 28px;
        }
        
        .sidebar-link:hover::before {
            transform: scaleY(1);
        }
        
        .active-link {
            background: rgba(255, 255, 255, 0.15) !important;
            border-left-color: #94a3b8 !important;
            font-weight: 600;
            padding-left: 28px !important;
            box-shadow: inset 0 0 20px rgba(148, 163, 184, 0.2);
        }
        
        .active-link::after {
            content: '';
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 60%;
            background: #94a3b8;
            border-radius: 2px 0 0 2px;
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
                <?php if ($userPhoto): ?>
                    <img id="sidebarUserPhoto" src="../uploads/profiles/<?php echo htmlspecialchars($userPhoto); ?>" alt="Foto Profil" class="w-12 h-12 rounded-full object-cover flex-shrink-0 ring-2 ring-white ring-opacity-30 group-hover:ring-opacity-60 transition-all">
                <?php else: ?>
                    <div id="sidebarUserPhoto" class="w-12 h-12 bg-slate-600 rounded-full flex items-center justify-center text-slate-300 font-bold text-lg flex-shrink-0 ring-2 ring-white ring-opacity-30 group-hover:ring-opacity-60 transition-all"><?php echo $initials; ?></div>
                <?php endif; ?>
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

    <!-- ======================================================= -->
    <!-- MODAL PROFIL SAYA -->
    <!-- ======================================================= -->
    <div id="profileModal" class="fixed inset-0 bg-black bg-opacity-60 hidden items-center justify-center z-50 p-4 backdrop-blur-sm">
        <div class="bg-white rounded-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto shadow-2xl">
            <div class="p-6 border-b bg-gradient-to-r from-slate-600 to-slate-700 text-white rounded-t-2xl flex justify-between items-center sticky top-0 z-10">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                        <i class="fas fa-user-circle text-xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold">Profil Saya</h3>
                </div>
                <button type="button" onclick="closeProfileModal()" class="text-white hover:bg-white hover:bg-opacity-20 rounded-full w-10 h-10 flex items-center justify-center transition-all text-2xl">&times;</button>
            </div>
            <!-- Form Ganti Foto Profil -->
            <div class="p-6 border-b bg-gradient-to-r from-blue-50 to-indigo-50">
                <h4 class="text-lg font-semibold mb-4 text-slate-800 flex items-center">
                    <i class="fas fa-camera mr-2 text-blue-600"></i>
                    Ganti Foto Profil
                </h4>
                <form id="photoUpdateForm" onsubmit="savePhoto(event)" class="flex flex-col sm:flex-row items-center gap-4">
                    <input type="hidden" name="action" value="update_photo"> 
                    <div class="relative group">
                        <img id="photoPreview" src="<?php echo $userPhoto ? '../uploads/profiles/' . htmlspecialchars($userPhoto) : 'https://via.placeholder.com/100'; ?>" alt="Preview" class="w-24 h-24 rounded-full object-cover bg-gray-200 ring-4 ring-blue-200 group-hover:ring-blue-400 transition-all">
                        <div class="absolute inset-0 bg-black bg-opacity-40 rounded-full opacity-0 group-hover:opacity-100 transition-all flex items-center justify-center">
                            <i class="fas fa-camera text-white text-2xl"></i>
                        </div>
                    </div>
                    <div class="flex-grow w-full sm:w-auto">
                        <input type="file" name="foto_profil" id="foto_profil" class="block w-full text-sm text-gray-600 file:mr-4 file:py-3 file:px-6 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-700 transition-all cursor-pointer" onchange="previewPhoto(event)" required>
                        <div id="photoUpdateError" class="hidden text-red-600 text-sm mt-2 bg-red-50 p-2 rounded-lg"></div>
                    </div>
                    <div class="w-full sm:w-auto">
                        <button type="submit" id="savePhotoButton" class="w-full sm:w-auto bg-gradient-to-r from-blue-600 to-blue-700 text-white py-3 px-6 rounded-full hover:from-blue-700 hover:to-blue-800 transition-all shadow-lg hover:shadow-xl">
                            <i class="fas fa-upload mr-2"></i>Unggah
                        </button>
                    </div>
                </form>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Form Informasi Pribadi -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                    <h4 class="text-lg font-semibold mb-4 text-slate-800 flex items-center">
                        <i class="fas fa-user-edit mr-2 text-slate-600"></i>
                        Informasi Pribadi
                    </h4>
                    <form id="profileUpdateForm" onsubmit="saveProfile(event)" class="space-y-4">
                        <input type="hidden" name="action" value="update_profile">
                        <div>
                            <label for="profile_fullname" class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-id-card mr-1 text-slate-500"></i>Nama Lengkap
                            </label>
                            <input type="text" name="fullname" id="profile_fullname" value="<?php echo htmlspecialchars($fullName); ?>" required class="mt-1 block w-full border-2 border-gray-300 rounded-lg shadow-sm p-3 form-input hover:border-slate-400 focus:border-slate-500 transition-all">
                        </div>
                        <div>
                            <label for="profile_email" class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-envelope mr-1 text-slate-500"></i>Email
                            </label>
                            <input type="email" id="profile_email" value="<?php echo htmlspecialchars($userEmail); ?>" disabled class="mt-1 block w-full border-2 border-gray-200 rounded-lg shadow-sm p-3 bg-gray-50 text-gray-500 cursor-not-allowed">
                        </div>
                        <div id="profileUpdateError" class="hidden text-red-600 text-sm bg-red-50 p-3 rounded-lg border border-red-200"></div>
                        <div class="text-right pt-2">
                            <button type="submit" id="saveProfileButton" class="bg-gradient-to-r from-slate-600 to-slate-700 text-white py-3 px-6 rounded-lg hover:from-slate-700 hover:to-slate-800 transition-all shadow-md hover:shadow-lg">
                                <i class="fas fa-save mr-2"></i>Simpan Nama
                            </button>
                        </div>
                    </form>
                </div>
                <!-- Form Ubah Password -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                    <h4 class="text-lg font-semibold mb-4 text-slate-800 flex items-center">
                        <i class="fas fa-lock mr-2 text-slate-600"></i>
                        Ubah Password
                    </h4>
                    <form id="passwordUpdateForm" onsubmit="savePassword(event)" class="space-y-4">
                        <input type="hidden" name="action" value="update_password">
                        <div>
                            <label for="old_password" class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-key mr-1 text-slate-500"></i>Password Lama
                            </label>
                            <input type="password" name="old_password" id="old_password" required class="mt-1 block w-full border-2 border-gray-300 rounded-lg shadow-sm p-3 form-input hover:border-slate-400 focus:border-slate-500 transition-all">
                        </div>
                        <div>
                            <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-key mr-1 text-slate-500"></i>Password Baru
                            </label>
                            <input type="password" name="new_password" id="new_password" required class="mt-1 block w-full border-2 border-gray-300 rounded-lg shadow-sm p-3 form-input hover:border-slate-400 focus:border-slate-500 transition-all">
                        </div>
                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-check-circle mr-1 text-slate-500"></i>Konfirmasi Password Baru
                            </label>
                            <input type="password" name="confirm_password" id="confirm_password" required class="mt-1 block w-full border-2 border-gray-300 rounded-lg shadow-sm p-3 form-input hover:border-slate-400 focus:border-slate-500 transition-all">
                        </div>
                        <div id="passwordUpdateError" class="hidden text-red-600 text-sm bg-red-50 p-3 rounded-lg border border-red-200"></div>
                        <div class="text-right pt-2">
                            <button type="submit" id="savePasswordButton" class="bg-gradient-to-r from-gray-700 to-gray-800 text-white py-3 px-6 rounded-lg hover:from-gray-800 hover:to-gray-900 transition-all shadow-md hover:shadow-lg">
                                <i class="fas fa-shield-alt mr-2"></i>Ubah Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

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

        // --- LOGIKA MODAL PROFIL ---
        function showProfileModal() {
            document.getElementById('profileModal').classList.remove('hidden');
            document.getElementById('profileModal').classList.add('flex');
        }

        function showNotifications() {
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
                                                ${notif.link ? `<a href="${notif.link}" class="text-xs text-blue-600 hover:text-blue-700 font-medium hover:underline">Lihat Detail →</a>` : ''}
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
                            const badge = document.getElementById('notifBadge');
                            if (badge) {
                                badge.style.animation = 'fadeOut 0.3s ease';
                                setTimeout(() => badge.remove(), 300);
                            }
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

        function closeProfileModal() {
            document.getElementById('profileModal').classList.add('hidden');
            document.getElementById('profileModal').classList.remove('flex');
            // Reset form dan pesan error
            document.getElementById('profileUpdateForm').reset();
            document.getElementById('passwordUpdateForm').reset();
            document.getElementById('profileUpdateError').classList.add('hidden');
            document.getElementById('passwordUpdateError').classList.add('hidden');
        }

        function previewPhoto(event) {
            const reader = new FileReader();
            reader.onload = function(){
                document.getElementById('photoPreview').src = reader.result;
            };
            reader.readAsDataURL(event.target.files[0]);
        }

        function saveProfile(event) {
            event.preventDefault();
            const form = document.getElementById('profileUpdateForm');
            const formData = new FormData(form);
            const button = document.getElementById('saveProfileButton');
            const errorBox = document.getElementById('profileUpdateError');

            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Menyimpan...';
            errorBox.classList.add('hidden');

            fetch('../pemilik_kos/process_profile.php', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: '<strong>Berhasil!</strong>',
                            html: `<p class="text-gray-600">${data.message}</p>`,
                            confirmButtonColor: '#475569',
                            customClass: {
                                popup: 'rounded-2xl',
                                confirmButton: 'rounded-lg px-6 py-3'
                            }
                        });
                        // Update nama di header
                        document.querySelector('#sidebar .font-bold.text-white').textContent = data.new_name;
                        document.getElementById('profile_fullname').value = data.new_name;
                        closeProfileModal();
                    } else {
                        errorBox.innerHTML = `<i class="fas fa-exclamation-circle mr-2"></i>${data.message}`;
                        errorBox.classList.remove('hidden');
                    }
                })
                .catch(err => {
                    errorBox.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>Terjadi kesalahan jaringan.';
                    errorBox.classList.remove('hidden');
                    console.error('Save Profile Error:', err);
                })
                .finally(() => {
                    button.disabled = false;
                    button.innerHTML = '<i class="fas fa-save mr-2"></i>Simpan Nama';
                });
        }

        function savePassword(event) {
            event.preventDefault();
            const form = document.getElementById('passwordUpdateForm');
            const formData = new FormData(form);
            const button = document.getElementById('savePasswordButton');
            const errorBox = document.getElementById('passwordUpdateError');

            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Menyimpan...';
            errorBox.classList.add('hidden');

            fetch('../pemilik_kos/process_profile.php', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: '<strong>Berhasil!</strong>',
                            html: `<p class="text-gray-600">${data.message}</p>`,
                            confirmButtonColor: '#475569',
                            customClass: {
                                popup: 'rounded-2xl',
                                confirmButton: 'rounded-lg px-6 py-3'
                            }
                        });
                        form.reset();
                        closeProfileModal();
                    } else {
                        errorBox.innerHTML = `<i class="fas fa-exclamation-circle mr-2"></i>${data.message}`;
                        errorBox.classList.remove('hidden');
                    }
                })
                .catch(err => {
                    errorBox.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>Terjadi kesalahan jaringan.';
                    errorBox.classList.remove('hidden');
                    console.error('Save Password Error:', err);
                })
                .finally(() => {
                    button.disabled = false;
                    button.innerHTML = '<i class="fas fa-shield-alt mr-2"></i>Ubah Password';
                });
        }

        function savePhoto(event) {
            event.preventDefault();
            const form = document.getElementById('photoUpdateForm');
            const formData = new FormData(form);
            const button = document.getElementById('savePhotoButton');
            const errorBox = document.getElementById('photoUpdateError');

            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Mengunggah...';
            errorBox.classList.add('hidden');

            fetch('../pemilik_kos/process_profile.php', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: '<strong>Berhasil!</strong>',
                            html: `<p class="text-gray-600">${data.message}</p>`,
                            confirmButtonColor: '#475569',
                            customClass: {
                                popup: 'rounded-2xl',
                                confirmButton: 'rounded-lg px-6 py-3'
                            }
                        });
                        const ts = Date.now();
                        const newPhotoUrl = `../uploads/profiles/${data.new_photo}?t=${ts}`;
                        // Refresh all profile images on the page
                        document.querySelectorAll('img').forEach(img => {
                            try {
                                if (img.src && img.src.indexOf('/uploads/profiles/') !== -1) {
                                    img.src = newPhotoUrl;
                                }
                            } catch (e) {}
                        });

                        const sidebarNode = document.getElementById('sidebarUserPhoto');
                        if (sidebarNode && sidebarNode.tagName !== 'IMG') {
                            const img = document.createElement('img');
                            img.id = 'sidebarUserPhoto';
                            img.className = 'w-12 h-12 rounded-full object-cover flex-shrink-0 ring-2 ring-white ring-opacity-30 group-hover:ring-opacity-60 transition-all';
                            img.src = newPhotoUrl;
                            img.alt = 'Foto Profil';
                            sidebarNode.parentNode.replaceChild(img, sidebarNode);
                        }

                        const preview = document.getElementById('photoPreview');
                        if (preview) preview.src = newPhotoUrl;
                        try { localStorage.setItem('newProfilePhoto', data.new_photo); } catch (e) {}
                        form.reset();
                    } else {
                        errorBox.innerHTML = `<i class="fas fa-exclamation-circle mr-2"></i>${data.message}`;
                        errorBox.classList.remove('hidden');
                    }
                })
                .catch(err => {
                    errorBox.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>Terjadi kesalahan jaringan.';
                    errorBox.classList.remove('hidden');
                })
                .finally(() => {
                    button.disabled = false;
                    button.innerHTML = '<i class="fas fa-upload mr-2"></i>Unggah';
                });
        }

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
            imagePreview.innerHTML = '<p class="text-gray-400">Preview Gambar</p>';
            document.getElementById('gambar_lama').value = '';

            if (mode === 'add') {
                title.textContent = 'Tambah Kos Baru';
                actionInput.value = 'add';
                idInput.value = '';
            } else { // mode === 'edit'
                title.textContent = 'Edit Data Kos';
                actionInput.value = 'edit';
                idInput.value = id;

                // Ambil data kos via AJAX untuk diisi ke form
                fetch(`../pemilik_kos/process_kost.php?action=get_details&id_kost=${id}`)
                    .then(response => response.json())
                    .then(res => {
                        if (res.status === 'success') {
                            document.getElementById('nama_kost').value = res.data.nama_kost;
                            document.getElementById('alamat').value = res.data.alamat;
                            document.getElementById('harga').value = res.data.harga;
                            document.getElementById('deskripsi').value = res.data.deskripsi;
                            document.getElementById('fasilitas').value = res.data.fasilitas;
                            document.getElementById('gambar_lama').value = res.data.gambar;
                            if (res.data.gambar) {
                                imagePreview.innerHTML = `<img src="../uploads/kost/${res.data.gambar}" class="h-full w-full object-contain">`;
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

        // Fungsi untuk edit kos (dipanggil dari modul owner_manage_kost)
        function editKos(id_kost) {
            showKosModal('edit', id_kost);
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

        function editKamar(id_kamar, nama_kamar, harga, status) {
            // Set form to edit mode with animation
            const formTitle = document.getElementById('formTitle');
            const saveButton = document.getElementById('saveKamarButton');
            
            document.getElementById('kamarAction').value = 'edit';
            document.getElementById('kamarId').value = id_kamar;
            document.getElementById('nama_kamar').value = nama_kamar;
            document.getElementById('harga_kamar').value = harga;
            document.getElementById('status_kamar').value = status;
            
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
            // Buat event palsu untuk menjaga konsistensi fungsi
            const fakeEvent = { preventDefault: () => {}, currentTarget: document.querySelector('[data-module="owner_dashboard_summary"]') };
            loadContent('owner_dashboard_summary', fakeEvent);
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
    </script>

    <!-- Modal untuk Tambah/Edit Kos -->
    <div id="kosModal" class="fixed inset-0 bg-black bg-opacity-60 hidden items-center justify-center z-50 p-4 backdrop-blur-sm">
        <div class="bg-white rounded-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto shadow-2xl">
            <form id="kosForm" onsubmit="saveKos(event)">
                <div class="p-6 border-b bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-t-2xl flex justify-between items-center sticky top-0 z-10">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-building text-xl"></i>
                        </div>
                        <h3 id="kosModalTitle" class="text-2xl font-bold">Tambah Kos Baru</h3>
                    </div>
                    <button type="button" onclick="closeKosModal()" class="text-white hover:bg-white hover:bg-opacity-20 rounded-full w-10 h-10 flex items-center justify-center transition-all text-2xl">&times;</button>
                </div>

                <div class="p-6">
                    <div id="kosModalError" class="hidden bg-red-100 text-red-700 p-4 rounded-xl text-sm mb-4 border border-red-200">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <span id="kosModalErrorText"></span>
                    </div>
                    <input type="hidden" name="action" id="kosAction" value="add">
                    <input type="hidden" name="id_kost" id="id_kost">
                    <input type="hidden" name="gambar_lama" id="gambar_lama">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div>
                                <label for="nama_kost" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-home mr-1 text-purple-600"></i>Nama Kos
                                </label>
                                <input type="text" name="nama_kost" id="nama_kost" class="mt-1 block w-full border-2 border-gray-300 rounded-lg shadow-sm p-3 form-input hover:border-purple-400 focus:border-purple-500 transition-all" required>
                            </div>
                            <div>
                                <label for="alamat" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-map-marker-alt mr-1 text-purple-600"></i>Alamat
                                </label>
                                <textarea name="alamat" id="alamat" rows="3" class="mt-1 block w-full border-2 border-gray-300 rounded-lg shadow-sm p-3 form-input hover:border-purple-400 focus:border-purple-500 transition-all" required></textarea>
                            </div>
                            <div>
                                <label for="harga" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-money-bill-wave mr-1 text-purple-600"></i>Harga Default (per bulan)
                                </label>
                                <input type="number" name="harga" id="harga" class="mt-1 block w-full border-2 border-gray-300 rounded-lg shadow-sm p-3 form-input hover:border-purple-400 focus:border-purple-500 transition-all" required>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label for="deskripsi" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-align-left mr-1 text-purple-600"></i>Deskripsi
                                </label>
                                <textarea name="deskripsi" id="deskripsi" rows="3" class="mt-1 block w-full border-2 border-gray-300 rounded-lg shadow-sm p-3 form-input hover:border-purple-400 focus:border-purple-500 transition-all" required></textarea>
                            </div>
                            <div>
                                <label for="fasilitas" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-list-ul mr-1 text-purple-600"></i>Fasilitas (pisahkan dengan koma)
                                </label>
                                <input type="text" name="fasilitas" id="fasilitas" class="mt-1 block w-full border-2 border-gray-300 rounded-lg shadow-sm p-3 form-input hover:border-purple-400 focus:border-purple-500 transition-all" required>
                            </div>
                            <div>
                                <label for="gambar" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-image mr-1 text-purple-600"></i>Gambar Kos
                                </label>
                                <input type="file" name="gambar" id="gambar" class="mt-1 block w-full text-sm text-gray-600 file:mr-4 file:py-3 file:px-6 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-purple-600 file:text-white hover:file:bg-purple-700 transition-all cursor-pointer">
                                <div id="image_preview" class="mt-3 h-32 w-full bg-gradient-to-br from-purple-50 to-indigo-50 rounded-xl flex items-center justify-center border-2 border-dashed border-purple-300">
                                    <p class="text-purple-400 flex items-center">
                                        <i class="fas fa-image mr-2"></i>Preview Gambar
                                    </p>
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
    <div id="kamarModal" class="fixed inset-0 bg-black bg-opacity-60 hidden items-center justify-center z-50 p-4 backdrop-blur-sm">
        <div id="kamarModalContent" class="bg-white rounded-2xl w-full max-w-5xl max-h-[90vh] overflow-y-auto shadow-2xl">
            <!-- Konten dinamis akan dimuat di sini -->
        </div>
    </div>
</body>
</html>
