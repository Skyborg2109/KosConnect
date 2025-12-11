<?php
session_start();
include 'config/db.php';

$id_kost = isset($_GET['kostId']) ? (int)$_GET['kostId'] : 0;
$kost_details = null;
$available_rooms = [];
$user_logged_in = isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;

if ($id_kost <= 0) {
    header("Location: index.php");
    exit();
}

// Ambil detail Kos
try {
    $stmt_kost = $conn->prepare("SELECT id_kost, nama_kost, alamat, deskripsi, fasilitas, harga, gambar, status_kos FROM kost WHERE id_kost = ?");
    $stmt_kost->bind_param("i", $id_kost);
    $stmt_kost->execute();
    $kost_details = $stmt_kost->get_result()->fetch_assoc();
    $stmt_kost->close();
} catch (mysqli_sql_exception $e) {
    $stmt_kost = $conn->prepare("SELECT id_kost, nama_kost, alamat, deskripsi, fasilitas, harga, status_kos FROM kost WHERE id_kost = ?");
    $stmt_kost->bind_param("i", $id_kost);
    $stmt_kost->execute();
    $kost_details = $stmt_kost->get_result()->fetch_assoc();
    $stmt_kost->close();
}

if (!$kost_details) {
    header("Location: index.php");
    exit();
}

