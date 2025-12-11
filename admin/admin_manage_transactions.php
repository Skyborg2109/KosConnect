<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/loginForm.php");
    exit();
}

include '../config/db.php'; 

// Query untuk mendapatkan semua data booking yang belum selesai (pending/dibayar)
$sql_all_bookings = "
    SELECT 
        b.id_booking, 
        u.nama_lengkap AS nama_penyewa, 
        k.nama_kamar, 
        t.nama_kost, 
        b.tanggal_booking, 
        b.status 
    FROM booking b
    JOIN user u ON b.id_penyewa = u.id_user
    JOIN kamar k ON b.id_kamar = k.id_kamar
    JOIN kost t ON k.id_kost = t.id_kost
    WHERE b.status IN ('pending', 'dibayar')
    ORDER BY b.tanggal_booking DESC";
$res_all_bookings = $conn->query($sql_all_bookings);

// Query untuk monitoring pembayaran (status 'menunggu')
$sql_pending_payments = "
    SELECT 
        p.id_payment, 
        u.nama_lengkap AS nama_penyewa, 
        p.jumlah, 
        p.tanggal_pembayaran 
    FROM pembayaran p
    JOIN booking b ON p.id_booking = b.id_booking
    JOIN user u ON b.id_penyewa = u.id_user
    WHERE p.status_pembayaran = 'menunggu'
    ORDER BY p.tanggal_pembayaran ASC";
$res_pending_payments = $conn->query($sql_pending_payments);
?>

<div class="space-y-8">
    <!-- Monitoring Pembayaran Tertunda -->
    <div class="bg-white rounded-xl shadow-md card-hover">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="icon-bg p-3 rounded-full">
                        <i class="fas fa-clock text-2xl text-white"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-slate-800">Monitoring Pembayaran Tertunda</h3>
                        <p class="text-sm text-slate-500"><?php echo $res_pending_payments->num_rows; ?> pembayaran menunggu verifikasi</p>
                    </div>
                </div>
                <div class="text-right">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        Perlu Perhatian
                    </span>
                </div>
            </div>
        </div>
        <div class="p-6">
            <?php if ($res_pending_payments->num_rows > 0): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">ID Payment</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Penyewa</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Jumlah</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Tgl. Upload</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php while($payment = $res_pending_payments->fetch_assoc()): ?>
                        <tr class="hover:bg-yellow-50 transition-colors duration-200">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900">#<?php echo $payment['id_payment']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900 font-medium"><?php echo htmlspecialchars($payment['nama_penyewa']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 font-semibold">Rp <?php echo number_format($payment['jumlah'], 0, ',', '.'); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500"><?php echo date('d M Y H:i', strtotime($payment['tanggal_pembayaran'])); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    <i class="fas fa-clock mr-1 text-xs"></i>
                                    Menunggu
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-12">
                <div class="icon-bg p-4 rounded-full mx-auto w-fit mb-4">
                    <i class="fas fa-check-circle text-3xl text-white"></i>
                </div>
                <h4 class="text-lg font-medium text-slate-900 mb-2">Tidak ada pembayaran tertunda</h4>
                <p class="text-slate-500">Semua pembayaran telah diproses dengan baik.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Daftar Semua Booking Aktif/Pending -->
    <div class="bg-white rounded-xl shadow-md card-hover">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="icon-bg p-3 rounded-full">
                        <i class="fas fa-calendar-check text-2xl text-white"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-slate-800">Daftar Booking Aktif & Pending</h3>
                        <p class="text-sm text-slate-500"><?php echo $res_all_bookings->num_rows; ?> booking dalam proses</p>
                    </div>
                </div>
                <div class="text-right">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                        <i class="fas fa-info-circle mr-1"></i>
                        Aktif
                    </span>
                </div>
            </div>
        </div>
        <div class="p-6">
            <?php if ($res_all_bookings->num_rows > 0): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Kos & Kamar</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Penyewa</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Tgl. Booking</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php while($booking = $res_all_bookings->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50 transition-colors duration-200">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-slate-900"><?php echo htmlspecialchars($booking['nama_kost']); ?></div>
                                <div class="text-sm text-slate-500"><?php echo htmlspecialchars($booking['nama_kamar']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900"><?php echo htmlspecialchars($booking['nama_penyewa']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500"><?php echo date('d M Y', strtotime($booking['tanggal_booking'])); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    <?php
                                        if ($booking['status'] == 'dibayar') echo 'bg-green-100 text-green-800';
                                        elseif ($booking['status'] == 'pending') echo 'bg-yellow-100 text-yellow-800';
                                        else echo 'bg-gray-100 text-gray-800';
                                    ?>">
                                    <i class="fas fa-circle mr-1 text-xs"></i>
                                    <?php echo htmlspecialchars(ucfirst($booking['status'])); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-12">
                <div class="icon-bg p-4 rounded-full mx-auto w-fit mb-4">
                    <i class="fas fa-calendar-times text-3xl text-white"></i>
                </div>
                <h4 class="text-lg font-medium text-slate-900 mb-2">Tidak ada booking aktif</h4>
                <p class="text-slate-500">Semua booking telah selesai atau dibatalkan.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>


