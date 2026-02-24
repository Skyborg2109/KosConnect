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
$sql_pemilik_kost_list = "
    SELECT 
        k.id_kost, 
        k.nama_kost, 
        k.alamat, 
        k.harga, 
        k.gambar, 
        k.deskripsi, 
        k.fasilitas,
        u.nama_lengkap AS nama_pemilik
    FROM kost k
    JOIN user u ON k.id_pemilik = u.id_user
    WHERE k.id_pemilik = ?
    ORDER BY k.id_kost DESC";
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
            <button onclick="showKosModal('add')" class="bg-gradient-to-r from-purple-600 to-indigo-600 text-white px-3 sm:px-4 py-2 rounded-lg hover:from-purple-700 hover:to-indigo-700 transition-all text-sm sm:text-base w-full sm:w-auto shadow-md">
                <i class="fas fa-plus mr-1 sm:mr-2"></i>Tambah Kos Baru
            </button>
        </div>
        
        <div class="p-2 sm:p-4 md:p-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4 md:gap-6">
                <?php if ($res_pemilik_kost_list->num_rows > 0): ?>
                    <?php while($row = $res_pemilik_kost_list->fetch_assoc()): ?>
                        <div class="bg-white rounded-lg sm:rounded-xl shadow-md hover:shadow-2xl transition-all duration-300 overflow-hidden group border border-gray-100 flex flex-col h-full">
                            <!-- Image Section -->
                            <div class="relative h-48 bg-gray-200 overflow-hidden group-hover:opacity-90 transition-opacity flex-shrink-0">
                                <?php 
                                    $gambar = $row['gambar'] ?? '';
                                    $is_url = strpos($gambar, 'http') === 0;
                                    $img_src = $is_url ? $gambar : '../uploads/kost/' . $gambar;
                                ?>
                                <?php if($gambar): ?>
                                    <img src="<?php echo htmlspecialchars($img_src); ?>" 
                                         alt="<?php echo htmlspecialchars($row['nama_kost']); ?>" 
                                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-blue-400 to-purple-500">
                                        <i class="fas fa-home text-white text-5xl opacity-50"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="absolute top-3 right-3 bg-blue-600 text-white px-3 py-1 rounded-full text-xs sm:text-sm font-bold shadow-lg backdrop-blur-sm bg-opacity-90">
                                    Rp <?php echo number_format($row['harga'], 0, ',', '.'); ?>/bln
                                </div>
                            </div>

                            <!-- Content Section -->
                            <div class="p-4 sm:p-5 flex flex-col flex-grow">
                                <h4 class="font-bold text-lg text-gray-800 mb-2 line-clamp-2 min-h-[3.5rem]"><?php echo htmlspecialchars($row['nama_kost']); ?></h4>
                                
                                <div class="flex items-start gap-2 mb-3 text-sm text-gray-600">
                                    <i class="fas fa-map-marker-alt text-red-500 mt-1 flex-shrink-0"></i>
                                    <span class="line-clamp-1"><?php echo htmlspecialchars($row['alamat']); ?></span>
                                </div>

                                <div class="mb-3 text-sm text-gray-500">
                                    <span class="font-medium text-gray-700">Pemilik:</span> <?php echo htmlspecialchars($row['nama_pemilik']); ?>
                                </div>

                                <div class="mb-4 bg-gray-50 p-3 rounded-lg border border-gray-100 flex-grow">
                                    <p class="text-xs font-semibold text-gray-500 mb-1">Deskripsi:</p>
                                    <p class="text-sm text-gray-600 line-clamp-3 leading-relaxed">
                                        <?php echo htmlspecialchars($row['deskripsi']); ?>
                                    </p>
                                </div>

                                <?php if(!empty($row['fasilitas'])): ?>
                                <div class="mb-5">
                                    <p class="text-xs font-semibold text-gray-500 mb-2">Fasilitas:</p>
                                    <div class="flex flex-wrap gap-1.5">
                                        <?php 
                                        $fasilitas_list = array_filter(array_map('trim', explode(',', $row['fasilitas'])));
                                        foreach(array_slice($fasilitas_list, 0, 3) as $fasilitas): 
                                        ?>
                                        <span class="bg-blue-50 text-blue-600 px-2.5 py-1 rounded-md text-xs font-medium border border-blue-100">
                                            <?php echo htmlspecialchars($fasilitas); ?>
                                        </span>
                                        <?php endforeach; ?>
                                        <?php if(count($fasilitas_list) > 3): ?>
                                        <span class="text-xs text-gray-400 self-center pl-1">+<?php echo count($fasilitas_list) - 3; ?> lainnya</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>


                         
                            <div class="px-5 pb-4 mt-auto">
                                <button onclick="showKamarModal(<?php echo $row['id_kost']; ?>)" class="w-full bg-gradient-to-r from-purple-600 to-indigo-600 text-white py-2.5 rounded-xl font-bold text-sm shadow-md hover:shadow-lg transition-all transform active:scale-95 flex items-center justify-center gap-2 mb-2">
                                    <i class="fas fa-door-open"></i> Kelola Kamar & Harga
                                </button>
                            </div>
                            
                            <!-- Footer Actions -->
                            <div class="border-t border-gray-100 flex">
                                <button onclick="editKos(<?php echo $row['id_kost']; ?>)" class="flex-1 py-3 text-blue-600 hover:bg-blue-50 font-semibold text-sm transition-colors border-r border-gray-100 flex items-center justify-center gap-2 group-hover/edit:text-blue-700">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button onclick="deleteKos(<?php echo $row['id_kost']; ?>)" class="flex-1 py-3 text-red-600 hover:bg-red-50 font-semibold text-sm transition-colors flex items-center justify-center gap-2 group-hover/delete:text-red-700">
                                    <i class="fas fa-trash-alt"></i> Hapus
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