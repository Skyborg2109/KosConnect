<?php
include '../config/db.php'; 
$id_pemilik = $_SESSION['user_id']; 

// Logika Filter
$filter_status = $_GET['status'] ?? 'pending'; // Default filter adalah 'pending'
$allowed_filters = ['pending', 'menunggu_pembayaran', 'dibayar', 'ditolak', 'batal', 'selesai', 'all'];
if (!in_array($filter_status, $allowed_filters)) {
    $filter_status = 'pending';
}

$where_clause = "WHERE t.id_pemilik = ?";
$params = [$id_pemilik];
$types = "i";

if ($filter_status !== 'all') {
    $where_clause .= " AND b.status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

// Ambil semua booking berdasarkan filter
$sql_bookings = "
    SELECT b.id_booking, b.tanggal_booking, b.status, u.nama_lengkap AS nama_penyewa, k.nama_kamar, t.nama_kost
    FROM booking b
    JOIN user u ON b.id_penyewa = u.id_user
    JOIN kamar k ON b.id_kamar = k.id_kamar
    JOIN kost t ON k.id_kost = t.id_kost
    $where_clause
    ORDER BY b.tanggal_booking DESC
";
$stmt = $conn->prepare($sql_bookings);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res_bookings = $stmt->get_result();
?>

<div class="p-0">
    <h2 class="text-3xl font-semibold text-gray-800 mb-6">Kelola Pesanan Masuk</h2>
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex flex-wrap items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-gray-800">Daftar Pesanan (<?php echo $res_bookings->num_rows; ?>)</h3>
            <div class="flex flex-wrap gap-2 mt-2 md:mt-0">
                <?php
                    $filter_buttons = [
                        'pending' => 'Baru Masuk',
                        'menunggu_pembayaran' => 'Dikonfirmasi',
                        'dibayar' => 'Dibayar',
                        'ditolak' => 'Ditolak',
                        'batal' => 'Dibatalkan',
                        'all' => 'Semua'
                    ];
                    foreach ($filter_buttons as $key => $text) {
                        $is_active = ($key === $filter_status);
                        $active_class = $is_active ? 'bg-slate-700 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300';
                        echo "<button onclick=\"loadContent('owner_manage_booking&status=$key', event)\" class=\"px-3 py-1 text-sm font-semibold rounded-full transition-colors $active_class\">$text</button>";
                    }
                ?>
            </div>
        </div>
        
        <div class="space-y-4">
            <?php if ($res_bookings->num_rows > 0): ?>
                <?php while($row = $res_bookings->fetch_assoc()): ?>
                    <?php
                        $status = $row['status'];
                        $status_info = [
                            'pending' => ['text' => 'PENDING', 'class' => 'border-yellow-500 bg-yellow-50', 'badge' => 'bg-yellow-600'],
                            'menunggu_pembayaran' => ['text' => 'DIKONFIRMASI', 'class' => 'border-blue-500 bg-blue-50', 'badge' => 'bg-blue-600'],
                            'dibayar' => ['text' => 'DIBAYAR', 'class' => 'border-green-500 bg-green-50', 'badge' => 'bg-green-600'],
                            'selesai' => ['text' => 'SELESAI', 'class' => 'border-gray-500 bg-gray-50', 'badge' => 'bg-gray-600'],
                            'ditolak' => ['text' => 'DITOLAK', 'class' => 'border-red-500 bg-red-50', 'badge' => 'bg-red-600'],
                            'batal' => ['text' => 'DIBATALKAN', 'class' => 'border-orange-500 bg-orange-50', 'badge' => 'bg-orange-600'],
                        ];
                        $current_status = $status_info[$status] ?? ['text' => strtoupper($status), 'class' => 'border-gray-500 bg-gray-50', 'badge' => 'bg-gray-600'];
                    ?>
                    <div class="border-l-4 <?php echo $current_status['class']; ?> p-4 rounded-lg">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <h4 class="font-bold text-lg text-gray-800"><?php echo htmlspecialchars($row['nama_penyewa']); ?></h4>
                                <p class="text-sm text-gray-600">Kos: <?php echo htmlspecialchars($row['nama_kost']); ?> - Kamar <?php echo htmlspecialchars($row['nama_kamar']); ?></p>
                                <p class="text-sm text-gray-500">Tgl Pesan: <?php echo date('d M Y', strtotime($row['tanggal_booking'])); ?></p>
                            </div>
                            <span class="px-3 py-1 <?php echo $current_status['badge']; ?> text-white rounded-full text-sm font-medium"><?php echo $current_status['text']; ?></span>
                        </div>
                        <?php if ($status === 'pending'): ?>
                            <div class="flex flex-col sm:flex-row gap-3 mt-4 pt-4 border-t border-gray-200 justify-end">
                                <button onclick="confirmBooking(<?php echo $row['id_booking']; ?>)" class="bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white px-6 py-3 rounded-xl text-sm font-semibold shadow-md hover:shadow-lg transition-all duration-300 flex items-center justify-center space-x-2 transform hover:scale-105">
                                    <i class="fas fa-check-circle text-lg"></i>
                                    <span>Konfirmasi Pesanan</span>
                                </button>
                                <button onclick="rejectBooking(<?php echo $row['id_booking']; ?>)" class="bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white px-6 py-3 rounded-xl text-sm font-semibold shadow-md hover:shadow-lg transition-all duration-300 flex items-center justify-center space-x-2 transform hover:scale-105">
                                    <i class="fas fa-times-circle text-lg"></i>
                                    <span>Tolak Pesanan</span>
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <p class="text-sm text-gray-500 italic flex items-center">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    Pesanan ini telah diproses
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="text-center text-gray-500 py-8">Tidak ada pesanan dengan status "<?php echo htmlspecialchars(ucfirst($filter_status)); ?>".</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Booking action handlers moved into the main dashboard script so they are available
         when this module is loaded via AJAX. -->
</div>