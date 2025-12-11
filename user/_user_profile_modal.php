<!-- ======================================================= -->
<!-- MODAL PROFIL SAYA (Reusable) -->
<!-- ======================================================= -->
<div id="profileModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg w-full max-w-4xl max-h-full overflow-y-auto">
        <div class="p-6 border-b flex justify-between items-center">
            <h3 class="text-2xl font-semibold text-gray-800">Profil Saya</h3>
            <button type="button" onclick="closeProfileModal()" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
        </div>
        <!-- Form Ganti Foto Profil -->
        <div class="p-6 border-b">
            <h4 class="text-lg font-semibold mb-4">Ganti Foto Profil</h4>
            <form id="photoUpdateForm" onsubmit="savePhoto(event)" class="flex items-center gap-4">
                <input type="hidden" name="action" value="update_photo">
                <img id="photoPreview" src="<?php echo $userPhoto ? '../uploads/profiles/' . htmlspecialchars($userPhoto) : 'https://via.placeholder.com/100'; ?>" alt="Preview" class="w-20 h-20 rounded-full object-cover bg-gray-200">
                <div>
                    <input type="file" name="foto_profil" id="foto_profil" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100" onchange="previewPhoto(event)" required>
                    <div id="photoUpdateError" class="hidden text-red-600 text-sm mt-1"></div>
                </div>
                <div class="ml-auto">
                    <button type="submit" id="savePhotoButton" class="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700">Unggah</button>
                </div>
            </form>
        </div>
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Form Informasi Pribadi -->
            <div>
                <h4 class="text-lg font-semibold mb-4">Informasi Pribadi</h4>
                <form id="profileUpdateForm" onsubmit="saveProfile(event)" class="space-y-4">
                    <input type="hidden" name="action" value="update_profile">
                    <div>
                        <label for="profile_fullname" class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                        <input type="text" name="fullname" id="profile_fullname" value="<?php echo $userName; ?>" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2 focus:ring-purple-500 focus:border-purple-500">
                    </div>
                    <div>
                        <label for="profile_email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" id="profile_email" value="<?php echo htmlspecialchars($userEmail); ?>" disabled class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2 bg-gray-100">
                    </div>
                    <div id="profileUpdateError" class="hidden text-red-600 text-sm"></div>
                    <div class="text-right">
                        <button type="submit" id="saveProfileButton" class="bg-purple-600 text-white py-2 px-4 rounded-md hover:bg-purple-700">Simpan Nama</button>
                    </div>
                </form>
            </div>
            <!-- Form Ubah Password -->
            <div>
                <h4 class="text-lg font-semibold mb-4">Ubah Password</h4>
                <form id="passwordUpdateForm" onsubmit="savePassword(event)" class="space-y-4">
                    <input type="hidden" name="action" value="update_password">
                    <div>
                        <label for="old_password" class="block text-sm font-medium text-gray-700">Password Lama</label>
                        <input type="password" name="old_password" id="old_password" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2 focus:ring-purple-500 focus:border-purple-500">
                    </div>
                    <div>
                        <label for="new_password" class="block text-sm font-medium text-gray-700">Password Baru</label>
                        <input type="password" name="new_password" id="new_password" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2 focus:ring-purple-500 focus:border-purple-500">
                    </div>
                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700">Konfirmasi Password Baru</label>
                        <input type="password" name="confirm_password" id="confirm_password" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2 focus:ring-purple-500 focus:border-purple-500">
                    </div>
                    <div id="passwordUpdateError" class="hidden text-red-600 text-sm"></div>
                    <div class="text-right">
                        <button type="submit" id="savePasswordButton" class="bg-gray-800 text-white py-2 px-4 rounded-md hover:bg-gray-900">Ubah Password</button>
                    </div>
                </form>
            </div>
        </div>
        <!-- Device Management Section -->
        <div class="p-6 border-t bg-gray-50">
            <div class="flex justify-between items-center mb-4">
                <h4 class="text-lg font-semibold">Kelola Device</h4>
                <a href="manage_sessions.php" class="text-purple-600 hover:text-purple-700 font-medium flex items-center gap-2">
                    <i class="fas fa-arrow-right"></i> Lihat Semua Device
                </a>
            </div>
            <p class="text-gray-600 text-sm mb-3">Anda dapat login dari berbagai device secara bersamaan. Klik tombol di bawah untuk mengelola semua device yang aktif dan logout dari device tertentu.</p>
        </div>
        </div>
    </div>
</div>

