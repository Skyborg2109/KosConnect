<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Session already started in pemilik_get_module.php when included
if (!isset($_SESSION['user_logged_in']) || $_SESSION['role'] !== 'pemilik') {
    header("Location: ../auth/loginForm.php");
    exit();
}

// Database connection already established in pemilik_get_module.php

$id_pemilik = $_SESSION['user_id'];

// Query untuk mendapatkan keluhan yang terkait dengan kos milik pemilik ini
$sql_complaints = "
    SELECT
        c.id_complaint,
        u.nama_lengkap AS nama_penyewa,
        t.nama_kost,
        c.pesan,
        c.status,
        c.created_at
    FROM complaint c
    JOIN user u ON c.id_penyewa = u.id_user
    JOIN kost t ON c.id_kost = t.id_kost
    WHERE t.id_pemilik = ?
    ORDER BY c.created_at DESC";
$stmt_complaints = $conn->prepare($sql_complaints);
$stmt_complaints->bind_param("i", $id_pemilik);
$stmt_complaints->execute();
$res_complaints = $stmt_complaints->get_result();


?>

<div class="space-y-8">
    <!-- Keluhan Kos Milik Anda -->
    <div class="bg-white rounded-xl shadow-md card-hover">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="icon-bg p-3 rounded-full">
                        <i class="fas fa-exclamation-triangle text-2xl text-white"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-slate-800">Keluhan Kos Milik Anda</h3>
                        <p class="text-sm text-slate-500"><?php echo $res_complaints->num_rows; ?> keluhan terkait kos Anda</p>
                    </div>
                </div>
                <div class="text-right">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-orange-100 text-orange-800">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        Perlu Perhatian
                    </span>
                </div>
            </div>
        </div>
        <div class="p-6">
            <?php if ($res_complaints->num_rows > 0): ?>
                <div class="space-y-4">
                    <?php while($c = $res_complaints->fetch_assoc()): ?>
                    <div class="border-l-4 <?php echo ($c['status'] == 'baru') ? 'border-red-500' : (($c['status'] == 'diproses') ? 'border-yellow-500' : 'border-green-500'); ?> bg-gray-50 p-4 rounded-lg">
                        <div class="flex justify-between items-start">
                            <div>
                                <h4 class="font-bold text-lg text-gray-800">Keluhan dari <?php echo htmlspecialchars($c['nama_penyewa']); ?></h4>
                                <p class="text-sm text-gray-600">Kos: <?php echo htmlspecialchars($c['nama_kost']); ?> | Tgl: <?php echo date('d M Y', strtotime($c['created_at'])); ?></p>
                            </div>
                            <span class="px-3 py-1 rounded-full text-sm font-medium
                                <?php echo ($c['status'] == 'baru') ? 'bg-red-100 text-red-800' : (($c['status'] == 'diproses') ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'); ?>">
                                <?php echo htmlspecialchars(ucfirst($c['status'])); ?>
                            </span>
                        </div>
                        <p class="mt-2 text-gray-700"><?php echo htmlspecialchars($c['pesan']); ?></p>
                        <div class="mt-3 pt-3 border-t border-gray-100 flex space-x-2">
                            <?php if ($c['status'] !== 'selesai'): ?>
                                <button onclick="markComplaintResolved(<?php echo $c['id_complaint']; ?>)" class="bg-green-500 text-white px-3 py-1 rounded text-sm hover:bg-green-600">Tandai Selesai</button>
                                <?php if ($c['status'] !== 'diproses'): ?>
                                    <button onclick="markComplaintProcessing(<?php echo $c['id_complaint']; ?>)" class="bg-blue-500 text-white px-3 py-1 rounded text-sm hover:bg-blue-600">Set Diproses</button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-12">
                    <div class="icon-bg p-4 rounded-full mx-auto w-fit mb-4">
                        <i class="fas fa-check-circle text-3xl text-white"></i>
                    </div>
                    <h4 class="text-lg font-medium text-slate-900 mb-2">Tidak ada keluhan</h4>
                    <p class="text-slate-500">Kos Anda belum menerima keluhan dari penyewa.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>


</div>

<script>
function markComplaintResolved(id) {
    Swal.fire({
        title: 'Tandai Selesai',
        text: 'Apakah Anda yakin ingin menandai keluhan ID ' + id + ' sebagai SELESAI?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10B981',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Ya, Selesai',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            updateComplaintStatus(id, 'resolve');
        }
    });
}

function markComplaintProcessing(id) {
    Swal.fire({
        title: 'Tandai Diproses',
        text: 'Apakah Anda yakin ingin menandai keluhan ID ' + id + ' sebagai DIPROSES?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3B82F6',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Ya, Diproses',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            updateComplaintStatus(id, 'process');
        }
    });
}

function updateComplaintStatus(id, action) {
    fetch('../admin/process_complaint.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'id_complaint=' + id + '&action=' + action
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                title: 'Berhasil!',
                text: data.message,
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                // Reload the current module
                const fakeEvent = { currentTarget: document.querySelector('[data-module="owner_view_feedback"]') };
                loadContent('owner_view_feedback', fakeEvent);
            });
        } else {
            Swal.fire({
                title: 'Gagal!',
                text: data.message,
                icon: 'error'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            title: 'Error!',
            text: 'Terjadi kesalahan saat memproses permintaan.',
            icon: 'error'
        });
    });
}
</script>

<style>
.icon-bg {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}
.card-hover:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}
</style>

