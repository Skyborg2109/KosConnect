<?php
session_start();
include '../config/db.php';

// Autentikasi admin
if (!isset($_SESSION['user_logged_in']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die("Akses ditolak. Anda harus login sebagai admin.");
}

// Ambil semua pengguna kecuali admin yang sedang login
$current_admin_id = $_SESSION['user_id'];
$sql = "SELECT id_user, nama_lengkap, email, role, is_active, is_blocked, created_at FROM user WHERE id_user != ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $current_admin_id);
$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>

<div class="bg-white p-8 rounded-xl shadow-lg">
    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm font-medium">Total Pengguna</p>
                    <p class="text-2xl font-bold"><?php echo count($users); ?></p>
                </div>
                <i class="fas fa-users text-3xl text-blue-200"></i>
            </div>
        </div>
        
        <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-xl p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm font-medium">Penyewa</p>
                    <p class="text-2xl font-bold"><?php echo count(array_filter($users, function($u) { return $u['role'] === 'penyewa'; })); ?></p>
                </div>
                <i class="fas fa-home text-3xl text-green-200"></i>
            </div>
        </div>
        
        <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm font-medium">Pemilik Kos</p>
                    <p class="text-2xl font-bold"><?php echo count(array_filter($users, function($u) { return $u['role'] === 'pemilik'; })); ?></p>
                </div>
                <i class="fas fa-building text-3xl text-purple-200"></i>
            </div>
        </div>
        
        <div class="bg-gradient-to-r from-red-500 to-red-600 rounded-xl p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-red-100 text-sm font-medium">Diblokir</p>
                    <p class="text-2xl font-bold"><?php echo count(array_filter($users, function($u) { return $u['is_blocked'] == 1; })); ?></p>
                </div>
                <i class="fas fa-ban text-3xl text-red-200"></i>
            </div>
        </div>
    </div>

    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800 flex items-center">
            <i class="fas fa-users mr-3 text-blue-600"></i>
            Manajemen Data Pengguna
        </h2>
        <div class="flex space-x-3">
            <button onclick="exportUsers()" class="bg-green-600 text-white px-4 py-3 rounded-xl hover:bg-green-700 transition-all shadow-lg hover:shadow-xl font-semibold">
                <i class="fas fa-download mr-2"></i>Export CSV
            </button>
            <button onclick="addUser()" class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-3 rounded-xl hover:from-blue-700 hover:to-blue-800 transition-all shadow-lg hover:shadow-xl font-semibold">
                <i class="fas fa-plus mr-2"></i>Tambah Pengguna Baru
            </button>
        </div>
    </div>

    <!-- Search and Filter -->
    <div class="bg-gray-50 p-6 rounded-xl mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Cari Pengguna</label>
                <input type="text" id="searchInput" placeholder="Nama atau email..." 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Filter Role</label>
                <select id="roleFilter" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">Semua Role</option>
                    <option value="penyewa">Penyewa</option>
                    <option value="pemilik">Pemilik Kos</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Filter Status</label>
                <select id="statusFilter" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">Semua Status</option>
                    <option value="active">Aktif</option>
                    <option value="inactive">Belum Aktif</option>
                    <option value="blocked">Diblokir</option>
                </select>
            </div>
            <div class="flex items-end">
                <button onclick="resetFilters()" class="w-full bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors">
                    <i class="fas fa-undo mr-2"></i>Reset
                </button>
            </div>
        </div>
    </div>

    <!-- Bulk Actions -->
    <div id="bulkActions" class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6 rounded-lg hidden">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                <span id="selectedCount" class="text-blue-700 font-medium">0 pengguna dipilih</span>
            </div>
            <div class="flex space-x-2">
                <button onclick="bulkAction('block')" class="bg-yellow-500 text-white px-4 py-2 rounded-lg hover:bg-yellow-600 transition-colors text-sm">
                    <i class="fas fa-ban mr-1"></i>Blokir
                </button>
                <button onclick="bulkAction('unblock')" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition-colors text-sm">
                    <i class="fas fa-check mr-1"></i>Aktifkan
                </button>
                <button onclick="bulkAction('delete')" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition-colors text-sm">
                    <i class="fas fa-trash mr-1"></i>Hapus
                </button>
            </div>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 bg-white rounded-xl shadow-lg overflow-hidden">
            <thead class="bg-gradient-to-r from-blue-600 to-purple-600">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">
                        <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    </th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">
                        <i class="fas fa-user mr-2"></i>Nama Lengkap
                    </th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">
                        <i class="fas fa-envelope mr-2"></i>Email
                    </th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">
                        <i class="fas fa-user-tag mr-2"></i>Role
                    </th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">
                        <i class="fas fa-check-circle mr-2"></i>Status Aktivasi
                    </th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">
                        <i class="fas fa-shield-alt mr-2"></i>Status Akun
                    </th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">
                        <i class="fas fa-calendar mr-2"></i>Dibuat
                    </th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">
                        <i class="fas fa-cogs mr-2"></i>Aksi
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($users as $index => $user): ?>
                <tr id="user-row-<?php echo $user['id_user']; ?>" class="hover:bg-gray-50 transition-colors user-row" 
                    data-role="<?php echo $user['role']; ?>" 
                    data-status="<?php echo $user['is_blocked'] ? 'blocked' : ($user['is_active'] ? 'active' : 'inactive'); ?>">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <input type="checkbox" class="user-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500" 
                               value="<?php echo $user['id_user']; ?>" 
                               data-name="<?php echo htmlspecialchars($user['nama_lengkap']); ?>">
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($user['nama_lengkap']); ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo htmlspecialchars($user['email']); ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold 
                            <?php 
                            if ($user['role'] === 'admin') echo 'bg-purple-100 text-purple-800';
                            elseif ($user['role'] === 'pemilik') echo 'bg-blue-100 text-blue-800';
                            else echo 'bg-green-100 text-green-800';
                            ?>">
                            <?php echo htmlspecialchars(ucfirst($user['role'])); ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <?php if ($user['is_active']): ?>
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800 flex items-center w-fit">
                                <i class="fas fa-check-circle mr-1"></i>Aktif
                            </span>
                        <?php else: ?>
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800 flex items-center w-fit">
                                <i class="fas fa-clock mr-1"></i>Belum Aktif
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold status-text">
                        <?php if ($user['is_blocked']): ?>
                            <span class="text-red-600 flex items-center">
                                <i class="fas fa-ban mr-1"></i>Diblokir
                            </span>
                        <?php else: ?>
                            <span class="text-green-600 flex items-center">
                                <i class="fas fa-check mr-1"></i>Aktif
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?php echo date('d/m/Y', strtotime($user['created_at'])); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div class="flex items-center space-x-3">
                            <?php if ($user['is_blocked']): ?>
                                <button onclick="toggleBlock(<?php echo $user['id_user']; ?>, 0, '<?php echo htmlspecialchars(addslashes($user['nama_lengkap'])); ?>')" 
                                        class="bg-green-500 text-white px-3 py-2 rounded-lg hover:bg-green-600 transition-colors text-xs font-semibold shadow-md hover:shadow-lg"
                                        title="Aktifkan Akun">
                                    <i class="fas fa-check mr-1"></i>Aktifkan
                                </button>
                            <?php else: ?>
                                <button onclick="toggleBlock(<?php echo $user['id_user']; ?>, 1, '<?php echo htmlspecialchars(addslashes($user['nama_lengkap'])); ?>')" 
                                        class="bg-yellow-500 text-white px-3 py-2 rounded-lg hover:bg-yellow-600 transition-colors text-xs font-semibold shadow-md hover:shadow-lg"
                                        title="Blokir Akun">
                                    <i class="fas fa-ban mr-1"></i>Blokir
                                </button>
                            <?php endif; ?>
                            <button onclick="editUser(<?php echo $user['id_user']; ?>)" 
                                    class="bg-blue-500 text-white px-3 py-2 rounded-lg hover:bg-blue-600 transition-colors text-xs font-semibold shadow-md hover:shadow-lg"
                                    title="Edit Pengguna">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="deleteUser(<?php echo $user['id_user']; ?>, '<?php echo htmlspecialchars(addslashes($user['nama_lengkap'])); ?>')" 
                                    class="bg-red-500 text-white px-3 py-2 rounded-lg hover:bg-red-600 transition-colors text-xs font-semibold shadow-md hover:shadow-lg"
                                    title="Hapus Pengguna">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Edit Pengguna -->
<div id="editUserModal" class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto" style="display: none;" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity backdrop-blur-sm" aria-hidden="true" onclick="closeModal()"></div>
    <div class="relative w-full max-w-md mx-auto p-4 my-auto overflow-hidden text-left transition-all transform bg-white shadow-2xl rounded-2xl">
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 px-6 py-4 -m-6 -m-6 mb-4 rounded-t-2xl">
                <h3 class="text-lg leading-6 font-bold text-white flex items-center" id="modal-title">
                    <i class="fas fa-user-plus mr-2"></i>
                    Tambah Pengguna Baru
                </h3>
            </div>
            <form id="editUserForm" class="bg-white">
                <div class="px-6 pt-5 pb-4">
                    <div class="space-y-6">
                        <input type="hidden" id="edit-user-id" name="user_id">
                        
                        <div class="relative">
                            <label for="edit-nama-lengkap" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-user mr-1 text-blue-600"></i>Nama Lengkap
                            </label>
                            <input type="text" name="nama_lengkap" id="edit-nama-lengkap" required 
                                   class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                   placeholder="Masukkan nama lengkap">
                        </div>
                        
                        <div class="relative">
                            <label for="edit-email" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-envelope mr-1 text-blue-600"></i>Email
                            </label>
                            <input type="email" name="email" id="edit-email" required 
                                   class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                   placeholder="Masukkan alamat email">
                        </div>
                        
                        <div class="relative">
                            <label for="edit-role" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-user-tag mr-1 text-blue-600"></i>Role
                            </label>
                            <select id="edit-role" name="role" required 
                                    class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all bg-white">
                                <option value="">Pilih Role</option>
                                <option value="penyewa">🏠 Penyewa</option>
                                <option value="pemilik">🏢 Pemilik Kos</option>
                                <option value="admin">⚙️ Admin</option>
                            </select>
                        </div>
                        
                        <div id="password-fields-container" class="relative">
                            <label for="edit-password" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-lock mr-1 text-blue-600"></i>Password
                            </label>
                            <div class="relative">
                                <input type="password" name="password" id="edit-password" 
                                       class="w-full px-4 py-3 pr-12 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                       placeholder="Minimal 6 karakter">
                                <button type="button" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-blue-600 transition-colors" onclick="togglePasswordVisibility()">
                                    <i class="fas fa-eye text-lg" id="togglePasswordIcon"></i>
                                </button>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Biarkan kosong jika tidak ingin mengubah password (hanya untuk pengguna baru)</p>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-6 py-4 flex flex-row-reverse gap-3">
                    <button type="submit" class="inline-flex justify-center items-center rounded-xl border border-transparent shadow-sm px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-base font-semibold text-white hover:from-blue-700 hover:to-purple-700 focus:outline-none transition-all">
                        <i class="fas fa-save mr-2"></i>Simpan
                    </button>
                    <button type="button" onclick="closeModal()" class="inline-flex justify-center items-center rounded-xl border border-gray-300 shadow-sm px-6 py-3 bg-white text-base font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none transition-all">
                        <i class="fas fa-times mr-2"></i>Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Make functions globally available for other scripts if needed
window.toggleBlock = toggleBlock;
window.editUser = editUser;
window.deleteUser = deleteUser;
window.addUser = addUser;
window.closeModal = closeModal;
window.showModal = showModal;
window.saveUserChanges = saveUserChanges;
window.exportUsers = exportUsers;
window.bulkAction = bulkAction;
window.resetFilters = resetFilters;
window.filterUsers = filterUsers;
window.togglePasswordVisibility = togglePasswordVisibility;

// Toggle password visibility
function togglePasswordVisibility() {
    const passwordInput = document.getElementById('edit-password');
    const toggleIcon = document.getElementById('togglePasswordIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}

// Main initialization logic when this script is loaded
document.addEventListener('DOMContentLoaded', main);
// Since content is loaded via fetch, DOMContentLoaded might not fire.
// We run main() directly as a fallback.
main(); 

function main() {
    // Attach event listener to form immediately
    attachFormListener();
    initializeSearchAndFilter();
    
    // Use MutationObserver to re-attach listener if form is recreated
    const observer = new MutationObserver(() => {
        attachFormListener();
    });
    
    observer.observe(document.body, { childList: true, subtree: true });
}

function attachFormListener() {
    const form = document.getElementById('editUserForm');
    if (form) {
        // Remove old listener to prevent duplicates
        form.removeEventListener('submit', saveUserChanges);
        // Attach new listener
        form.addEventListener('submit', saveUserChanges);
    }
}


function initializeSearchAndFilter() {
    const searchInput = document.getElementById('searchInput');
    const roleFilter = document.getElementById('roleFilter');
    const statusFilter = document.getElementById('statusFilter');
    
    if(searchInput) searchInput.addEventListener('input', filterUsers);
    if(roleFilter) roleFilter.addEventListener('change', filterUsers);
    if(statusFilter) statusFilter.addEventListener('change', filterUsers);
}

function filterUsers() {
    // Filter implementation - search and filter users by name, email, role, status
    const searchTerm = (document.getElementById('searchInput')?.value || '').toLowerCase();
    const roleFilter = document.getElementById('roleFilter')?.value || '';
    const statusFilter = document.getElementById('statusFilter')?.value || '';
    
    const rows = document.querySelectorAll('tbody tr');
    let visibleCount = 0;
    
    rows.forEach(row => {
        const name = (row.cells[1]?.textContent || '').toLowerCase();
        const email = (row.cells[2]?.textContent || '').toLowerCase();
        const role = row.getAttribute('data-role') || '';
        const status = row.getAttribute('data-status') || '';
        
        const matchesSearch = name.includes(searchTerm) || email.includes(searchTerm);
        const matchesRole = !roleFilter || role === roleFilter;
        const matchesStatus = !statusFilter || status === statusFilter;
        
        if (matchesSearch && matchesRole && matchesStatus) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
}

function resetFilters() {
    // Reset all filter inputs
    const searchInput = document.getElementById('searchInput');
    const roleFilter = document.getElementById('roleFilter');
    const statusFilter = document.getElementById('statusFilter');
    
    if (searchInput) searchInput.value = '';
    if (roleFilter) roleFilter.value = '';
    if (statusFilter) statusFilter.value = '';
    
    // Show all rows
    filterUsers();
}

function exportUsers() {
    // Export users to CSV
    const rows = document.querySelectorAll('tbody tr:not([style*="display: none"])');
    if (rows.length === 0) {
        Swal.fire('Tidak Ada Data', 'Tidak ada pengguna untuk diekspor.', 'warning');
        return;
    }
    
    let csvContent = '';
    csvContent += 'Nama Lengkap,Email,Role,Status Aktivasi,Status Akun,Tgl Dibuat\n';
    
    rows.forEach(row => {
        const cells = row.cells;
        const name = cells[1]?.textContent.trim() || '';
        const email = cells[2]?.textContent.trim() || '';
        const role = cells[3]?.textContent.trim() || '';
        const activation = cells[4]?.textContent.trim() || '';
        const accountStatus = cells[5]?.textContent.trim() || '';
        const date = cells[6]?.textContent.trim() || '';
        
        const escapeCSV = (str) => '"' + (str || '').replace(/"/g, '""') + '"';
        csvContent += `${escapeCSV(name)},${escapeCSV(email)},${escapeCSV(role)},${escapeCSV(activation)},${escapeCSV(accountStatus)},${escapeCSV(date)}\n`;
    });
    
    // Use Blob for secure download
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    if (link.download !== undefined) {
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', `users_${new Date().toISOString().split('T')[0]}.csv`);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    }
    
    Swal.fire('Berhasil!', 'Data pengguna telah diekspor ke CSV.', 'success');
}

function bulkAction(action) {
    // Perform bulk actions on selected users
    const checkboxes = document.querySelectorAll('.user-checkbox:checked');
    if (checkboxes.length === 0) {
        Swal.fire('Tidak Ada Pilihan', 'Silakan pilih minimal satu pengguna.', 'warning');
        return;
    }
    
    const userIds = Array.from(checkboxes).map(cb => cb.value);
    const userNames = Array.from(checkboxes).map(cb => cb.getAttribute('data-name')).join(', ');
    
    let confirmText = '';
    let actionName = '';
    
    if (action === 'block') {
        confirmText = `Yakin memblokir ${userIds.length} pengguna ini?`;
        actionName = 'blokir';
    } else if (action === 'unblock') {
        confirmText = `Yakin mengaktifkan ${userIds.length} pengguna ini?`;
        actionName = 'aktifkan';
    } else if (action === 'delete') {
        confirmText = `Yakin menghapus ${userIds.length} pengguna ini secara permanen?`;
        actionName = 'hapus';
    }
    
    Swal.fire({
        title: 'Konfirmasi',
        text: confirmText,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: action === 'delete' ? '#d33' : '#3085d6',
        cancelButtonColor: '#6c757d',
        confirmButtonText: `Ya, ${actionName}!`,
        cancelButtonText: 'Batal'
    }).then(result => {
        if (result.isConfirmed) {
            executeBulkAction(action, userIds);
        }
    });
}

function executeBulkAction(action, userIds) {
    // Execute the bulk action via API
    const data = {
        action: action,
        user_ids: userIds
    };
    
    // For now, show placeholder - implement actual API call if needed
    Swal.fire('Proses Bulk', `Menjalankan aksi ${action} untuk ${userIds.length} pengguna...`, 'info');
}


function closeModal() {
    const modal = document.getElementById('editUserModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

function showModal() {
    const modal = document.getElementById('editUserModal');
    if (modal) {
        modal.style.display = 'flex';
    }
}

function addUser() {
    const form = document.getElementById('editUserForm');
    form.reset();
    document.getElementById('modal-title').innerHTML = '<i class="fas fa-user-plus mr-2"></i> Tambah Pengguna Baru';
    document.getElementById('edit-user-id').value = '';
    
    const emailField = document.getElementById('edit-email');
    emailField.disabled = false;
    emailField.classList.remove('bg-gray-100', 'cursor-not-allowed');

    const passwordContainer = document.getElementById('password-fields-container');
    passwordContainer.style.display = 'block';
    passwordContainer.querySelector('input').required = true;
    passwordContainer.querySelector('p').textContent = 'Password wajib diisi untuk pengguna baru (minimal 6 karakter).';
    
    showModal();
}

function editUser(userId) {
    fetch(`../admin/process_user.php?action=get_details&user_id=${userId}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const form = document.getElementById('editUserForm');
            form.reset();
            
            document.getElementById('edit-user-id').value = data.data.id_user;
            document.getElementById('edit-nama-lengkap').value = data.data.nama_lengkap;
            document.getElementById('edit-role').value = data.data.role;

            const emailField = document.getElementById('edit-email');
            emailField.value = data.data.email;
            emailField.disabled = true;
            emailField.classList.add('bg-gray-100', 'cursor-not-allowed');
            
            document.getElementById('modal-title').innerHTML = '<i class="fas fa-edit mr-2"></i> Edit Data Pengguna';
            
            const passwordContainer = document.getElementById('password-fields-container');
            passwordContainer.style.display = 'block';
            const passwordInput = passwordContainer.querySelector('input');
            passwordInput.required = false;
            passwordInput.placeholder = "Isi jika ingin mengubah password";
            passwordContainer.querySelector('p').textContent = 'Biarkan kosong jika tidak ingin mengubah password.';
            
            showModal();
        } else {
            Swal.fire('Gagal', data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire('Error', 'Gagal mengambil data pengguna.', 'error');
    });
}

function saveUserChanges(event) {
    event.preventDefault();
    
    const form = document.getElementById('editUserForm');
    const formData = new FormData(form);
    const userId = formData.get('user_id');
    
    // Get values from form, considering disabled fields
    const namaLengkapInput = document.getElementById('edit-nama-lengkap');
    const emailInput = document.getElementById('edit-email');
    const roleInput = document.getElementById('edit-role');
    const passwordInput = document.getElementById('edit-password');
    
    // Safe null checking before calling .trim()
    const namaLengkap = (namaLengkapInput?.value || '').trim();
    const email = (emailInput?.value || '').trim();
    const role = (roleInput?.value || '').trim();
    const password = (passwordInput?.value || '').trim();

    if (!namaLengkap || !email || !role) {
        Swal.fire('Validasi Gagal', 'Nama, Email, dan Role wajib diisi.', 'warning');
        return;
    }
    
    if (!userId && (!password || password.length < 6)) {
        Swal.fire('Validasi Gagal', 'Password minimal 6 karakter untuk pengguna baru.', 'warning');
        return;
    }
    
    if (userId && password && password.length < 6) {
        Swal.fire('Validasi Gagal', 'Password baru minimal harus 6 karakter.', 'warning');
        return;
    }

    // Prepare data to send
    const dataToSend = new FormData();
    dataToSend.append('action', userId ? 'update' : 'add');
    dataToSend.append('user_id', userId || '');
    dataToSend.append('nama_lengkap', namaLengkap);
    dataToSend.append('email', email);
    dataToSend.append('role', role);
    if (password) {
        dataToSend.append('password', password);
    }

    const submitBtn = form.querySelector('button[type="submit"]');
    const originalBtnHtml = submitBtn.innerHTML;
    submitBtn.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i>Menyimpan...`;
    submitBtn.disabled = true;

    fetch('../admin/process_user.php', {
        method: 'POST',
        body: dataToSend
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeModal();
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: data.message,
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                if (typeof window.loadContent === 'function') {
                    // Reload the user management module to show changes
                    window.loadContent('admin_manage_users');
                } else {
                    location.reload(); // Fallback
                }
            });
        } else {
            Swal.fire('Gagal!', data.message || 'Terjadi kesalahan yang tidak diketahui.', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire('Error', 'Terjadi kesalahan jaringan. Silakan coba lagi.', 'error');
    })
    .finally(() => {
        submitBtn.innerHTML = originalBtnHtml;
        submitBtn.disabled = false;
    });
}

function toggleBlock(userId, newStatus, userName) {
    const actionText = newStatus === 1 ? 'memblokir' : 'mengaktifkan';
    Swal.fire({
        title: `Yakin ingin ${actionText} akun "${userName}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: newStatus === 1 ? '#d33' : '#3085d6',
        confirmButtonText: `Ya, ${actionText}!`,
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('../admin/toggle_user_block.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: userId, is_blocked: newStatus })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Berhasil!', data.message, 'success');
                    window.loadContent('admin_manage_users');
                } else {
                    Swal.fire('Gagal!', data.message, 'error');
                }
            });
        }
    });
}

function deleteUser(userId, userName) {
    Swal.fire({
        title: `Yakin ingin menghapus "${userName}"?`,
        text: "Aksi ini tidak dapat dibatalkan!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Ya, hapus permanen!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('user_id', userId);
            fetch('../admin/process_user.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Dihapus!', data.message, 'success');
                    window.loadContent('admin_manage_users');
                } else {
                    Swal.fire('Gagal!', data.message, 'error');
                }
            });
        }
    });
}
</script>