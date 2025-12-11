<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['role'] !== 'pemilik') {
    header("Location: ../auth/loginForm.php");
    exit();
}

include '../config/db.php';

$id_pemilik = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'update_profile') {
            $fullname = trim($_POST['fullname']);
            if (empty($fullname)) {
                $error_message = "Nama lengkap tidak boleh kosong.";
            } else {
                $stmt = $conn->prepare("UPDATE user SET nama_lengkap = ? WHERE id_user = ?");
                $stmt->bind_param("si", $fullname, $id_pemilik);
                if ($stmt->execute()) {
                    $_SESSION['fullname'] = $fullname;
                    $success_message = "Nama lengkap berhasil diperbarui.";
                } else {
                    $error_message = "Gagal memperbarui nama.";
                }
                $stmt->close();
            }
        } elseif ($_POST['action'] === 'update_password') {
            $old_password = $_POST['old_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];

            if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
                $error_message = "Semua field password harus diisi.";
            } elseif ($new_password !== $confirm_password) {
                $error_message = "Password baru dan konfirmasi tidak cocok.";
            } elseif (strlen($new_password) < 6) {
                $error_message = "Password baru minimal harus 6 karakter.";
            } else {
                $stmt = $conn->prepare("SELECT password FROM user WHERE id_user = ?");
                $stmt->bind_param("i", $id_pemilik);
                $stmt->execute();
                $result = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($result && password_verify($old_password, $result['password'])) {
                    $new_password_hashed = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt_update = $conn->prepare("UPDATE user SET password = ? WHERE id_user = ?");
                    $stmt_update->bind_param("si", $new_password_hashed, $id_pemilik);
                    if ($stmt_update->execute()) {
                        $success_message = "Password berhasil diubah.";
                    } else {
                        $error_message = "Gagal mengubah password.";
                    }
                    $stmt_update->close();
                } else {
                    $error_message = "Password lama salah.";
                }
            }
        }
    }
}

$stmt_user = $conn->prepare("SELECT nama_lengkap, email FROM user WHERE id_user = ?");
$stmt_user->bind_param("i", $id_pemilik);
$stmt_user->execute();
$user_data = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();

$userName = htmlspecialchars($user_data['nama_lengkap'] ?? 'Pemilik');
$userEmail = htmlspecialchars($user_data['email'] ?? '');

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - Dashboard Pemilik</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 font-sans">

    <nav class="bg-white shadow-lg fixed w-full top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <a href="../dashboard/dashboardpemilik.php" class="flex items-center">
                    <h1 class="text-2xl font-bold text-slate-800">Pemilik Panel</h1>
                </a>
                <a href="../dashboard/dashboardpemilik.php" class="text-slate-600 hover:underline font-semibold">&larr; Kembali ke Dashboard</a>
            </div>
        </div>
    </nav>

    <main class="pt-24 pb-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-4xl font-bold text-gray-800 mb-8">Profil Saya</h1>

            <?php if ($success_message): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-md" role="alert">
                <p class="font-bold">Sukses</p>
                <p><?php echo $success_message; ?></p>
            </div>
            <?php endif; ?>
            <?php if ($error_message): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md" role="alert">
                <p class="font-bold">Error</p>
                <p><?php echo $error_message; ?></p>
            </div>
            <?php endif; ?>

            <div class="bg-white rounded-xl shadow-md p-6 md:p-8 mb-8">
                <h2 class="text-2xl font-semibold text-gray-800 mb-6">Informasi Pribadi</h2>
                <form method="POST" action="profile.php">
                    <input type="hidden" name="action" value="update_profile">
                    <div class="space-y-6">
                        <div>
                            <label for="fullname" class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                            <input type="text" name="fullname" id="fullname" value="<?php echo $userName; ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-slate-500 focus:border-slate-500">
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" name="email" id="email" value="<?php echo $userEmail; ?>" disabled class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed">
                            <p class="text-xs text-gray-500 mt-1">Email tidak dapat diubah.</p>
                        </div>
                    </div>
                    <div class="mt-8 text-right">
                        <button type="submit" class="bg-slate-700 text-white px-6 py-2.5 rounded-lg font-semibold hover:bg-slate-800 transition-colors">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6 md:p-8">
                <h2 class="text-2xl font-semibold text-gray-800 mb-6">Ubah Password</h2>
                <form method="POST" action="profile.php">
                    <input type="hidden" name="action" value="update_password">
                    <div class="space-y-6">
                        <div>
                            <label for="old_password" class="block text-sm font-medium text-gray-700 mb-1">Password Lama</label>
                            <input type="password" name="old_password" id="old_password" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-slate-500 focus:border-slate-500">
                        </div>
                        <div>
                            <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">Password Baru</label>
                            <input type="password" name="new_password" id="new_password" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-slate-500 focus:border-slate-500">
                        </div>
                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Password Baru</label>
                            <input type="password" name="confirm_password" id="confirm_password" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-slate-500 focus:border-slate-500">
                        </div>
                    </div>
                    <div class="mt-8 text-right">
                        <button type="submit" class="bg-gray-800 text-white px-6 py-2.5 rounded-lg font-semibold hover:bg-gray-900 transition-colors">
                            Ubah Password
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </main>
</body>
</html>