<script>
    // --- LOGIKA MODAL PROFIL (Reusable) ---
    function showProfileModal() {
        document.getElementById('profileModal').classList.remove('hidden');
        document.getElementById('profileModal').classList.add('flex');
    }

    function closeProfileModal() {
        document.getElementById('profileModal').classList.add('hidden');
        document.getElementById('profileModal').classList.remove('flex');
        // Reset form dan pesan error
        document.getElementById('profileUpdateForm').reset();
        document.getElementById('passwordUpdateForm').reset();
        document.getElementById('photoUpdateForm').reset();
        document.getElementById('profileUpdateError').classList.add('hidden');
        document.getElementById('passwordUpdateError').classList.add('hidden');
        document.getElementById('photoUpdateError').classList.add('hidden');
    }

    function previewPhoto(event) {
        const reader = new FileReader();
        reader.onload = function(){
            document.getElementById('photoPreview').src = reader.result;
        };
        reader.readAsDataURL(event.target.files[0]);
    }

    function saveProfile(event) {
        event.preventDefault();
        const form = document.getElementById('profileUpdateForm');
        const formData = new FormData(form);
        const button = document.getElementById('saveProfileButton');
        const errorBox = document.getElementById('profileUpdateError');

        button.disabled = true;
        button.textContent = 'Menyimpan...';
        errorBox.classList.add('hidden');

        fetch('../user/process_profile.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire('Berhasil', data.message, 'success');
                    // Update nama di semua tempat yang relevan
                    document.querySelectorAll('.user-info-box .font-semibold').forEach(el => el.textContent = data.new_name);
                    const welcomeHeader = document.querySelector('#home h1');
                    if(welcomeHeader) welcomeHeader.textContent = `Selamat Datang, ${data.new_name.split(' ')[0]}!`;
                    closeProfileModal();
                } else {
                    errorBox.textContent = data.message;
                    errorBox.classList.remove('hidden');
                }
            })
            .catch(err => {
                errorBox.textContent = 'Terjadi kesalahan jaringan.';
                errorBox.classList.remove('hidden');
            })
            .finally(() => {
                button.disabled = false;
                button.textContent = 'Simpan Nama';
            });
    }

    function savePassword(event) {
        event.preventDefault();
        const form = document.getElementById('passwordUpdateForm');
        const formData = new FormData(form);
        const button = document.getElementById('savePasswordButton');
        const errorBox = document.getElementById('passwordUpdateError');

        button.disabled = true;
        button.textContent = 'Menyimpan...';
        errorBox.classList.add('hidden');

        fetch('../user/process_profile.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire('Berhasil', data.message, 'success');
                    closeProfileModal();
                } else {
                    errorBox.textContent = data.message;
                    errorBox.classList.remove('hidden');
                }
            })
            .catch(err => {
                errorBox.textContent = 'Terjadi kesalahan jaringan.';
                errorBox.classList.remove('hidden');
            })
            .finally(() => {
                button.disabled = false;
                button.textContent = 'Ubah Password';
            });
    }

    function savePhoto(event) {
        event.preventDefault();
        const form = document.getElementById('photoUpdateForm');
        const formData = new FormData(form);
        const button = document.getElementById('savePhotoButton');
        const errorBox = document.getElementById('photoUpdateError');

        button.disabled = true;
        button.textContent = 'Mengunggah...';
        errorBox.classList.add('hidden');

        fetch('../user/process_profile.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire('Berhasil', data.message, 'success');
                    const ts = Date.now();
                    const newPhotoUrl = `../uploads/profiles/${data.new_photo}?t=${ts}`;
                    
                    // Update semua elemen <img> yang menampilkan foto profil
                    document.querySelectorAll('#headerUserPhoto, #photoPreview').forEach(img => {
                        if (img.tagName === 'IMG') {
                            img.src = newPhotoUrl;
                        } else { // Jika masih berupa div (placeholder)
                            const newImg = document.createElement('img');
                            newImg.id = img.id;
                            newImg.className = img.className;
                            newImg.src = newPhotoUrl;
                            newImg.alt = 'Foto Profil';
                            img.parentNode.replaceChild(newImg, img);
                        }
                    });

                    try { localStorage.setItem('newProfilePhoto', data.new_photo); } catch (e) {}
                    form.reset();
                } else {
                    errorBox.textContent = data.message || 'Terjadi kesalahan saat mengunggah.';
                    errorBox.classList.remove('hidden');
                }
            })
            .catch(err => {
                errorBox.textContent = 'Terjadi kesalahan jaringan.';
                errorBox.classList.remove('hidden');
            })
            .finally(() => {
                button.disabled = false;
                button.textContent = 'Unggah';
            });
    }

    // Tutup modal jika klik di luar area konten
    document.getElementById('profileModal').addEventListener('click', function(e) {
        if (e.target === this) closeProfileModal();
    });
</script>