// Ambil kamar yang tersedia
$stmt_rooms = $conn->prepare("SELECT id_kamar, nama_kamar, harga, status FROM kamar WHERE id_kost = ? AND status = 'tersedia' ORDER BY harga ASC");
$stmt_rooms->bind_param("i", $id_kost);
$stmt_rooms->execute();
$result_rooms = $stmt_rooms->get_result();
while ($row = $result_rooms->fetch_assoc()) {
    $available_rooms[] = $row;
}
$stmt_rooms->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($kost_details['nama_kost']); ?> - KosConnect</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        html { scroll-behavior: smooth; }
        
        nav {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95) !important;
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
        
        nav a:hover::after {
            width: 100%;
        }
        
        .gradient-text {
            background: linear-gradient(135deg, #9333ea 0%, #4f46e5 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .card-hover {
            transition: all 0.3s ease;
        }
        
        .card-hover:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(147, 51, 234, 0.15);
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navbar -->
    <nav class="bg-white shadow-lg fixed w-full top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <a href="index.php" class="flex items-center group">
                    <div class="w-10 h-10 bg-gradient-to-br from-purple-600 to-indigo-600 rounded-lg flex items-center justify-center mr-2 group-hover:scale-110 transition-transform">
                        <i class="fas fa-home text-white"></i>
                    </div>
                    <h1 class="text-2xl font-bold gradient-text">Kos<span class="text-purple-600">Connect</span></h1>
                </a>
                
                <div class="hidden md:flex items-center space-x-4">
                    <?php if ($user_logged_in): ?>
                        <a href="dashboard/dashboarduser.php" class="text-gray-700 font-medium hover:text-purple-600 py-2">Dashboard</a>
                        <a href="auth/logout.php" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600">
                            <i class="fas fa-sign-out-alt mr-1"></i>Logout
                        </a>
                    <?php else: ?>
                        <a href="auth/loginForm.php" class="text-purple-600 font-medium hover:text-purple-700 px-4 py-2">Login</a>
                        <a href="auth/RegisterForm.php" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700">
                            Daftar
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <main class="pt-24 pb-12">
        <div class="max-w-6xl mx-auto px-4">
            <!-- Breadcrumb -->
            <div class="mb-8">
                <a href="index.php#pilihan-kos" class="text-purple-600 hover:text-purple-700 font-medium">
                    <i class="fas fa-arrow-left mr-2"></i>Kembali
                </a>
            </div>

            <!-- Header -->
            <div class="bg-white rounded-2xl shadow-lg p-8 mb-8">
                <div class="grid md:grid-cols-2 gap-8">
                    <!-- Gambar -->
                    <div class="h-96 bg-gradient-to-br from-purple-400 to-purple-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-home text-white text-8xl opacity-50"></i>
                    </div>

                    <!-- Info Kos -->
                    <div>
                        <h1 class="text-4xl font-bold text-gray-800 mb-4"><?php echo htmlspecialchars($kost_details['nama_kost']); ?></h1>
                        
                        <div class="flex items-center text-lg text-gray-600 mb-6">
                            <i class="fas fa-map-marker-alt text-purple-600 mr-3"></i>
                            <?php echo htmlspecialchars($kost_details['alamat']); ?>
                        </div>

                        <div class="flex items-center mb-6">
                            <div class="flex text-yellow-400">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star-half-alt"></i>
                            </div>
                            <span class="ml-3 text-gray-600">(4.5 dari 5 - 23 ulasan)</span>
                        </div>

                        <div class="mb-8">
                            <h3 class="text-xl font-bold text-gray-800 mb-4">Deskripsi</h3>
                            <p class="text-gray-600 leading-relaxed"><?php echo htmlspecialchars($kost_details['deskripsi']); ?></p>
                        </div>

                        <div class="mb-8">
                            <h3 class="text-xl font-bold text-gray-800 mb-4">Fasilitas</h3>
                            <div class="flex flex-wrap gap-3">
                                <?php 
                                if (!empty($kost_details['fasilitas'])) {
                                    $fasilitas_list = explode(',', $kost_details['fasilitas']);
                                    foreach ($fasilitas_list as $f): 
                                ?>
                                <span class="bg-purple-100 text-purple-700 px-4 py-2 rounded-full text-sm font-medium">
                                    <i class="fas fa-check-circle mr-2"></i><?php echo trim($f); ?>
                                </span>
                                <?php endforeach; } ?>
                            </div>
                        </div>

                        <div class="bg-gradient-to-r from-purple-50 to-indigo-50 p-6 rounded-xl">
                            <p class="text-gray-600 text-sm mb-2">Harga mulai dari</p>
                            <p class="text-3xl font-bold text-purple-600">Rp <?php echo number_format($kost_details['harga'], 0, ',', '.'); ?></p>
                            <p class="text-gray-500 text-sm">per bulan</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kamar Tersedia -->
            <div class="mb-8">
                <h2 class="text-3xl font-bold text-gray-800 mb-6">Kamar Tersedia (<?php echo count($available_rooms); ?>)</h2>
                
                <?php if (!empty($available_rooms)): ?>
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($available_rooms as $room): ?>
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden card-hover border border-gray-100">
                        <div class="h-48 bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center">
                            <i class="fas fa-door-open text-white text-5xl opacity-50"></i>
                        </div>
                        <div class="p-6">
                            <h3 class="text-xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($room['nama_kamar']); ?></h3>
                            
                            <div class="mb-6">
                                <p class="text-gray-500 text-sm mb-1">Harga</p>
                                <p class="text-2xl font-bold text-blue-600">Rp <?php echo number_format($room['harga'], 0, ',', '.'); ?></p>
                            </div>

                            <?php if ($user_logged_in): ?>
                                <a href="user/booking.php?kostId=<?php echo $id_kost; ?>&roomId=<?php echo $room['id_kamar']; ?>" class="w-full bg-blue-600 text-white py-3 rounded-lg font-medium hover:bg-blue-700 transition-colors text-center">
                                    Booking Sekarang
                                </a>
                            <?php else: ?>
                                <button onclick="showLoginAlert()" class="w-full bg-blue-600 text-white py-3 rounded-lg font-medium hover:bg-blue-700 transition-colors">
                                    Booking Sekarang
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="bg-gray-50 rounded-xl p-12 text-center">
                    <i class="fas fa-door-open text-gray-300 text-5xl mb-4"></i>
                    <p class="text-gray-600 text-lg">Tidak ada kamar yang tersedia saat ini</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer class="bg-gray-800 text-white py-12 mt-20">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p class="text-gray-400">© 2025 KosConnect. All rights reserved.</p>
        </div>
    </footer>

    <script>
        function showLoginAlert() {
            Swal.fire({
                title: 'Login Diperlukan',
                html: '<p class="text-gray-600">Silakan login terlebih dahulu untuk melakukan booking.</p>',
                icon: 'info',
                showCancelButton: true,
                confirmButtonText: 'Login Sekarang',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#9333ea',
                cancelButtonColor: '#6b7280'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'auth/loginForm.php';
                }
            });
        }
    </script>
</body>
</html>
