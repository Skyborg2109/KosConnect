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

// Ambil daftar kos yang sedang di-booking oleh penyewa
$stmt_kost = $conn->prepare("
    SELECT DISTINCT k.id_kost, t.nama_kost, t.alamat
    FROM booking b
    INNER JOIN kamar k ON b.id_kamar = k.id_kamar
    INNER JOIN kost t ON k.id_kost = t.id_kost
    WHERE b.id_penyewa = ? AND b.status IN ('pending', 'dibayar', 'menunggu_pembayaran')
    ORDER BY t.nama_kost ASC
");
$stmt_kost->bind_param("i", $id_penyewa);
$stmt_kost->execute();
$result_kost = $stmt_kost->get_result();
$available_kost = [];
while ($row = $result_kost->fetch_assoc()) {
    $available_kost[] = $row;
}
$stmt_kost->close();

// Ambil riwayat keluhan penyewa
$stmt_complaints = $conn->prepare("
    SELECT c.id_complaint, c.pesan, c.status, c.created_at, t.nama_kost
    FROM complaint c
    INNER JOIN kost t ON c.id_kost = t.id_kost
    WHERE c.id_penyewa = ?
    ORDER BY c.created_at DESC
");
$stmt_complaints->bind_param("i", $id_penyewa);
$stmt_complaints->execute();
$result_complaints = $stmt_complaints->get_result();
$user_complaints = [];
while ($row = $result_complaints->fetch_assoc()) {
    $user_complaints[] = $row;
}
$stmt_complaints->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajukan Keluhan - KosConnect</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .user-info-box { display: flex; align-items: center; padding: 8px 12px; border-radius: 9999px; background-color: #f3f4f6; transition: all 0.2s ease; }
        .user-info-box:hover { background-color: #e5e7eb; }
        .card-hover:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.08); transition: all 0.3s ease-in-out; }
        .status-badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; }
        .status-baru { background-color: #fee2e2; color: #991b1b; }
        .status-diproses { background-color: #fef3c7; color: #92400e; }
        .status-selesai { background-color: #d1fae5; color: #065f46; }
        
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

            /* Form Inputs and Selects */
            input,
            textarea,
            select {
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
            .bg-red-100,
            .bg-blue-100 {
                padding: 0.75rem !important;
                border-radius: 0.5rem !important;
                margin-bottom: 1rem !important;
            }

            .bg-green-100 p,
            .bg-red-100 p,
            .bg-blue-100 p {
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

            /* Grid and Flex */
            .grid.grid-cols-1.md\:grid-cols-2 {
                grid-template-columns: 1fr !important;
            }

            .flex.justify-between {
                flex-direction: column !important;
            }

            .flex.space-x-4 {
                flex-direction: column !important;
                gap: 0.75rem !important;
            }

            /* Complaint/Issue Cards */
            .bg-white.border {
                padding: 1rem !important;
                margin-bottom: 0.75rem !important;
                border-radius: 0.75rem !important;
            }

            /* Table Responsiveness */
            table {
                font-size: 0.8rem !important;
            }

            table th,
            table td {
                padding: 0.5rem !important;
            }

            thead {
                display: none !important;
            }

            tbody tr {
                display: block !important;
                margin-bottom: 1rem !important;
                border: 1px solid #e5e7eb !important;
                border-radius: 0.5rem !important;
                padding: 0.75rem !important;
            }

            tbody td {
                display: block !important;
                text-align: right !important;
                padding: 0.5rem 0 !important;
                border: none !important;
            }

            tbody td::before {
                content: attr(data-label) !important;
                float: left !important;
                font-weight: 600 !important;
                color: #374151 !important;
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

            .flex.space-x-4 {
                flex-direction: column !important;
                gap: 0.5rem !important;
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

    <main class="pt-24 pb-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Header -->
            <div class="bg-white rounded-2xl shadow-lg p-8 mb-8">
                <div class="flex items-center mb-4">
                    <i class="fas fa-exclamation-triangle text-3xl text-red-500 mr-4"></i>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800">Ajukan Keluhan</h1>
                        <p class="text-gray-600 mt-1">Laporkan masalah atau keluhan terkait kos yang sedang Anda sewa</p>
                    </div>
                </div>
            </div>

            <!-- Form Pengajuan Keluhan -->
            <div class="bg-white rounded-2xl shadow-lg p-8 mb-8">
                <h2 class="text-2xl font-semibold text-gray-800 mb-6">Form Pengajuan Keluhan</h2>

                <?php if (!empty($available_kost)): ?>
                <form id="complaintForm" class="space-y-6">
                    <div>
                        <label for="id_kost" class="block text-sm font-medium text-gray-700 mb-2">Pilih Kos</label>
                        <select id="id_kost" name="id_kost" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            <option value="">-- Pilih Kos --</option>
                            <?php foreach ($available_kost as $kost): ?>
                                <option value="<?php echo $kost['id_kost']; ?>"><?php echo htmlspecialchars($kost['nama_kost']); ?> - <?php echo htmlspecialchars($kost['alamat']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="pesan" class="block text-sm font-medium text-gray-700 mb-2">Deskripsi Keluhan</label>
                        <textarea id="pesan" name="pesan" rows="5" required
                            placeholder="Jelaskan keluhan Anda secara detail..."
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent resize-vertical"
                            maxlength="1000"></textarea>
                        <p class="text-sm text-gray-500 mt-1">Maksimal 1000 karakter</p>
                    </div>

                    <button type="submit" class="w-full bg-red-500 text-white py-3 px-6 rounded-lg font-semibold hover:bg-red-600 transition-colors flex items-center justify-center">
                        <i class="fas fa-paper-plane mr-2"></i>
                        Kirim Keluhan
                    </button>
                </form>
                <?php else: ?>
                    <div class="text-center py-8">
                        <i class="fas fa-info-circle text-4xl text-gray-400 mb-4"></i>
                        <p class="text-gray-500 text-lg">Anda tidak memiliki booking aktif saat ini.</p>
                        <p class="text-gray-400">Untuk mengajukan keluhan, Anda harus memiliki booking aktif di salah satu kos.</p>
                        <a href="../dashboard/dashboarduser.php#pilihan-kos" class="inline-block mt-4 bg-purple-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-purple-700 transition-colors">
                            Cari Kos Sekarang
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Riwayat Keluhan -->
            <div class="bg-white rounded-2xl shadow-lg p-8">
                <h2 class="text-2xl font-semibold text-gray-800 mb-6">Riwayat Keluhan Anda</h2>

                <?php if (!empty($user_complaints)): ?>
                    <div class="space-y-4">
                        <?php foreach ($user_complaints as $complaint): ?>
                        <div class="border-l-4 <?php echo ($complaint['status'] == 'baru') ? 'border-red-500' : (($complaint['status'] == 'diproses') ? 'border-yellow-500' : 'border-green-500'); ?> bg-gray-50 p-4 rounded-lg">
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="font-bold text-lg text-gray-800"><?php echo htmlspecialchars($complaint['nama_kost']); ?></h3>
                                <span class="status-badge status-<?php echo $complaint['status']; ?>">
                                    <?php echo htmlspecialchars(ucfirst($complaint['status'])); ?>
                                </span>
                            </div>
                            <p class="text-gray-700 mb-2"><?php echo htmlspecialchars($complaint['pesan']); ?></p>
                            <p class="text-sm text-gray-500">Diajukan pada: <?php echo date('d M Y H:i', strtotime($complaint['created_at'])); ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8">
                        <i class="fas fa-history text-4xl text-gray-400 mb-4"></i>
                        <p class="text-gray-500 text-lg">Belum ada riwayat keluhan.</p>
                        <p class="text-gray-400">Keluhan yang Anda ajukan akan muncul di sini.</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </main>

    <?php include '_user_profile_modal.php'; ?>

    <script>
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
                            img.className = 'w-8 h-8 rounded-full object-cover';
                            img.src = url;
                            img.alt = 'Foto Profil';
                            headerPhoto.parentNode.replaceChild(img, headerPhoto);
                        }
                    }
                    localStorage.removeItem('newProfilePhoto');
                }
            } catch (e) {
                console.error("Gagal memuat foto profil baru dari localStorage", e);
            }
        })();

        // Form submission
        document.getElementById('complaintForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            Swal.fire({
                title: 'Mengirim Keluhan...',
                text: 'Mohon tunggu sebentar',
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch('process_complaint.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Berhasil!',
                        text: data.message,
                        icon: 'success',
                        timer: 3000,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Gagal!',
                        text: data.message,
                        icon: 'error'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error!',
                    text: 'Terjadi kesalahan saat mengirim keluhan. Silakan coba lagi.',
                    icon: 'error'
                });
            });
        });

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
                    fetch('reset_notifications.php', { method: 'POST', body: new URLSearchParams({ specific: 'payment_success' }) })
                        .then(() => { location.reload(); });
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
    </script>

</body>
</html>
