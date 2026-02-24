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
    $stmt_kost = $conn->prepare("SELECT k.id_kost, k.nama_kost, k.alamat, k.deskripsi, k.fasilitas, k.peraturan, k.harga, k.gambar, k.status_kos, k.tipe_kost, k.jenis_kamar, u.nama_lengkap, u.foto_profil FROM kost k LEFT JOIN user u ON k.id_pemilik = u.id_user WHERE k.id_kost = ?");
    $stmt_kost->bind_param("i", $id_kost);
    $stmt_kost->execute();
    $kost_details = $stmt_kost->get_result()->fetch_assoc();
    $stmt_kost->close();
} catch (mysqli_sql_exception $e) {
    // Retry without specific columns or handle error better. Assuming migration ran, we try again with full set or fallback.
    // Ideally we should just rely on the first query if migration is robust.
    $stmt_kost = $conn->prepare("SELECT id_kost, nama_kost, alamat, deskripsi, fasilitas, harga, status_kos, tipe_kost FROM kost WHERE id_kost = ?");
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
$sql_rooms = "SELECT k.id_kamar, k.nama_kamar, k.tipe_kamar, k.fasilitas, k.harga, k.status, k.foto, rt.foto_tipe as foto_tipe_master, rt.peraturan_kamar 
              FROM kamar k 
              LEFT JOIN kost_room_types rt ON k.tipe_kamar = rt.nama_tipe AND rt.id_kost = k.id_kost 
              WHERE k.id_kost = ? AND k.status = 'tersedia' 
              ORDER BY k.harga ASC";
$stmt_rooms = $conn->prepare($sql_rooms);
$stmt_rooms->bind_param("i", $id_kost);
$stmt_rooms->execute();
$result_rooms = $stmt_rooms->get_result();
while ($row = $result_rooms->fetch_assoc()) {
    $available_rooms[] = $row;
}
$stmt_rooms->close();

// Ambil foto-foto kost
$kost_photos = [
    'Bangunan' => [],
    'Kamar' => [],
    'Kamar Mandi' => [],
    'Fasilitas Bersama' => [],
    'Lainnya' => []
];

// Pastikan kategori yang mungkin ada di database tercover
$stmt_photos = $conn->prepare("SELECT file_name, category FROM kost_photos WHERE id_kost = ?");
$stmt_photos->bind_param("i", $id_kost);
$stmt_photos->execute();
$res_photos = $stmt_photos->get_result();
while ($row = $res_photos->fetch_assoc()) {
    $cat = ucfirst(strtolower($row['category'] ?? 'Lainnya')); // Normalize case
    // Kategori mapping jika database menggunakan format berbeda atau lowercase
    // Sesuaikan dengan key array $kost_photos
    if ($cat == 'Bangunan' || $cat == 'Depan') $target = 'Bangunan';
    elseif ($cat == 'Kamar' || $cat == 'Dalam') $target = 'Kamar';
    elseif ($cat == 'Kamar_mandi' || $cat == 'Kamar Mandi') $target = 'Kamar Mandi';
    elseif ($cat == 'Fasilitas' || $cat == 'Fasilitas Umum' || $cat == 'Fasilitas Bersama') $target = 'Fasilitas Bersama';
    else $target = 'Lainnya';
    
    $kost_photos[$target][] = $row['file_name'];
}
$stmt_photos->close();

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
            <div class="bg-white rounded-2xl shadow-lg p-8 mb-6">
                <div class="grid md:grid-cols-2 gap-8">
                    <!-- Gambar -->
                    <!-- Gambar -->
                    <?php 
                    $gambar = $kost_details['gambar'] ?? '';
                    $is_url = strpos($gambar, 'http') === 0;
                    $img_src = $is_url ? $gambar : 'uploads/kost/' . $gambar;
                    $show_image = !empty($gambar) && ($is_url || file_exists(__DIR__ . '/' . $img_src));

                    if ($show_image): 
                    ?>
                        <div class="h-96 rounded-xl overflow-hidden shadow-md group">
                            <img src="<?php echo htmlspecialchars($img_src); ?>" 
                                 alt="<?php echo htmlspecialchars($kost_details['nama_kost']); ?>" 
                                 class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-500">
                        </div>
                    <?php else: ?>
                        <div class="h-96 bg-gradient-to-br from-purple-400 to-purple-600 rounded-xl flex items-center justify-center shadow-md">
                            <i class="fas fa-home text-white text-8xl opacity-50"></i>
                        </div>
                    <?php endif; ?>

                    <!-- Info Kos -->
                    <div>
                        <div class="flex flex-wrap items-center gap-3 mb-4">
                            <h1 class="text-4xl font-bold text-gray-800"><?php echo htmlspecialchars($kost_details['nama_kost']); ?></h1>
                            <?php 
                                $tipe = $kost_details['tipe_kost'] ?? 'Campuran';
                                $tipe_bg = 'bg-gray-600';
                                $tipe_icon = '🚻';
                                if ($tipe === 'Putra') {
                                    $tipe_bg = 'bg-blue-600';
                                    $tipe_icon = '🚹';
                                } elseif ($tipe === 'Putri') {
                                    $tipe_bg = 'bg-pink-600';
                                    $tipe_icon = '🚺';
                                }
                            ?>
                            <span class="<?php echo $tipe_bg; ?> text-white px-4 py-1.5 rounded-full text-sm font-bold shadow-md flex items-center gap-2">
                                <?php echo $tipe_icon . ' ' . $tipe; ?>
                            </span>
                            <?php if (!empty($kost_details['jenis_kamar'])): ?>
                                <span class="bg-indigo-600 text-white px-4 py-1.5 rounded-full text-sm font-bold shadow-md flex items-center gap-2">
                                    🛏️ <?php echo htmlspecialchars($kost_details['jenis_kamar']); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        
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

                        <div class="mb-4">
                            <h3 class="text-xl font-bold text-gray-800 mb-4">Deskripsi</h3>
                            <p class="text-gray-600 leading-relaxed"><?php echo htmlspecialchars($kost_details['deskripsi']); ?></p>
                        </div>

                        <div class="mb-4">
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

                        <?php if (!empty($kost_details['peraturan'])): ?>
                        <div class="mb-4 p-6 bg-red-50 border border-red-100 rounded-xl">
                            <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                                <i class="fas fa-exclamation-circle text-red-500 mr-2"></i> Peraturan Kos
                            </h3>
                            <div class="prose text-gray-600 leading-relaxed font-medium">
                                <?php echo nl2br(htmlspecialchars($kost_details['peraturan'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Owner Profile (Mobile Accessible) -->
                        <div class="mb-4 bg-blue-50 rounded-xl p-6 border border-blue-100">
                            <div class="flex items-center mb-4">
                                <?php 
                                    $ownerPhoto = $kost_details['foto_profil'] ?? '';
                                    $ownerName = $kost_details['nama_lengkap'] ?? 'Pemilik';
                                    $ownerInitials = strtoupper(substr($ownerName, 0, 1));
                                    
                                    if (!empty($ownerPhoto)) {
                                        $isUrl = strpos($ownerPhoto, 'http') === 0;
                                        $photoSrc = $isUrl ? $ownerPhoto : "uploads/profiles/" . htmlspecialchars($ownerPhoto);
                                        echo '<img src="' . $photoSrc . '" alt="' . htmlspecialchars($ownerName) . '" class="w-14 h-14 rounded-full object-cover border-2 border-white shadow-md mr-4">';
                                    } else {
                                        echo '<div class="w-14 h-14 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white font-bold text-xl border-2 border-white shadow-md mr-4">' . $ownerInitials . '</div>';
                                    }
                                ?>
                                <div>
                                    <h4 class="font-bold text-gray-900 text-lg leading-tight"><?php echo htmlspecialchars($ownerName); ?></h4>
                                    <p class="text-xs text-blue-600 font-semibold uppercase tracking-wide bg-blue-100 px-2 py-0.5 rounded-full inline-block mt-1">Pemilik Kos</p>
                                </div>
                            </div>
                            
                            <p class="text-sm text-blue-800 mb-4 leading-relaxed">
                                Hubungi pemilik kos untuk menanyakan ketersediaan kamar, menegosiasikan harga, atau info fasilitas lebih lanjut.
                            </p>
                            
                            <a href="https://wa.me/?text=Halo%20saya%20tertarik%20dengan%20kos%20<?php echo urlencode($kost_details['nama_kost']); ?>" target="_blank" class="w-full bg-white text-blue-600 hover:text-white hover:bg-blue-600 border border-blue-200 font-bold py-2.5 px-4 rounded-xl transition-all shadow-sm hover:shadow-md flex items-center justify-start pl-6 group">
                                <i class="fab fa-whatsapp text-lg mr-2 group-hover:text-white duration-300"></i>
                                Hubungi via WhatsApp
                            </a>
                        </div>

                        <div class="bg-gradient-to-r from-purple-50 to-indigo-50 p-6 rounded-xl">
                            <p class="text-gray-600 text-sm mb-2">Harga mulai dari</p>
                            <p class="text-3xl font-bold text-purple-600">Rp <?php echo number_format($kost_details['harga'], 0, ',', '.'); ?></p>
                            <p class="text-gray-500 text-sm">per bulan</p>
                        </div>
                    </div>
                </div>

                <!-- Bagian Foto Kategori -->
                <div class="mt-6">
                    <h3 class="text-2xl font-bold text-gray-800 mb-6">Galeri Foto</h3>
                    
                    <?php 
                    $has_photos = false;
                    foreach ($kost_photos as $category => $photos) {
                        if (!empty($photos)) {
                            $has_photos = true;
                            break;
                        }
                    }
                    ?>

                    <?php if ($has_photos): ?>
                        <div class="space-y-8">
                            <?php foreach ($kost_photos as $category => $photos): ?>
                                <?php if (!empty($photos)): ?>
                                    <div class="bg-gray-50 rounded-xl p-6">
                                        <h4 class="text-xl font-semibold text-purple-700 mb-4 flex items-center">
                                            <?php 
                                            // Icon mapping
                                            $icon = 'fa-image';
                                            if ($category == 'Bangunan') $icon = 'fa-building';
                                            elseif ($category == 'Kamar') $icon = 'fa-bed';
                                            elseif ($category == 'Kamar Mandi') $icon = 'fa-bath';
                                            elseif ($category == 'Fasilitas Bersama') $icon = 'fa-users';
                                            ?>
                                            <i class="fas <?php echo $icon; ?> mr-2"></i>
                                            Foto <?php echo htmlspecialchars($category); ?>
                                        </h4>
                                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                            <?php foreach ($photos as $photo): 
                                                $is_url = strpos($photo, 'http') === 0;
                                                $img_src = $is_url ? $photo : 'uploads/kost/' . $photo;
                                            ?>
                                                <div class="aspect-video rounded-lg overflow-hidden shadow-sm group cursor-pointer" onclick="openModal('<?php echo htmlspecialchars($img_src); ?>')">
                                                    <img src="<?php echo htmlspecialchars($img_src); ?>" 
                                                         alt="<?php echo htmlspecialchars($category); ?>" 
                                                         class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-300">
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8 text-gray-500 bg-gray-50 rounded-xl">
                            <i class="fas fa-images text-4xl mb-3 opacity-50"></i>
                            <p>Belum ada foto tambahan.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Modal Image Viewer -->
                <div id="imageModal" class="fixed inset-0 z-[60] hidden bg-black bg-opacity-90 flex items-center justify-center p-4" onclick="closeModal()">
                    <span class="absolute top-4 right-4 text-white text-4xl cursor-pointer hover:text-gray-300">&times;</span>
                    <img id="modalImage" class="max-w-full max-h-[90vh] rounded-lg shadow-2xl" src="" alt="Full Size">
                </div>

                <script>
                    function openModal(src) {
                        const modal = document.getElementById('imageModal');
                        const img = document.getElementById('modalImage');
                        img.src = src;
                        modal.classList.remove('hidden');
                        document.body.style.overflow = 'hidden'; // Prevent scrolling
                    }

                    function closeModal() {
                        const modal = document.getElementById('imageModal');
                        modal.classList.add('hidden');
                        document.body.style.overflow = ''; // Restore scrolling
                    }
                    
                    // Close on Escape key
                    document.addEventListener('keydown', function(event) {
                        if (event.key === "Escape") {
                            closeModal();
                        }
                    });
                </script>


                <!-- Lokasi Section -->
                <div class="mt-6">
                    <?php 
                        // Use kost name as primary location identifier
                        $location_query = htmlspecialchars($kost_details['nama_kost']);
                        $maps_url = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($location_query);
                    ?>
                    
                    <!-- Enhanced Location Card with Red Icon Header -->
                    <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-200">
                        <!-- Header with Red Icon -->
                        <div class="bg-gradient-to-r from-red-50 to-pink-50 px-6 py-4 border-b border-red-100">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-red-600 rounded-xl flex items-center justify-center shadow-lg">
                                        <i class="fas fa-map-marked-alt text-white text-xl"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-2xl font-bold text-gray-800">Lokasi Kos</h3>
                                        <p class="text-sm text-gray-600">Temukan lokasi kos dengan mudah</p>
                                    </div>
                                </div>
                                <a href="<?php echo $maps_url; ?>" target="_blank" 
                                   class="hidden md:inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2.5 rounded-lg hover:bg-blue-700 transition-all shadow-md hover:shadow-lg transform hover:-translate-y-0.5 font-medium text-sm">
                                    <i class="fas fa-external-link-alt"></i>
                                    Buka di Google Maps
                                </a>
                            </div>
                        </div>
                        
                        <!-- Map Container -->
                        <div class="relative h-96 md:h-[450px] bg-gray-100">
                            <iframe 
                                width="100%" 
                                height="100%" 
                                frameborder="0" 
                                scrolling="no" 
                                marginheight="0" 
                                marginwidth="0" 
                                loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade"
                                src="https://maps.google.com/maps?q=<?php echo urlencode($location_query); ?>&t=&z=17&ie=UTF8&iwloc=&output=embed"
                                allowfullscreen
                                class="w-full h-full">
                            </iframe>
                            
                            <!-- Loading Placeholder -->
                            <div class="absolute inset-0 bg-gradient-to-br from-gray-100 to-gray-200 animate-pulse pointer-events-none" id="mapLoader">
                                <div class="flex flex-col items-center justify-center h-full">
                                    <i class="fas fa-spinner fa-spin text-4xl text-gray-400 mb-3"></i>
                                    <p class="text-gray-500 text-sm">Memuat peta...</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Location Info Card -->
                        <div class="bg-gradient-to-br from-white to-gray-50 p-6 border-t border-gray-200">
                            <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                                <div class="flex-1">
                                    <!-- Kost Name with Pin Icon -->
                                    <div class="flex items-start gap-3 mb-3">
                                        <div class="w-10 h-10 bg-red-500 rounded-lg flex items-center justify-center flex-shrink-0 shadow-md">
                                            <i class="fas fa-map-pin text-white text-lg"></i>
                                        </div>
                                        <div class="flex-1">
                                            <h4 class="font-bold text-gray-900 text-lg leading-tight mb-1">
                                                <?php echo htmlspecialchars($kost_details['nama_kost']); ?>
                                            </h4>
                                            <p class="text-gray-600 text-sm md:text-base leading-relaxed">
                                                <?php echo htmlspecialchars($kost_details['alamat']); ?>
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <!-- Additional Info -->
                                    <div class="flex flex-wrap gap-2 mt-4">
                                        <span class="inline-flex items-center gap-1.5 bg-blue-50 text-blue-700 px-3 py-1.5 rounded-lg text-xs font-medium border border-blue-200">
                                            <i class="fas fa-location-crosshairs"></i>
                                            Lihat di Peta
                                        </span>
                                        <a href="<?php echo $maps_url; ?>" target="_blank" 
                                           class="inline-flex items-center gap-1.5 bg-green-50 text-green-700 px-3 py-1.5 rounded-lg text-xs font-medium border border-green-200 hover:bg-green-100 transition-colors">
                                            <i class="fas fa-route"></i>
                                            Petunjuk Arah
                                        </a>
                                    </div>
                                </div>
                                
                                <!-- Mobile "Open in Maps" Button -->
                                <a href="<?php echo $maps_url; ?>" target="_blank" 
                                   class="md:hidden flex items-center justify-center gap-2 bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-3.5 rounded-xl hover:from-blue-700 hover:to-blue-800 transition-all shadow-md hover:shadow-lg font-semibold">
                                    <i class="fas fa-directions text-lg"></i>
                                    Buka di Google Maps
                                </a>
                            </div>
                            
                            <!-- Map Tips -->
                            <div class="mt-5 pt-5 border-t border-gray-200">
                                <div class="flex items-start gap-3 bg-blue-50 p-4 rounded-lg border border-blue-100">
                                    <i class="fas fa-info-circle text-blue-600 mt-0.5"></i>
                                    <div class="flex-1">
                                        <p class="text-sm text-blue-900 font-medium mb-1">Tips Menggunakan Peta:</p>
                                        <ul class="text-xs text-blue-700 space-y-1">
                                            <li>• Gunakan scroll mouse atau pinch untuk zoom in/out</li>
                                            <li>• Klik dan drag untuk menggeser peta</li>
                                            <li>• Klik "Buka di Google Maps" untuk navigasi lengkap</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <script>
                    // Hide loading placeholder when iframe loads
                    window.addEventListener('load', function() {
                        setTimeout(function() {
                            const loader = document.getElementById('mapLoader');
                            if (loader) {
                                loader.style.opacity = '0';
                                loader.style.transition = 'opacity 0.5s ease';
                                setTimeout(() => loader.remove(), 500);
                            }
                        }, 1500);
                    });
                </script>
            </div>

            <!-- Kamar Tersedia -->
            <div class="mb-8">
                <h2 class="text-3xl font-bold text-gray-800 mb-6">Kamar Tersedia (<?php echo count($available_rooms); ?>)</h2>
                
                <?php if (!empty($available_rooms)): ?>
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($available_rooms as $room): ?>
                    <div class="bg-white rounded-2xl shadow-xl overflow-hidden card-hover border border-gray-100 group/card">
                        <?php 
                            $room_foto = $room['foto'] ?? '';
                            $room_foto_master = $room['foto_tipe_master'] ?? '';
                            
                            $final_img = '';
                            $show_room_img = false;
                            
                            // 1. Cek foto kamar spesifik
                            if ($room_foto) {
                                $isUrl = strpos($room_foto, 'http') === 0;
                                if ($isUrl || file_exists(__DIR__ . '/uploads/rooms/' . $room_foto)) {
                                    $final_img = $isUrl ? $room_foto : 'uploads/rooms/' . $room_foto;
                                    $show_room_img = true;
                                }
                            }
                            
                            // 2. Fallback ke foto tipe (master)
                            if (!$show_room_img && $room_foto_master) {
                                $final_img = $room_foto_master;
                                $show_room_img = true;
                            }
                            
                            if ($show_room_img): 
                        ?>
                            <div class="relative h-56 overflow-hidden">
                                <img src="<?php echo htmlspecialchars($final_img); ?>" alt="<?php echo htmlspecialchars($room['nama_kamar']); ?>" class="w-full h-full object-cover transition-transform duration-500 group-hover/card:scale-110">
                                <!-- Gradient overlay -->
                                <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent"></div>
                                <!-- Status badge on image -->
                                <div class="absolute top-4 right-4">
                                    <span class="bg-green-500 text-white px-3 py-1.5 rounded-full text-xs font-bold shadow-lg flex items-center gap-1.5">
                                        <i class="fas fa-circle text-[6px] animate-pulse"></i>
                                        TERSEDIA
                                    </span>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="relative h-56 bg-gradient-to-br from-purple-400 via-blue-500 to-indigo-600 flex items-center justify-center">
                                <i class="fas fa-door-open text-white text-6xl opacity-30"></i>
                                <div class="absolute top-4 right-4">
                                    <span class="bg-green-500 text-white px-3 py-1.5 rounded-full text-xs font-bold shadow-lg flex items-center gap-1.5">
                                        <i class="fas fa-circle text-[6px] animate-pulse"></i>
                                        TERSEDIA
                                    </span>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="p-6">
                            <!-- Room name and type badge -->
                            <div class="mb-4">
                                <div class="flex items-start justify-between gap-2 mb-2">
                                    <h3 class="text-xl font-bold text-gray-900 leading-tight">
                                        <?php echo htmlspecialchars($room['nama_kamar']); ?>
                                    </h3>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center gap-1.5 bg-gradient-to-r from-purple-50 to-indigo-50 text-purple-700 px-3 py-1 rounded-full text-xs font-bold border border-purple-200">
                                        <i class="fas fa-certificate text-[10px]"></i>
                                        <?php echo htmlspecialchars($room['tipe_kamar'] ?? 'STANDARD'); ?>
                                    </span>
                                    <span class="inline-flex items-center gap-1.5 bg-gray-50 text-gray-600 px-3 py-1 rounded-full text-xs font-medium border border-gray-200">
                                        <i class="fas fa-id-card text-[10px]"></i>
                                        ID: <?php echo $room['id_kamar']; ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Room Facilities -->
                            <div class="grid grid-cols-2 gap-x-4 gap-y-1 mb-5">
                                <?php 
                                $fasilitas_list = [];
                                if (!empty($room['fasilitas'])) {
                                    $fasilitas_list = array_map('trim', explode(',', $room['fasilitas']));
                                }
                                
                                if (!empty($fasilitas_list)): 
                                    foreach ($fasilitas_list as $fasilitas): 
                                        $icon = 'fa-check-circle';
                                        $f_lower = strtolower($fasilitas);
                                        if (strpos($f_lower, 'kasur') !== false || strpos($f_lower, 'bed') !== false) $icon = 'fa-bed';
                                        elseif (strpos($f_lower, 'kamar mandi') !== false || strpos($f_lower, 'wc') !== false) $icon = 'fa-bath';
                                        elseif (strpos($f_lower, 'ac') !== false || strpos($f_lower, 'pendingin') !== false) $icon = 'fa-snowflake';
                                        elseif (strpos($f_lower, 'wifi') !== false || strpos($f_lower, 'internet') !== false) $icon = 'fa-wifi';
                                        elseif (strpos($f_lower, 'tv') !== false) $icon = 'fa-tv';
                                        elseif (strpos($f_lower, 'lemari') !== false || strpos($f_lower, 'storage') !== false) $icon = 'fa-door-closed';
                                        elseif (strpos($f_lower, 'meja') !== false) $icon = 'fa-table';
                                        elseif (strpos($f_lower, 'kursi') !== false) $icon = 'fa-chair';
                                        elseif (strpos($f_lower, 'kipas') !== false || strpos($f_lower, 'fan') !== false) $icon = 'fa-fan';
                                        elseif (strpos($f_lower, 'jendela') !== false) $icon = 'fa-columns';
                                        elseif (strpos($f_lower, 'cermin') !== false) $icon = 'fa-magic';
                                        elseif (strpos($f_lower, 'bantal') !== false || strpos($f_lower, 'guling') !== false) $icon = 'fa-cloud';
                                        elseif (strpos($f_lower, 'dispenser') !== false) $icon = 'fa-tint';
                                ?>
                                    <div class="flex items-start text-base font-medium text-gray-800">
                                        <i class="fas <?php echo $icon; ?> text-green-500 mr-3 mt-1 text-sm flex-shrink-0"></i>
                                        <span class="leading-tight"><?php echo htmlspecialchars($fasilitas); ?></span>
                                    </div>
                                <?php 
                                    endforeach;
                                else: 
                                    // Default facilities if none specified
                                    $default_facilities = ['Fasilitas lengkap', 'Lokasi strategis', 'Aman dan nyaman', 'Bebas banjir'];
                                    foreach ($default_facilities as $fasilitas): 
                                ?>
                                    <div class="flex items-start text-base font-medium text-gray-800">
                                        <i class="fas fa-check-circle text-green-500 mr-2 mt-0.5 text-xs flex-shrink-0"></i>
                                        <span class="leading-tight"><?php echo htmlspecialchars($fasilitas); ?></span>
                                    </div>
                                <?php 
                                    endforeach;
                                endif; ?>
                            </div>

                            <!-- Room Rules -->
                            <?php if (!empty($room['peraturan_kamar'])): ?>
                            <div class="mt-4 pt-4 border-t border-gray-100">
                                <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3 flex items-center gap-2">
                                    <i class="fas fa-file-signature text-purple-500"></i> Peraturan Kamar
                                </h4>
                                <ul class="space-y-1">
                                    <?php 
                                    $rules = array_filter(array_map('trim', explode("\n", $room['peraturan_kamar'])));
                                    if (empty($rules)) {
                                        // Fallback if no newlines but content exists (though array_filter should catch empty strings)
                                        $rules = [$room['peraturan_kamar']]; 
                                    }
                                    foreach ($rules as $rule):
                                    ?>
                                    <li class="text-base font-medium text-gray-700 flex items-start">
                                        <span class="leading-relaxed"><?php echo htmlspecialchars($rule); ?></span>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Price section -->
                            <div class="mb-5 pb-5 border-t border-gray-100 pt-4">
                                <p class="text-xs text-gray-500 mb-1 uppercase tracking-wide font-semibold">Harga Sewa Per Bulan</p>
                                <p class="text-3xl font-extrabold bg-gradient-to-r from-purple-600 to-blue-600 bg-clip-text text-transparent">
                                    Rp <?php echo number_format($room['harga'], 0, ',', '.'); ?>
                                </p>
                            </div>

                            <!-- Booking button -->
                            <?php if ($user_logged_in): ?>
                                <a href="user/booking.php?kostId=<?php echo $id_kost; ?>&roomId=<?php echo $room['id_kamar']; ?>" 
                                   class="block w-full bg-gradient-to-r from-purple-600 to-blue-600 text-white py-3.5 rounded-xl font-bold hover:from-purple-700 hover:to-blue-700 transition-all duration-300 text-center shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center justify-center gap-2">
                                    <i class="fas fa-calendar-check"></i>
                                    <span>BOOK SEKARANG</span>
                                </a>
                            <?php else: ?>
                                <button onclick="showLoginAlert()" 
                                        class="w-full bg-gradient-to-r from-purple-600 to-blue-600 text-white py-3.5 rounded-xl font-bold hover:from-purple-700 hover:to-blue-700 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center justify-center gap-2">
                                    <i class="fas fa-calendar-check"></i>
                                    <span>BOOK SEKARANG</span>
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
