 <?php
session_start();
require_once '../config/db.php';
require_once '../config/SessionManager.php';

$success_message = '';
$error = '';
$show_resend = false;
$allowed_roles = ['penyewa', 'pemilik']; // roles allowed in the login form (admin excluded on purpose)

// --- Cek Notifikasi Sukses Register ---
if (isset($_SESSION['registration_success'])) {
    $success_message = $_SESSION['registration_success'];
    unset($_SESSION['registration_success']); // Hapus penanda agar tidak muncul lagi
    $show_resend = true; // Tampilkan link kirim ulang setelah registrasi
}

// --- Cek Notifikasi Sukses Aktivasi ---
if (isset($_SESSION['activation_success'])) {
    $success_message = $_SESSION['activation_success'];
    unset($_SESSION['activation_success']); // Hapus penanda agar tidak muncul lagi
}

// --- Cek Notifikasi Sukses Kirim Ulang ---
if (isset($_SESSION['resend_success'])) {
    $success_message = $_SESSION['resend_success'];
    unset($_SESSION['resend_success']); // Hapus penanda agar tidak muncul lagi
}

// --- Tidak redirect otomatis agar support multi-login di multiple tabs/devices ---
// User bisa stay di login page untuk login sebagai role berbeda di tab lain
// Session token di cookie akan handle state management untuk different roles
// -----------------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['email']) || !isset($_POST['password'])) {
        $error = "Email dan password harus diisi!";
    } else {
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $selected_role = isset($_POST['role']) ? trim($_POST['role']) : '';

        // If a role was selected, validate it; if not selected, allow login (admin can login without choosing)
        if ($selected_role !== '' && !in_array($selected_role, $allowed_roles, true)) {
            $error = "Role tidak valid.";
        }

        // Query aman dengan prepared statement
        $sql = "SELECT id_user, nama_lengkap, password, role, is_active, is_blocked FROM user WHERE email = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (empty($error) && $result && mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);

            // --- BAGIAN 1: Validasi Role ---
            // Pengecekan ini penting untuk memastikan pengguna memilih role yang benar.
            // Admin dikecualikan dari pengecekan ini karena mereka tidak memiliki opsi role di form ini.
            if ($user['role'] !== 'admin' && $user['role'] !== $selected_role) {
                $error = "Role yang dipilih tidak cocok dengan akun. Pilih role yang sesuai atau cek kembali email Anda.";
            } else {
                // --- BAGIAN 2: Verifikasi Password ---
                // Lanjutkan hanya jika role sudah benar.
                $is_password_valid = false;
                if (password_verify($password, $user['password'])) {
                    $is_password_valid = true;
                }
                // Fallback untuk password teks biasa (jika ada) dan rehash otomatis
                elseif ($password === $user['password']) {
                $is_password_valid = true;
                    // Rehash the password for security
                    $new_hash = password_hash($password, PASSWORD_DEFAULT);
                    $update_sql = "UPDATE user SET password = ? WHERE id_user = ?";
                    $update_stmt = mysqli_prepare($conn, $update_sql);
                    mysqli_stmt_bind_param($update_stmt, "si", $new_hash, $user['id_user']);
                    mysqli_stmt_execute($update_stmt);
                    mysqli_stmt_close($update_stmt);
                }

                if ($is_password_valid) {
                    // --- BAGIAN 3: Cek Status Aktivasi Akun ---
                    if ($user['is_blocked'] == 1) {
                        $error = "Akun Anda telah diblokir. Silakan hubungi administrator.";
                    } elseif ($user['is_active'] == 1) {
                        // SETEL VARIABEL SESI dengan session token
                        $_SESSION['user_id'] = $user['id_user'];
                        $_SESSION['fullname'] = $user['nama_lengkap'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['user_logged_in'] = true;
                        
                        // Generate session token untuk multi-device access
                        $sessionManager = new SessionManager($conn);
                        $session_token = $sessionManager->createSessionToken($user['id_user']);
                        
                        if ($session_token) {
                            // Simpan token di session (untuk validasi di dashboard)
                            $_SESSION['session_token'] = $session_token;
                            // Simpan token di cookie (untuk persistent access)
                            setcookie('session_token', $session_token, time() + (30 * 24 * 60 * 60), '/');
                        }

                        // PENTING: Redirect berdasarkan role
                        if ($user['role'] === 'admin') {
                            header("Location: ../dashboard/dashboardadmin.php");
                        } elseif ($user['role'] === 'pemilik') {
                            header("Location: ../dashboard/dashboardpemilik.php");
                        } else { // Role 'penyewa'
                            header("Location: ../dashboard/dashboarduser.php");
                        }
                        exit();
                    } else {
                        $error = "Akun belum diaktifkan. Silakan periksa email Anda untuk link aktivasi.";
                        $show_resend = true; // Tampilkan link untuk kirim ulang email
                    }

                } else {
                    $error = "Password salah!";
                }
            }
        } else {
            $error = "Email tidak ditemukan!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - KosConnect</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../css/Login.css">
    <style>
        /* Pastikan teks OPSI di dalam dropdown berwarna hitam agar terbaca */
        .role-select option {
            color: #333; /* Warna teks hitam/gelap untuk keterbacaan */
        }
        /* Teks yang TERPILIH di dalam box tetap putih, sesuai tema */
        .role-select {
            color: white;
        }
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
            padding: 12px 24px;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.2), rgba(255, 255, 255, 0.1));
            border: 1px solid rgba(255, 255, 255, 0.4);
            color: #fff;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 10;
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            font-size: 14px;
            letter-spacing: 0.5px;
        }
        .back-to-home-btn i {
            margin-right: 8px;
            transition: transform 0.3s ease;
        }
        .back-to-home-btn:hover {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.3), rgba(255, 255, 255, 0.2));
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            border-color: rgba(255, 255, 255, 0.6);
        }
        .back-to-home-btn:hover i {
            transform: translateX(-2px);
        }
        .back-to-home-btn:active {
            transform: translateY(-1px) scale(1.02);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4);
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
        <form class="login-form" method="POST" action="loginForm.php">
            <div class="header">
                <h1 class="title">Kos<span class="highlight">Connect</span></h1>
                <p class="subtitle">Login</p>
                <p class="welcome">Silakan login ke akun Anda</p>
            </div>

            <div id="errorMessage" class="message-box hidden">
                <i class='bx bx-error-circle'></i>
                <span id="errorText"></span>
            </div>

            <?php
            if (isset($error) && $error) {
                echo "<script>document.getElementById('errorText').textContent = '" . addslashes($error) . "'; document.getElementById('errorMessage').className = 'message-box error'; document.getElementById('errorMessage').classList.remove('hidden');</script>";
            } elseif ($success_message) {
                 echo "<script>document.getElementById('errorText').textContent = '" . addslashes($success_message) . "'; document.getElementById('errorMessage').className = 'message-box success'; document.getElementById('errorMessage').querySelector('i').className = 'bx bx-check-circle'; document.getElementById('errorMessage').classList.remove('hidden');</script>";
            }
            ?>

            <div class="input-box">
                <div class="input-icon"><i class='bx bxs-user'></i></div>
                <input type="email" name="email" placeholder="Email" required class="input-field">
            </div>

            <div class="input-box">
                <div class="input-icon"><i class='bx bxs-lock-alt'></i></div>
                <input type="password" id="password" name="password" placeholder="Password" required class="input-field">
                <button type="button" id="togglePassword" class="toggle-password"><i class='bx bx-show'></i></button>
            </div>

            <div class="input-box">
                <div class="input-icon"><i class='bx bxs-user-pin'></i></div>
                <select name="role" class="input-field role-select" id="roleSelect" required>
                    <option value="" selected>Pilih Role</option>
                    <option value="penyewa">Penyewa</option>
                    <option value="pemilik">Pemilik</option>
                </select>
            </div>

            <button type="submit" class="btn-login">
                <i class='bx bx-log-in'></i>
                Masuk
            </button>

            <div class="register-link">
                <p>Belum punya akun?<a href="RegisterForm.php">Register Sekarang</a></p>
                <?php if ($show_resend): ?>
                <p>Belum menerima email aktivasi?<a href="resend.php">Kirim Ulang Link</a></p>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <script>
        // Panggil setup toggle password dari loginForm.js (yang harusnya di-include di sini)
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');

        if (togglePassword && password) {
            togglePassword.addEventListener('click', () => {
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                
                const icon = togglePassword.querySelector('i');
                icon.classList.toggle('bx-show');
                icon.classList.toggle('bx-hide');
            });
        }
        
        // Asumsi form.js juga mengelola efek animasi input
    </script>
</body>
</html>
