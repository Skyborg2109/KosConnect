<?php
session_start();
// --- AUTENTIKASI ---
if (!isset($_SESSION['user_logged_in']) || $_SESSION['role'] !== 'admin') { //
    header("Location: ../auth/loginForm.php");
    exit();
}

// Hanya butuh koneksi untuk header atau fungsi lain
include '../config/db.php';
include '../config/SessionChecker.php';
include '../config/SessionManager.php';

// Validate multi-device session
if (!checkMultiDeviceSession($conn)) {
    session_destroy();
    header("Location: ../auth/loginForm.php");
    exit();
} 

$adminName = $_SESSION['fullname'] ?? 'Admin Sistem';
$adminID = $_SESSION['user_id'];

// =======================================================
// QUERY STATISTIK UTAMA ADMIN (Untuk Dashboard Ringkas)
// =======================================================
// Catatan: Query ini dieksekusi HANYA SEKALI di halaman utama
$sql_stats = "SELECT 
    (SELECT COUNT(id_user) FROM user) AS total_user,
    (SELECT COUNT(id_user) FROM user WHERE role = 'pemilik') AS total_pemilik,
    (SELECT COUNT(id_kost) FROM kost) AS total_kost,
    (SELECT COUNT(id_booking) FROM booking WHERE status IN ('dibayar', 'selesai')) AS total_booking_aktif,
    (SELECT COUNT(id_complaint) FROM complaint WHERE status IN ('baru', 'diproses')) AS total_complaint_open,
    (SELECT COUNT(id_payment) FROM pembayaran WHERE status_pembayaran = 'menunggu') AS total_payment_pending,
    (SELECT COUNT(id_user) FROM user WHERE role = 'penyewa') AS total_penyewa";
$stats = $conn->query($sql_stats)->fetch_assoc();
$total_penyewa = $stats['total_user'] - $stats['total_pemilik'] - 1;

// Daftar 5 Pembayaran Terbaru (Untuk Dashboard Ringkas)
$sql_latest_payments = "
    SELECT 
        p.jumlah, p.tanggal_pembayaran, p.status_pembayaran, u.nama_lengkap
    FROM pembayaran p
    JOIN booking b ON p.id_booking = b.id_booking
    JOIN user u ON b.id_penyewa = u.id_user
    ORDER BY p.tanggal_pembayaran DESC LIMIT 5
