<?php
session_start();
include '../config/db.php';

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

$message = '';
$type = 'error';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    // Cari user dengan email dan belum aktif
    $sql = "SELECT id_user, nama_lengkap, role FROM user WHERE email = ? AND is_active = 0";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        $fullname = $user['nama_lengkap'];
        $id_user = $user['id_user'];

        // Generate new token
        $activation_token = bin2hex(random_bytes(32));

        // Update token
        $update_sql = "UPDATE user SET activation_token = ? WHERE id_user = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "si", $activation_token, $id_user);

        if (mysqli_stmt_execute($update_stmt)) {
            // Kirim email menggunakan PHPMailer
            $mail = new PHPMailer();
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'willyjuaness@gmail.com';
            $mail->Password = 'dupi ihcu tylj dmvf'; // App password for Gmail SMTP
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('willyjuaness@gmail.com', 'KosConnect');
            $mail->addAddress($email);

            $mail->isHTML(true); // Kirim sebagai HTML
            $mail->Subject = "Aktivasi Akun KosConnect - Link Baru";
            $activation_link = "http://localhost/KosConnect/auth/activate.php?token=$activation_token";

            $mail->Body = "
                <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                    <div style='max-width: 600px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
                        <h2 style='color: #667eea; text-align: center;'>Link Aktivasi Baru Anda</h2>
                        <p>Halo <strong>" . htmlspecialchars($fullname) . "</strong>,</p>
                        <p>Anda meminta link aktivasi baru. Untuk menyelesaikan proses aktivasi akun Anda, silakan klik tombol di bawah ini:</p>
                        <div style='text-align: center; margin: 30px 0;'>
                            <a href='" . $activation_link . "' style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Aktifkan Akun Saya</a>
                        </div>
                        <p>Jika tombol di atas tidak berfungsi, Anda juga bisa menyalin dan menempelkan tautan berikut di browser Anda:</p>
                        <p style='word-break: break-all; font-size: 0.9em;'><a href='" . $activation_link . "'>" . $activation_link . "</a></p>
                        <hr style='border: none; border-top: 1px solid #eee; margin: 20px 0;'>
                        <p style='font-size: 0.9em; color: #777; text-align: center;'>&copy; " . date("Y") . " KosConnect. All rights reserved.</p>
                    </div>
                </div>";

            if ($mail->send()) {
                $message = "Link aktivasi telah dikirim ulang ke email Anda.";
                $type = 'success';
                $_SESSION['resend_success'] = $message;

                header("Location: loginForm.php");
                exit();
            } else {
                $message = "Gagal mengirim email. Coba lagi nanti.";
            }
        } else {
            $message = "Terjadi kesalahan saat memperbarui token.";
        }
        mysqli_stmt_close($update_stmt);
    } else {
        $message = "Email tidak ditemukan atau akun sudah aktif.";
    }
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kirim Ulang Link Aktivasi - KosConnect</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/Login.css">
    <style>
        .message-box.error { background-color: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; padding: 10px; border-radius: 8px; margin-bottom: 15px; }
        .message-box.success { background-color: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; padding: 10px; border-radius: 8px; margin-bottom: 15px; }
        /* Mengganti background dengan gambar dan overlay */
        body {
            background-image: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('../img/kost2.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
        /* Menyembunyikan dekorasi lingkaran */
        .bg-decorations {
            display: none;
        }
        .back-to-home-btn {
            position: absolute;
            top: 25px;
            left: 25px;
            display: inline-flex;
            align-items: center;
            padding: 10px 20px;
            background-color: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: #fff;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            z-index: 10;
        }
        .back-to-home-btn i {
            margin-right: 8px;
        }
        .back-to-home-btn:hover {
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    
    <a href="../index.php" class="back-to-home-btn"><i class='bx bx-arrow-back'></i> Kembali</a>

    <div class="bg-decorations">
        <div class="floating-circle circle-1"></div>
        <div class="floating-circle circle-2"></div>
        <div class="floating-circle circle-3"></div>
    </div>

    <div class="wrapper">
        <form class="login-form" method="POST" action="resend.php">
            <div class="header">
                <h1 class="title">Kos<span class="highlight">Connect</span></h1>
                <p class="subtitle">Kirim Ulang Link Aktivasi</p>
                <p class="welcome">Masukkan email Anda untuk menerima link aktivasi baru</p>
            </div>

            <div id="messageBox" class="message-box hidden">
                <i class='bx bx-error-circle'></i>
                <span id="messageText"><?php echo $message; ?></span>
            </div>
            
            <?php if ($message): ?>
            <script>
                document.getElementById('messageBox').className = 'message-box <?php echo $type; ?>';
                document.getElementById('messageBox').classList.remove('hidden');
                document.getElementById('messageBox').querySelector('i').className = '<?php echo $type === 'success' ? 'bx bx-check-circle' : 'bx bx-error-circle'; ?>';
            </script>
            <?php endif; ?>

            <div class="input-box">
                <div class="input-icon"><i class='bx bxs-envelope'></i></div>
                <input type="email" name="email" placeholder="Email" required class="input-field">
            </div>

            <button type="submit" class="btn-login">
                <i class='bx bx-send'></i>
                Kirim Ulang Link
            </button>

            <div class="register-link">
                <p><a href="loginForm.php">Kembali ke Login</a></p>
            </div>
        </form>
    </div>

</body>
</html>
