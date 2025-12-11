<?php
// Pastikan sesi dimulai untuk menyetel notifikasi sukses register
session_start();
include '../config/db.php';

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari POST
    $fullname = trim($_POST['fullname']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $role     = 'pemilik'; // Fixed role for owner
    
    // 1. Validasi sederhana: Cek apakah email sudah terdaftar
    $check_sql = "SELECT email FROM user WHERE email = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "s", $email);
    mysqli_stmt_execute($check_stmt);
    mysqli_stmt_store_result($check_stmt);
    
    if (mysqli_stmt_num_rows($check_stmt) > 0) {
        $error_message = "❌ Email ini sudah terdaftar. Silakan gunakan email lain.";
    } else {
        // 2. Enkripsi password, generate token & Insert ke database
        $password_hashed = password_hash($password, PASSWORD_DEFAULT);
        $activation_token = bin2hex(random_bytes(32));

        $sql = "INSERT INTO user (nama_lengkap, email, password, role, activation_token, is_active)
                VALUES (?, ?, ?, ?, ?, 0)";

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sssss", $fullname, $email, $password_hashed, $role, $activation_token);

        if (mysqli_stmt_execute($stmt)) {
            // 3. Kirim email konfirmasi menggunakan PHPMailer
            $mail = new PHPMailer(true); // Aktifkan exception
            try {
                // Konfigurasi Server
                // $mail->SMTPDebug = 2; // Aktifkan untuk debug detail
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'willyjuaness@gmail.com';
                $mail->Password = 'dupi ihcu tylj dmvf'; // App password for Gmail SMTP
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                // Penerima
                $mail->setFrom('willyjuaness@gmail.com', 'KosConnect');
                $mail->addAddress($email, $fullname); // Menggunakan email dari form registrasi

                // Konten Email
                $mail->isHTML(true); // Kirim sebagai HTML
                $mail->Subject = "Selamat Datang di KosConnect! Aktifkan Akun Pemilik Anda";
                $activation_link = "http://localhost/KosConnect/auth/activate.php?token=$activation_token";

                $mail->Body = "
                    <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                        <div style='max-width: 600px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
                            <h2 style='color: #667eea; text-align: center;'>Selamat Datang di KosConnect!</h2>
                            <p>Halo <strong>" . htmlspecialchars($fullname) . "</strong>,</p>
                            <p>Terima kasih telah mendaftar sebagai <strong>Pemilik Kos</strong>. Hanya satu langkah lagi untuk mengaktifkan akun Anda. Silakan klik tombol di bawah ini:</p>
                            <div style='text-align: center; margin: 30px 0;'>
                                <a href='" . $activation_link . "' style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Aktifkan Akun Saya</a>
                            </div>
                            <p>Jika tombol di atas tidak berfungsi, Anda juga bisa menyalin dan menempelkan tautan berikut di browser Anda:</p>
                            <p style='word-break: break-all; font-size: 0.9em;'><a href='" . $activation_link . "'>" . $activation_link . "</a></p>
                            <p>Jika Anda tidak merasa mendaftar di KosConnect, abaikan saja email ini.</p>
                            <hr style='border: none; border-top: 1px solid #eee; margin: 20px 0;'>
                            <p style='font-size: 0.9em; color: #777; text-align: center;'>&copy; " . date("Y") . " KosConnect. All rights reserved.</p>
                        </div>
                    </div>";

                $mail->send();
                // 4. SETEL PENANDA SESI SUKSES setelah email berhasil terkirim
                $_SESSION['registration_success'] = "Registrasi berhasil! Silakan periksa email Anda untuk aktivasi akun.";
            } catch (Exception $e) {
                // Jika email gagal, berikan pesan error yang lebih informatif
                $error_message = "❌ Registrasi berhasil, tetapi gagal mengirim email konfirmasi. Mailer Error: {$mail->ErrorInfo}";
                // Hapus sesi sukses jika email gagal, agar tidak ada redirect
                unset($_SESSION['registration_success']);
            }
        } else {
            $error_message = "❌ Terjadi kesalahan saat insert: " . mysqli_error($conn);
        }
    }
    // Jika registrasi dan pengiriman email berhasil, redirect ke login
    if (isset($_SESSION['registration_success'])) {
        session_write_close(); // Pastikan sesi tersimpan sebelum redirect
        header("Location: loginForm.php");
        exit();
    }
    // Tutup statement
    if (isset($check_stmt)) mysqli_stmt_close($check_stmt);
    if (isset($stmt)) mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Pemilik Kos - KosConnect</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/Register.css">
    <style>
        /* Tambahkan CSS sederhana untuk memastikan message box terlihat */
        .message-box.error { background-color: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; padding: 10px; border-radius: 8px; margin-bottom: 15px; }
        /* Mengganti background dengan gambar */
        body {
            /* Menambahkan overlay gelap untuk keterbacaan form */
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
        <div class="floating-circle circle-4"></div>
    </div>

    <div class="wrapper">
        <div>
            <form class="register-form" method="POST" action="RegisterForm_owner.php">
            <div class="header">
                <h1 class="title">Kos<span class="highlight">Connect</span></h1>
                <h2 class="subtitle">Register Pemilik Kos</h2>
                <p class="welcome">Daftar sebagai pemilik kos</p>
            </div>

            <div id="messageBox" class="message-box hidden">
                <i class='bx bx-error-circle'></i>
                <span id="messageText"><?php echo $error_message; ?></span>
            </div>
            
            <?php if ($error_message): ?>
            <script>
                document.getElementById('messageBox').className = 'message-box error';
                document.getElementById('messageBox').classList.remove('hidden');
            </script>
            <?php endif; ?>

            <div class="input-box">
                <div class="input-icon"><i class='bx bxs-user'></i></div>
                <input type="text" name="fullname" placeholder="Nama Lengkap" required class="input-field" value="<?php echo htmlspecialchars($_POST['fullname'] ?? ''); ?>">
            </div>
            <div class="input-box">
                <div class="input-icon"><i class='bx bxs-envelope'></i></div>
                <input type="email" name="email" placeholder="Email" required class="input-field" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            <div class="input-box">
                <div class="input-icon"><i class='bx bxs-lock-alt'></i></div>
                <input type="password" name="password" id="password" placeholder="Password (Min. 6 Karakter)" required class="input-field">
                <button type="button" id="togglePassword" class="toggle-password"><i class='bx bx-show'></i></button>
            </div>

            <button type="button" class="btn-register" onclick="submitRegisterForm()">
                <i class='bx bx-user-plus'></i>
                Register
            </button>

            <div class="login-link">
                <p>Sudah punya akun? <a href="loginForm.php">Login Sekarang</a></p>
            </div>
        </form>
    </div>

    <script>
        // Panggil setupPasswordToggle dari file registerForm.js yang sudah ada
        function setupPasswordToggle(toggleId, passwordId) {
            const toggleButton = document.querySelector(`#${toggleId}`);
            const passwordField = document.querySelector(`#${passwordId}`);
            if (toggleButton && passwordField) {
                toggleButton.addEventListener('click', () => {
                    const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordField.setAttribute('type', type);
                    const icon = toggleButton.querySelector('i');
                    icon.classList.toggle('bx-show');
                    icon.classList.toggle('bx-hide');
                });
            }
        }
        setupPasswordToggle('togglePassword', 'password');

        // Fungsi pesan (disesuaikan dari file registerForm.js Anda)
        function showMessage(message, type = 'error') {
            const messageBox = document.getElementById('messageBox');
            const messageText = document.getElementById('messageText');
            if (messageBox && messageText) {
                messageText.textContent = message;
                messageBox.className = `message-box ${type}`;
                messageBox.classList.remove('hidden');

                const icon = messageBox.querySelector('i');
                if (icon) {
                    icon.className = (type === 'success') ? 'bx bx-check-circle' : 'bx bx-error-circle';
                }

                if (type === 'error') {
                    setTimeout(() => { messageBox.classList.add('hidden'); }, 5000);
                }
            }
        }

        // FUNGSI INI MENGGANTIKAN ONSUBMIT DARI FORM
        function submitRegisterForm() {
            const form = document.querySelector('.register-form');
            const passwordField = document.getElementById('password').value;

            // 1. Validasi Client-side (Cek panjang password)
            if (passwordField.length < 6) {
                showMessage('Password minimal 6 karakter', 'error');
                return;
            }

            // 2. Jika validasi Client-side lolos, kirim form ke PHP
            form.submit();
        }
    </script>
</body>
</html>
