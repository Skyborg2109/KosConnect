<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['role'] !== 'penyewa') {
    header("Location: ../auth/loginForm.php");
    exit();
}

include '../config/db.php';

// =======================================================
// BAGIAN 1: LOGIKA PEMROSESAN FORM
// =======================================================

$id_penyewa = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Cek form mana yang disubmit
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'update_profile') {
            // --- Proses Update Nama Lengkap ---
            $fullname = trim($_POST['fullname']);
            if (empty($fullname)) {
                $error_message = "Nama lengkap tidak boleh kosong.";
            } else {
                $stmt = $conn->prepare("UPDATE user SET nama_lengkap = ? WHERE id_user = ?");
                $stmt->bind_param("si", $fullname, $id_penyewa);
                if ($stmt->execute()) {
                    $_SESSION['fullname'] = $fullname; // Update sesi juga
                    $success_message = "Nama lengkap berhasil diperbarui.";
                } else {
                    $error_message = "Gagal memperbarui nama.";
                }
                $stmt->close();
            }
        } elseif ($_POST['action'] === 'update_password') {
            // --- Proses Update Password ---
            $old_password = $_POST['old_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];

            if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
                $error_message = "Semua field password harus diisi.";
            } elseif ($new_password !== $confirm_password) {
                $error_message = "Password baru dan konfirmasi tidak cocok.";
            } elseif (strlen($new_password) < 6) {
                $error_message = "Password baru minimal harus 6 karakter.";
            } else {
                // Ambil hash password saat ini dari DB
                $stmt = $conn->prepare("SELECT password FROM user WHERE id_user = ?");
                $stmt->bind_param("i", $id_penyewa);
                $stmt->execute();
                $result = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($result && password_verify($old_password, $result['password'])) {
                    // Password lama cocok, hash password baru dan update
                    $new_password_hashed = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt_update = $conn->prepare("UPDATE user SET password = ? WHERE id_user = ?");
                    $stmt_update->bind_param("si", $new_password_hashed, $id_penyewa);
                    if ($stmt_update->execute()) {
                        $success_message = "Password berhasil diubah.";
                    } else {
                        $error_message = "Gagal mengubah password.";
                    }
                    $stmt_update->close();
                } else {
                    $error_message = "Password lama salah.";
                }
            }
        }
    }
}

// Ambil data terbaru pengguna untuk ditampilkan di form
$stmt_user = $conn->prepare("SELECT nama_lengkap, email FROM user WHERE id_user = ?");
$stmt_user->bind_param("i", $id_penyewa);
$stmt_user->execute();
$user_data = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();

