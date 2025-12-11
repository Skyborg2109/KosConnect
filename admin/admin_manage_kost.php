<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/loginForm.php");
    exit();
}

include '../config/db.php'; 

// Query untuk mendapatkan semua data kos beserta nama pemilik
$sql_all_kost = "
    SELECT 
        t.id_kost, 
        t.nama_kost, 
        t.alamat, 
        t.harga,
        t.deskripsi,
        t.fasilitas,
        t.gambar,
        u.nama_lengkap AS nama_pemilik,
        u.id_user AS id_pemilik
    FROM kost t 
    JOIN user u ON t.id_pemilik = u.id_user 
    ORDER BY t.nama_kost ASC";
$res_all_kost = $conn->query($sql_all_kost);

// Get list of owners for dropdown
$sql_owners = "SELECT id_user, nama_lengkap FROM user WHERE role = 'pemilik' ORDER BY nama_lengkap ASC";
$res_owners = $conn->query($sql_owners);
$owners = [];
while($owner = $res_owners->fetch_assoc()) {
    $owners[] = $owner;
}
?>

<!-- Fragment untuk dimuat via AJAX (tanpa DOCTYPE/head/body) -->
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 p-6">
    <!-- Header Section -->
    <div class="mb-8 flex justify-between items-center">
        <div>
            <h2 class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent mb-2">
                <i class="fas fa-building mr-3"></i>Manajemen Data Kos
            </h2>
            <p class="text-gray-600">Kelola semua data kos dengan mudah dan efisien</p>
        </div>
        <button id="btn-add-kos" class="bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white px-6 py-3 rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all duration-300 flex items-center gap-2">
            <i class="fas fa-plus"></i>Tambah Kos Baru
        </button>
    </div>

    <!-- Stats Bar -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <div class="bg-white rounded-xl p-4 shadow-md border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm">Total Kos</p>
                    <p class="text-3xl font-bold text-blue-600 mt-1"><?php echo $res_all_kost->num_rows; ?></p>
                </div>
                <div class="bg-blue-100 rounded-full p-4">
                    <i class="fas fa-building text-blue-600 text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Kos Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="kosCardsContainer">
        <?php 
        $kos_data = [];
        while($kos = $res_all_kost->fetch_assoc()): 
            $kos_data[] = $kos;
        ?>
        <div class="bg-white rounded-xl shadow-md hover:shadow-2xl transition-all duration-300 overflow-hidden group" data-kos-id="<?php echo $kos['id_kost']; ?>">
            <!-- Image Section -->
            <div class="relative h-48 bg-gradient-to-br from-blue-400 to-purple-500 overflow-hidden">
                <?php if($kos['gambar']): ?>
                    <img src="../uploads/kost/<?php echo htmlspecialchars($kos['gambar']); ?>" 
                         alt="<?php echo htmlspecialchars($kos['nama_kost']); ?>" 
                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                <?php else: ?>
                    <div class="w-full h-full flex items-center justify-center">
                        <i class="fas fa-image text-white text-6xl opacity-50"></i>
                    </div>
                <?php endif; ?>
                <div class="absolute top-3 right-3 bg-blue-600 text-white px-3 py-1 rounded-full text-sm font-semibold">
                    Rp <?php echo number_format($kos['harga'], 0, ',', '.'); ?>/bln
                </div>
            </div>

            <!-- Content Section -->
            <div class="p-5">
                <h3 class="text-xl font-bold text-gray-800 mb-2 truncate">
                    <?php echo htmlspecialchars($kos['nama_kost']); ?>
                </h3>
                
                <div class="mb-3 text-sm text-gray-600 flex items-start gap-2">
                    <i class="fas fa-map-marker-alt text-red-500 flex-shrink-0 mt-1"></i>
                    <p class="line-clamp-2"><?php echo htmlspecialchars($kos['alamat']); ?></p>
                </div>

                <div class="mb-3 text-sm">
                    <p class="text-gray-600"><span class="font-semibold">Pemilik:</span> <?php echo htmlspecialchars($kos['nama_pemilik']); ?></p>
                </div>

                <div class="mb-4 text-sm text-gray-700 bg-gray-50 p-3 rounded-lg line-clamp-3">
                    <p class="font-semibold text-gray-800 mb-1">Deskripsi:</p>
                    <?php echo htmlspecialchars($kos['deskripsi']); ?>
                </div>

                <?php if($kos['fasilitas']): ?>
                <div class="mb-4 text-xs">
                    <p class="font-semibold text-gray-800 mb-2">Fasilitas:</p>
                    <div class="flex flex-wrap gap-2">
                        <?php 
                        $fasilitas_list = array_filter(array_map('trim', explode(',', $kos['fasilitas'])));
                        foreach(array_slice($fasilitas_list, 0, 3) as $fasilitas): 
                        ?>
                        <span class="bg-blue-100 text-blue-700 px-2 py-1 rounded-md">
                            <?php echo htmlspecialchars($fasilitas); ?>
                        </span>
                        <?php endforeach; ?>
                        <?php if(count($fasilitas_list) > 3): ?>
                        <span class="text-gray-500">+<?php echo count($fasilitas_list) - 3; ?> lainnya</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Action Buttons -->
                <div class="flex gap-3 pt-4 border-t border-gray-200">
                    <button class="btn-edit-kos flex-1 bg-blue-50 hover:bg-blue-100 text-blue-600 font-semibold py-2 rounded-lg transition-colors" data-id="<?php echo $kos['id_kost']; ?>">
                        <i class="fas fa-edit mr-2"></i>Edit
                    </button>
                    <button class="btn-delete-kos flex-1 bg-red-50 hover:bg-red-100 text-red-600 font-semibold py-2 rounded-lg transition-colors" data-id="<?php echo $kos['id_kost']; ?>">
                        <i class="fas fa-trash mr-2"></i>Hapus
                    </button>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>

    <!-- Empty State -->
    <?php if(count($kos_data) === 0): ?>
    <div class="bg-white rounded-xl shadow-lg p-12 text-center">
        <div class="inline-flex items-center justify-center w-20 h-20 bg-gray-100 rounded-full mb-4">
            <i class="fas fa-inbox text-gray-400 text-3xl"></i>
        </div>
        <h3 class="text-2xl font-bold text-gray-800 mb-2">Belum ada data kos</h3>
        <p class="text-gray-600 mb-6">Mulai dengan menambahkan kos baru untuk mengelola properti Anda</p>
        <button id="btn-add-kos-empty" class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-3 rounded-xl font-semibold hover:from-blue-700 hover:to-blue-800 transition-all">
            <i class="fas fa-plus mr-2"></i>Tambah Kos Pertama
        </button>
    </div>
    <?php endif; ?>