<style>
    @media (max-width: 768px) {
        #profileModal {
            padding: 0.5rem !important;
        }

        #profileModal .bg-white {
            max-width: 100% !important;
            max-height: 95vh !important;
            margin: auto !important;
            border-radius: 0.75rem !important;
        }

        #profileModal .p-6 {
            padding: 1rem !important;
        }

        #profileModal h3 {
            font-size: 1.5rem !important;
        }

        #profileModal h4 {
            font-size: 1.1rem !important;
        }

        #profileModal .text-lg {
            font-size: 1.1rem !important;
        }

        #profileModal .text-sm {
            font-size: 0.85rem !important;
        }

        #profileModal input[type="text"],
        #profileModal input[type="email"],
        #profileModal input[type="password"],
        #profileModal input[type="file"],
        #profileModal textarea,
        #profileModal select {
            padding: 0.75rem !important;
            font-size: 1rem !important;
            border-radius: 0.5rem !important;
        }

        #profileModal button {
            padding: 0.75rem 1.25rem !important;
            font-size: 0.9rem !important;
        }

        #profileModal .space-y-4 > * + * {
            margin-top: 0.75rem !important;
        }

        #profileModal .gap-8 {
            gap: 1rem !important;
        }

        #profileModal .gap-4 {
            gap: 0.75rem !important;
        }

        #profileModal .grid {
            grid-template-columns: 1fr !important;
        }

        #profileModal .grid.md\:grid-cols-2 {
            grid-template-columns: 1fr !important;
        }

        #profileModal .w-20 {
            width: 4rem !important;
            height: 4rem !important;
        }

        #profileModal .flex {
            flex-direction: row !important;
            flex-wrap: wrap !important;
            gap: 0.75rem !important;
        }

        #profileModal .flex.flex-col {
            flex-direction: column !important;
            gap: 0.75rem !important;
        }

        #profileModal .flex.justify-between {
            justify-content: space-between !important;
            gap: 0.5rem !important;
        }

        #profileModal .flex.items-center {
            gap: 0.5rem !important;
        }

        #profileModal .border-b {
            border-bottom: 1px solid #e5e7eb !important;
        }

        #profileModal .border-t {
            border-top: 1px solid #e5e7eb !important;
        }

        #profileModal .rounded-lg {
            border-radius: 0.5rem !important;
        }

        #profileModal .rounded-full {
            border-radius: 50% !important;
        }

        #profileModal .text-right {
            text-align: right !important;
        }

        #profileModal .ml-auto {
            margin-left: auto !important;
        }

        #profileModal .mb-4 {
            margin-bottom: 0.75rem !important;
        }

        #profileModal .mb-3 {
            margin-bottom: 0.5rem !important;
        }

        #profileModal .mt-1 {
            margin-top: 0.25rem !important;
        }

        #profileModal .hidden {
            display: none !important;
        }
    }

    @media (max-width: 640px) {
        #profileModal {
            padding: 0.25rem !important;
        }

        #profileModal .bg-white {
            max-width: 100% !important;
            max-height: 95vh !important;
            border-radius: 0.5rem !important;
        }

        #profileModal .p-6 {
            padding: 0.75rem !important;
        }

        #profileModal h3 {
            font-size: 1.25rem !important;
        }

        #profileModal h4 {
            font-size: 1rem !important;
        }

        #profileModal .text-lg {
            font-size: 1rem !important;
        }

        #profileModal .text-sm {
            font-size: 0.8rem !important;
        }

        #profileModal input[type="text"],
        #profileModal input[type="email"],
        #profileModal input[type="password"],
        #profileModal input[type="file"],
        #profileModal textarea,
        #profileModal select {
            padding: 0.65rem !important;
            font-size: 1rem !important;
        }

        #profileModal button {
            padding: 0.65rem 1rem !important;
            font-size: 0.85rem !important;
        }

        #profileModal .space-y-4 > * + * {
            margin-top: 0.5rem !important;
        }

        #profileModal .gap-8,
        #profileModal .gap-4 {
            gap: 0.5rem !important;
        }

        #profileModal .grid {
            grid-template-columns: 1fr !important;
            gap: 0.5rem !important;
        }

        #profileModal .flex {
            flex-direction: column !important;
            gap: 0.5rem !important;
        }

        #profileModal .flex.flex-col {
            flex-direction: column !important;
            gap: 0.5rem !important;
        }

        #profileModal .text-right {
            text-align: center !important;
        }

        #profileModal .flex.justify-between {
            justify-content: space-between !important;
            gap: 0.25rem !important;
        }

        #profileModal .w-20 {
            width: 3.5rem !important;
            height: 3.5rem !important;
        }

        #profileModal .ml-auto {
            margin-left: 0 !important;
            text-align: center !important;
        }

        #profileModal .mb-4 {
            margin-bottom: 0.5rem !important;
        }

        #profileModal .mb-3 {
            margin-bottom: 0.375rem !important;
        }

        #profileModal .mt-1 {
            margin-top: 0.25rem !important;
        }
    }
</style>