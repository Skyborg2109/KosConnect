<!-- ======================================================= -->
<!-- MODAL PROFIL SAYA (Redesigned) -->
<!-- ======================================================= -->
<div id="profileModal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <!-- Backdrop with blur -->
    <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity opacity-100" onclick="closeProfileModal()"></div>

    <!-- Modal Content -->
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-4xl flex flex-col max-h-[90vh]">
                
                <!-- Header -->
                <div class="bg-gradient-to-r from-purple-600 to-indigo-600 px-6 py-4 flex justify-between items-center shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="bg-white/20 p-2 rounded-lg backdrop-blur-sm">
                            <i class="fas fa-user-circle text-white text-xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-white tracking-wide" id="modal-title">Pengaturan Profil</h3>
                    </div>
                    <button type="button" onclick="closeProfileModal()" class="text-white/80 hover:text-white hover:bg-white/10 rounded-full p-2 transition-colors">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Body (Scrollable) -->
                <div class="px-6 py-6 sm:px-8 space-y-8 overflow-y-auto custom-scrollbar bg-gray-50/50">
                    
                    <!-- Section 1: Foto Profil -->
                    <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 relative overflow-hidden group">
                        <div class="absolute top-0 right-0 p-4 opacity-50 text-gray-300">
                             <i class="fas fa-camera text-6xl transform rotate-12 -translate-y-2 translate-x-2"></i>
                        </div>
                        
                        <h4 class="text-lg font-bold text-gray-800 mb-6 flex items-center relative z-10">
                            <span class="w-1 h-6 bg-purple-500 rounded-full mr-3"></span>
                            Foto Profil
                        </h4>
                        
                        <form id="photoUpdateForm" onsubmit="savePhoto(event)" class="relative z-10">
                            <input type="hidden" name="action" value="update_photo">
                            <div class="flex flex-col sm:flex-row items-center gap-6 sm:gap-8">
                                <div class="relative shrink-0">
                                    <div class="h-28 w-28 sm:h-32 sm:w-32 rounded-full ring-4 ring-purple-100 overflow-hidden shadow-lg group-hover:ring-purple-200 transition-all">
                                        <img id="photoPreview" 
                                             src="<?php 
                                                 $photo = htmlspecialchars($userPhoto ?? '');
                                                 echo (strpos($photo, 'http') === 0) ? $photo : ($photo ? '../uploads/profiles/' . $photo : 'https://ui-avatars.com/api/?name=' . urlencode($userName) . '&background=random'); 
                                             ?>" 
                                             alt="Preview" 
                                             onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($userName); ?>&background=random';"
                                             class="h-full w-full object-cover">
                                    </div>
                                    <label for="foto_profil" class="absolute bottom-1 right-1 bg-purple-600 text-white p-2 rounded-full cursor-pointer hover:bg-purple-700 shadow-md transition-transform hover:scale-110" title="Ganti Foto">
                                        <i class="fas fa-camera text-sm"></i>
                                    </label>
                                </div>
                                
                                <div class="flex-1 w-full text-center sm:text-left">
                                    <h5 class="font-medium text-gray-900 mb-2">Unggah Foto Baru</h5>
                                    <p class="text-sm text-gray-500 mb-4 leading-relaxed">Format yang didukung: JPG, PNG, atau GIF. Ukuran maksimal 2MB. Gunakan foto persegi untuk hasil terbaik.</p>
                                    
                                    <div class="flex flex-col sm:flex-row items-center gap-3">
                                        <input type="file" name="foto_profil" id="foto_profil" class="hidden" onchange="previewPhoto(event)" accept="image/*">
                                        <label for="foto_profil" class="w-full sm:w-auto px-5 py-2.5 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg cursor-pointer hover:bg-gray-50 hover:text-purple-600 transition-colors shadow-sm text-center">
                                            <i class="fas fa-folder-open mr-2"></i>Pilih File
                                        </label>
                                        <button type="submit" id="savePhotoButton" class="w-full sm:w-auto px-5 py-2.5 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 shadow-sm shadow-purple-200 transition-all text-center">
                                            <i class="fas fa-cloud-upload-alt mr-2"></i>Simpan Foto
                                        </button>
                                    </div>
                                    <div id="photoUpdateError" class="hidden mt-3 p-3 bg-red-50 text-red-600 text-sm rounded-lg border border-red-100 flex items-center">
                                        <i class="fas fa-exclamation-circle mr-2"></i>
                                        <span class="error-text"></span>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 sm:gap-8">
                        <!-- Section 2: Biodata -->
                        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 flex flex-col h-full">
                            <h4 class="text-lg font-bold text-gray-800 mb-6 flex items-center">
                                <span class="w-1 h-6 bg-blue-500 rounded-full mr-3"></span>
                                Informasi Pribadi
                            </h4>
                            
                            <form id="profileUpdateForm" onsubmit="saveProfile(event)" class="space-y-5 flex-1 flex flex-col">
                                <input type="hidden" name="action" value="update_profile">
                                
                                <div class="space-y-1">
                                    <label for="profile_fullname" class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="fas fa-user text-gray-400"></i>
                                        </div>
                                        <input type="text" name="fullname" id="profile_fullname" value="<?php echo $userName; ?>" required 
                                            class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                    </div>
                                </div>
                                
                                <div class="space-y-1">
                                    <label for="profile_email" class="block text-sm font-medium text-gray-700">Alamat Email</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="fas fa-envelope text-gray-400"></i>
                                        </div>
                                        <input type="email" id="profile_email" value="<?php echo htmlspecialchars($userEmail); ?>" disabled 
                                            class="block w-full pl-10 pr-3 py-2.5 border border-gray-200 bg-gray-50 rounded-lg text-sm text-gray-500 cursor-not-allowed">
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <i class="fas fa-lock text-gray-400 text-xs" title="Tidak dapat diubah"></i>
                                        </div>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">Email tidak dapat diubah untuk alasan keamanan.</p>
                                </div>

                                <div id="profileUpdateError" class="hidden p-3 bg-red-50 text-red-600 text-sm rounded-lg border border-red-100 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-2"></i>
                                    <span class="error-text"></span>
                                </div>

                                <div class="mt-auto pt-4 text-right">
                                    <button type="submit" id="saveProfileButton" class="w-full sm:w-auto px-6 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 shadow-sm shadow-blue-200 transition-colors">
                                        <i class="fas fa-save mr-2"></i>Simpan Perubahan
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Section 3: Keamanan -->
                        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 flex flex-col h-full">
                            <h4 class="text-lg font-bold text-gray-800 mb-6 flex items-center">
                                <span class="w-1 h-6 bg-red-500 rounded-full mr-3"></span>
                                Keamanan Akun
                            </h4>
                            
                            <form id="passwordUpdateForm" onsubmit="savePassword(event)" class="space-y-4 flex-1 flex flex-col">
                                <input type="hidden" name="action" value="update_password">
                                
                                <div class="space-y-1">
                                    <label for="old_password" class="block text-sm font-medium text-gray-700">Password Lama</label>
                                    <div class="relative">
                                        <input type="password" name="old_password" id="old_password" required 
                                            class="block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-colors">
                                    </div>
                                </div>

                                <div class="space-y-1">
                                    <label for="new_password" class="block text-sm font-medium text-gray-700">Password Baru</label>
                                    <div class="relative">
                                        <input type="password" name="new_password" id="new_password" required 
                                            class="block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-colors">
                                    </div>
                                </div>

                                <div class="space-y-1">
                                    <label for="confirm_password" class="block text-sm font-medium text-gray-700">Konfirmasi Password</label>
                                    <div class="relative">
                                        <input type="password" name="confirm_password" id="confirm_password" required 
                                            class="block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-colors">
                                    </div>
                                </div>

                                <div id="passwordUpdateError" class="hidden p-3 bg-red-50 text-red-600 text-sm rounded-lg border border-red-100 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-2"></i>
                                    <span class="error-text"></span>
                                </div>

                                <div class="mt-auto pt-4 text-right">
                                    <button type="submit" id="savePasswordButton" class="w-full sm:w-auto px-6 py-2.5 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 shadow-sm shadow-red-200 transition-colors">
                                        <i class="fas fa-lock mr-2"></i>Update Password
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Section 4: Device Management Banner -->
                    <div class="bg-gradient-to-r from-gray-900 to-gray-800 rounded-xl p-6 shadow-lg text-white flex flex-col md:flex-row items-center justify-between gap-6 overflow-hidden relative">
                        <!-- Decor -->
                        <div class="absolute top-0 right-0 -mr-16 -mt-16 w-48 h-48 bg-white/5 rounded-full blur-2xl"></div>
                        
                        <div class="flex items-center gap-4 relative z-10">
                            <div class="w-12 h-12 bg-white/10 rounded-lg flex items-center justify-center backdrop-blur-sm shrink-0">
                                <i class="fas fa-laptop-medical text-2xl text-purple-300"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-lg">Kelola Perangkat Aktif</h4>
                                <p class="text-gray-300 text-sm mt-1 max-w-lg">Kontrol keamanan akun Anda dengan mengelola sesi login di berbagai perangkat.</p>
                            </div>
                        </div>
                        <a href="../user/manage_sessions.php" class="relative z-10 shrink-0 px-6 py-2.5 bg-white text-gray-900 font-medium text-sm rounded-lg hover:bg-purple-50 hover:text-purple-700 transition-all flex items-center">
                            <span>Kelola Sesi</span>
                            <i class="fas fa-arrow-right ml-2 text-xs"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // --- LOGIKA MODAL PROFIL (Enhanced) ---
    function showProfileModal() {
        const modal = document.getElementById('profileModal');
        modal.classList.remove('hidden');
        // Small delay to allow display:block to apply before opacity transition
        setTimeout(() => {
            const backdrop = modal.querySelector('.fixed.inset-0.bg-gray-900\\/60');
            const content = modal.querySelector('.relative.transform');
            // Check if elements exist before interacting
            if(backdrop) backdrop.classList.remove('opacity-0');
            // We can add scale animation class if needed, checking standard tailwind behavior
        }, 10);
    }

    function closeProfileModal() {
        const modal = document.getElementById('profileModal');
        
        // Reset Logic
        document.getElementById('profileUpdateForm').reset();
        document.getElementById('passwordUpdateForm').reset();
        document.getElementById('photoUpdateForm').reset();
        
        // Hide errors
        const errorBoxes = ['profileUpdateError', 'passwordUpdateError', 'photoUpdateError'];
        errorBoxes.forEach(id => {
            const el = document.getElementById(id);
            if(el) el.classList.add('hidden');
        });

        // Hide Modal
        modal.classList.add('hidden');
    }

    function previewPhoto(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e){
                document.getElementById('photoPreview').src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    }

    function showFeedback(elementId, message, isError = true) {
        const el = document.getElementById(elementId);
        if(!el) return;
        
        const textSpan = el.querySelector('.error-text') || el;
        if(el.querySelector('.error-text')) {
             textSpan.textContent = message;
        } else {
             el.textContent = message;
        }
        
        el.className = isError 
            ? 'mt-3 p-3 bg-red-50 text-red-600 text-sm rounded-lg border border-red-100 flex items-center'
            : 'mt-3 p-3 bg-green-50 text-green-600 text-sm rounded-lg border border-green-100 flex items-center';
        
        el.classList.remove('hidden');
    }

    function saveProfile(event) {
        event.preventDefault();
        const form = document.getElementById('profileUpdateForm');
        const formData = new FormData(form);
        const button = document.getElementById('saveProfileButton');
        const errorId = 'profileUpdateError';

        // Loading state
        const originalBtnContent = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Menyimpan...';
        document.getElementById(errorId).classList.add('hidden');

        fetch('../user/process_profile.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    
                    // Update DOM elements if name changed
                    document.querySelectorAll('.user-info-box .font-semibold').forEach(el => el.textContent = data.new_name);
                    const welcomeHeader = document.querySelector('#home h1');
                    if(welcomeHeader) welcomeHeader.textContent = `Selamat Datang, ${data.new_name.split(' ')[0]}!`;
                    
                    closeProfileModal();
                } else {
                    showFeedback(errorId, data.message, true);
                }
            })
            .catch(err => {
                showFeedback(errorId, 'Terjadi kesalahan jaringan.', true);
            })
            .finally(() => {
                button.disabled = false;
                button.innerHTML = originalBtnContent;
            });
    }

    function savePassword(event) {
        event.preventDefault();
        const form = document.getElementById('passwordUpdateForm');
        const formData = new FormData(form);
        const button = document.getElementById('savePasswordButton');
        const errorId = 'passwordUpdateError';

        const originalBtnContent = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Memproses...';
        document.getElementById(errorId).classList.add('hidden');

        fetch('../user/process_profile.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Password Diperbarui',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    closeProfileModal();
                } else {
                    showFeedback(errorId, data.message, true);
                }
            })
            .catch(err => {
                showFeedback(errorId, 'Terjadi kesalahan jaringan.', true);
            })
            .finally(() => {
                button.disabled = false;
                button.innerHTML = originalBtnContent;
            });
    }

    function savePhoto(event) {
        event.preventDefault();
        const form = document.getElementById('photoUpdateForm');
        const formData = new FormData(form);
        const button = document.getElementById('savePhotoButton');
        const errorId = 'photoUpdateError';
        const fileInput = document.getElementById('foto_profil');

        if (!fileInput.files || fileInput.files.length === 0) {
            showFeedback(errorId, 'Silakan pilih foto terlebih dahulu.', true);
            return;
        }

        const originalBtnContent = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-circle-notch fa-spin mr-2"></i>Mengunggah...';
        document.getElementById(errorId).classList.add('hidden');

        fetch('../user/process_profile.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Foto Profil Diperbarui',
                        text: 'Tampilan foto profil Anda telah berhasil diubah.',
                        timer: 2000,
                        showConfirmButton: false
                    });

                    const ts = Date.now();
                    // Check if new_photo is a URL (starts with http)
                    const newPhotoUrl = data.new_photo.startsWith('http') 
                        ? data.new_photo 
                        : `../uploads/profiles/${data.new_photo}?t=${ts}`;
                    
                    // Update global images
                    document.querySelectorAll('#headerUserPhoto, #photoPreview, #sidebarUserPhoto').forEach(img => {
                         if (img.tagName === 'IMG') {
                            img.src = newPhotoUrl;
                         } else {
                            // If it was a div placeholder, replace with img
                            const newImg = document.createElement('img');
                            newImg.id = img.id;
                            newImg.className = img.className.replace('bg-gradient-to-br', '').replace('flex', '').replace('items-center', '').replace('justify-center', '').replace('text-white', '').replace('font-bold', '') + ' object-cover';
                            
                            // Remove text content
                            newImg.src = newPhotoUrl;
                            newImg.alt = 'Foto Profil';
                            img.parentNode.replaceChild(newImg, img);
                         }
                    });

                    try { localStorage.setItem('newProfilePhoto', data.new_photo); } catch (e) {}
                    form.reset();
                    // Keep modal open or close? User might want to see result. Let's close for consistency.
                    // closeProfileModal(); 
                } else {
                    showFeedback(errorId, data.message || 'Gagal mengunggah foto.', true);
                }
            })
            .catch(err => {
                showFeedback(errorId, 'Terjadi kesalahan jaringan.', true);
            })
            .finally(() => {
                button.disabled = false;
                button.innerHTML = originalBtnContent;
            });
    }
</script>

<style>
    /* Custom Scrollbar for the modal body */
    .custom-scrollbar::-webkit-scrollbar {
        width: 8px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 4px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
</style>