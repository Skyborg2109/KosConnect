<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/loginForm.php");
    exit();
}

include '../config/db.php'; 



// Query untuk mendapatkan semua feedback
$sql_feedback = "
    SELECT 
        f.id_feedback, 
        u.nama_lengkap AS nama_penyewa, 
        f.pesan, 
        f.created_at
    FROM feedback f
    JOIN user u ON f.id_penyewa = u.id_user
    ORDER BY f.created_at DESC";
$res_feedback = $conn->query($sql_feedback);
?>

<!-- Admin Manage Complaints Fragment -->
    <div class="space-y-8">
        


        <div class="bg-white rounded-xl shadow-lg">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-2xl font-bold text-gray-800">Feedback Aplikasi (<?php echo $res_feedback->num_rows; ?>)</h3>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <?php while($f = $res_feedback->fetch_assoc()): ?>
                    <div class="border-l-4 border-blue-500 bg-blue-50 p-4 rounded-lg">
                        <p class="text-sm text-gray-600">Dari: **<?php echo htmlspecialchars($f['nama_penyewa']); ?>** | Tgl: <?php echo date('d M Y', strtotime($f['created_at'])); ?></p>
                        <p class="mt-1 text-gray-700 font-medium"><?php echo htmlspecialchars($f['pesan']); ?></p>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>
    