$userName = htmlspecialchars($user_data['nama_lengkap'] ?? 'Penyewa');
$userEmail = htmlspecialchars($user_data['email'] ?? '');

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - KosConnect</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
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

        /* Mobile Responsive Design */
        @media (max-width: 768px) {
            /* Navigation */
            nav {
                height: auto;
                padding: 0.5rem 0;
            }

            nav .text-2xl {
                font-size: 1.25rem !important;
            }

            /* Main Content */
            main {
                padding-top: 5rem !important;
                padding-bottom: 2rem !important;
            }

            .max-w-4xl {
                max-width: 100% !important;
                padding: 0 0.75rem !important;
            }

            /* Page Title */
            h1 {
                font-size: 1.75rem !important;
                margin-bottom: 1.5rem !important;
            }

            h2 {
                font-size: 1.25rem !important;
                margin-bottom: 1rem !important;
            }

            /* Form Sections */
            .bg-white.rounded-xl {
                padding: 1rem !important;
                border-radius: 0.75rem !important;
                margin-bottom: 1rem !important;
            }

            .space-y-6 {
                gap: 1rem !important;
            }

            .space-y-6 > div {
                margin-bottom: 1rem !important;
            }

            /* Form Inputs */
            input,
            textarea {
                font-size: 1rem !important;
                padding: 0.75rem !important;
                border-radius: 0.5rem !important;
            }

            label {
                font-size: 0.875rem !important;
                margin-bottom: 0.5rem !important;
            }

            /* Buttons */
            button,
            .btn-action {
                padding: 0.75rem 1rem !important;
                font-size: 0.875rem !important;
                border-radius: 0.5rem !important;
            }

            .flex.justify-between button {
                width: 100% !important;
                margin-bottom: 0.5rem !important;
            }

            /* Alerts */
            .bg-green-100,
            .bg-red-100 {
                padding: 0.75rem !important;
                border-radius: 0.5rem !important;
                margin-bottom: 1rem !important;
            }

            .bg-green-100 p,
            .bg-red-100 p {
                font-size: 0.9rem !important;
                line-height: 1.4 !important;
            }

            /* Text Sizes */
            .text-sm {
                font-size: 0.8rem !important;
            }

            .text-xs {
                font-size: 0.7rem !important;
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
                padding-left: 0.75rem !important;
                padding-right: 0.75rem !important;
            }

            .px-6 {
                padding-left: 1rem !important;
                padding-right: 1rem !important;
            }

            .md\:p-8 {
                padding: 1rem !important;
            }

            /* Form Grid */
            .grid.grid-cols-1.md\:grid-cols-2 {
                grid-template-columns: 1fr !important;
            }

            /* Flex Responsiveness */
            .flex.justify-between {
                flex-direction: column !important;
            }

            .flex.space-x-4 {
                flex-direction: column !important;
                gap: 0.75rem !important;
            }
        }

        @media (max-width: 768px) {
            #mobileMenuPanel {
                transform: translateX(100%);
            }
        }

        /* Extra small devices (< 640px) */
        @media (max-width: 640px) {
            h1 {
                font-size: 1.5rem !important;
            }

            h2 {
                font-size: 1.1rem !important;
            }

            .px-4 {
                padding-left: 0.5rem !important;
                padding-right: 0.5rem !important;
            }

            nav .text-2xl {
                font-size: 1rem !important;
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
                        <button id="notifBtn" class="relative text-gray-600 hover:text-purple-600 p-2 rounded-lg hover:bg-purple-50 transition-all" aria-label="Notifikasi" title="Notifikasi">
                            <i class="fas fa-bell text-xl" aria-hidden="true"></i>
                        </button>
                        <button class="flex items-center space-x-2 p-2 rounded-full hover:bg-gray-100 transition">
                            <div class="w-9 h-9 rounded-full bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center text-white font-bold">
                                U
                            </div>
                        </button>
                        <a href="../auth/logout.php" onclick="confirmLogout(event)" class="bg-gradient-to-r from-red-500 to-red-600 text-white px-4 py-2 rounded-lg font-medium text-sm hover:from-red-600 hover:to-red-700 shadow-md hover:shadow-lg cursor-pointer">
                            <i class="fas fa-sign-out-alt mr-1"></i>Logout
                        </a>
                    </div>
                </div>
                
                <!-- Mobile Menu Button di Navbar -->
                <div class="flex md:hidden items-center space-x-2">
                    <button id="notifBtn" class="relative text-gray-600 hover:text-purple-600 p-2 rounded-lg hover:bg-purple-50 transition-all" aria-label="Notifikasi" title="Notifikasi">
                        <i class="fas fa-bell text-xl" aria-hidden="true"></i>
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
                <button id="mobileLogoutBtn" class="w-full px-4 py-3 bg-red-500 text-white rounded-lg font-medium hover:bg-red-600 transition-all text-center flex items-center justify-center" type="button" style="pointer-events: auto; cursor: pointer;">
                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                </button>
            </div>
        </div>
    </div>

    <main class="pt-24 pb-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-4xl font-bold text-gray-800 mb-8">Profil Saya</h1>

            <!-- Notifikasi -->
            <?php if ($success_message): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-md" role="alert">
                <p class="font-bold">Sukses</p>
                <p><?php echo $success_message; ?></p>
            </div>
            <?php endif; ?>
            <?php if ($error_message): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md" role="alert">
                <p class="font-bold">Error</p>
                <p><?php echo $error_message; ?></p>
            </div>
            <?php endif; ?>

            <!-- Form Informasi Pribadi -->
            <div class="bg-white rounded-xl shadow-md p-6 md:p-8 mb-8">
                <h2 class="text-2xl font-semibold text-gray-800 mb-6">Informasi Pribadi</h2>
                <form method="POST" action="profile.php">
                    <input type="hidden" name="action" value="update_profile">
                    <div class="space-y-6">
                        <div>
                            <label for="fullname" class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                            <input type="text" name="fullname" id="fullname" value="<?php echo $userName; ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" name="email" id="email" value="<?php echo $userEmail; ?>" disabled class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed">
                            <p class="text-xs text-gray-500 mt-1">Email tidak dapat diubah.</p>
                        </div>
                    </div>
                    <div class="mt-8 text-right">
                        <button type="submit" class="bg-purple-600 text-white px-6 py-2.5 rounded-lg font-semibold hover:bg-purple-700 transition-colors">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>

            <!-- Form Ubah Password -->
            <div class="bg-white rounded-xl shadow-md p-6 md:p-8">
                <h2 class="text-2xl font-semibold text-gray-800 mb-6">Ubah Password</h2>
                <form method="POST" action="profile.php">
                    <input type="hidden" name="action" value="update_password">
                    <div class="space-y-6">
                        <div>
                            <label for="old_password" class="block text-sm font-medium text-gray-700 mb-1">Password Lama</label>
                            <input type="password" name="old_password" id="old_password" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                        </div>
                        <div>
                            <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">Password Baru</label>
                            <input type="password" name="new_password" id="new_password" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                        </div>
                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Password Baru</label>
                            <input type="password" name="confirm_password" id="confirm_password" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                        </div>
                    </div>
                    <div class="mt-8 text-right">
                        <button type="submit" class="bg-gray-800 text-white px-6 py-2.5 rounded-lg font-semibold hover:bg-gray-900 transition-colors">
                            Ubah Password
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </main>

    <script>
        // Mobile Menu Script
        let mobileMenuActive = false;

        document.addEventListener('DOMContentLoaded', function() {
            const logoutBtn = document.getElementById('mobileLogoutBtn');
            
            if (logoutBtn) {
                logoutBtn.addEventListener('mousedown', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    closeMobileMenu();
                    setTimeout(() => {
                        if (typeof confirmLogout === 'function') {
                            confirmLogout(e);
                        } else {
                            window.location.href = '../auth/logout.php';
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
    </script>

</body>
</html>