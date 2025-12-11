<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/loginForm.php");
    exit();
}

include '../config/db.php';

// Fungsi untuk mendapatkan data laporan berdasarkan filter
function getReportData($conn, $period = '6', $type = 'booking') {
    $limit = intval($period);
    $data = [];

    if ($type === 'booking') {
        // Booking per bulan dengan persentase sukses
        $sql = "
            SELECT
                DATE_FORMAT(tanggal_booking, '%Y-%m') AS bulan,
                COUNT(id_booking) AS total_booking,
                SUM(CASE WHEN status IN ('dibayar', 'selesai') THEN 1 ELSE 0 END) AS booking_sukses,
                ROUND((SUM(CASE WHEN status IN ('dibayar', 'selesai') THEN 1 ELSE 0 END) / COUNT(id_booking)) * 100, 1) AS persentase_sukses
            FROM booking
            GROUP BY bulan
            ORDER BY bulan DESC LIMIT ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    } elseif ($type === 'payment') {
        // Pembayaran per bulan
        $sql = "
            SELECT
                DATE_FORMAT(tanggal_pembayaran, '%Y-%m') AS bulan,
                COUNT(id_payment) AS total_pembayaran,
                SUM(jumlah) AS total_jumlah,
                SUM(CASE WHEN status_pembayaran = 'dikonfirmasi' THEN 1 ELSE 0 END) AS pembayaran_sukses,
                ROUND((SUM(CASE WHEN status_pembayaran = 'dikonfirmasi' THEN 1 ELSE 0 END) / COUNT(id_payment)) * 100, 1) AS persentase_sukses
            FROM pembayaran
            GROUP BY bulan
            ORDER BY bulan DESC LIMIT ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    } elseif ($type === 'user') {
        // Registrasi user per bulan
        $sql = "
            SELECT
                DATE_FORMAT(tanggal_daftar, '%Y-%m') AS bulan,
                COUNT(id_user) AS total_user_baru,
                SUM(CASE WHEN role = 'penyewa' THEN 1 ELSE 0 END) AS penyewa_baru,
                SUM(CASE WHEN role = 'pemilik' THEN 1 ELSE 0 END) AS pemilik_baru
            FROM user
            GROUP BY bulan
            ORDER BY bulan DESC LIMIT ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }

    return $data;
}

// Handle AJAX request untuk data laporan
if (isset($_GET['ajax'])) {
    $period = $_GET['period'] ?? '6';
    $type = $_GET['type'] ?? 'booking';
    $data = getReportData($conn, $period, $type);

    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

// Handle export CSV
if (isset($_GET['export'])) {
    $period = $_GET['period'] ?? '6';
    $type = $_GET['type'] ?? 'booking';
    $data = getReportData($conn, $period, $type);

    // Set headers for CSV download
    ob_start();
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="laporan_' . $type . '_' . date('Y-m-d_His') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    
    // BOM for UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    if ($type === 'booking') {
        fputcsv($output, ['Bulan', 'Total Booking', 'Booking Sukses', 'Persentase Sukses'], ',');
        foreach ($data as $row) {
            fputcsv($output, [
                $row['bulan'], 
                $row['total_booking'], 
                $row['booking_sukses'], 
                $row['persentase_sukses'] . '%'
            ], ',');
        }
    } elseif ($type === 'payment') {
        fputcsv($output, ['Bulan', 'Total Pembayaran', 'Total Jumlah', 'Pembayaran Sukses', 'Persentase Sukses'], ',');
        foreach ($data as $row) {
            fputcsv($output, [
                $row['bulan'], 
                $row['total_pembayaran'], 
                'Rp ' . number_format($row['total_jumlah'], 0, ',', '.'), 
                $row['pembayaran_sukses'], 
                $row['persentase_sukses'] . '%'
            ], ',');
        }
    } elseif ($type === 'user') {
        fputcsv($output, ['Bulan', 'Total User Baru', 'Penyewa Baru', 'Pemilik Baru'], ',');
        foreach ($data as $row) {
            fputcsv($output, [
                $row['bulan'], 
                $row['total_user_baru'], 
                $row['penyewa_baru'], 
                $row['pemilik_baru']
            ], ',');
        }
    }

    fclose($output);
    ob_end_flush();
    exit();
}