</div>

<!-- Modal Form Kos -->
<div id="kostModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
        <!-- Header -->
        <div class="sticky top-0 bg-gradient-to-r from-blue-600 to-purple-600 p-6 flex justify-between items-center rounded-t-2xl">
            <div>
                <h3 id="modalTitle" class="text-3xl font-bold text-white">Tambah Kos Baru</h3>
                <p id="modalSubtitle" class="text-blue-100 text-sm mt-1">Isi form dibawah untuk menambahkan data kos baru</p>
            </div>
            <button onclick="closeKosModal()" class="text-white hover:bg-white hover:bg-opacity-20 p-2 rounded-lg transition-colors">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>
        
        <form id="kostForm" class="p-8" enctype="multipart/form-data">
            <input type="hidden" id="kost-id" name="kost_id" value="">
            <input type="hidden" name="action" value="add">

            <!-- Grid Layout for Form -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Nama Kos -->
                <div>
                    <label for="kost-nama" class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-building mr-2 text-blue-600"></i>Nama Kos *
                    </label>
                    <input type="text" id="kost-nama" name="nama_kost" required 
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                        placeholder="Contoh: Kos Manis">
                </div>

                <!-- Pemilik (Dropdown) -->
                <div>
                    <label for="kost-pemilik" class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-user mr-2 text-blue-600"></i>Pemilik Kos *
                    </label>
                    <select id="kost-pemilik" name="id_pemilik" required 
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                        <option value="">-- Pilih Pemilik --</option>
                        <?php foreach($owners as $owner): ?>
                        <option value="<?php echo $owner['id_user']; ?>">
                            <?php echo htmlspecialchars($owner['nama_lengkap']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Harga -->
                <div>
                    <label for="kost-harga" class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-money-bill mr-2 text-blue-600"></i>Harga Kamar (per bulan) *
                    </label>
                    <div class="relative">
                        <span class="absolute left-4 top-3 text-gray-500 font-semibold">Rp</span>
                        <input type="number" id="kost-harga" name="harga" required min="0" step="1000"
                            class="w-full px-4 py-3 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                            placeholder="100000">
                    </div>
                </div>
            </div>

            <!-- Alamat -->
            <div class="mb-6">
                <label for="kost-alamat" class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-map-marker-alt mr-2 text-blue-600"></i>Alamat *
                </label>
                <textarea id="kost-alamat" name="alamat" rows="3" required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                    placeholder="Jalan, No. Rumah, Kelurahan, Kecamatan, Kota"></textarea>
            </div>

            <!-- Deskripsi -->
            <div class="mb-6">
                <label for="kost-deskripsi" class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-file-alt mr-2 text-blue-600"></i>Deskripsi *
                </label>
                <textarea id="kost-deskripsi" name="deskripsi" rows="4" required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                    placeholder="Deskripsi lengkap tentang kos..."></textarea>
            </div>

            <!-- Fasilitas -->
            <div class="mb-6">
                <label for="kost-fasilitas" class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-check-circle mr-2 text-blue-600"></i>Fasilitas
                </label>
                <textarea id="kost-fasilitas" name="fasilitas" rows="2" 
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                    placeholder="Contoh: WiFi, AC, Kasur (pisahkan dengan koma)"></textarea>
                <p class="text-xs text-gray-500 mt-1"><i class="fas fa-info-circle mr-1"></i>Pisahkan fasilitas dengan tanda koma</p>
            </div>

            <!-- Gambar -->
            <div class="mb-6">
                <label for="kost-gambar" class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-image mr-2 text-blue-600"></i>Gambar Kos
                </label>
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-blue-500 transition-colors cursor-pointer" id="dragDropArea">
                    <input type="file" id="kost-gambar" name="gambar" accept="image/*" class="hidden">
                    <div id="uploadPrompt">
                        <i class="fas fa-cloud-upload-alt text-5xl text-gray-300 mb-3"></i>
                        <p class="text-gray-600 font-semibold">Drag dan drop gambar di sini</p>
                        <p class="text-gray-500 text-sm">atau <span class="text-blue-600 cursor-pointer hover:underline">klik untuk memilih</span></p>
                    </div>
                </div>
                <img id="kost-gambar-preview" src="" alt="Preview" class="hidden mt-4 max-h-32 rounded-lg mx-auto border border-gray-300">
                <input type="hidden" id="kost-gambar-lama" name="gambar_lama" value="">
            </div>

            <!-- Button -->
            <div class="flex justify-end gap-3 pt-6 border-t border-gray-200">
                <button type="button" onclick="closeKosModal()" 
                    class="px-6 py-3 border-2 border-gray-300 rounded-lg text-gray-700 font-semibold hover:bg-gray-50 transition-colors">
                    <i class="fas fa-times mr-2"></i>Batal
                </button>
                <button type="submit" 
                    class="px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg font-semibold hover:from-blue-700 hover:to-blue-800 shadow-lg hover:shadow-xl transition-all">
                    <i class="fas fa-save mr-2"></i><span id="btnText">Simpan</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Open modal for adding new kos
window.openAddKosModal = function() {
    const modal = document.getElementById('kostModal');
    const form = document.getElementById('kostForm');
    
    // Reset form
    form.reset();
    form.querySelector('input[name="action"]').value = 'add';
    document.getElementById('kost-id').value = '';
    document.getElementById('modalTitle').textContent = 'Tambah Kos Baru';
    document.getElementById('modalSubtitle').textContent = 'Isi form dibawah untuk menambahkan data kos baru';
    document.getElementById('btnText').textContent = 'Simpan';
    document.getElementById('kost-gambar-preview').classList.add('hidden');
    document.getElementById('uploadPrompt').classList.remove('hidden');
    document.getElementById('kost-gambar-lama').value = '';
    document.getElementById('kost-pemilik').disabled = false;
    
    // Show modal
    modal.classList.remove('hidden');
};

// Close modal
window.closeKosModal = function() {
    const modal = document.getElementById('kostModal');
    modal.classList.add('hidden');
    document.getElementById('kostForm').reset();
};

// Load kos data for editing
window.loadKosForEdit = function(kosId) {
    fetch(`../admin/process_kost.php?action=get_details&id=${kosId}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const kos = data.data;
                document.getElementById('kost-id').value = kos.id_kost;
                document.getElementById('kost-nama').value = kos.nama_kost;
                document.getElementById('kost-pemilik').value = kos.id_pemilik;
                document.getElementById('kost-pemilik').disabled = false;
                document.getElementById('kost-alamat').value = kos.alamat;
                document.getElementById('kost-harga').value = kos.harga;
                document.getElementById('kost-deskripsi').value = kos.deskripsi;
                document.getElementById('kost-fasilitas').value = kos.fasilitas || '';
                document.getElementById('kost-gambar-lama').value = kos.gambar || '';
                
                // Show preview if image exists
                if (kos.gambar) {
                    document.getElementById('kost-gambar-preview').src = `../uploads/kost/${kos.gambar}`;
                    document.getElementById('kost-gambar-preview').classList.remove('hidden');
                    document.getElementById('uploadPrompt').classList.add('hidden');
                }
                
                // Update modal for edit mode
                document.getElementById('modalTitle').textContent = 'Edit Kos';
                document.getElementById('modalSubtitle').textContent = 'Ubah data kos yang ingin diperbarui';
                document.getElementById('btnText').textContent = 'Update';
                document.getElementById('kostForm').querySelector('input[name="action"]').value = 'update';
                
                // Show modal
                document.getElementById('kostModal').classList.remove('hidden');
            } else {
                Swal.fire('Error', data.message || 'Gagal memuat data', 'error');
            }
        })
        .catch(err => {
            console.error(err);
            Swal.fire('Error', 'Terjadi kesalahan jaringan', 'error');
        });
};

// Drag and Drop functionality
const dragDropArea = document.getElementById('dragDropArea');
if (dragDropArea) {
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dragDropArea.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        dragDropArea.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dragDropArea.addEventListener(eventName, unhighlight, false);
    });

    function highlight(e) {
        dragDropArea.classList.add('border-blue-500', 'bg-blue-50');
    }

    function unhighlight(e) {
        dragDropArea.classList.remove('border-blue-500', 'bg-blue-50');
    }

    dragDropArea.addEventListener('drop', handleDrop, false);

    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        document.getElementById('kost-gambar').files = files;
        handleFiles(files);
    }

    dragDropArea.addEventListener('click', function() {
        document.getElementById('kost-gambar').click();
    });
}

// Handle form submission
document.getElementById('kostForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const kosId = document.getElementById('kost-id').value;
    
    // Validate required fields
    if (!formData.get('nama_kost')?.trim() || !formData.get('id_pemilik') || 
        !formData.get('alamat')?.trim() || !formData.get('harga') || 
        !formData.get('deskripsi')?.trim()) {
        Swal.fire('Validasi Gagal', 'Semua field wajib diisi', 'warning');
        return;
    }
    
    const btn = this.querySelector('button[type="submit"]');
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Menyimpan...';
    btn.disabled = true;

    fetch('../admin/process_kost.php', {
        method: 'POST',
        body: formData
    })
    .then(r => {
        const contentType = r.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return r.json();
        } else {
            return r.text().then(text => {
                console.error('Invalid JSON response:', text);
                throw new Error('Response is not JSON: ' + text.substring(0, 100));
            });
        }
    })
    .then(data => {
        if (data.success) {
            closeKosModal();
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: data.message,
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                if (typeof window.loadContent === 'function') {
                    window.loadContent('admin_manage_kost');
                } else {
                    location.reload();
                }
            });
        } else {
            Swal.fire('Gagal!', data.message || 'Terjadi kesalahan', 'error');
        }
    })
    .catch(err => {
        console.error('Form submission error:', err);
        Swal.fire('Error', err.message || 'Terjadi kesalahan jaringan', 'error');
    })
    .finally(() => {
        btn.innerHTML = originalHtml;
        btn.disabled = false;
    });
});

// Delete kos
window.deleteKos = function(kosId) {
    Swal.fire({
        title: 'Konfirmasi Hapus',
        text: '⚠️ Yakin menghapus kos ini dan semua datanya?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Hapus'
    }).then(result => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id_kost', kosId);

            fetch('../admin/process_kost.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Terhapus!', data.message, 'success').then(() => {
                        if (typeof window.loadContent === 'function') {
                            window.loadContent('admin_manage_kost');
                        } else {
                            location.reload();
                        }
                    });
                } else {
                    Swal.fire('Gagal', data.message || 'Gagal menghapus', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                Swal.fire('Error', 'Terjadi kesalahan jaringan', 'error');
            });
        }
    });
};

// Handle file selection and preview
function handleFiles(files) {
    const file = files[0];
    if (file && file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function(event) {
            document.getElementById('kost-gambar-preview').src = event.target.result;
            document.getElementById('kost-gambar-preview').classList.remove('hidden');
            document.getElementById('uploadPrompt').classList.add('hidden');
        };
        reader.readAsDataURL(file);
    }
}

// Preview image on file input change
document.getElementById('kost-gambar')?.addEventListener('change', function(e) {
    handleFiles(e.target.files);
});

// Bind button events
function bindKosButtons() {
    const addBtn = document.getElementById('btn-add-kos');
    if (addBtn) addBtn.addEventListener('click', window.openAddKosModal);

    // Empty state button
    const emptyBtn = document.getElementById('btn-add-kos-empty');
    if (emptyBtn) emptyBtn.addEventListener('click', window.openAddKosModal);

    document.querySelectorAll('.btn-edit-kos').forEach(b => {
        b.addEventListener('click', (e) => {
            const id = e.currentTarget.getAttribute('data-id');
            window.loadKosForEdit(id);
        });
    });

    document.querySelectorAll('.btn-delete-kos').forEach(b => {
        b.addEventListener('click', (e) => {
            const id = e.currentTarget.getAttribute('data-id');
            window.deleteKos(id);
        });
    });
}

// Bind on load and when content changes
bindKosButtons();
if (window.MutationObserver) {
    const observer = new MutationObserver(() => bindKosButtons());
    observer.observe(document.body, { childList: true, subtree: true });
}

// Close modal when clicking outside
document.getElementById('kostModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeKosModal();
});
</script>