";
$res_latest_payments = $conn->query($sql_latest_payments); //

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Admin - KosConnect</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> <!-- Chart.js Added -->
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
    
    @keyframes slideInLeft {
      from {
        opacity: 0;
        transform: translateX(-30px);
      }
      to {
        opacity: 1;
        transform: translateX(0);
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
    
    @keyframes spin {
      from { transform: rotate(0deg); }
      to { transform: rotate(360deg); }
    }

    body {
      box-sizing: border-box;
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    }
    
    /* Sidebar Styles */
    .dark-gradient {
      background: linear-gradient(180deg, #0f172a 0%, #1e293b 50%, #334155 100%);
      position: relative;
      overflow: hidden;
    }
    
    .dark-gradient::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: radial-gradient(circle at top right, rgba(99, 102, 241, 0.1) 0%, transparent 60%);
      pointer-events: none;
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
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
      transition: left 0.6s;
    }
    
    .card-hover:hover {
      transform: translateY(-8px) scale(1.02);
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    }
    
    .card-hover:hover::before {
      left: 100%;
    }
    
    /* Sidebar Links */
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
      bottom: 0;
      width: 4px;
      background: linear-gradient(135deg, #6366f1, #8b5cf6);
      transform: scaleY(0);
      transition: transform 0.3s ease;
    }
    
    .sidebar-link:hover {
      background: rgba(255, 255, 255, 0.1);
      padding-left: 2rem;
    }
    
    .sidebar-link:hover::before {
      transform: scaleY(1);
    }
    
    .sidebar-link.active {
      background: linear-gradient(90deg, rgba(99, 102, 241, 0.2) 0%, rgba(139, 92, 246, 0.1) 100%);
      border-left: 4px solid #6366f1;
      font-weight: 600;
      color: #fff;
    }
    
    .sidebar-link.active i {
      color: #818cf8;
    }
    
    /* Icon Backgrounds */
    .icon-bg {
      background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
      box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
      transition: all 0.3s ease;
    }
    
    .card-hover:hover .icon-bg {
      transform: scale(1.1) rotate(5deg);
      box-shadow: 0 6px 20px rgba(99, 102, 241, 0.5);
    }
    
    /* Stat Cards */
    .stat-card {
      border-left: 4px solid transparent;
      transition: all 0.3s ease;
    }
    
    .stat-card-blue {
      border-left-color: #3b82f6;
      background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
    }
    
    .stat-card-purple {
      border-left-color: #8b5cf6;
      background: linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%);
    }
    
    .stat-card-green {
      border-left-color: #10b981;
      background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
    }
    
    .stat-card-orange {
      border-left-color: #f59e0b;
      background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
    }
    
    .stat-card-red {
      border-left-color: #ef4444;
      background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
    }
    
    .stat-card-indigo {
      border-left-color: #6366f1;
      background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
    }
    
    /* Table Styles */
    .table-row {
      transition: all 0.2s ease;
    }
    
    .table-row:hover {
      background: linear-gradient(90deg, #f8fafc 0%, #f1f5f9 100%);
      transform: scale(1.01);
    }
    
    /* Status Badges */
    .status-badge {
      animation: pulse 2s infinite;
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
      background: linear-gradient(135deg, #6366f1, #8b5cf6);
      border-radius: 5px;
    }
    
    ::-webkit-scrollbar-thumb:hover {
      background: linear-gradient(135deg, #4f46e5, #7c3aed);
    }
    
    /* Loading Animation */
    .loading-spinner {
      animation: spin 1s linear infinite;
    }
    
    /* Page Animations */
    main {
      animation: fadeIn 0.6s ease-out;
    }
    
    .sidebar {
      animation: slideInLeft 0.5s ease-out;
    }
    
    /* Button Hover Effects */
    button, .btn {
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    button:hover:not(:disabled), .btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(0,0,0,0.15);
    }
    
    /* Notification Badge */
    .notification-badge {
      animation: pulse 2s infinite;
    }
    
    /* Mobile Sidebar Styles */
    .sidebar {
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Responsive */
    @media (max-width: 768px) {
      .card-hover:hover {
        transform: translateY(-4px) scale(1.01);
      }

      .sidebar {
        position: fixed;
        left: 0;
        top: 0;
        width: 280px;
        height: 100vh;
        z-index: 50;
        box-shadow: 2px 0 15px rgba(0, 0, 0, 0.3);
        transform: translateX(-100%);
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      }

      .sidebar.show-mobile {
        transform: translateX(0);
        animation: slideInLeft 0.3s ease-out;
      }

      .main-content {
        margin-left: 0 !important;
        transition: all 0.3s ease;
      }

      .mobile-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.6);
        z-index: 40;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.3s ease;
      }

      .mobile-overlay.show {
        opacity: 1;
        pointer-events: auto;
      }

      header {
        margin-left: 0;
        z-index: 30;
        position: relative;
      }

      .ml-64 {
        margin-left: 0 !important;
      }

      #hamburgerBtn {
        display: block;
        z-index: 35;
      }
    }

    @media (min-width: 769px) {
      #hamburgerBtn {
        display: none !important;
      }
      
      .sidebar {
        transform: translateX(0) !important;
      }
      
      .mobile-overlay {
        display: none !important;
      }
    }

    @keyframes slideInLeft {
      from {
        opacity: 0;
        transform: translateX(-100%);
      }
      to {
        opacity: 1;
        transform: translateX(0);
      }
    }

    /* Responsive card sizing */
    @media (max-width: 640px) {
      .stat-card {
        min-height: auto;
      }
      
      .icon-bg {
        box-shadow: 0 2px 8px rgba(99, 102, 241, 0.2);
      }
    }

    @media (min-width: 641px) and (max-width: 1024px) {
      .stat-card {
        min-height: auto;
      }
    }

    @media (min-width: 1025px) {
      .stat-card {
        min-height: 140px;
      }
    }
  </style>
</head>
<body class="bg-gray-100 font-sans">
  <!-- Mobile Overlay -->
  <div id="mobileOverlay" class="mobile-overlay" onclick="toggleMobileMenu()"></div>

  <!-- Sidebar -->
  <div class="sidebar fixed left-0 top-0 h-full w-64 dark-gradient text-white shadow-2xl" id="sidebar" style="z-index: 50; top: 0; left: 0;">
      <div class="p-6 border-b border-white border-opacity-10">
          <div class="flex items-center space-x-4">
              <div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full w-14 h-14 flex items-center justify-center flex-shrink-0 shadow-lg" style="animation: pulse 3s infinite;">
                  <i class="fas fa-shield-halved text-white text-2xl"></i>
              </div>
              <div>
                  <p class="font-bold text-white text-lg"><?php echo htmlspecialchars($adminName); ?></p>
                  <p class="text-sm text-slate-300 flex items-center">
                      <span class="w-2 h-2 bg-green-400 rounded-full mr-2 animate-pulse"></span>
                      Admin System
                  </p>
              </div>
          </div>
      </div>
    
    <nav class="mt-8 px-3">
        <a href="#" data-module="admin_dashboard_summary" onclick="loadContent('admin_dashboard_summary', event)" class="sidebar-link active flex items-center px-6 py-4 text-white border-l-4 border-indigo-500 rounded-r-lg mb-2">
            <i class="fas fa-tachometer-alt w-5 mr-4 text-lg"></i>
            <span class="font-medium">Dashboard</span>
        </a>
        <a href="#" data-module="admin_manage_users" onclick="loadContent('admin_manage_users', event)" class="sidebar-link flex items-center px-6 py-4 text-slate-300 hover:text-white border-l-4 border-transparent rounded-r-lg mb-2">
            <i class="fas fa-users w-5 mr-4 text-lg"></i>
            <span class="font-medium">Data Pengguna</span>
        </a>
        <a href="#" data-module="admin_manage_kost" onclick="loadContent('admin_manage_kost', event)" class="sidebar-link flex items-center px-6 py-4 text-slate-300 hover:text-white border-l-4 border-transparent rounded-r-lg mb-2">
            <i class="fas fa-building w-5 mr-4 text-lg"></i>
            <span class="font-medium">Data Kos</span>
        </a>
        <a href="#" data-module="admin_manage_transactions" onclick="loadContent('admin_manage_transactions', event)" class="sidebar-link flex items-center px-6 py-4 text-slate-300 hover:text-white border-l-4 border-transparent rounded-r-lg mb-2">
            <i class="fas fa-exchange-alt w-5 mr-4 text-lg"></i>
            <span class="font-medium">Transaksi</span>
        </a>
        <a href="#" data-module="admin_manage_complaints" onclick="loadContent('admin_manage_complaints', event)" class="sidebar-link flex items-center px-6 py-4 text-slate-300 hover:text-white border-l-4 border-transparent rounded-r-lg mb-2">
            <i class="fas fa-comments w-5 mr-4 text-lg"></i>
            <span class="font-medium">Feedback Aplikasi</span>
        </a>
        <a href="#" data-module="admin_view_reports" onclick="loadContent('admin_view_reports', event)" class="sidebar-link flex items-center px-6 py-4 text-slate-300 hover:text-white border-l-4 border-transparent rounded-r-lg mb-2">
            <i class="fas fa-chart-line w-5 mr-4 text-lg"></i>
            <span class="font-medium">Laporan</span>
        </a>
    </nav>
    
    <div class="absolute bottom-6 left-6 right-6">
      <a href="../auth/logout.php" onclick="confirmLogout(event)" class="flex items-center justify-center bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white py-3 px-4 rounded-xl transition-all duration-300 font-semibold shadow-lg hover:shadow-xl cursor-pointer group">
        <i class="fas fa-sign-out-alt mr-2 group-hover:translate-x-1 transition-transform"></i> Logout
      </a>
    </div>
  </div>

  <!-- Main Content -->
  <div class="ml-64 min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 main-content" id="main-content">
    <!-- Header -->
    <header class="bg-white shadow-md border-b-2 border-indigo-100 px-4 sm:px-6 lg:px-8 py-4 sm:py-6 sticky top-0 z-20" style="animation: slideUp 0.5s ease-out;">
        <div class="flex justify-between items-center w-full gap-4">
            <div class="flex items-center space-x-2 sm:space-x-4 min-w-0">
                <button id="hamburgerBtn" class="md:hidden text-slate-600 hover:text-slate-800 p-2 rounded-lg hover:bg-gray-100 transition-all flex-shrink-0">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <div class="min-w-0">
                    <h1 id="pageTitle" class="text-2xl sm:text-3xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent truncate">Dashboard</h1>
                    <p class="text-xs sm:text-sm text-gray-500 mt-1 truncate">Selamat datang kembali, <?php echo htmlspecialchars(explode(' ', $adminName)[0]); ?>! 👋</p>
                </div>
            </div>
            <div class="flex items-center space-x-2 sm:space-x-4">
                <div class="hidden md:flex items-center space-x-2 bg-gradient-to-r from-indigo-50 to-purple-50 px-3 sm:px-4 py-2 rounded-xl border border-indigo-100">
                    <i class="fas fa-calendar-alt text-indigo-600 text-sm"></i>
                    <span class="text-xs sm:text-sm text-gray-700 font-medium" id="currentDate"></span>
                </div>
                <button onclick="showNotifications()" class="relative text-slate-600 hover:text-indigo-600 p-2 sm:p-3 rounded-xl hover:bg-indigo-50 transition-all flex-shrink-0">
                    <i class="fas fa-bell text-lg sm:text-xl"></i>
                    <?php if ($stats['total_payment_pending'] > 0): ?>
                    <span class="notification-badge absolute -top-1 -right-1 bg-gradient-to-r from-red-500 to-red-600 text-white text-xs rounded-full h-5 w-5 sm:h-6 sm:w-6 flex items-center justify-center font-bold shadow-lg text-xxs sm:text-xs"><?php echo $stats['total_payment_pending']; ?></span>
                    <?php endif; ?>
                </button>
            </div>
        </div>
    </header>

    <!-- Dashboard Content -->
    <main id="contentContainer" class="p-4 sm:p-6 lg:p-8">
      <!-- Konten dashboard summary akan disisipkan di sini saat halaman dimuat -->
    </main>
  </div>

  <script>
    // Display current date
    function displayCurrentDate() {
        const options = { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        };
        const today = new Date();
        const formattedDate = today.toLocaleDateString('id-ID', options);
        const dateElement = document.getElementById('currentDate');
        if (dateElement) {
            dateElement.textContent = formattedDate;
        }
    }

    // Mobile Menu Toggle with Overlay
    function toggleMobileMenu() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('mobileOverlay');
        
        if (sidebar && overlay) {
            const isVisible = sidebar.classList.contains('show-mobile');
            
            if (isVisible) {
                sidebar.classList.remove('show-mobile');
                overlay.classList.remove('show');
            } else {
                sidebar.classList.add('show-mobile');
                overlay.classList.add('show');
            }
        }
    }

    // Close sidebar when clicking a link on mobile
    function closeMobileMenu() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('mobileOverlay');
        
        if (sidebar && overlay) {
            sidebar.classList.remove('show-mobile');
            overlay.classList.remove('show');
        }
    }

    function loadContent(moduleName, event) {
        if (event) event.preventDefault();

        // Close mobile menu when loading content
        closeMobileMenu();

        const container = document.getElementById('contentContainer');
        const pageTitle = document.getElementById('pageTitle');
        const activeLink = event ? event.currentTarget : document.querySelector(`[data-module="${moduleName}"]`);

        // Simpan module yang aktif ke localStorage
        localStorage.setItem('activeAdminModule', moduleName);

        // Update active link style
        document.querySelectorAll('.sidebar-link').forEach(link => {
            link.classList.remove('active');
            link.style.borderColor = 'transparent';
        });
        if (activeLink) {
            activeLink.classList.add('active');
            activeLink.style.borderColor = '#94a3b8'; // slate-400
            pageTitle.textContent = activeLink.textContent.trim();
        }

        // Enhanced loading state with spinner
        container.innerHTML = `
            <div class="flex justify-center items-center h-64">
                <div class="text-center">
                    <div class="loader mb-4"></div>
                    <p class="text-gray-600 font-semibold">Memuat ${activeLink ? activeLink.textContent.trim() : 'konten'}...</p>
                </div>
            </div>
        `;

        // Fetch content from the respective admin module file
        fetch(`../admin/${moduleName}.php`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Gagal memuat modul (HTTP ${response.status})`);
                }
                return response.text();
            })
            .then(html => {
                container.innerHTML = html;
                
                // Execute any scripts in the loaded HTML
                const scripts = container.querySelectorAll('script');
                scripts.forEach(script => {
                    if (script.src) {
                        // External script
                        const newScript = document.createElement('script');
                        newScript.src = script.src;
                        document.head.appendChild(newScript);
                    } else {
                        // Inline script
                        try {
                            eval(script.textContent);
                        } catch (e) {
                            console.error('Error executing inline script:', e);
                        }
                    }
                });
                
                // Scroll to top smoothly
                window.scrollTo({ top: 0, behavior: 'smooth' });
            })
            .catch(error => {
                container.innerHTML = `
                    <div class="text-center p-10">
                        <div class="inline-flex items-center justify-center w-16 h-16 bg-red-100 rounded-full mb-4">
                            <i class="fas fa-exclamation-triangle text-3xl text-red-600"></i>
                        </div>
                        <h3 class="text-xl font-bold text-slate-800 mb-2">Gagal Memuat Konten</h3>
                        <p class="text-red-600 mb-4">${error.message}</p>
                        <button onclick="loadContent('${moduleName}', null)" class="bg-gradient-to-r from-indigo-500 to-purple-600 text-white px-6 py-2 rounded-xl font-semibold hover:from-indigo-600 hover:to-purple-700 transition-all">
                            <i class="fas fa-redo mr-2"></i>Coba Lagi
                        </button>
                    </div>
                `;
                console.error('Load Content Error:', error);
            });
    }

    // Make loadContent function globally accessible for dynamically loaded content
    window.loadContent = loadContent;

    // Initial load
    document.addEventListener('DOMContentLoaded', function() {
        // Ensure sidebar is hidden on mobile on page load
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('mobileOverlay');
        
        if (sidebar) {
            sidebar.classList.remove('show-mobile');
        }
        if (overlay) {
            overlay.classList.remove('show');
        }

        // Check if there's a saved active module in localStorage
        const savedModule = localStorage.getItem('activeAdminModule');
        const moduleToLoad = savedModule || 'admin_dashboard_summary';
        
        // Load the module (saved or default dashboard)
        loadContent(moduleToLoad);

        // Display current date
        displayCurrentDate();

        // Mobile menu hamburger button
        const hamburgerBtn = document.getElementById('hamburgerBtn');
        
        if (hamburgerBtn && sidebar) {
            hamburgerBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                e.preventDefault();
                toggleMobileMenu();
            });
        }

        // Add click outside to close mobile menu
        document.addEventListener('click', function(event) {
            const hamburgerBtn = document.getElementById('hamburgerBtn');
            const clickedOnMenu = event.target.closest('.sidebar');
            const clickedOnButton = event.target.closest('#hamburgerBtn');
            
            if (sidebar && sidebar.classList.contains('show-mobile') && !clickedOnMenu && !clickedOnButton && overlay) {
                closeMobileMenu();
            }
        });

        // Close menu when clicking on sidebar links
        const sidebarLinks = document.querySelectorAll('.sidebar-link');
        sidebarLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                closeMobileMenu();
            });
        });

        // Add smooth scroll behavior
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const href = this.getAttribute('href');
                // Only process valid selectors (not just "#")
                if (href && href !== '#' && href.length > 1) {
                    const target = document.querySelector(href);
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                }
            });
        });
    });

    function showNotifications() {
        const pendingPayments = <?php echo $stats['total_payment_pending']; ?>;
        const openComplaints = <?php echo $stats['total_complaint_open']; ?>;
        
        let notifications = [];
        
        if (pendingPayments > 0) {
            notifications.push({
                icon: 'fa-money-bill-wave',
                iconColor: 'from-yellow-400 to-orange-500',
                title: 'Pembayaran Pending',
                message: `Ada <strong>${pendingPayments}</strong> pembayaran yang menunggu verifikasi`,
                action: 'admin_manage_transactions'
            });
        }
        
        if (openComplaints > 0) {
            notifications.push({
                icon: 'fa-exclamation-circle',
                iconColor: 'from-red-400 to-pink-500',
                title: 'Keluhan Baru',
                message: `Ada <strong>${openComplaints}</strong> keluhan yang belum ditangani`,
                action: 'admin_manage_complaints'
            });
        }
        
        if (notifications.length > 0) {
            let html = '<div class="max-h-96 overflow-y-auto">';
            notifications.forEach(notif => {
                html += `
                    <div class="p-4 border-b border-gray-100 hover:bg-gray-50 transition-colors cursor-pointer" onclick="handleNotificationClick('${notif.action}')">
                        <div class="flex items-start space-x-3">
                            <div class="w-12 h-12 rounded-full bg-gradient-to-br ${notif.iconColor} flex items-center justify-center flex-shrink-0 shadow-md">
                                <i class="fas ${notif.icon} text-white text-lg"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-bold text-slate-800">${notif.title}</p>
                                <p class="text-sm text-gray-600 mt-1">${notif.message}</p>
                                <p class="text-xs text-indigo-600 font-semibold mt-2">
                                    <i class="fas fa-arrow-right mr-1"></i>Klik untuk melihat detail
                                </p>
                            </div>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            
            Swal.fire({
                title: '<span class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">Notifikasi</span>',
                html: html,
                width: '600px',
                showCloseButton: true,
                showConfirmButton: false,
                customClass: {
                    popup: 'rounded-2xl',
                    closeButton: 'hover:bg-gray-100 rounded-lg transition-colors'
                }
            });
        } else {
            Swal.fire({
                icon: 'info',
                title: 'Tidak Ada Notifikasi',
                text: 'Semua dalam keadaan baik! Tidak ada notifikasi baru.',
                confirmButtonColor: '#6366f1',
                customClass: {
                    popup: 'rounded-2xl',
                    confirmButton: 'rounded-xl font-semibold px-6 py-3'
                }
            });
        }
    }

    function handleNotificationClick(moduleName) {
        Swal.close();
        const link = document.querySelector(`[data-module="${moduleName}"]`);
        if (link) {
            loadContent(moduleName, { currentTarget: link });
        }
    }

    function confirmLogout(event) {
        event.preventDefault();
        Swal.fire({
            title: '<span class="text-2xl font-bold text-slate-800">Konfirmasi Logout</span>',
            html: '<p class="text-gray-600">Apakah Anda yakin ingin keluar dari sistem?</p>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#6366f1',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="fas fa-sign-out-alt mr-2"></i>Ya, Logout',
            cancelButtonText: '<i class="fas fa-times mr-2"></i>Batal',
            customClass: {
                popup: 'rounded-2xl',
                confirmButton: 'rounded-xl font-semibold px-6 py-3',
                cancelButton: 'rounded-xl font-semibold px-6 py-3'
            },
            buttonsStyling: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading
                Swal.fire({
                    title: 'Logging out...',
                    text: 'Mohon tunggu sebentar',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    willOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Redirect to logout
                setTimeout(() => {
                    window.location.href = '../auth/logout.php';
                }, 500);
            }
        });
    }
  </script>
</body>
</html>
