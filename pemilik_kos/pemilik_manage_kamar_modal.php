<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include '../config/db.php';

// Autentikasi & Otorisasi
if (!isset($_SESSION['user_logged_in']) || $_SESSION['role'] !== 'pemilik') {
    die('<p class="text-red-500 p-6">Akses tidak sah.</p>');
}

$id_pemilik = $_SESSION['user_id'];
$id_kost = filter_var($_GET['id_kost'] ?? 0, FILTER_VALIDATE_INT);

if ($id_kost <= 0) {
    die('<p class="text-red-500 p-6">ID Kos tidak valid.</p>');
}

// Verifikasi kepemilikan kos
$stmt_check = $conn->prepare("SELECT nama_kost FROM kost WHERE id_kost = ? AND id_pemilik = ?");
$stmt_check->bind_param("ii", $id_kost, $id_pemilik);
$stmt_check->execute();
$result_check = $stmt_check->get_result();
if ($result_check->num_rows === 0) {
    die('<p class="text-red-500 p-6">Anda tidak memiliki akses ke kos ini.</p>');
}
$kost = $result_check->fetch_assoc();
$stmt_check->close();

// Ambil daftar kamar
// Ambil daftar kamar dengan foto tipe
$sql_kamar = "SELECT k.id_kamar, k.nama_kamar, k.tipe_kamar, k.harga, k.status, k.foto, k.fasilitas, rt.foto_tipe as foto_tipe_master 
              FROM kamar k 
              LEFT JOIN kost_room_types rt ON k.tipe_kamar = rt.nama_tipe AND rt.id_kost = k.id_kost 
              WHERE k.id_kost = ? 
              ORDER BY k.nama_kamar ASC";
$stmt_kamar = $conn->prepare($sql_kamar);
$stmt_kamar->bind_param("i", $id_kost);
$stmt_kamar->execute();
$res_kamar = $stmt_kamar->get_result();

// Ambil daftar tipe kamar (defined Master Data) dengan fasilitas
// Check if fasilitas column exists first
$has_fasilitas_column = false;
try {
    $check_col = $conn->query("SHOW COLUMNS FROM kost_room_types LIKE 'fasilitas'");
    $has_fasilitas_column = ($check_col && $check_col->num_rows > 0);
} catch (Exception $e) {
    error_log('Error checking fasilitas column: ' . $e->getMessage());
}

if ($has_fasilitas_column) {
    $stmt_types = $conn->prepare("SELECT nama_tipe, foto_tipe, fasilitas FROM kost_room_types WHERE id_kost = ?");
} else {
    $stmt_types = $conn->prepare("SELECT nama_tipe, foto_tipe, '' as fasilitas FROM kost_room_types WHERE id_kost = ?");
}
$stmt_types->bind_param("i", $id_kost);
$stmt_types->execute();
$res_types = $stmt_types->get_result();
$room_types_list = [];
while($rt = $res_types->fetch_assoc()) {
    $room_types_list[] = $rt;
}
$stmt_types->close();
?>

<div class="p-6 border-b flex justify-between items-center modal-header">
    <div>
        <h3 class="text-3xl font-bold modal-title mb-1">
            <i class="fas fa-door-open mr-2"></i>Kelola Kamar
        </h3>
        <p class="text-gray-600 flex items-center">
            <i class="fas fa-building mr-2 text-purple-500"></i>
            <span class="font-semibold"><?php echo htmlspecialchars($kost['nama_kost']); ?></span>
        </p>
    </div>
    <button onclick="closeKamarModal()" class="text-gray-400 hover:text-gray-600 text-3xl hover:bg-gray-100 rounded-full w-10 h-10 flex items-center justify-center transition-all hover:rotate-90" title="Tutup">
        <i class="fas fa-times"></i>
    </button>
</div>

