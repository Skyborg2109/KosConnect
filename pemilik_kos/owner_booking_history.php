<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include '../config/db.php';
$id_pemilik = $_SESSION['user_id'];

// Ambil semua riwayat booking untuk kos milik pemilik ini
$sql_history = "
    SELECT 
        b.id_booking, b.tanggal_booking, b.status,
        u.nama_lengkap AS nama_penyewa,
        k.nama_kamar, k.harga,
        t.nama_kost
    FROM booking b
    JOIN user u ON b.id_penyewa = u.id_user
    JOIN kamar k ON b.id_kamar = k.id_kamar
    JOIN kost t ON k.id_kost = t.id_kost
    WHERE t.id_pemilik = ?
    ORDER BY b.tanggal_booking DESC
";
$stmt = $conn->prepare($sql_history);
$stmt->bind_param("i", $id_pemilik);
$stmt->execute();
$res_history = $stmt->get_result();
?>

<div class="p-0">
    <h2 class="text-3xl font-semibold text-gray-800 mb-6">Riwayat Semua Pesanan</h2>
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-xl font-bold text-gray-800 mb-4">Total Pesanan: <?php echo $res_history->num_rows; ?></h3>
        
        <div class="space-y-4">
            <?php if ($res_history->num_rows > 0): ?>
                <?php while($row = $res_history->fetch_assoc()): ?>
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
                        $current_status = $status_info[$status] ?? ['text' => strtoupper($status), 'class' => 'border-gray-400 bg-gray-50', 'badge' => 'bg-gray-500'];
                    ?>
                    <div class="border-l-4 <?php echo $current_status['class']; ?> p-4 rounded-lg">
                        <div class="flex justify-between items-start">
                            <div>
                                <h4 class="font-bold text-lg text-gray-800"><?php echo htmlspecialchars($row['nama_penyewa']); ?></h4>
                                <p class="text-sm text-gray-600">
                                    <?php echo htmlspecialchars($row['nama_kost']); ?> - <?php echo htmlspecialchars($row['nama_kamar']); ?>
                                </p>
                                <p class="text-sm text-gray-500">
                                    Tgl Pesan: <?php echo date('d M Y', strtotime($row['tanggal_booking'])); ?>
                                </p>
                                <p class="font-semibold text-purple-600">
                                    Harga: Rp <?php echo number_format($row['harga'], 0, ',', '.'); ?>
                                </p>
                            </div>
                            <span class="px-3 py-1 <?php echo $current_status['badge']; ?> text-white rounded-full text-sm font-medium whitespace-nowrap">
                                <?php echo $current_status['text']; ?>
                            </span>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="text-center text-gray-500 py-8">Belum ada riwayat pesanan sama sekali.</p>
            <?php endif; ?>
        </div>
    </div>
</div>