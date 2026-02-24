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

<div class="space-y-6">
    <!-- Header Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <!-- Pending Payments Card -->
        <div class="bg-gradient-to-br from-yellow-50 to-orange-50 rounded-2xl shadow-lg border border-yellow-200 overflow-hidden transform hover:scale-105 transition-all duration-300">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center space-x-3">
                        <div class="bg-gradient-to-br from-yellow-500 to-orange-500 p-3 rounded-xl shadow-lg">
                            <i class="fas fa-hourglass-half text-2xl text-white"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-yellow-700">Pembayaran Tertunda</p>
                            <p class="text-3xl font-bold text-yellow-900"><?php echo $res_pending_payments->num_rows; ?></p>
                        </div>
                    </div>
                    <div class="bg-yellow-200 rounded-full p-2">
                        <i class="fas fa-exclamation-triangle text-yellow-700"></i>
                    </div>
                </div>
                <div class="flex items-center text-xs text-yellow-700">
                    <i class="fas fa-info-circle mr-1"></i>
                    <span>Menunggu verifikasi admin</span>
                </div>
            </div>
        </div>

        <!-- Active Bookings Card -->
        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-2xl shadow-lg border border-blue-200 overflow-hidden transform hover:scale-105 transition-all duration-300">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center space-x-3">
                        <div class="bg-gradient-to-br from-blue-500 to-indigo-500 p-3 rounded-xl shadow-lg">
                            <i class="fas fa-calendar-check text-2xl text-white"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-blue-700">Booking Aktif</p>
                            <p class="text-3xl font-bold text-blue-900"><?php echo $res_all_bookings->num_rows; ?></p>
                        </div>
                    </div>
                    <div class="bg-blue-200 rounded-full p-2">
                        <i class="fas fa-check-circle text-blue-700"></i>
                    </div>
                </div>
                <div class="flex items-center text-xs text-blue-700">
                    <i class="fas fa-info-circle mr-1"></i>
                    <span>Sedang dalam proses</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Payments Section -->
    <?php if ($res_pending_payments->num_rows > 0): ?>
    <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100">
        <div class="bg-gradient-to-r from-yellow-500 to-orange-500 p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="bg-white bg-opacity-20 p-3 rounded-xl backdrop-blur-sm">
                        <i class="fas fa-money-bill-wave text-2xl text-white"></i>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-white">Pembayaran Menunggu Verifikasi</h3>
                        <p class="text-yellow-100 text-sm">Segera proses untuk konfirmasi booking</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 gap-4">
                <?php 
                $res_pending_payments->data_seek(0);
                while($payment = $res_pending_payments->fetch_assoc()): 
                ?>
                <div class="bg-gradient-to-r from-yellow-50 to-orange-50 rounded-xl p-5 border-l-4 border-yellow-500 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4 flex-1">
                            <div class="bg-yellow-500 rounded-full p-3 shadow-md">
                                <i class="fas fa-user text-white text-xl"></i>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center space-x-2 mb-1">
                                    <span class="text-xs font-semibold text-yellow-700 bg-yellow-200 px-2 py-1 rounded-full">
                                        #<?php echo $payment['id_payment']; ?>
                                    </span>
                                    <span class="text-sm font-bold text-gray-800"><?php echo htmlspecialchars($payment['nama_penyewa']); ?></span>
                                </div>
                                <div class="flex items-center space-x-4 text-sm text-gray-600">
                                    <div class="flex items-center">
                                        <i class="fas fa-coins text-green-600 mr-1"></i>
                                        <span class="font-bold text-green-700">Rp <?php echo number_format($payment['jumlah'], 0, ',', '.'); ?></span>
                                    </div>
                                    <div class="flex items-center">
                                        <i class="fas fa-clock text-gray-500 mr-1"></i>
                                        <span><?php echo date('d M Y, H:i', strtotime($payment['tanggal_pembayaran'])); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold bg-yellow-200 text-yellow-800 animate-pulse">
                                <i class="fas fa-hourglass-half mr-1"></i>
                                Menunggu
                            </span>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Active Bookings Section -->
    <?php if ($res_all_bookings->num_rows > 0): ?>
    <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100">
        <div class="bg-gradient-to-r from-blue-500 to-indigo-600 p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="bg-white bg-opacity-20 p-3 rounded-xl backdrop-blur-sm">
                        <i class="fas fa-list-check text-2xl text-white"></i>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-white">Daftar Booking Aktif</h3>
                        <p class="text-blue-100 text-sm">Semua pesanan yang sedang berjalan</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 gap-4">
                <?php 
                $res_all_bookings->data_seek(0);
                while($booking = $res_all_bookings->fetch_assoc()): 
                    $status_color = $booking['status'] == 'dibayar' ? 'green' : 'yellow';
                    $status_icon = $booking['status'] == 'dibayar' ? 'check-circle' : 'clock';
                ?>
                <div class="bg-gradient-to-r from-gray-50 to-blue-50 rounded-xl p-5 border-l-4 border-<?php echo $status_color; ?>-500 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4 flex-1">
                            <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl p-3 shadow-md">
                                <i class="fas fa-building text-white text-xl"></i>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center space-x-2 mb-2">
                                    <h4 class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($booking['nama_kost']); ?></h4>
                                    <span class="text-xs font-semibold text-blue-700 bg-blue-100 px-2 py-1 rounded-full">
                                        <i class="fas fa-door-open mr-1"></i><?php echo htmlspecialchars($booking['nama_kamar']); ?>
                                    </span>
                                </div>
                                <div class="flex items-center space-x-4 text-sm text-gray-600">
                                    <div class="flex items-center">
                                        <i class="fas fa-user text-indigo-600 mr-1"></i>
                                        <span class="font-medium"><?php echo htmlspecialchars($booking['nama_penyewa']); ?></span>
                                    </div>
                                    <div class="flex items-center">
                                        <i class="fas fa-calendar text-gray-500 mr-1"></i>
                                        <span><?php echo date('d M Y', strtotime($booking['tanggal_booking'])); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold
                                <?php echo $booking['status'] == 'dibayar' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                <i class="fas fa-<?php echo $status_icon; ?> mr-1"></i>
                                <?php echo ucfirst($booking['status']); ?>
                            </span>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Empty States -->
    <?php if ($res_pending_payments->num_rows == 0 && $res_all_bookings->num_rows == 0): ?>
    <div class="bg-white rounded-2xl shadow-xl p-12 text-center">
        <div class="bg-gradient-to-br from-blue-100 to-indigo-100 rounded-full p-6 w-24 h-24 mx-auto mb-6 flex items-center justify-center">
            <i class="fas fa-inbox text-4xl text-blue-600"></i>
        </div>
        <h3 class="text-2xl font-bold text-gray-800 mb-2">Tidak Ada Transaksi</h3>
        <p class="text-gray-600">Belum ada pembayaran atau booking yang perlu diproses saat ini.</p>
    </div>
    <?php endif; ?>
</div>