// Default data untuk halaman awal
$reportData = getReportData($conn, '6', 'booking');
$currentType = 'booking';
$currentPeriod = '6';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Laporan - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        .animate-fade-in {
            animation: fadeIn 0.5s ease-out;
        }
        
        .card-hover {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(99, 102, 241, 0.15);
        }
        
        .stat-icon-bg {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }
        
        .table-row {
            transition: all 0.2s ease;
        }
        
        .table-row:hover {
            background: linear-gradient(90deg, #f8fafc 0%, #f1f5f9 100%);
        }
        
        .gradient-header {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #d946ef 100%);
        }
        
        .filter-card {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border: 1px solid #e2e8f0;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <!-- Header Section -->
    <div class="gradient-header text-white p-8 mb-8 rounded-b-2xl shadow-lg">
        <div class="flex items-center space-x-4 mb-4">
            <div class="bg-white bg-opacity-20 rounded-full p-4 backdrop-blur-sm">
                <i class="fas fa-chart-bar text-2xl"></i>
            </div>
            <div>
                <h1 class="text-4xl font-bold">Laporan & Analitik</h1>
                <p class="text-indigo-100 mt-2">Pantau performa sistem KosConnect secara real-time</p>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-8 pb-12">
        <!-- Filter Section -->
        <div class="filter-card rounded-2xl p-8 mb-8 shadow-md card-hover animate-fade-in">
            <h3 class="text-lg font-bold text-gray-800 mb-6 flex items-center">
                <i class="fas fa-sliders-h mr-3 text-indigo-600"></i>Filter Laporan
            </h3>
            
            <div class="grid md:grid-cols-4 gap-4 items-end">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-3">
                        <i class="fas fa-calendar-alt mr-2 text-indigo-600"></i>Periode Laporan
                    </label>
                    <select id="periodSelect" class="w-full border-2 border-gray-300 rounded-xl px-4 py-3 focus:border-indigo-500 focus:outline-none transition-colors bg-white shadow-sm">
                        <option value="3">3 Bulan Terakhir</option>
                        <option value="6" selected>6 Bulan Terakhir</option>
                        <option value="12">12 Bulan Terakhir</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-3">
                        <i class="fas fa-list mr-2 text-indigo-600"></i>Jenis Data
                    </label>
                    <select id="typeSelect" class="w-full border-2 border-gray-300 rounded-xl px-4 py-3 focus:border-indigo-500 focus:outline-none transition-colors bg-white shadow-sm">
                        <option value="booking">Booking</option>
                        <option value="payment">Pembayaran</option>
                        <option value="user">User Registration</option>
                    </select>
                </div>
                <div>
                    <button id="filterBtn" class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 text-white py-3 px-4 rounded-xl hover:from-indigo-700 hover:to-purple-700 transition-all font-semibold shadow-md hover:shadow-lg flex items-center justify-center">
                        <i class="fas fa-search mr-2"></i>Tampilkan Laporan
                    </button>
                </div>
                <div>
                    <button id="exportBtn" class="w-full bg-gradient-to-r from-green-500 to-emerald-600 text-white py-3 px-4 rounded-xl hover:from-green-600 hover:to-emerald-700 transition-all font-semibold shadow-md hover:shadow-lg flex items-center justify-center">
                        <i class="fas fa-download mr-2"></i>Export CSV
                    </button>
                </div>
            </div>
        </div>

        <!-- Report Container -->
        <div class="bg-white rounded-2xl shadow-lg p-8 card-hover animate-fade-in" id="reportContainer">
            <h2 class="text-2xl font-bold text-gray-800 mb-2" id="reportTitle">Laporan Booking 6 Bulan Terakhir</h2>
            <p class="text-gray-600 mb-6" id="reportSubtitle">Data pemesanan dan statistik kesuksesan transaksi</p>
            
            <div class="border-t border-gray-200 pt-6">
                <div class="overflow-x-auto">
                    <table class="w-full" id="reportTable">
                        <thead id="tableHead">
                            <tr class="border-b-2 border-gray-300 bg-gradient-to-r from-indigo-50 to-purple-50">
                                <th class="text-left py-4 px-6 font-bold text-gray-700">Bulan</th>
                                <th class="text-left py-4 px-6 font-bold text-gray-700">Total Booking</th>
                                <th class="text-left py-4 px-6 font-bold text-gray-700">Booking Sukses</th>
                                <th class="text-left py-4 px-6 font-bold text-gray-700">Persentase Sukses</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody">
                            <?php foreach($reportData as $row): ?>
                            <tr class="table-row border-b border-gray-100">
                                <td class="py-4 px-6 font-medium text-gray-800"><?php echo date('M Y', strtotime($row['bulan'] . '-01')); ?></td>
                                <td class="py-4 px-6">
                                    <div class="flex items-center space-x-2">
                                        <span class="bg-blue-100 text-blue-700 font-bold px-3 py-1 rounded-full text-sm"><?php echo $row['total_booking']; ?></span>
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="flex items-center space-x-2">
                                        <span class="bg-green-100 text-green-700 font-bold px-3 py-1 rounded-full text-sm"><?php echo $row['booking_sukses']; ?></span>
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="flex items-center space-x-2">
                                        <div class="w-full bg-gray-200 rounded-full h-2 max-w-xs">
                                            <div class="bg-gradient-to-r from-green-400 to-green-600 h-2 rounded-full" style="width: <?php echo $row['persentase_sukses']; ?>%"></div>
                                        </div>
                                        <span class="font-bold text-green-600 text-sm w-12"><?php echo $row['persentase_sukses']; ?>%</span>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const filterBtn = document.getElementById('filterBtn');
            const exportBtn = document.getElementById('exportBtn');
            const periodSelect = document.getElementById('periodSelect');
            const typeSelect = document.getElementById('typeSelect');
            const reportTitle = document.getElementById('reportTitle');
            const reportSubtitle = document.getElementById('reportSubtitle');
            const tableHead = document.getElementById('tableHead');
            const tableBody = document.getElementById('tableBody');

            // Fungsi untuk update laporan
            function updateReport() {
                const period = periodSelect.value;
                const type = typeSelect.value;

                // Update judul
                const typeLabels = {
                    'booking': 'Booking',
                    'payment': 'Pembayaran',
                    'user': 'User Registration'
                };
                const periodLabels = {
                    '3': '3 Bulan Terakhir',
                    '6': '6 Bulan Terakhir',
                    '12': '12 Bulan Terakhir'
                };
                const subtitles = {
                    'booking': 'Data pemesanan dan statistik kesuksesan transaksi',
                    'payment': 'Laporan pembayaran dan verifikasi transaksi',
                    'user': 'Statistik pendaftaran pengguna baru'
                };
                
                reportTitle.textContent = `Laporan ${typeLabels[type]} ${periodLabels[period]}`;
                reportSubtitle.textContent = subtitles[type];

                // Show loading state
                const originalBtnHtml = filterBtn.innerHTML;
                filterBtn.disabled = true;
                filterBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Memproses...';

                // Fetch data via AJAX
                fetch(`../admin/admin_view_reports.php?ajax=1&period=${period}&type=${type}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        updateTable(data, type);
                        filterBtn.disabled = false;
                        filterBtn.innerHTML = originalBtnHtml;
                    })
                    .catch(error => {
                        console.error('Error fetching report data:', error);
                        filterBtn.disabled = false;
                        filterBtn.innerHTML = originalBtnHtml;
                        
                        // Show error message with SweetAlert if available
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal Memuat Laporan',
                                text: 'Terjadi kesalahan saat memproses data: ' + error.message,
                                confirmButtonColor: '#6366f1',
                                customClass: {
                                    popup: 'rounded-2xl',
                                    confirmButton: 'rounded-xl font-semibold px-6 py-3'
                                }
                            });
                        } else {
                            alert('Terjadi kesalahan saat memuat data laporan: ' + error.message);
                        }
                    });
            }

            // Fungsi untuk update tabel
            function updateTable(data, type) {
                let headers = [];
                let bodyContent = '';

                if (type === 'booking') {
                    headers = ['Bulan', 'Total Booking', 'Booking Sukses', 'Persentase Sukses'];
                    data.forEach(row => {
                        bodyContent += `
                            <tr class="table-row border-b border-gray-100">
                                <td class="py-4 px-6 font-medium text-gray-800">${new Date(row.bulan + '-01').toLocaleDateString('id-ID', { month: 'short', year: 'numeric' })}</td>
                                <td class="py-4 px-6">
                                    <div class="flex items-center space-x-2">
                                        <span class="bg-blue-100 text-blue-700 font-bold px-3 py-1 rounded-full text-sm">${row.total_booking}</span>
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="flex items-center space-x-2">
                                        <span class="bg-green-100 text-green-700 font-bold px-3 py-1 rounded-full text-sm">${row.booking_sukses}</span>
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="flex items-center space-x-2">
                                        <div class="w-full bg-gray-200 rounded-full h-2 max-w-xs">
                                            <div class="bg-gradient-to-r from-green-400 to-green-600 h-2 rounded-full" style="width: ${row.persentase_sukses}%"></div>
                                        </div>
                                        <span class="font-bold text-green-600 text-sm w-12">${row.persentase_sukses}%</span>
                                    </div>
                                </td>
                            </tr>
                        `;
                    });
                } else if (type === 'payment') {
                    headers = ['Bulan', 'Total Pembayaran', 'Total Jumlah', 'Pembayaran Sukses', 'Persentase Sukses'];
                    data.forEach(row => {
                        bodyContent += `
                            <tr class="table-row border-b border-gray-100">
                                <td class="py-4 px-6 font-medium text-gray-800">${new Date(row.bulan + '-01').toLocaleDateString('id-ID', { month: 'short', year: 'numeric' })}</td>
                                <td class="py-4 px-6">
                                    <span class="bg-blue-100 text-blue-700 font-bold px-3 py-1 rounded-full text-sm">${row.total_pembayaran}</span>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="bg-purple-100 text-purple-700 font-bold px-3 py-1 rounded-full text-sm">Rp ${new Intl.NumberFormat('id-ID').format(row.total_jumlah)}</span>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="bg-indigo-100 text-indigo-700 font-bold px-3 py-1 rounded-full text-sm">${row.pembayaran_sukses}</span>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="flex items-center space-x-2">
                                        <div class="w-full bg-gray-200 rounded-full h-2 max-w-xs">
                                            <div class="bg-gradient-to-r from-green-400 to-green-600 h-2 rounded-full" style="width: ${row.persentase_sukses}%"></div>
                                        </div>
                                        <span class="font-bold text-green-600 text-sm w-12">${row.persentase_sukses}%</span>
                                    </div>
                                </td>
                            </tr>
                        `;
                    });
                } else if (type === 'user') {
                    headers = ['Bulan', 'Total User Baru', 'Penyewa Baru', 'Pemilik Baru'];
                    data.forEach(row => {
                        bodyContent += `
                            <tr class="table-row border-b border-gray-100">
                                <td class="py-4 px-6 font-medium text-gray-800">${new Date(row.bulan + '-01').toLocaleDateString('id-ID', { month: 'short', year: 'numeric' })}</td>
                                <td class="py-4 px-6">
                                    <span class="bg-blue-100 text-blue-700 font-bold px-3 py-1 rounded-full text-sm">${row.total_user_baru}</span>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="bg-green-100 text-green-700 font-bold px-3 py-1 rounded-full text-sm">${row.penyewa_baru}</span>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="bg-orange-100 text-orange-700 font-bold px-3 py-1 rounded-full text-sm">${row.pemilik_baru}</span>
                                </td>
                            </tr>
                        `;
                    });
                }

                // Update header
                tableHead.innerHTML = '<tr class="border-b-2 border-gray-300 bg-gradient-to-r from-indigo-50 to-purple-50">' +
                    headers.map(header => '<th class="text-left py-4 px-6 font-bold text-gray-700">' + header + '</th>').join('') +
                    '</tr>';

                // Update body
                tableBody.innerHTML = bodyContent;
            }

            // Event listeners
            filterBtn.addEventListener('click', updateReport);

            exportBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const period = periodSelect.value;
                const type = typeSelect.value;
                
                // Show loading state
                const originalBtnHtml = exportBtn.innerHTML;
                exportBtn.disabled = true;
                exportBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Mengunduh...';
                
                // Create hidden link for download
                const downloadLink = document.createElement('a');
                downloadLink.href = `../admin/admin_view_reports.php?export=1&period=${period}&type=${type}`;
                downloadLink.target = '_blank';
                downloadLink.style.display = 'none';
                
                // Append and trigger click
                document.body.appendChild(downloadLink);
                downloadLink.click();
                
                // Cleanup
                setTimeout(() => {
                    document.body.removeChild(downloadLink);
                    exportBtn.disabled = false;
                    exportBtn.innerHTML = originalBtnHtml;
                }, 1500);
            });

            // Trigger update on page load if needed
            window.triggerReportUpdate = updateReport;
        });
    </script>
</body>
</html>
