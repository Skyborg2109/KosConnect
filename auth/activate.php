<?php
session_start();
include '../config/db.php';

$message = '';
$type = 'error';

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Cari user dengan token
    $sql = "SELECT id_user, role FROM user WHERE activation_token = ? AND is_active = 0";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $token);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        $id_user = $user['id_user'];

        // Aktifkan akun
        $update_sql = "UPDATE user SET is_active = 1, activation_token = NULL WHERE id_user = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "i", $id_user);

        if (mysqli_stmt_execute($update_stmt)) {
            $message = "✅ Akun berhasil diaktifkan! Silakan login.";
            $type = 'success';
            $_SESSION['activation_success'] = $message;

            // Selalu arahkan ke form login utama
            $redirect_url = 'loginForm.php';
        } else {
            $message = "❌ Terjadi kesalahan saat mengaktifkan akun.";
        }
        mysqli_stmt_close($update_stmt);
    } else {
        $message = "❌ Token tidak valid atau akun sudah diaktifkan.";
    }
    mysqli_stmt_close($stmt);
} else {
    $message = "❌ Token tidak ditemukan.";
}

// Jika tidak ada URL redirect (karena token tidak valid), default ke login umum
if (!isset($redirect_url)) { //
    $redirect_url = 'loginForm.php';
}

// Redirect ke halaman login yang sesuai
header("Location: " . $redirect_url);
exit();
?>
