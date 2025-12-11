<?php
session_start();
include 'config/db.php';

// --- Cek Sesi untuk Redirect ---
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    $role = $_SESSION['role'];
    
    if ($role === 'admin') {
        header("Location: dashboard/dashboardadmin.php");
    } elseif ($role === 'pemilik') { //
        header("Location: dashboard/dashboardpemilik.php");
    } else { // Termasuk role 'penyewa'
        header("Location: dashboard/dashboarduser.php");
    }
    exit();
}

// Ambil data kos dari database
$kost_list = [];
try {
    $sql = "SELECT id_kost, nama_kost, alamat, deskripsi, harga, fasilitas FROM kost WHERE status_kos = 'tersedia' LIMIT 6";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $kost_list[] = $row;
    }
} catch (Exception $e) {
    // Jika error, gunakan data kosong
    $kost_list = [];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KosConnect - Platform Pencarian dan Manajemen Kos</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/landing.css?v=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="scroll-smooth">
    <nav id="navbar" class="fixed top-0 w-full z-50 transition-all duration-300 py-3 sm:py-4">
        <div class="container mx-auto px-3 sm:px-6">
            <div class="flex items-center justify-between">
                <a href="#beranda" class="flex items-center">
                    <span class="text-2xl sm:text-4xl font-bold logo-text">Kos<span class="logo-highlight">Connect</span></span>
                </a>
                
                <div class="hidden md:flex items-center space-x-8">
                    <a href="#beranda" class="nav-link text-gray-700 hover:text-blue-500 font-medium transition-colors">Beranda</a>
                    <a href="#pilihan-kos" class="nav-link text-gray-700 hover:text-blue-500 font-medium transition-colors">Pilihan Kos</a>
                    <a href="#tentang" class="nav-link text-gray-700 hover:text-blue-500 font-medium transition-colors">Tentang Kami</a>
                    <a href="#fitur" class="nav-link text-gray-700 hover:text-blue-500 font-medium transition-colors">Fitur</a>
                    <a href="#kontak" class="nav-link text-gray-700 hover:text-blue-500 font-medium transition-colors">Kontak</a>
                </div>
                
                <div class="flex items-center space-x-2 sm:space-x-4">
                    <a href="auth/loginForm.php" class="login-btn hidden sm:block text-blue-500 hover:text-blue-600 font-medium transition-colors px-4 py-2 rounded-lg">Login</a>
                    <a href="auth/RegisterForm.php" class="register-btn hidden sm:block bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg font-medium transition-colors">Daftar</a>
                    
                    <button id="mobile-menu" class="md:hidden text-gray-700 p-1.5 hover:bg-gray-100 rounded-lg transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <div id="mobile-nav" class="fixed top-0 left-0 w-full h-screen bg-white z-40 transform -translate-x-full transition-transform duration-300 md:hidden overflow-y-auto">
        <div class="p-4 border-b flex items-center justify-between">
            <span class="text-2xl font-bold text-blue-500">KosConnect</span>
            <button id="close-menu" class="p-2 hover:bg-gray-100 rounded-lg">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        
        <div class="space-y-1 px-4 py-6">
            <a href="#beranda" class="block px-4 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-500 font-medium rounded-lg transition-colors">Beranda</a>
            <a href="#pilihan-kos" class="block px-4 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-500 font-medium rounded-lg transition-colors">Pilihan Kos</a>
            <a href="#tentang" class="block px-4 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-500 font-medium rounded-lg transition-colors">Tentang Kami</a>
            <a href="#fitur" class="block px-4 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-500 font-medium rounded-lg transition-colors">Fitur</a>
            <a href="#kontak" class="block px-4 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-500 font-medium rounded-lg transition-colors">Kontak</a>
            <div class="pt-6 border-t mt-6">
                <a href="auth/loginForm.php" class="block w-full text-center text-blue-500 font-medium py-3 mb-3 px-4 rounded-lg border-2 border-blue-500 hover:bg-blue-50 transition-colors">Login</a>
                <a href="auth/RegisterForm.php" class="block w-full bg-blue-500 text-white px-4 py-3 rounded-lg font-medium text-center hover:bg-blue-600 transition-colors">Daftar</a>
            </div>
        </div>
    </div>

    <section id="beranda" class="hero-bg min-h-screen flex items-center pt-20">
        <div class="container mx-auto px-4 sm:px-6">
            <div class="max-w-4xl mx-auto text-center">
                <div class="text-white">
                    <div class="flex items-center justify-center mb-4 animate-fade-in-up">
                        <span class="text-4xl sm:text-5xl font-bold text-white">Kos<span class="text-yellow-300">Connect</span></span>
                    </div>
                    <h1 class="text-2xl sm:text-4xl md:text-6xl font-bold mb-4 sm:mb-6 leading-tight animate-fade-in-up animation-delay-300">
                        Temukan Kos Impian Anda dengan <span class="text-yellow-300">Mudah & Cepat</span>
                    </h1>
                    <p class="text-base sm:text-xl md:text-2xl mb-6 sm:mb-10 text-blue-100 animate-fade-in-up animation-delay-600">
                        KosConnect adalah platform terpadu yang menghubungkan penyewa dengan pemilik kos di seluruh Indonesia.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-2 sm:gap-4 justify-center animate-fade-in-up animation-delay-900 px-2 sm:px-0">
                        <a href="auth/RegisterForm.php" class="bg-white text-blue-500 px-6 sm:px-8 py-3 sm:py-4 rounded-lg font-semibold hover:bg-gray-100 transition-colors flex items-center justify-center">
                            <i class='bx bx-search mr-2'></i>
                            <span>Cari Kos</span>
                        </a>
                        <a href="auth/RegisterForm_owner.php" class="border-2 border-white text-white px-6 sm:px-8 py-3 sm:py-4 rounded-lg font-semibold hover:bg-white hover:text-blue-500 transition-colors flex items-center justify-center">
                            <i class='bx bx-home mr-2'></i>
                            <span>Daftar Pemilik</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="pilihan-kos" class="py-12 sm:py-20 bg-white">
        <div class="container mx-auto px-4 sm:px-6">
            <div class="text-center mb-8 sm:mb-16">
                <h2 class="text-2xl sm:text-4xl font-bold text-gray-800 mb-2 sm:mb-4">Pilihan Kos Terpopuler</h2>
                <p class="text-base sm:text-xl text-gray-600">Lihat beberapa kos terbaik yang tersedia di berbagai kota</p>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-8 mb-8 sm:mb-12">
                <?php if (!empty($kost_list)): ?>
                    <?php foreach ($kost_list as $index => $kost): ?>
                    <div class="bg-white rounded-lg sm:rounded-xl shadow-lg overflow-hidden card-hover border border-gray-200 hover:border-blue-300 transition-colors">
                        <div class="h-40 sm:h-48 bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center">
                            <div class="text-center text-white">
                                <i class='bx bx-home-alt text-5xl sm:text-6xl mb-2 sm:mb-4'></i>
                                <p class="text-base sm:text-lg font-semibold">Kos</p>
                            </div>
                        </div>
                        <div class="p-4 sm:p-6">
                            <h3 class="text-lg sm:text-xl font-semibold text-gray-800 mb-2 line-clamp-2"><?php echo htmlspecialchars($kost['nama_kost']); ?></h3>
                            <p class="text-gray-600 text-xs sm:text-sm mb-2 sm:mb-3 line-clamp-2">
                                <i class='bx bx-map mr-1 sm:mr-2 text-blue-500'></i>
                                <?php echo htmlspecialchars($kost['alamat']); ?>
                            </p>
                            <p class="text-gray-700 text-xs sm:text-sm mb-3 sm:mb-4 line-clamp-2"><?php echo htmlspecialchars(substr($kost['deskripsi'], 0, 80)) . '...'; ?></p>
                            
                            <?php if (!empty($kost['fasilitas'])): ?>
                            <div class="mb-3 sm:mb-4">
                                <div class="flex flex-wrap gap-1">
                                    <?php 
                                    $fasilitas = array_slice(explode(',', $kost['fasilitas']), 0, 2);
                                    foreach ($fasilitas as $f): 
                                    ?>
                                    <span class="bg-gray-100 text-gray-700 px-2 py-1 rounded text-xs">
                                        <?php echo trim($f); ?>
                                    </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="flex items-center justify-between mb-3 sm:mb-4">
                                <div>
                                    <p class="text-xs text-gray-500">Harga mulai dari</p>
                                    <p class="text-xl sm:text-2xl font-bold text-blue-600">Rp <?php echo number_format($kost['harga'], 0, ',', '.'); ?></p>
                                </div>
                                <div class="flex text-yellow-400 text-sm">
                                    <i class='bx bxs-star'></i>
                                    <i class='bx bxs-star'></i>
                                    <i class='bx bxs-star'></i>
                                    <i class='bx bxs-star'></i>
                                    <i class='bx bxs-star-half'></i>
                                </div>
                            </div>
                            <button onclick="window.location.href='detail_kos.php?kostId=<?php echo $kost['id_kost']; ?>'" class="w-full bg-blue-500 hover:bg-blue-600 text-white px-3 sm:px-4 py-2 rounded-lg font-medium transition-colors text-sm sm:text-base">
                                Lihat Detail
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-span-full text-center py-12">
                        <p class="text-gray-500 text-base sm:text-lg mb-4">Belum ada kos yang tersedia saat ini.</p>
                        <p class="text-gray-400 text-sm sm:text-base">Silakan login untuk melihat semua pilihan kos.</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="text-center">
                <a href="index.php#pilihan-kos" class="inline-block bg-blue-500 hover:bg-blue-600 text-white px-6 sm:px-8 py-3 sm:py-4 rounded-lg font-semibold transition-colors text-sm sm:text-base">
                    <i class='bx bx-search mr-2'></i>
                    Lihat Semua Kos
                </a>
            </div>
        </div>
    </section>

    <section id="fitur" class="py-12 sm:py-20 bg-gray-50">
        <div class="container mx-auto px-4 sm:px-6">
            <div class="text-center mb-8 sm:mb-16">
                <h2 class="text-2xl sm:text-4xl font-bold text-gray-800 mb-2 sm:mb-4">Fitur Unggulan KosConnect</h2>
                <p class="text-base sm:text-xl text-gray-600">Solusi lengkap untuk kebutuhan kos Anda</p>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-8">
                <div class="bg-white p-6 sm:p-8 rounded-lg sm:rounded-xl shadow-lg card-hover text-center">
                    <div class="w-14 sm:w-16 h-14 sm:h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4 sm:mb-6">
                        <i class='bx bx-search text-2xl sm:text-3xl text-blue-500'></i>
                    </div>
                    <h3 class="text-lg sm:text-xl font-semibold text-gray-800 mb-3 sm:mb-4">Pencarian Kos Cepat</h3>
                    <p class="text-gray-600 text-sm sm:text-base">Temukan kos impian dengan filter lokasi, harga, dan fasilitas yang lengkap</p>
                </div>
                
                <div class="bg-white p-6 sm:p-8 rounded-lg sm:rounded-xl shadow-lg card-hover text-center">
                    <div class="w-14 sm:w-16 h-14 sm:h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4 sm:mb-6">
                        <i class='bx bx-home-alt text-2xl sm:text-3xl text-green-500'></i>
                    </div>
                    <h3 class="text-lg sm:text-xl font-semibold text-gray-800 mb-3 sm:mb-4">Manajemen Kos Online</h3>
                    <p class="text-gray-600 text-sm sm:text-base">Kelola data kos, update harga, dan pantau penyewa dari dashboard</p>
                </div>
                
                <div class="bg-white p-6 sm:p-8 rounded-lg sm:rounded-xl shadow-lg card-hover text-center">
                    <div class="w-14 sm:w-16 h-14 sm:h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4 sm:mb-6">
                        <i class='bx bx-chat text-2xl sm:text-3xl text-purple-500'></i>
                    </div>
                    <h3 class="text-lg sm:text-xl font-semibold text-gray-800 mb-3 sm:mb-4">Hubungi Pemilik Langsung</h3>
                    <p class="text-gray-600 text-sm sm:text-base">Komunikasi langsung dengan pemilik kos melalui chat terintegrasi</p>
                </div>
                
                <div class="bg-white p-6 sm:p-8 rounded-lg sm:rounded-xl shadow-lg card-hover text-center">
                    <div class="w-14 sm:w-16 h-14 sm:h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4 sm:mb-6">
                        <i class='bx bx-shield-check text-2xl sm:text-3xl text-red-500'></i>
                    </div>
                    <h3 class="text-lg sm:text-xl font-semibold text-gray-800 mb-3 sm:mb-4">Login Aman dan Cepat</h3>
                    <p class="text-gray-600 text-sm sm:text-base">Sistem keamanan berlapis untuk melindungi data pribadi Anda</p>
                </div>
            </div>
        </div>
    </section>

    <section id="tentang" class="py-12 sm:py-20 bg-white">
        <div class="container mx-auto px-4 sm:px-6">
            <div class="text-center mb-8 sm:mb-16">
                <h2 class="text-2xl sm:text-4xl font-bold text-gray-800 mb-2 sm:mb-4">Untuk Siapa KosConnect Dibuat?</h2>
                <p class="text-base sm:text-xl text-gray-600">Solusi terbaik untuk penyewa dan pemilik kos</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 sm:gap-12">
                <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-6 sm:p-8 rounded-xl sm:rounded-2xl">
                    <div class="flex items-center mb-4 sm:mb-6">
                        <div class="w-10 sm:w-12 h-10 sm:h-12 bg-blue-500 rounded-full flex items-center justify-center mr-3 sm:mr-4 flex-shrink-0">
                            <i class='bx bx-user text-white text-lg sm:text-xl'></i>
                        </div>
                        <h3 class="text-xl sm:text-2xl font-bold text-gray-800">Untuk Penyewa</h3>
                    </div>
                    <p class="text-gray-700 mb-4 sm:mb-6 leading-relaxed text-sm sm:text-base">
                        Temukan kos dengan mudah, bandingkan harga & fasilitas. Dapatkan informasi lengkap tentang kos impian Anda dengan sistem pencarian yang canggih.
                    </p>
                    <ul class="space-y-2 sm:space-y-3 mb-6 sm:mb-8 text-sm sm:text-base">
                        <li class="flex items-center text-gray-700">
                            <i class='bx bx-check-circle text-blue-500 mr-2 sm:mr-3 flex-shrink-0'></i>
                            <span>Pencarian berdasarkan lokasi dan budget</span>
                        </li>
                        <li class="flex items-center text-gray-700">
                            <i class='bx bx-check-circle text-blue-500 mr-2 sm:mr-3 flex-shrink-0'></i>
                            <span>Foto dan deskripsi lengkap fasilitas</span>
                        </li>
                        <li class="flex items-center text-gray-700">
                            <i class='bx bx-check-circle text-blue-500 mr-2 sm:mr-3 flex-shrink-0'></i>
                            <span>Review dari penyewa sebelumnya</span>
                        </li>
                    </ul>
                    <a href="auth/RegisterForm.php" class="bg-blue-500 hover:bg-blue-600 text-white px-6 sm:px-8 py-3 sm:py-3 rounded-lg font-semibold transition-colors w-full inline-block text-center text-sm sm:text-base">
                        Daftar Sebagai Penyewa
                    </a>
                </div>
                
                <div class="bg-gradient-to-br from-green-50 to-green-100 p-6 sm:p-8 rounded-xl sm:rounded-2xl">
                    <div class="flex items-center mb-4 sm:mb-6">
                        <div class="w-10 sm:w-12 h-10 sm:h-12 bg-green-500 rounded-full flex items-center justify-center mr-3 sm:mr-4 flex-shrink-0">
                            <i class='bx bx-building-house text-white text-lg sm:text-xl'></i>
                        </div>
                        <h3 class="text-xl sm:text-2xl font-bold text-gray-800">Untuk Pemilik Kos</h3>
                    </div>
                    <p class="text-gray-700 mb-4 sm:mb-6 leading-relaxed text-sm sm:text-base">
                        Kelola data kos, update harga, dan pantau penyewa dari dashboard yang mudah digunakan. Tingkatkan okupansi kos Anda dengan jangkauan yang lebih luas.
                    </p>
                    <ul class="space-y-2 sm:space-y-3 mb-6 sm:mb-8 text-sm sm:text-base">
                        <li class="flex items-center text-gray-700">
                            <i class='bx bx-check-circle text-green-500 mr-2 sm:mr-3 flex-shrink-0'></i>
                            <span>Dashboard manajemen kos lengkap</span>
                        </li>
                        <li class="flex items-center text-gray-700">
                            <i class='bx bx-check-circle text-green-500 mr-2 sm:mr-3 flex-shrink-0'></i>
                            <span>Laporan keuangan otomatis</span>
                        </li>
                        <li class="flex items-center text-gray-700">
                            <i class='bx bx-check-circle text-green-500 mr-2 sm:mr-3 flex-shrink-0'></i>
                            <span>Notifikasi pembayaran real-time</span>
                        </li>
                    </ul>
                    <a href="auth/RegisterForm_owner.php" class="bg-green-500 hover:bg-green-600 text-white px-6 sm:px-8 py-3 sm:py-3 rounded-lg font-semibold transition-colors w-full inline-block text-center text-sm sm:text-base">
                        Daftar Sebagai Pemilik Kos
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="py-12 sm:py-20 bg-gray-50">
        <div class="container mx-auto px-4 sm:px-6">
            <div class="text-center mb-8 sm:mb-16">
                <h2 class="text-2xl sm:text-4xl font-bold text-gray-800 mb-2 sm:mb-4">Cuplikan Tampilan Aplikasi</h2>
                <p class="text-base sm:text-xl text-gray-600">Lihat betapa mudahnya menggunakan KosConnect</p>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 sm:gap-8">
                <div class="bg-white rounded-lg sm:rounded-2xl shadow-lg overflow-hidden card-hover">
                    <div class="h-32 sm:h-48 bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center">
                        <div class="text-center text-white">
                            <i class='bx bx-log-in text-5xl sm:text-6xl mb-2 sm:mb-4'></i>
                            <p class="text-base sm:text-lg font-semibold">Halaman Login</p>
                        </div>
                    </div>
                    <div class="p-4 sm:p-6">
                        <h3 class="text-lg sm:text-xl font-semibold text-gray-800 mb-2 sm:mb-3">Login Mudah & Aman</h3>
                        <p class="text-gray-600 text-sm sm:text-base">Interface login yang sederhana dengan keamanan berlapis untuk melindungi akun Anda.</p>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg sm:rounded-2xl shadow-lg overflow-hidden card-hover">
                    <div class="h-32 sm:h-48 bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center">
                        <div class="text-center text-white">
                            <i class='bx bx-user text-5xl sm:text-6xl mb-2 sm:mb-4'></i>
                            <p class="text-base sm:text-lg font-semibold">Dashboard Penyewa</p>
                        </div>
                    </div>
                    <div class="p-4 sm:p-6">
                        <h3 class="text-lg sm:text-xl font-semibold text-gray-800 mb-2 sm:mb-3">Dashboard Penyewa</h3>
                        <p class="text-gray-600 text-sm sm:text-base">Kelola profil, riwayat pencarian, dan bookmark kos favorit dalam satu tempat.</p>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg sm:rounded-2xl shadow-lg overflow-hidden card-hover">
                    <div class="h-32 sm:h-48 bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center">
                        <div class="text-center text-white">
                            <i class='bx bx-building text-5xl sm:text-6xl mb-2 sm:mb-4'></i>
                            <p class="text-base sm:text-lg font-semibold">Dashboard Pemilik</p>
                        </div>
                    </div>
                    <div class="p-4 sm:p-6">
                        <h3 class="text-lg sm:text-xl font-semibold text-gray-800 mb-2 sm:mb-3">Dashboard Pemilik Kos</h3>
                        <p class="text-gray-600 text-sm sm:text-base">Pantau okupansi, kelola pembayaran, dan update informasi kos dengan mudah.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-12 sm:py-20 bg-white">
        <div class="container mx-auto px-4 sm:px-6">
            <div class="text-center mb-8 sm:mb-16">
                <h2 class="text-2xl sm:text-4xl font-bold text-gray-800 mb-2 sm:mb-4">Apa Kata Pengguna Kami?</h2>
                <p class="text-base sm:text-xl text-gray-600">Testimoni dari penyewa dan pemilik kos yang puas</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-8">
                <div class="testimonial-card p-4 sm:p-6 rounded-lg sm:rounded-xl shadow-lg">
                    <div class="flex items-center mb-3 sm:mb-4">
                        <div class="w-10 sm:w-12 h-10 sm:h-12 bg-blue-500 rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                            <span class="text-white font-bold">B</span>
                        </div>
                        <div class="min-w-0">
                            <h4 class="font-semibold text-gray-800 text-sm sm:text-base">Budi Santoso</h4>
                            <p class="text-xs sm:text-sm text-gray-600">Pemilik Kos di Jakarta</p>
                        </div>
                    </div>
                    <p class="text-gray-700 italic text-sm sm:text-base mb-3 sm:mb-4">"Sekarang saya bisa kelola kos tanpa ribet! Dashboard KosConnect sangat membantu untuk memantau pembayaran dan okupansi kos saya."</p>
                    <div class="flex text-yellow-400 text-sm">
                        <i class='bx bxs-star'></i>
                        <i class='bx bxs-star'></i>
                        <i class='bx bxs-star'></i>
                        <i class='bx bxs-star'></i>
                        <i class='bx bxs-star'></i>
                    </div>
                </div>
                
                <div class="testimonial-card p-4 sm:p-6 rounded-lg sm:rounded-xl shadow-lg">
                    <div class="flex items-center mb-3 sm:mb-4">
                        <div class="w-10 sm:w-12 h-10 sm:h-12 bg-green-500 rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                            <span class="text-white font-bold">S</span>
                        </div>
                        <div class="min-w-0">
                            <h4 class="font-semibold text-gray-800 text-sm sm:text-base">Sari Dewi</h4>
                            <p class="text-xs sm:text-sm text-gray-600">Mahasiswa di Bandung</p>
                        </div>
                    </div>
                    <p class="text-gray-700 italic text-sm sm:text-base mb-3 sm:mb-4">"Pencarian kos jadi super mudah! Saya bisa bandingkan harga dan fasilitas langsung dari aplikasi. Sangat recommended!"</p>
                    <div class="flex text-yellow-400 text-sm">
                        <i class='bx bxs-star'></i>
                        <i class='bx bxs-star'></i>
                        <i class='bx bxs-star'></i>
                        <i class='bx bxs-star'></i>
                        <i class='bx bxs-star'></i>
                    </div>
                </div>
                
                <div class="testimonial-card p-4 sm:p-6 rounded-lg sm:rounded-xl shadow-lg">
                    <div class="flex items-center mb-3 sm:mb-4">
                        <div class="w-10 sm:w-12 h-10 sm:h-12 bg-purple-500 rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                            <span class="text-white font-bold">A</span>
                        </div>
                        <div class="min-w-0">
                            <h4 class="font-semibold text-gray-800 text-sm sm:text-base">Ahmad Rizki</h4>
                            <p class="text-xs sm:text-sm text-gray-600">Pemilik Kos di Yogyakarta</p>
                        </div>
                    </div>
                    <p class="text-gray-700 italic text-sm sm:text-base mb-3 sm:mb-4">"Okupansi kos saya meningkat 80% setelah menggunakan KosConnect. Platform yang sangat efektif untuk menjangkau calon penyewa!"</p>
                    <div class="flex text-yellow-400 text-sm">
                        <i class='bx bxs-star'></i>
                        <i class='bx bxs-star'></i>
                        <i class='bx bxs-star'></i>
                        <i class='bx bxs-star'></i>
                        <i class='bx bxs-star'></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-12 sm:py-20 bg-gray-50">
        <div class="container mx-auto px-4 sm:px-6">
            <div class="text-center mb-8 sm:mb-16">
                <h2 class="text-2xl sm:text-4xl font-bold text-gray-800 mb-2 sm:mb-4">Pertanyaan yang Sering Diajukan</h2>
                <p class="text-base sm:text-xl text-gray-600">Temukan jawaban untuk pertanyaan umum tentang KosConnect</p>
            </div>
            
            <div class="max-w-3xl mx-auto space-y-3 sm:space-y-6">
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <button onclick="toggleFAQ(1)" class="w-full p-4 sm:p-6 text-left flex justify-between items-center hover:bg-gray-50 transition-colors">
                        <span class="font-semibold text-gray-800 text-sm sm:text-base pr-3">Bagaimana cara mendaftar sebagai pemilik kos?</span>
                        <i class='bx bx-chevron-down text-2xl text-gray-500 transform transition-transform flex-shrink-0' id="icon-1"></i>
                    </button>
                    <div id="faq-1" class="hidden px-4 sm:px-6 pb-4 sm:pb-6">
                        <p class="text-gray-600 text-sm sm:text-base">Klik tombol "Daftar Sebagai Pemilik Kos", isi formulir pendaftaran dengan data lengkap kos Anda, upload foto-foto kos, dan tunggu verifikasi dari tim kami dalam 1x24 jam.</p>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <button onclick="toggleFAQ(2)" class="w-full p-4 sm:p-6 text-left flex justify-between items-center hover:bg-gray-50 transition-colors">
                        <span class="font-semibold text-gray-800 text-sm sm:text-base pr-3">Apakah ada biaya untuk menggunakan KosConnect?</span>
                        <i class='bx bx-chevron-down text-2xl text-gray-500 transform transition-transform flex-shrink-0' id="icon-2"></i>
                    </button>
                    <div id="faq-2" class="hidden px-4 sm:px-6 pb-4 sm:pb-6">
                        <p class="text-gray-600 text-sm sm:text-base">Untuk penyewa, KosConnect gratis 100%. Untuk pemilik kos, kami mengenakan biaya komisi yang sangat kompetitif hanya ketika ada transaksi yang berhasil.</p>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <button onclick="toggleFAQ(3)" class="w-full p-4 sm:p-6 text-left flex justify-between items-center hover:bg-gray-50 transition-colors">
                        <span class="font-semibold text-gray-800 text-sm sm:text-base pr-3">Bagaimana sistem keamanan di KosConnect?</span>
                        <i class='bx bx-chevron-down text-2xl text-gray-500 transform transition-transform flex-shrink-0' id="icon-3"></i>
                    </button>
                    <div id="faq-3" class="hidden px-4 sm:px-6 pb-4 sm:pb-6">
                        <p class="text-gray-600 text-sm sm:text-base">Kami menggunakan enkripsi SSL, verifikasi identitas berlapis, dan sistem monitoring 24/7 untuk memastikan keamanan data dan transaksi Anda.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer id="kontak" class="bg-gray-800 text-white py-12 sm:py-16">
        <div class="container mx-auto px-4 sm:px-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6 sm:gap-8">
                <div>
                    <div class="flex items-center space-x-2 mb-4 sm:mb-6">
                        <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center">
                            <i class='bx bx-home-alt text-white text-xl'></i>
                        </div>
                        <span class="text-lg sm:text-2xl font-bold">KosConnect</span>
                    </div>
                    <p class="text-gray-300 mb-4 sm:mb-6 text-sm sm:text-base">Platform terpercaya untuk pencarian dan manajemen kos di Indonesia.</p>
                    <div class="flex space-x-4">
                        <a href="#" class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center hover:bg-blue-700 transition-colors">
                            <i class='bx bxl-facebook text-white'></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-pink-600 rounded-full flex items-center justify-center hover:bg-pink-700 transition-colors">
                            <i class='bx bxl-instagram text-white'></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-black rounded-full flex items-center justify-center hover:bg-gray-900 transition-colors">
                            <i class='bx bxl-tiktok text-white'></i>
                        </a>
                    </div>
                </div>
                
                <div>
                    <h3 class="text-lg sm:text-xl font-semibold mb-4 sm:mb-6">Navigasi</h3>
                    <ul class="space-y-2 sm:space-y-3 text-sm sm:text-base">
                        <li><a href="#beranda" class="text-gray-300 hover:text-white transition-colors">Beranda</a></li>
                        <li><a href="#pilihan-kos" class="text-gray-300 hover:text-white transition-colors">Pilihan Kos</a></li>
                        <li><a href="#tentang" class="text-gray-300 hover:text-white transition-colors">Tentang Kami</a></li>
                        <li><a href="#fitur" class="text-gray-300 hover:text-white transition-colors">Fitur</a></li>
                        <li><a href="#kontak" class="text-gray-300 hover:text-white transition-colors">Kontak</a></li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="text-lg sm:text-xl font-semibold mb-4 sm:mb-6">Layanan</h3>
                    <ul class="space-y-2 sm:space-y-3 text-sm sm:text-base">
                        <li><a href="#" class="text-gray-300 hover:text-white transition-colors">Cari Kos</a></li>
                        <li><a href="#" class="text-gray-300 hover:text-white transition-colors">Daftar Kos</a></li>
                        <li><a href="#" class="text-gray-300 hover:text-white transition-colors">Bantuan</a></li>
                        <li><a href="#" class="text-gray-300 hover:text-white transition-colors">FAQ</a></li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="text-lg sm:text-xl font-semibold mb-4 sm:mb-6">Kontak</h3>
                    <div class="space-y-3 sm:space-y-4 text-sm sm:text-base">
                        <div class="flex items-center">
                            <i class='bx bx-envelope mr-3 text-blue-400 flex-shrink-0'></i>
                            <span class="text-gray-300 break-all">info@kosconnect.com</span>
                        </div>
                        <div class="flex items-center">
                            <i class='bx bx-phone mr-3 text-blue-400 flex-shrink-0'></i>
                            <span class="text-gray-300">+62 812-3456-7890</span>
                        </div>
                        <div class="flex items-center">
                            <i class='bx bx-map mr-3 text-blue-400 flex-shrink-0'></i>
                            <span class="text-gray-300">Jakarta, Indonesia</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="border-t border-gray-700 mt-8 sm:mt-12 pt-6 sm:pt-8 text-center">
                <p class="text-gray-300 text-sm sm:text-base">© 2025 KosConnect. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <button id="scrollTop" class="fixed bottom-8 right-8 w-12 h-12 bg-blue-500 text-white rounded-full shadow-lg hover:bg-blue-600 transition-all duration-300 opacity-0 pointer-events-none">
        <i class='bx bx-up-arrow-alt text-xl'></i>
    </button>

    <script>
        // Parallax effect for hero background
        window.addEventListener('scroll', function() {
            const scrolled = window.pageYOffset;
            const heroBg = document.querySelector('.hero-bg');
            if (heroBg) {
                const rate = scrolled * -0.5;
                heroBg.style.backgroundPosition = `center ${rate}px`;
            }
        });

        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('navbar');
            const scrollTop = document.getElementById('scrollTop');

            if (window.scrollY > 100) {
                navbar.classList.add('navbar-blur');
                navbar.classList.remove('navbar-transparent');
                scrollTop.classList.remove('opacity-0', 'pointer-events-none');
            } else {
                navbar.classList.remove('navbar-blur');
                navbar.classList.add('navbar-transparent');
                scrollTop.classList.add('opacity-0', 'pointer-events-none');
            }
        });

        // Set initial navbar state
        document.addEventListener('DOMContentLoaded', function() {
            const navbar = document.getElementById('navbar');
            if (window.scrollY <= 100) {
                navbar.classList.add('navbar-transparent');
            }
        });

        // Mobile menu toggle
        const mobileMenu = document.getElementById('mobile-menu');
        const mobileNav = document.getElementById('mobile-nav');
        const closeMenu = document.getElementById('close-menu');

        mobileMenu.addEventListener('click', function() {
            mobileNav.classList.remove('-translate-x-full');
        });

        closeMenu.addEventListener('click', function() {
            mobileNav.classList.add('-translate-x-full');
        });

        // Close mobile menu when clicking on links
        const mobileLinks = mobileNav.querySelectorAll('a');
        mobileLinks.forEach(link => {
            link.addEventListener('click', function() {
                mobileNav.classList.add('-translate-x-full');
            });
        });

        // Smooth scroll function
        function scrollToSection(sectionId) {
            const element = document.getElementById(sectionId);
            if (element) {
                element.scrollIntoView({ behavior: 'smooth' });
            }
        }

        // Scroll to top
        document.getElementById('scrollTop').addEventListener('click', function() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        // FAQ toggle
        function toggleFAQ(id) {
            const faq = document.getElementById(`faq-${id}`);
            const icon = document.getElementById(`icon-${id}`);
            
            if (faq.classList.contains('hidden')) {
                faq.classList.remove('hidden');
                icon.classList.add('rotate-180');
            } else {
                faq.classList.add('hidden');
                icon.classList.remove('rotate-180');
            }
        }

        // Smooth scroll for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>