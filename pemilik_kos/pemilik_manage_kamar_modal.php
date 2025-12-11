<?php
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
$stmt_kamar = $conn->prepare("SELECT id_kamar, nama_kamar, harga, status FROM kamar WHERE id_kost = ? ORDER BY nama_kamar ASC");
$stmt_kamar->bind_param("i", $id_kost);
$stmt_kamar->execute();
$res_kamar = $stmt_kamar->get_result();
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
                                    <div class="w-12 h-12 bg-gradient-to-br from-purple-100 to-indigo-100 rounded-xl flex items-center justify-center">
                                        <i class="fas fa-bed text-purple-600 text-xl"></i>
                                    </div>
                                    <div>
                                        <p class="font-bold text-gray-800 text-lg"><?php echo htmlspecialchars($kamar['nama_kamar']); ?></p>
                                        <p class="text-sm price-display">Rp <?php echo number_format($kamar['harga'], 0, ',', '.'); ?>/bulan</p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <span class="status-badge <?php echo $kamar['status']; ?> px-3 py-1 text-xs font-bold rounded-full">
                                        <i class="fas fa-circle mr-1 text-[10px]"></i>
                                        <?php echo ucfirst(htmlspecialchars($kamar['status'])); ?>
                                    </span>
                                    <button onclick="editKamar(<?php echo $kamar['id_kamar']; ?>, '<?php echo htmlspecialchars($kamar['nama_kamar']); ?>', <?php echo $kamar['harga']; ?>, '<?php echo $kamar['status']; ?>')" class="action-btn edit" title="Edit Kamar">
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
                        
                        <div>
                            <label for="nama_kamar" class="block text-sm font-bold text-gray-700 mb-2">
                                <i class="fas fa-tag mr-1 text-purple-600"></i>Nama/Nomor Kamar
                            </label>
                            <input type="text" name="nama_kamar" id="nama_kamar" required 
                                   placeholder="Contoh: 101, Kamar A" 
                                   class="form-input mt-1 block w-full rounded-lg shadow-sm p-3 bg-white">
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

<?php
$stmt_kamar->close();
$conn->close();
?>