<?php
session_start();
// --- AUTENTIKASI ---
if (!isset($_SESSION['user_logged_in']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die("Akses ditolak. Anda harus login sebagai admin.");
}

// Hanya butuh koneksi untuk header atau fungsi lain
include '../config/db.php';

$adminName = $_SESSION['fullname'] ?? 'Admin Sistem';
$adminID = $_SESSION['user_id'];

// =======================================================
// QUERY STATISTIK UTAMA ADMIN
// =======================================================
$sql_stats = "SELECT
    (SELECT COUNT(id_user) FROM user) AS total_user,
    (SELECT COUNT(id_user) FROM user WHERE role = 'pemilik') AS total_pemilik,
    (SELECT COUNT(id_kost) FROM kost) AS total_kost,
    (SELECT COUNT(id_booking) FROM booking WHERE status IN ('dibayar', 'selesai')) AS total_booking_aktif,
    (SELECT COUNT(id_complaint) FROM complaint WHERE status IN ('baru', 'diproses')) AS total_complaint_open,
    (SELECT COUNT(id_payment) FROM pembayaran WHERE status_pembayaran = 'menunggu') AS total_payment_pending,
    (SELECT COUNT(id_user) FROM user WHERE role = 'penyewa') AS total_penyewa";
$stats = $conn->query($sql_stats)->fetch_assoc();
$total_penyewa = $stats['total_user'] - $stats['total_pemilik'] - 1;

// Daftar 5 Pembayaran Terbaru
$sql_latest_payments = "
    SELECT
        p.jumlah, p.tanggal_pembayaran, p.status_pembayaran, u.nama_lengkap
    FROM pembayaran p
    JOIN booking b ON p.id_booking = b.id_booking
    JOIN user u ON b.id_penyewa = u.id_user
    ORDER BY p.tanggal_pembayaran DESC LIMIT 5
";
$res_latest_payments = $conn->query($sql_latest_payments);

// --- QUERY UTK CHART ---

// 1. Grafik Pendapatan 6 Bulan Terakhir
// 1. Grafik Pendapatan 6 Bulan Terakhir
// Inisialisasi array 6 bulan terakhir dengan nilai 0
$revenue_map = [];
$chart_revenue_labels = [];

// Loop dari 5 bulan lalu sampai bulan ini (ascending)
for ($i = 5; $i >= 0; $i--) {
    $date = new DateTime("-$i months");
    $key = $date->format('Y-m');
    $monthName = $date->format('F');
    $monthsID = [
        'January' => 'Januari', 'February' => 'Februari', 'March' => 'Maret', 'April' => 'April',
        'May' => 'Mei', 'June' => 'Juni', 'July' => 'Juli', 'August' => 'Agustus', 
        'September' => 'September', 'October' => 'Oktober', 'November' => 'November', 'December' => 'Desember'
    ];
    $label = ($monthsID[$monthName] ?? $monthName) . ' ' . $date->format('Y');
    
    $revenue_map[$key] = 0;
    $chart_revenue_labels[] = $label; 
}

// Query data (ambil data sejak 6 bulan lalu)
$six_months_ago = date('Y-m-01', strtotime("-5 months")); 

// UPDATE: Include 'menunggu' and 'berhasil' because schema uses 'berhasil', not 'disetujui'
$sql_revenue = "SELECT DATE_FORMAT(tanggal_pembayaran, '%Y-%m') as periode, SUM(jumlah) as total 
                FROM pembayaran 
                WHERE status_pembayaran IN ('berhasil', 'disetujui', 'sukses', 'paid', 'menunggu') 
                AND tanggal_pembayaran >= '$six_months_ago'
                GROUP BY periode";

$res_revenue = $conn->query($sql_revenue);
$total_revenue_six_months = 0;

if ($res_revenue) {
    while ($row = $res_revenue->fetch_assoc()) {
        if (isset($revenue_map[$row['periode']])) {
            $val = (float)$row['total'];
            $revenue_map[$row['periode']] = $val;
            $total_revenue_six_months += $val;
        }
    }
}

// Extract data values sesuai urutan map
$chart_revenue_data = array_values($revenue_map);

// 2. Grafik Status Booking
$sql_booking_status = "SELECT status, COUNT(*) as count FROM booking GROUP BY status";
$res_booking_status = $conn->query($sql_booking_status);
$chart_booking_labels = [];
$chart_booking_data = [];
if ($res_booking_status) {
    while ($row = $res_booking_status->fetch_assoc()) {
        $chart_booking_labels[] = ucfirst($row['status']);
        $chart_booking_data[] = $row['count'];
    }
}
?>

<!-- Welcome Banner -->
<div class="relative bg-gradient-to-r from-indigo-600 to-purple-600 rounded-3xl p-6 sm:p-10 mb-8 shadow-2xl overflow-hidden" style="animation: fadeIn 0.5s ease-out;">
    <div class="absolute top-0 right-0 -mt-10 -mr-10 w-40 h-40 bg-white opacity-10 rounded-full blur-2xl"></div>
    <div class="absolute bottom-0 left-0 -mb-10 -ml-10 w-40 h-40 bg-white opacity-10 rounded-full blur-2xl"></div>
    
    <div class="relative z-10 flex flex-col sm:flex-row items-center justify-between gap-6">
        <div>
            <h2 class="text-3xl font-bold text-white mb-2">Dashboard Overview</h2>
            <p class="text-indigo-100 text-lg">Pantau performa bisnis kos Anda hari ini.</p>
        </div>
        <div class="flex gap-3">
             <button onclick="loadContent('admin_dashboard_summary')" class="bg-white/20 hover:bg-white/30 text-white px-5 py-2.5 rounded-xl backdrop-blur-sm transition-all text-sm font-semibold flex items-center">
                <i class="fas fa-sync-alt mr-2"></i> Refresh Data
            </button>
            <button onclick="loadContent('admin_view_reports')" class="bg-white text-indigo-600 px-5 py-2.5 rounded-xl shadow-lg hover:bg-indigo-50 transition-all text-sm font-bold flex items-center">
                <i class="fas fa-chart-pie mr-2"></i> Laporan Lengkap
            </button>
        </div>
    </div>
</div>

<!-- Statistics Cards Grid -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Card 1 -->
    <div class="bg-white rounded-2xl p-6 shadow-sm hover:shadow-xl transition-all duration-300 border border-slate-100 group">
        <div class="flex justify-between items-start mb-4">
            <div>
                <p class="text-slate-500 text-sm font-medium mb-1">Total Penyewa</p>
                <h3 class="text-3xl font-bold text-slate-800"><?php echo $stats['total_penyewa']; ?></h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                <i class="fas fa-user-friends"></i>
            </div>
        </div>
        <div class="flex items-center text-sm">
            <span class="text-green-500 font-semibold bg-green-50 px-2 py-0.5 rounded-lg flex items-center">
                <i class="fas fa-arrow-up text-xs mr-1"></i> Aktif
            </span>
            <span class="text-slate-400 ml-2">Akun terdaftar</span>
        </div>
    </div>

    <!-- Card 2 -->
    <div class="bg-white rounded-2xl p-6 shadow-sm hover:shadow-xl transition-all duration-300 border border-slate-100 group">
         <div class="flex justify-between items-start mb-4">
            <div>
                <p class="text-slate-500 text-sm font-medium mb-1">Pemilik Kos</p>
                <h3 class="text-3xl font-bold text-slate-800"><?php echo $stats['total_pemilik']; ?></h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                <i class="fas fa-user-tie"></i>
            </div>
        </div>
        <div class="flex items-center text-sm">
             <span class="text-slate-400">Total mitra properti</span>
        </div>
    </div>

    <!-- Card 3 -->
    <div class="bg-white rounded-2xl p-6 shadow-sm hover:shadow-xl transition-all duration-300 border border-slate-100 group">
         <div class="flex justify-between items-start mb-4">
            <div>
                 <p class="text-slate-500 text-sm font-medium mb-1">Total Kos</p>
                <h3 class="text-3xl font-bold text-slate-800"><?php echo $stats['total_kost']; ?></h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                <i class="fas fa-building"></i>
            </div>
        </div>
        <div class="flex items-center text-sm">
            <span class="text-slate-400">Unit tersedia</span>
        </div>
    </div>

    <!-- Card 4 -->
    <div class="bg-white rounded-2xl p-6 shadow-sm hover:shadow-xl transition-all duration-300 border border-slate-100 group">
         <div class="flex justify-between items-start mb-4">
            <div>
                 <p class="text-slate-500 text-sm font-medium mb-1">Booking Aktif</p>
                <h3 class="text-3xl font-bold text-slate-800"><?php echo $stats['total_booking_aktif']; ?></h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                <i class="fas fa-calendar-check"></i>
            </div>
        </div>
        <div class="flex items-center text-sm">
            <span class="text-indigo-500 font-semibold bg-indigo-50 px-2 py-0.5 rounded-lg">Berjalan</span>
        </div>
    </div>
</div>

<!-- Charts Section -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
    <!-- Revenue Chart -->
    <div class="bg-white p-6 rounded-2xl shadow-lg border border-slate-100 flex flex-col">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-lg font-bold text-slate-800">
                    <i class="fas fa-chart-line text-indigo-500 mr-2"></i>Tren Pendapatan
                </h3>
                <p class="text-sm text-slate-500 mt-1">Total: <span class="font-bold text-indigo-600">Rp <?php echo number_format($total_revenue_six_months, 0, ',', '.'); ?></span></p>
            </div>
            <span class="text-xs text-slate-400 bg-slate-100 px-2 py-1 rounded-full">6 Bulan Terakhir</span>
        </div>
        <div class="relative h-64 w-full">
            <?php if ($total_revenue_six_months == 0): ?>
                <div class="absolute inset-0 flex flex-col items-center justify-center bg-white bg-opacity-80 z-10">
                   <p class="text-slate-400 font-medium text-sm">Belum ada data pendapatan</p>
                </div>
            <?php endif; ?>
            <canvas id="revenueChart"></canvas>
        </div>
    </div>

    <!-- Booking Status Chart -->
    <div class="bg-white p-6 rounded-2xl shadow-lg border border-slate-100 flex flex-col">
        <div class="flex items-center justify-between mb-6">
             <h3 class="text-lg font-bold text-slate-800">
                <i class="fas fa-chart-pie text-purple-500 mr-2"></i>Distribusi Status Booking
            </h3>
        </div>
        <div class="relative h-64 w-full flex justify-center">
            <canvas id="bookingChart"></canvas>
        </div>
    </div>
</div>

<!-- Recent Transactions -->
<div class="bg-white rounded-2xl shadow-lg border border-slate-100 overflow-hidden mb-8">
    <div class="p-6 border-b border-slate-50 flex items-center justify-between bg-gray-50/50">
        <h3 class="text-lg font-bold text-slate-800 flex items-center">
             <i class="fas fa-history text-green-500 mr-3"></i>Transaksi Terbaru
        </h3>
        <button onclick="loadContent('admin_manage_transactions')" class="text-sm text-indigo-600 hover:text-indigo-800 font-semibold flex items-center transition-colors">
            Lihat Semua <i class="fas fa-arrow-right ml-1"></i>
        </button>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Penyewa</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Jumlah</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Tanggal</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <?php while($row = $res_latest_payments->fetch_assoc()): ?>
                <tr class="hover:bg-slate-50/80 transition-colors">
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                           <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-xs font-bold mr-3">
                                <?php echo strtoupper(substr($row['nama_lengkap'], 0, 1)); ?>
                           </div>
                           <span class="text-sm font-medium text-slate-700"><?php echo htmlspecialchars($row['nama_lengkap']); ?></span>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-sm font-bold text-slate-700">Rp <?php echo number_format($row['jumlah'], 0, ',', '.'); ?></span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-sm text-slate-500"><?php echo date('d/m/Y', strtotime($row['tanggal_pembayaran'])); ?></span>
                    </td>
                    <td class="px-6 py-4">
                        <?php
                        $status = $row['status_pembayaran'];
                        $colorClass = match($status) {
                            'disetujui' => 'bg-green-100 text-green-700',
                            'menunggu' => 'bg-yellow-100 text-yellow-700',
                            'ditolak' => 'bg-red-100 text-red-700',
                            default => 'bg-gray-100 text-gray-700'
                        };
                        ?>
                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold <?php echo $colorClass; ?>">
                            <?php echo ucfirst($status); ?>
                        </span>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    // Initialize Charts
    // Revenue Chart
    const ctxRevenue = document.getElementById('revenueChart').getContext('2d');
    new Chart(ctxRevenue, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($chart_revenue_labels); ?>,
            datasets: [{
                label: 'Pendapatan (Rp)',
                data: <?php echo json_encode($chart_revenue_data); ?>,
                borderColor: '#4f46e5', // indigo-600
                backgroundColor: 'rgba(79, 70, 229, 0.1)',
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#4f46e5',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1e293b',
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(context.parsed.y);
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { borderDash: [2, 4], color: '#e2e8f0' },
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + (value/1000000).toFixed(1) + 'jt';
                        },
                        font: { size: 11 }
                    }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 11 } }
                }
            }
        }
    });

    // Booking Status Chart
    const ctxBooking = document.getElementById('bookingChart').getContext('2d');
    new Chart(ctxBooking, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($chart_booking_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($chart_booking_data); ?>,
                backgroundColor: [
                    '#4f46e5', // indigo
                    '#10b981', // green
                    '#f59e0b', // amber
                    '#ef4444', // red
                    '#64748b'  // slate
                ],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { usePointStyle: true, padding: 20 }
                }
            }
        }
    });
</script>