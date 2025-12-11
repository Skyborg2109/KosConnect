<?php
if (!isset($_SESSION['user_logged_in']) || $_SESSION['role'] !== 'pemilik') {
    echo '<p class="text-red-500">Unauthorized access</p>';
    exit();
}

if (!isset($_SESSION['user_id']) || !is_numeric($_SESSION['user_id'])) {
    echo '<p class="text-red-500">Invalid user session</p>';
    exit();
}

include '../config/db.php';
$id_pemilik = (int)$_SESSION['user_id'];

// --- OPTIMASI: Mengambil semua statistik dalam satu query ---
$sql_stats = "
    SELECT
        (SELECT COUNT(id_kost) FROM kost WHERE id_pemilik = ?) AS total_kost,
        (SELECT COUNT(k.id_kamar) FROM kamar k JOIN kost t ON k.id_kost = t.id_kost WHERE t.id_pemilik = ?) AS total_kamar,
        (SELECT COUNT(k.id_kamar) FROM kamar k JOIN kost t ON k.id_kost = t.id_kost WHERE t.id_pemilik = ? AND k.status = 'tersedia') AS kamar_tersedia,        
        (SELECT COUNT(k.id_kamar) FROM kamar k JOIN kost t ON k.id_kost = t.id_kost WHERE t.id_pemilik = ? AND k.status IN ('terisi', 'dipesan')) AS kamar_terisi,
        (SELECT COUNT(b.id_booking) FROM booking b JOIN kamar k ON b.id_kamar = k.id_kamar JOIN kost t ON k.id_kost = t.id_kost WHERE t.id_pemilik = ? AND b.status = 'pending') AS booking_pending,
        (SELECT SUM(p.jumlah) FROM pembayaran p JOIN booking b ON p.id_booking = b.id_booking JOIN kamar k ON b.id_kamar = k.id_kamar JOIN kost t ON k.id_kost = t.id_kost WHERE t.id_pemilik = ? AND p.status_pembayaran = 'berhasil' AND MONTH(p.tanggal_pembayaran) = MONTH(CURDATE()) AND YEAR(p.tanggal_pembayaran) = YEAR(CURDATE())) AS pendapatan_bulan_ini
";
$stmt_stats = $conn->prepare($sql_stats);
$stmt_stats->bind_param("iiiiii", $id_pemilik, $id_pemilik, $id_pemilik, $id_pemilik, $id_pemilik, $id_pemilik);
$stmt_stats->execute();
$stats = $stmt_stats->get_result()->fetch_assoc();
$stmt_stats->close();

$total_kost = (int)($stats['total_kost'] ?? 0);
$total_semua_kamar = (int)($stats['total_kamar'] ?? 0);
$total_kamar_tersedia = (int)($stats['kamar_tersedia'] ?? 0);
$total_kamar_terisi = (int)($stats['kamar_terisi'] ?? 0);
$total_booking_pending = (int)($stats['booking_pending'] ?? 0);
$pendapatan_bulan_ini = (float)($stats['pendapatan_bulan_ini'] ?? 0);

$tingkat_hunian = ($total_semua_kamar > 0) ? round(($total_kamar_terisi / $total_semua_kamar) * 100) : 0;

// Daftar 5 Booking Terbaru
$sql_latest_bookings = "SELECT b.tanggal_booking, b.status, u.nama_lengkap AS nama_penyewa, k.nama_kamar, t.nama_kost FROM booking b JOIN user u ON b.id_penyewa = u.id_user JOIN kamar k ON b.id_kamar = k.id_kamar JOIN kost t ON k.id_kost = t.id_kost WHERE t.id_pemilik = ? ORDER BY b.tanggal_booking DESC LIMIT 5";
$stmt_bookings = $conn->prepare($sql_latest_bookings);
$stmt_bookings->bind_param("i", $id_pemilik);
$stmt_bookings->execute();
$res_latest_bookings = $stmt_bookings->get_result();

// Data untuk Grafik Pendapatan (6 bulan terakhir)
$chart_labels = [];
$chart_data = [];
$income_data = [];

// Ambil data dari DB
$sql_chart = "
    SELECT DATE_FORMAT(p.tanggal_pembayaran, '%Y-%m') AS bulan, SUM(p.jumlah) AS total
    FROM pembayaran p
    JOIN booking b ON p.id_booking = b.id_booking
    JOIN kamar k ON b.id_kamar = k.id_kamar
    JOIN kost t ON k.id_kost = t.id_kost
    WHERE t.id_pemilik = ? AND p.status_pembayaran = 'berhasil' AND p.tanggal_pembayaran >= DATE_FORMAT(CURDATE() - INTERVAL 5 MONTH, '%Y-%m-01')
    GROUP BY DATE_FORMAT(p.tanggal_pembayaran, '%Y-%m')
    ORDER BY bulan ASC
