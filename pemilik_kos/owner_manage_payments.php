<?php
// session_start() sudah dipanggil oleh file pemanggil (pemilik_get_module.php -> dashboardpemilik.php)
include '../config/db.php';
$id_pemilik = $_SESSION['user_id'];

// Ambil semua pembayaran untuk kos milik pemilik ini, diurutkan berdasarkan yang terbaru
$sql_pending_payments = "
    SELECT 
        p.id_payment, p.jumlah, p.tanggal_pembayaran, p.bukti_pembayaran, p.status_pembayaran,
        b.id_booking,
        u.nama_lengkap AS nama_penyewa,
        k.nama_kamar,
        t.nama_kost
    FROM pembayaran p
    LEFT JOIN booking b ON p.id_booking = b.id_booking
    JOIN user u ON b.id_penyewa = u.id_user
    JOIN kamar k ON b.id_kamar = k.id_kamar
    JOIN kost t ON k.id_kost = t.id_kost
    WHERE t.id_pemilik = ? AND p.status_pembayaran = 'menunggu'
    ORDER BY p.tanggal_pembayaran ASC
";
$stmt = $conn->prepare($sql_pending_payments);
$stmt->bind_param("i", $id_pemilik);
$stmt->execute();
$res_pending_payments = $stmt->get_result();
?>

<div class="p-0">
    <h2 class="text-3xl font-semibold text-gray-800 mb-6">Verifikasi Pembayaran</h2>
    <div class="bg-white rounded-xl shadow-sm p-6 overflow-hidden">
        <h3 class="text-xl font-bold text-gray-800 mb-4">Daftar Pembayaran Masuk</h3>
        
        <div class="space-y-4">
            <?php if ($res_pending_payments->num_rows > 0): ?>
                <?php while($row = $res_pending_payments->fetch_assoc()): ?>
                    <?php
                        // Logika untuk menentukan warna dan teks status
                        $status = $row['status_pembayaran'];
                        $status_info = [
                            'menunggu' => ['text' => 'MENUNGGU', 'class' => 'border-blue-500 bg-blue-50', 'badge' => 'bg-blue-600'],
                            'berhasil' => ['text' => 'BERHASIL', 'class' => 'border-green-500 bg-green-50', 'badge' => 'bg-green-600'],
                            'gagal' => ['text' => 'GAGAL', 'class' => 'border-red-500 bg-red-50', 'badge' => 'bg-red-600'],
                        ];
                        $current_status = $status_info[$status] ?? $status_info['menunggu'];
                    ?>
                    <div class="border-l-4 <?php echo $current_status['class']; ?> p-4 rounded-lg">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <h4 class="font-bold text-lg text-gray-800"><?php echo htmlspecialchars($row['nama_penyewa']); ?></h4>
                                <p class="text-sm text-gray-600">Kos: <?php echo htmlspecialchars($row['nama_kost']); ?> - Kamar <?php echo htmlspecialchars($row['nama_kamar']); ?></p>
                                <p class="text-sm text-gray-500">Tgl Bayar: <?php echo date('d M Y H:i', strtotime($row['tanggal_pembayaran'])); ?></p>
                                <p class="font-semibold text-green-600">Jumlah: Rp <?php echo number_format($row['jumlah'], 0, ',', '.'); ?></p>
                            </div>
                            <span class="px-3 py-1 <?php echo $current_status['badge']; ?> text-white rounded-full text-sm font-medium"><?php echo $current_status['text']; ?></span>
                        </div>
                        
                        <?php if ($status === 'menunggu'): ?>
                            <div class="flex space-x-3 mt-3 pt-3 border-t border-gray-200">
                                <a href="../uploads/payments/<?php echo htmlspecialchars($row['bukti_pembayaran']); ?>" target="_blank" class="bg-blue-500 text-white px-3 py-1 rounded text-sm hover:bg-blue-600">
                                    <i class="fas fa-eye mr-1"></i> Lihat Bukti
                                </a>
                                <button onclick="handlePaymentAction(<?php echo $row['id_payment']; ?>, <?php echo $row['id_booking']; ?>, 'verify')" class="bg-green-500 text-white px-3 py-1 rounded text-sm hover:bg-green-600">
                                    <i class="fas fa-check mr-1"></i> Verifikasi
                                </button>
                                <button onclick="handlePaymentAction(<?php echo $row['id_payment']; ?>, <?php echo $row['id_booking']; ?>, 'reject')" class="bg-red-500 text-white px-3 py-1 rounded text-sm hover:bg-red-600">
                                    <i class="fas fa-times mr-1"></i> Tolak
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="text-center text-gray-500">Tidak ada data pembayaran yang masuk.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Fungsi handlePaymentAction sudah ada di dashboardpemilik.php -->