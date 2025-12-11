<?php
// session_start() sudah dipanggil di dashboardpemilik.php
if (!isset($_SESSION['user_logged_in']) || $_SESSION['role'] !== 'pemilik') {
    echo '<p class="text-red-500 p-4">Akses tidak sah.</p>';
    return; // Gunakan return agar tidak menghentikan skrip pemanggil
}

// File ini sekarang mengasumsikan variabel $conn (koneksi DB) dan $id_pemilik sudah tersedia
// dari file yang memanggilnya (pemilik_get_module.php).
if (!isset($conn) || !($conn instanceof mysqli) || !isset($id_pemilik)) {
    echo '<p class="text-red-500 p-4">Error: Koneksi database atau sesi pengguna tidak ditemukan.</p>';
    return;
}

// Ambil daftar kos milik pemilik ini
$sql_pemilik_kost_list = "SELECT id_kost, nama_kost, alamat, harga FROM kost WHERE id_pemilik = ?";
$stmt = $conn->prepare($sql_pemilik_kost_list);
$stmt->bind_param("i", $id_pemilik);
$stmt->execute();
$res_pemilik_kost_list = $stmt->get_result();
$stmt->close();
?>

<div class="p-0">
    <h2 class="text-2xl sm:text-3xl font-semibold text-gray-800 mb-4 sm:mb-6">Manajemen Data Kos & Kamar</h2>
    <div class="bg-white rounded-lg sm:rounded-xl shadow-sm">
        <div class="p-3 sm:p-6 border-b border-gray-200 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 sm:gap-0">
            <h3 class="text-lg sm:text-xl font-semibold text-gray-800">Daftar Kos Saya</h3>
            <button onclick="showKosModal('add')" class="gradient-bg text-white px-3 sm:px-4 py-2 rounded-lg hover:opacity-90 transition-colors text-sm sm:text-base w-full sm:w-auto">
                <i class="fas fa-plus mr-1 sm:mr-2"></i>Tambah Kos Baru
            </button>
        </div>
        
        <div class="p-2 sm:p-4 md:p-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4 md:gap-6">
                <?php if ($res_pemilik_kost_list->num_rows > 0): ?>
                    <?php while($row = $res_pemilik_kost_list->fetch_assoc()): ?>
                        <div class="bg-white rounded-lg sm:rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-shadow duration-300 flex flex-col">
                            <div class="p-3 sm:p-5">
                                <div class="flex justify-between items-start mb-2 gap-2">
                                    <h4 class="font-bold text-base sm:text-xl text-gray-800 flex-grow break-words"><?php echo htmlspecialchars($row['nama_kost']); ?></h4>
                                    <div class="flex gap-1 flex-shrink-0">
                                        <button onclick="editKos(<?php echo $row['id_kost']; ?>)" title="Edit Kos" class="text-blue-500 hover:text-blue-700 text-sm sm:text-lg px-1.5 sm:px-2 py-1 rounded-full hover:bg-blue-100 transition-colors"><i class="fas fa-edit"></i></button>
                                        <button onclick="deleteKos(<?php echo $row['id_kost']; ?>)" title="Hapus Kos" class="text-red-500 hover:text-red-700 text-sm sm:text-lg px-1.5 sm:px-2 py-1 rounded-full hover:bg-red-100 transition-colors"><i class="fas fa-trash"></i></button>
                                    </div>
                                </div>
                                <p class="text-xs sm:text-sm text-gray-500 mb-3 flex items-start gap-1.5"><i class="fas fa-map-marker-alt mr-1 mt-0.5 text-gray-400 flex-shrink-0"></i><span class="break-words"><?php echo htmlspecialchars($row['alamat']); ?></span></p>
                                <p class="text-base sm:text-lg font-bold text-purple-600">Rp <?php echo number_format($row['harga'], 0, ',', '.'); ?><span class="text-xs sm:text-sm font-normal text-gray-500">/bulan</span></p>
                            </div>
                            <div class="mt-auto bg-gray-50 border-t border-gray-200 px-3 sm:px-5 py-2 sm:py-3">
                                <button class="w-full text-center bg-purple-100 text-purple-700 hover:bg-purple-200 hover:text-purple-800 text-xs sm:text-sm font-bold py-2 px-3 sm:px-4 rounded-lg transition-colors" onclick="showKamarModal(<?php echo $row['id_kost']; ?>)">
                                    <i class="fas fa-door-open mr-1 sm:mr-2"></i>Kelola Kamar
                                </button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-span-1 sm:col-span-2 lg:col-span-3 text-center py-8 sm:py-12">
                        <i class="fas fa-house-damage text-3xl sm:text-4xl text-gray-300 mb-3 sm:mb-4"></i>
                        <h3 class="text-lg sm:text-xl font-semibold text-gray-700">Belum Ada Kos Terdaftar</h3>
                        <p class="text-gray-500 mt-2 text-sm sm:text-base">Anda belum memiliki properti kos. Klik tombol "Tambah Kos Baru" untuk memulai.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Fungsi editKos, deleteKos, dan showKamarModal sudah didefinisikan di dashboardpemilik.php
        // Kita hanya perlu memastikan pemanggilannya benar dan menambahkan implementasi delete.
        function editKos(id) {
            showKosModal('edit', id);
        }

        function deleteKos(id) {
            Swal.fire({
                title: 'Konfirmasi Hapus Kos',
                text: 'Yakin menghapus Kos ID ' + id + '? Ini akan menghapus semua kamar dan data terkait di dalamnya secara permanen.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('action', 'delete');
                    formData.append('id_kost', id);

                    fetch('process_kost.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(res => {
                        if (res.status === 'success') {
                            Swal.fire('Berhasil', res.message, 'success');
                            loadContent('owner_manage_kost'); // Muat ulang daftar kos
                        } else {
                            Swal.fire('Gagal', 'Gagal menghapus: ' + res.message, 'error');
                        }
                    })
                    .catch(err => {
                        Swal.fire('Error', 'Terjadi kesalahan jaringan saat mencoba menghapus.', 'error');
                        console.error('Delete Kos Error:', err);
                    });
                }
            });
        }
    </script>
</div>