";
$stmt_chart = $conn->prepare($sql_chart);
$stmt_chart->bind_param("i", $id_pemilik);
$stmt_chart->execute();
$res_chart = $stmt_chart->get_result();
while ($row = $res_chart->fetch_assoc()) {
    $income_data[$row['bulan']] = $row['total'];
}

for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i month"));
    $chart_labels[] = date('M Y', strtotime($month));
    $chart_data[] = $income_data[$month] ?? 0;
}
$chart_data_json = json_encode(['labels' => $chart_labels, 'data' => $chart_data]);
?>

<style>
    .stat-card {
        background: white;
        border-radius: 1rem;
        padding: 1.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        border-left: 4px solid transparent;
    }
    
    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }
    
    .stat-card.blue { border-left-color: #3b82f6; }
    .stat-card.green { border-left-color: #10b981; }
    .stat-card.yellow { border-left-color: #f59e0b; }
    .stat-card.purple { border-left-color: #8b5cf6; }
    .stat-card.indigo { border-left-color: #6366f1; }
    .stat-card.emerald { border-left-color: #059669; }
    
    .stat-icon {
        width: 3.5rem;
        height: 3.5rem;
        border-radius: 0.75rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
    
    .stat-icon.blue { background: linear-gradient(135deg, #3b82f6, #60a5fa); }
    .stat-icon.green { background: linear-gradient(135deg, #10b981, #34d399); }
    .stat-icon.yellow { background: linear-gradient(135deg, #f59e0b, #fbbf24); }
    .stat-icon.purple { background: linear-gradient(135deg, #8b5cf6, #a78bfa); }
    .stat-icon.indigo { background: linear-gradient(135deg, #6366f1, #818cf8); }
    .stat-icon.emerald { background: linear-gradient(135deg, #059669, #10b981); }
    
    .chart-container {
        background: white;
        border-radius: 1rem;
        padding: 1.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        margin-top: 1.5rem;
    }
    
    .chart-title {
        font-size: 1.125rem;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .chart-title i {
        color: #64748b;
    }
</style>

<!-- Welcome Banner -->
<div class="bg-gradient-to-r from-slate-700 via-slate-600 to-slate-700 rounded-xl p-6 mb-6 text-white shadow-lg">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold mb-2">Selamat Datang, <?php echo htmlspecialchars($_SESSION['fullname'] ?? 'Pemilik'); ?>! 👋</h2>
            <p class="text-slate-200">Berikut adalah ringkasan bisnis kos Anda hari ini</p>
        </div>
        <div class="hidden md:block">
            <div class="text-right">
                <p class="text-sm text-slate-300">Tanggal</p>
                <p class="text-lg font-semibold"><?php echo date('d F Y'); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
    <!-- Total Kos -->
    <div class="stat-card blue">
        <div class="flex items-center space-x-4">
            <div class="stat-icon blue">
                <i class="fas fa-building text-white"></i>
            </div>
            <div class="flex-1">
                <p class="text-sm text-slate-500 mb-1">Total Kos</p>
                <p class="text-3xl font-bold text-slate-800"><?php echo $total_kost; ?></p>
            </div>
        </div>
    </div>
    
    <!-- Kamar Tersedia -->
    <div class="stat-card green">
        <div class="flex items-center space-x-4">
            <div class="stat-icon green">
                <i class="fas fa-door-open text-white"></i>
            </div>
            <div class="flex-1">
                <p class="text-sm text-slate-500 mb-1">Kamar Tersedia</p>
                <p class="text-3xl font-bold text-slate-800"><?php echo $total_kamar_tersedia; ?></p>
                <p class="text-xs text-slate-400 mt-1">dari <?php echo $total_semua_kamar; ?> total kamar</p>
            </div>
        </div>
    </div>
    
    <!-- Pesanan Pending -->
    <div class="stat-card yellow">
        <div class="flex items-center space-x-4">
            <div class="stat-icon yellow">
                <i class="fas fa-clock text-white"></i>
            </div>
            <div class="flex-1">
                <p class="text-sm text-slate-500 mb-1">Pesanan Pending</p>
                <p class="text-3xl font-bold text-slate-800"><?php echo $total_booking_pending; ?></p>
                <?php if ($total_booking_pending > 0): ?>
                <p class="text-xs text-amber-600 mt-1">Perlu ditindaklanjuti</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Tingkat Hunian -->
    <div class="stat-card purple">
        <div class="flex items-center space-x-4">
            <div class="stat-icon purple">
                <i class="fas fa-chart-pie text-white"></i>
            </div>
            <div class="flex-1">
                <p class="text-sm text-slate-500 mb-1">Tingkat Hunian</p>
                <p class="text-3xl font-bold text-slate-800"><?php echo $tingkat_hunian; ?>%</p>
                <div class="w-full bg-slate-200 rounded-full h-2 mt-2">
                    <div class="bg-purple-500 h-2 rounded-full transition-all" style="width: <?php echo $tingkat_hunian; ?>%"></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Total Kamar -->
    <div class="stat-card indigo">
        <div class="flex items-center space-x-4">
            <div class="stat-icon indigo">
                <i class="fas fa-home text-white"></i>
            </div>
            <div class="flex-1">
                <p class="text-sm text-slate-500 mb-1">Total Kamar</p>
                <p class="text-3xl font-bold text-slate-800"><?php echo $total_semua_kamar; ?></p>
                <p class="text-xs text-slate-400 mt-1"><?php echo $total_kamar_terisi; ?> terisi</p>
            </div>
        </div>
    </div>
    
    <!-- Pendapatan Bulan Ini -->
    <div class="stat-card emerald">
        <div class="flex items-center space-x-4">
            <div class="stat-icon emerald">
                <i class="fas fa-money-bill-wave text-white"></i>
            </div>
            <div class="flex-1">
                <p class="text-sm text-slate-500 mb-1">Pendapatan Bulan Ini</p>
                <p class="text-xl font-bold text-slate-800">Rp <?php echo number_format($pendapatan_bulan_ini, 0, ',', '.'); ?></p>
                <p class="text-xs text-slate-400 mt-1"><?php echo date('F Y'); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Charts Section -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Grafik Pendapatan -->
    <div class="chart-container">
        <h3 class="chart-title">
            <i class="fas fa-chart-line"></i>
            Grafik Pendapatan (6 Bulan Terakhir)
        </h3>
        <div style="height: 300px; position: relative;">
            <canvas id="incomeChart"></canvas>
        </div>
    </div>
    
    <!-- Grafik Tingkat Hunian -->
    <div class="chart-container">
        <h3 class="chart-title">
            <i class="fas fa-chart-pie"></i>
            Distribusi Kamar
        </h3>
        <div style="height: 300px; position: relative;">
            <canvas id="occupancyChart"></canvas>
        </div>
    </div>
</div>

<!-- Recent Bookings Table -->
<div class="chart-container">
    <h3 class="chart-title">
        <i class="fas fa-clipboard-list"></i>
        5 Pesanan Terbaru
    </h3>
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead>
                <tr class="border-b border-slate-200">
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Penyewa</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Kos & Kamar</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Tanggal</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php
                if ($res_latest_bookings->num_rows > 0):
                    $res_latest_bookings->data_seek(0);
                    while($row = $res_latest_bookings->fetch_assoc()): ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-4 py-3 text-sm text-slate-900"><?php echo htmlspecialchars($row['nama_penyewa']); ?></td>
                            <td class="px-4 py-3 text-sm text-slate-600"><?php echo htmlspecialchars($row['nama_kost']); ?> - <?php echo htmlspecialchars($row['nama_kamar']); ?></td>
                            <td class="px-4 py-3 text-sm text-slate-600"><?php echo htmlspecialchars(date('d M Y', strtotime($row['tanggal_booking']))); ?></td>
                            <td class="px-4 py-3 text-sm">
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
                                    <?php
                                        if ($row['status'] == 'pending') echo 'bg-yellow-100 text-yellow-800';
                                        elseif ($row['status'] == 'dibayar') echo 'bg-blue-100 text-blue-800';
                                        else echo 'bg-green-100 text-green-800';
                                    ?>">
                                    <?php echo htmlspecialchars(ucfirst($row['status'])); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile;
                else: ?>
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-sm text-slate-500">
                            <i class="fas fa-inbox text-4xl text-slate-300 mb-2"></i>
                            <p>Tidak ada data pesanan terbaru.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    // Set data untuk grafik
    window.chartData = <?php echo $chart_data_json; ?>;
    window.occupancyData = {
        labels: ['Tersedia', 'Terisi'],
        data: [<?php echo $total_kamar_tersedia; ?>, <?php echo $total_kamar_terisi; ?>]
    };

    // Fungsi untuk inisialisasi grafik
    function initDashboardCharts() {
        console.log('🎨 Initializing dashboard charts...');
        console.log('📊 Chart.js available:', typeof Chart !== 'undefined');
        
        // Cek apakah Chart.js sudah loaded
        if (typeof Chart === 'undefined') {
            console.error('❌ Chart.js not loaded yet, retrying in 200ms...');
            setTimeout(initDashboardCharts, 200);
            return;
        }
        
        // Grafik Pendapatan (Line Chart)
        const incomeCtx = document.getElementById('incomeChart');
        console.log('📈 Income chart canvas:', incomeCtx);
        console.log('💰 Chart data:', window.chartData);
        
        if (incomeCtx && window.chartData) {
            try {
                new Chart(incomeCtx, {
                    type: 'line',
                    data: {
                        labels: window.chartData.labels,
                        datasets: [{
                            label: 'Pendapatan (Rp)',
                            data: window.chartData.data,
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#10b981',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 5,
                            pointHoverRadius: 7,
                            pointHoverBackgroundColor: '#059669',
                            pointHoverBorderWidth: 3
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top',
                                labels: {
                                    font: {
                                        size: 12,
                                        family: "system-ui, -apple-system, sans-serif",
                                        weight: '500'
                                    },
                                    padding: 15,
                                    usePointStyle: true,
                                    pointStyle: 'circle'
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                padding: 12,
                                titleFont: {
                                    size: 14,
                                    weight: 'bold'
                                },
                                bodyFont: {
                                    size: 13
                                },
                                borderColor: '#10b981',
                                borderWidth: 1,
                                callbacks: {
                                    label: function(context) {
                                        return 'Pendapatan: Rp ' + context.parsed.y.toLocaleString('id-ID');
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        if (value >= 1000000) {
                                            return 'Rp ' + (value / 1000000).toFixed(1) + 'jt';
                                        } else if (value >= 1000) {
                                            return 'Rp ' + (value / 1000).toFixed(0) + 'rb';
                                        }
                                        return 'Rp ' + value.toLocaleString('id-ID');
                                    },
                                    font: {
                                        size: 11
                                    },
                                    color: '#64748b'
                                },
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)',
                                    drawBorder: false
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    font: {
                                        size: 11
                                    },
                                    color: '#64748b'
                                }
                            }
                        }
                    }
                });
                console.log('✅ Income chart created successfully');
            } catch (error) {
                console.error('❌ Error creating income chart:', error);
            }
        } else {
            console.error('❌ Failed to create income chart - canvas or data missing');
        }

        // Grafik Tingkat Hunian (Doughnut Chart)
        const occupancyCtx = document.getElementById('occupancyChart');
        console.log('🥧 Occupancy chart canvas:', occupancyCtx);
        console.log('📊 Occupancy data:', window.occupancyData);
        
        if (occupancyCtx && window.occupancyData) {
            try {
                new Chart(occupancyCtx, {
                    type: 'doughnut',
                    data: {
                        labels: window.occupancyData.labels,
                        datasets: [{
                            data: window.occupancyData.data,
                            backgroundColor: [
                                '#10b981',  // Green for available
                                '#6366f1'   // Indigo for occupied
                            ],
                            borderColor: '#fff',
                            borderWidth: 3,
                            hoverOffset: 15,
                            hoverBorderWidth: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'bottom',
                                labels: {
                                    font: {
                                        size: 13,
                                        family: "system-ui, -apple-system, sans-serif",
                                        weight: '500'
                                    },
                                    padding: 20,
                                    usePointStyle: true,
                                    pointStyle: 'circle',
                                    generateLabels: function(chart) {
                                        const data = chart.data;
                                        if (data.labels.length && data.datasets.length) {
                                            return data.labels.map((label, i) => {
                                                const value = data.datasets[0].data[i];
                                                const total = data.datasets[0].data.reduce((a, b) => a + b, 0);
                                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                                return {
                                                    text: `${label}: ${value} (${percentage}%)`,
                                                    fillStyle: data.datasets[0].backgroundColor[i],
                                                    hidden: false,
                                                    index: i
                                                };
                                            });
                                        }
                                        return [];
                                    }
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                padding: 12,
                                titleFont: {
                                    size: 14,
                                    weight: 'bold'
                                },
                                bodyFont: {
                                    size: 13
                                },
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = context.parsed || 0;
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                        return `${label}: ${value} kamar (${percentage}%)`;
                                    }
                                }
                            }
                        }
                    }
                });
                console.log('✅ Occupancy chart created successfully');
            } catch (error) {
                console.error('❌ Error creating occupancy chart:', error);
            }
        } else {
            console.error('❌ Failed to create occupancy chart - canvas or data missing');
        }
    }

    // Jalankan inisialisasi grafik dengan delay kecil untuk memastikan canvas sudah di-render
    setTimeout(initDashboardCharts, 150);
</script>