<div class="p-6 min-h-[70vh] flex items-center justify-center">
    <div class="w-full max-w-6xl mx-auto">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
            <!-- Kolom Daftar Kamar (1/2) - Kiri -->
            <div class="lg:col-span-1">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-lg font-bold text-gray-800 section-header">
                        <i class="fas fa-list mr-2 text-purple-600"></i>Daftar Kamar
                    </h4>
                    <span class="text-xs text-gray-500 bg-purple-50 px-2 py-1 rounded-full font-semibold">
                        <i class="fas fa-door-closed mr-1"></i>
                        <span id="totalKamarCount"><?php echo $res_kamar->num_rows; ?></span>
                    </span>
                </div>
                <div id="kamarListContainer" class="space-y-3 max-h-[65vh] overflow-y-auto pr-2">
                    <?php if ($res_kamar->num_rows > 0): ?>
                        <?php 
                        $index = 0;
                        while($kamar = $res_kamar->fetch_assoc()): 
                            $index++;
                        ?>
                            <div id="kamar-<?php echo $kamar['id_kamar']; ?>" class="kamar-card flex justify-between items-center p-4 rounded-xl shadow-md" style="animation-delay: <?php echo $index * 0.05; ?>s;">
                                <div class="flex items-center space-x-4">
                                    <div class="w-16 h-16 rounded-xl overflow-hidden shadow-sm border border-purple-100 flex-shrink-0">
                                        <?php 
                                        $foto = $kamar['foto'] ?? '';
                                        $foto_tipe = $kamar['foto_tipe_master'] ?? '';
                                        
                                        $final_img_src = '';
                                        $show_img = false;

                                        // Prioritas 1: Foto Kamar Spesifik
                                        if (!empty($foto)) {
                                            $is_url = strpos($foto, 'http') === 0;
                                            if ($is_url) {
                                                $final_img_src = $foto;
                                                $show_img = true;
                                            } elseif (file_exists(__DIR__ . '/../uploads/rooms/' . $foto)) {
                                                $final_img_src = '../uploads/rooms/' . $foto;
                                                $show_img = true;
                                            }
                                        }

                                        // Prioritas 2: Foto Tipe Kamar (Master)
                                        if (!$show_img && !empty($foto_tipe)) {
                                            $final_img_src = $foto_tipe; // Asumsi URL Cloudinary dari process_kost
                                            $show_img = true;
                                        }
                                        
                                        if ($show_img): 
                                    ?>
                                        <img src="<?php echo htmlspecialchars($final_img_src); ?>" alt="<?php echo htmlspecialchars($kamar['nama_kamar']); ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <div class="w-full h-full bg-purple-100 flex items-center justify-center text-purple-500">
                                            <i class="fas fa-bed text-xl"></i>
                                        </div>
                                    <?php endif; ?>
                                    </div>
                                    <div>
                                        <p class="font-bold text-gray-800 text-lg"><?php echo htmlspecialchars($kamar['nama_kamar']); ?></p>
                                        <div class="flex items-center space-x-2">
                                            <p class="text-xs font-semibold text-purple-600 bg-purple-50 px-2 py-0.5 rounded-md border border-purple-100">
                                                <i class="fas fa-certificate mr-1"></i><?php echo htmlspecialchars($kamar['tipe_kamar'] ?? 'Standard'); ?>
                                            </p>
                                            <p class="text-sm price-display font-bold">Rp <?php echo number_format($kamar['harga'], 0, ',', '.'); ?></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <span class="status-badge <?php echo $kamar['status']; ?> px-3 py-1 text-xs font-bold rounded-full">
                                        <i class="fas fa-circle mr-1 text-[10px]"></i>
                                        <?php echo ucfirst(htmlspecialchars($kamar['status'])); ?>
                                    </span>
                                    <button onclick="editKamar(<?php echo $kamar['id_kamar']; ?>, '<?php echo htmlspecialchars($kamar['nama_kamar']); ?>', '<?php echo htmlspecialchars($kamar['tipe_kamar'] ?? 'Standard'); ?>', <?php echo $kamar['harga']; ?>, '<?php echo $kamar['status']; ?>', '<?php echo $final_img_src; ?>', '<?php echo htmlspecialchars($kamar['fasilitas'] ?? ''); ?>')" class="action-btn edit" title="Edit Kamar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deleteKamar(<?php echo $kamar['id_kamar']; ?>, <?php echo $id_kost; ?>)" class="action-btn delete" title="Hapus Kamar">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-state text-center py-12">
                            <div class="w-24 h-24 bg-gradient-to-br from-purple-100 to-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-inbox text-purple-400 text-4xl"></i>
                            </div>
                            <p class="text-gray-500 font-semibold text-lg mb-2">Belum Ada Kamar</p>
                            <p class="text-gray-400 text-sm">Mulai tambahkan kamar untuk kos ini</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Kolom Form Tambah/Edit Kamar (1/2) - Kanan, Centered -->
            <div class="lg:col-span-1 flex items-center justify-center">
                <div class="bg-gradient-to-br from-purple-50 to-indigo-50 p-8 rounded-2xl shadow-xl border border-purple-100 w-full">
                    <h4 class="text-2xl font-bold mb-8 section-header flex items-center justify-center" id="formTitle">
                        <i class="fas fa-plus-circle mr-2 text-purple-600"></i>Tambah Kamar Baru
                    </h4>
                    <form id="kamarForm" onsubmit="saveKamar(event, <?php echo $id_kost; ?>)" class="space-y-6">
                        <input type="hidden" name="action" id="kamarAction" value="add">
                        <input type="hidden" name="id_kamar" id="kamarId" value="">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="nama_kamar" class="block text-sm font-bold text-gray-700 mb-2">
                                    <i class="fas fa-tag mr-1 text-purple-600"></i>Nama Kamar
                                </label>
                                <input type="text" name="nama_kamar" id="nama_kamar" required 
                                       placeholder="101, A, Deluxe" 
                                       oninput="updateRoomNamePreview()"
                                       class="form-input mt-1 block w-full rounded-lg shadow-sm p-3 bg-white">
                                <p class="text-xs text-gray-500 mt-1">Nama dasar untuk kamar (akan di-generate otomatis jika jumlah > 1)</p>
                            </div>
                            <div id="quantityFieldContainer">
                                <label for="jumlah_kamar" class="block text-sm font-bold text-gray-700 mb-2">
                                    <i class="fas fa-hashtag mr-1 text-purple-600"></i>Jumlah Kamar
                                </label>
                                <input type="number" name="jumlah_kamar" id="jumlah_kamar" 
                                       value="1" min="1" max="50" required
                                       oninput="updateRoomNamePreview()"
                                       class="form-input mt-1 block w-full rounded-lg shadow-sm p-3 bg-white">
                                <p class="text-xs text-gray-500 mt-1">Tambahkan 1-50 kamar sekaligus</p>
                            </div>
                        </div>
                        
                        <!-- Room Name Preview -->
                        <div id="roomNamePreview" class="hidden bg-purple-50 border-2 border-purple-200 rounded-lg p-4">
                            <p class="text-sm font-bold text-purple-800 mb-2">
                                <i class="fas fa-eye mr-1"></i>Preview Nama Kamar yang Akan Dibuat:
                            </p>
                            <div id="roomNameList" class="flex flex-wrap gap-2"></div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="tipe_kamar" class="block text-sm font-bold text-gray-700 mb-2">
                                    <i class="fas fa-certificate mr-1 text-purple-600"></i>Tipe Kamar
                                </label>
                                <select name="tipe_kamar" id="tipe_kamar" onchange="updateRoomTypeImage(this)" class="form-input mt-1 block w-full rounded-lg shadow-sm p-3 bg-white">
                                    <option value="Standard" data-foto="">Standard (Default)</option>
                                    <?php foreach($room_types_list as $type): ?>
                                        <option value="<?php echo htmlspecialchars($type['nama_tipe']); ?>" 
                                                data-foto="<?php echo htmlspecialchars($type['foto_tipe'] ?? ''); ?>"
                                                data-fasilitas="<?php echo htmlspecialchars($type['fasilitas'] ?? ''); ?>">
                                            <?php echo htmlspecialchars($type['nama_tipe']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div></div>
                        </div>
                        
                        <div>
                            <label for="harga_kamar" class="block text-sm font-bold text-gray-700 mb-2">
                                <i class="fas fa-money-bill-wave mr-1 text-purple-600"></i>Harga per Bulan
                            </label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 font-semibold">Rp</span>
                                <input type="number" name="harga" id="harga_kamar" required 
                                       placeholder="700000" 
                                       class="form-input mt-1 block w-full rounded-lg shadow-sm p-3 pl-12 bg-white">
                            </div>
                        </div>
                        
                        <div>
                            <label for="status_kamar" class="block text-sm font-bold text-gray-700 mb-2">
                                <i class="fas fa-info-circle mr-1 text-purple-600"></i>Status
                            </label>
                            <select name="status" id="status_kamar" class="form-input mt-1 block w-full rounded-lg shadow-sm p-3 bg-white">
                                <option value="tersedia">✅ Tersedia</option>
                                <option value="terisi">🔴 Terisi</option>
                                <option value="dipesan">⏳ Dipesan</option>
                            </select>
                        </div>

                        <div>
                            <label for="fasilitas_kamar" class="block text-sm font-bold text-gray-700 mb-2">
                                <i class="fas fa-list-check mr-1 text-purple-600"></i>Fasilitas Kamar
                            </label>
                            <textarea name="fasilitas" id="fasilitas_kamar" rows="3" 
                                   placeholder="Contoh: WiFi, AC, Kasur, Lemari, Meja Belajar, Kamar Mandi Dalam" 
                                   class="form-input mt-1 block w-full rounded-lg shadow-sm p-3 bg-white resize-none"></textarea>
                            <p class="text-xs text-gray-500 mt-1">Pisahkan dengan koma (,) untuk setiap fasilitas</p>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">
                                <i class="fas fa-camera mr-1 text-purple-600"></i>Foto Kamar
                            </label>
                            <div class="mt-2 flex items-center space-x-4">
                                <div id="kamarPhotoPreview" class="w-24 h-24 rounded-xl border-2 border-dashed border-purple-200 flex items-center justify-center overflow-hidden bg-white">
                                    <i class="fas fa-image text-purple-200 text-3xl"></i>
                                </div>
                                <div class="flex-1">
                                    <input type="file" name="foto_kamar" id="foto_kamar" accept="image/*" class="hidden" onchange="previewKamarPhoto(this)">
                                    <button type="button" onclick="document.getElementById('foto_kamar').click()" class="bg-white border-2 border-purple-500 text-purple-600 px-4 py-2 rounded-lg font-bold hover:bg-purple-50 transition-all flex items-center">
                                        <i class="fas fa-upload mr-2"></i>Pilih Foto
                                    </button>
                                    <p class="text-xs text-gray-500 mt-2">Format: JPG, PNG, WEBP (Maks. 2MB)</p>
                                </div>
                            </div>
                        </div>
                        
                        <div id="kamarFormError" class="hidden bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-lg text-sm">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            <span></span>
                        </div>
                        
                        <div class="flex space-x-4 pt-4">
                            <button type="submit" id="saveKamarButton" class="submit-btn flex-1 text-white py-3 px-6 rounded-xl font-bold shadow-lg">
                                <i class="fas fa-save mr-2"></i>Simpan
                            </button>
                            <button type="button" id="cancelEditButton" onclick="cancelEdit()" class="cancel-btn hidden flex-1 bg-gray-200 text-gray-700 py-3 px-6 rounded-xl font-bold hover:bg-gray-300 shadow-md">
                                <i class="fas fa-times mr-2"></i>Batal
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Enhanced Animations */
    @keyframes slideInRight {
        from {
            opacity: 0;
            transform: translateX(30px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }
    
    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    /* Kamar Card Enhancements */
    .kamar-card {
        animation: slideUp 0.3s ease-out;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid transparent;
        background: linear-gradient(135deg, #f9fafb 0%, #ffffff 100%);
    }
    
    .kamar-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 24px rgba(147, 51, 234, 0.15);
        border-color: rgba(147, 51, 234, 0.2);
    }
    
    /* Status Badge */
    .status-badge {
        animation: fadeIn 0.3s ease-out;
        transition: all 0.2s ease;
    }
    
    .status-badge.tersedia {
        background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
        color: #065f46;
        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.2);
    }
    
    .status-badge.dipesan {
        background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
        color: #92400e;
        box-shadow: 0 2px 8px rgba(245, 158, 11, 0.2);
    }
    
    .status-badge.terisi {
        background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
        color: #991b1b;
        box-shadow: 0 2px 8px rgba(239, 68, 68, 0.2);
    }
    
    /* Action Buttons */
    .action-btn {
        transition: all 0.2s ease;
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.5rem;
    }
    
    .action-btn:hover {
        transform: scale(1.1);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    
    .action-btn.edit {
        background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
        color: #1e40af;
    }
    
    .action-btn.delete {
        background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
        color: #991b1b;
    }
    
    /* Form Enhancements */
    .form-input {
        transition: all 0.3s ease;
        border: 2px solid #e5e7eb;
    }
    
    .form-input:focus {
        border-color: #9333ea;
        box-shadow: 0 0 0 3px rgba(147, 51, 234, 0.1);
        transform: scale(1.01);
        outline: none;
    }
    
    /* Submit Button */
    .submit-btn {
        background: linear-gradient(135deg, #9333ea 0%, #7c3aed 100%);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .submit-btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
        transition: left 0.5s;
    }
    
    .submit-btn:hover::before {
        left: 100%;
    }
    
    .submit-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(147, 51, 234, 0.3);
    }
    
    /* Cancel Button */
    .cancel-btn {
        transition: all 0.3s ease;
    }
    
    .cancel-btn:hover {
        background: #e5e7eb;
        transform: translateY(-2px);
    }
    
    /* Empty State */
    .empty-state {
        animation: fadeIn 0.5s ease-out;
    }
    
    /* Scrollbar */
    #kamarListContainer::-webkit-scrollbar {
        width: 8px;
    }
    
    #kamarListContainer::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 4px;
    }
    
    #kamarListContainer::-webkit-scrollbar-thumb {
        background: linear-gradient(135deg, #9333ea, #7c3aed);
        border-radius: 4px;
    }
    
    #kamarListContainer::-webkit-scrollbar-thumb:hover {
        background: linear-gradient(135deg, #7e22ce, #6d28d9);
    }
    
    /* Section Headers */
    .section-header {
        position: relative;
        padding-bottom: 0.5rem;
    }
    
    .section-header::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 60px;
        height: 3px;
        background: linear-gradient(90deg, #9333ea, #7c3aed);
        border-radius: 2px;
    }
    
    /* Center aligned section header for form */
    #formTitle.section-header::after {
        left: 50%;
        transform: translateX(-50%);
    }
    
    /* Price Display */
    .price-display {
        font-weight: 600;
        background: linear-gradient(135deg, #9333ea 0%, #7c3aed 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    /* Header Enhancement */
    .modal-header {
        background: linear-gradient(135deg, #faf5ff 0%, #f3e8ff 100%);
        border-bottom: 2px solid rgba(147, 51, 234, 0.1);
    }
    
    .modal-title {
        background: linear-gradient(135deg, #9333ea 0%, #7c3aed 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    /* Form Title Animation */
    #formTitle {
        transition: opacity 0.3s ease;
    }
    
    /* Input Focus Ring */
    .form-input:focus {
        animation: pulse 0.5s ease-out;
    }
    
    /* Number Input Styling */
    input[type="number"]::-webkit-inner-spin-button,
    input[type="number"]::-webkit-outer-spin-button {
        opacity: 1;
    }
    
    /* Select Dropdown */
    select.form-input {
        cursor: pointer;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%239333ea' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
        background-position: right 0.5rem center;
        background-repeat: no-repeat;
        background-size: 1.5em 1.5em;
        padding-right: 2.5rem;
        appearance: none;
    }
    
    /* Loading State */
    button:disabled {
        cursor: not-allowed;
        opacity: 0.6;
    }
    
    /* Card Fade Out Animation */
    .fade-out {
        animation: fadeOut 0.3s ease-out forwards;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .kamar-card {
            padding: 1rem;
        }
        
        .action-btn {
            width: 28px;
            height: 28px;
            font-size: 0.875rem;
        }
        
        #kamarListContainer {
            max-height: 300px;
        }
    }
</style>

<script>
    function updateRoomTypeImage(select) {
        const selectedOption = select.options[select.selectedIndex];
        const fotoUrl = selectedOption.getAttribute('data-foto');
        const fasilitasData = selectedOption.getAttribute('data-fasilitas');
        const preview = document.getElementById('kamarPhotoPreview');
        const fileInput = document.getElementById('foto_kamar');
        const fasilitasTextarea = document.getElementById('fasilitas_kamar');
        
        // Auto-fill facilities from room type
        if (fasilitasData && fasilitasData.trim() !== '') {
            // Only auto-fill if textarea is empty or in add mode
            const isEditMode = document.getElementById('kamarAction').value === 'edit';
            if (!isEditMode || !fasilitasTextarea.value || fasilitasTextarea.value.trim() === '') {
                fasilitasTextarea.value = fasilitasData;
                // Add visual feedback
                fasilitasTextarea.style.borderColor = '#3b82f6';
                fasilitasTextarea.style.backgroundColor = '#eff6ff';
                setTimeout(() => {
                    fasilitasTextarea.style.borderColor = '';
                    fasilitasTextarea.style.backgroundColor = '';
                }, 1000);
            }
        }

        // Only update if no custom file is currently selected (optional logic, but good UX)
        // Actually user wants to see the type image, so we show it properly.
        
        // If file input has files, we show the file preview (handled by onchange="previewKamarPhoto"). 
        // If we change type, we might want to override or show the type image as "base". 
        // Let's assume changing type shows the type image as preview if currently showing default placeholder.
        
        if (fotoUrl && fotoUrl.trim() !== '') {
            let src = fotoUrl;
            // Handle if it's local path ? likely standard URL from cloudinary or path
            // Our standard seems to be full URL or relative
            if (!src.startsWith('http')) {
                // It might be relative from process_kost, usually kept as full URL if from cloudinary
                // or local relative path.
                 // ../uploads if logic used simply
            }
            
            preview.innerHTML = `<img src="${src}" class="w-full h-full object-cover">`;
        } else {
             // If no type image, revert to placeholder ONLY if we don't have a specific room image loaded (editing)
             // This is tricky during Edit.
             // Simplest: If no URL, show placeholder.
             if (!fileInput.files.length) {
                 // Check if we are editing and have an existing image? 
                 // The easiest is just: if no URL, show placeholder.
                 preview.innerHTML = `<i class="fas fa-image text-purple-200 text-3xl"></i>`;
             }
        }
    }

    
    // Hook into existing editKamar to trigger this update if useful, 
    // or editKamar handles its own image loading.
    // editKamar calls: 
    // document.getElementById('kamarPhotoPreview').innerHTML = `<img src="${imgSrc}"...`
    // So editKamar takes precedence, which is correct.
    
    // But specific logic: if I edit a room, it has a photo. I change type -> show type photo?
    // User request: "foto gambar di tampilkan di form ini dan sesuai dengn tipe kamar yang dipilih"
    // So YES, selecting type should update preview.
    
    /**
     * Generate room names based on base name and quantity
     */
    function generateRoomNames(baseName, quantity) {
        if (!baseName || quantity < 1) return [];
        
        const names = [];
        baseName = baseName.trim();
        
        // Check if base name is purely numeric (e.g., "101")
        if (/^\d+$/.test(baseName)) {
            const baseNum = parseInt(baseName);
            for (let i = 0; i < quantity; i++) {
                names.push(String(baseNum + i));
            }
        }
        // Check if base name is a single letter (e.g., "A")
        else if (/^[A-Za-z]$/.test(baseName)) {
            for (let i = 1; i <= quantity; i++) {
                names.push(`${baseName}-${i}`);
            }
        }
        // Otherwise treat as text (e.g., "Deluxe", "VIP")
        else {
            for (let i = 1; i <= quantity; i++) {
                names.push(`${baseName} ${i}`);
            }
        }
        
        return names;
    }
    
    /**
     * Update room name preview when quantity or base name changes
     */
    function updateRoomNamePreview() {
        const baseName = document.getElementById('nama_kamar').value;
        const quantity = parseInt(document.getElementById('jumlah_kamar').value) || 1;
        const previewContainer = document.getElementById('roomNamePreview');
        const nameList = document.getElementById('roomNameList');
        
        // Only show preview if quantity > 1
        if (quantity > 1 && baseName) {
            const names = generateRoomNames(baseName, quantity);
            
            // Show max 20 names in preview to avoid overwhelming UI
            const displayNames = names.slice(0, 20);
            const hasMore = names.length > 20;
            
            nameList.innerHTML = displayNames.map(name => 
                `<span class="bg-white border-2 border-purple-300 text-purple-700 px-3 py-1 rounded-lg text-sm font-semibold shadow-sm">
                    ${name}
                </span>`
            ).join('');
            
            if (hasMore) {
                nameList.innerHTML += `<span class="text-purple-600 text-sm font-semibold px-3 py-1">
                    ... dan ${names.length - 20} lainnya
                </span>`;
            }
            
            previewContainer.classList.remove('hidden');
        } else {
            previewContainer.classList.add('hidden');
        }
    }
    
    /**
     * Toggle quantity field visibility based on mode (add/edit)
     */
    function toggleQuantityField(mode) {
        const quantityContainer = document.getElementById('quantityFieldContainer');
        const quantityInput = document.getElementById('jumlah_kamar');
        const previewContainer = document.getElementById('roomNamePreview');
        
        if (mode === 'edit') {
            // Hide quantity field in edit mode
            quantityContainer.classList.add('hidden');
            previewContainer.classList.add('hidden');
            quantityInput.value = 1;
        } else {
            // Show quantity field in add mode
            quantityContainer.classList.remove('hidden');
        }
    }

</script>

<?php
$stmt_kamar->close();
$conn->close();
?>