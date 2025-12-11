<?php
session_start();
require_once '../config/db.php';
require_once '../config/SessionManager.php';
require_once '../config/SessionChecker.php';

// Check apakah user sudah login
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: ../auth/loginForm.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$sessionManager = new SessionManager($conn);

// Handle logout dari session tertentu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'logout_session' && isset($_POST['session_token'])) {
        $token_to_logout = $_POST['session_token'];
        $sessionManager->logoutSession($token_to_logout);
        
        // Jika logout device sendiri, redirect ke login
        if ($token_to_logout === ($_SESSION['session_token'] ?? null)) {
            unset($_SESSION['user_logged_in']);
            unset($_SESSION['session_token']);
            unset($_SESSION['user_id']);
            setcookie('session_token', '', time() - 3600, '/');
            header("Location: ../auth/loginForm.php?message=logout_success");
            exit();
        }
        
        // Jika logout device lain, refresh halaman
        header("Location: manage_sessions.php?message=session_removed");
        exit();
    } elseif ($action === 'logout_all') {
        // Logout semua devices
        $sessionManager->logoutAllSessions($user_id);
        unset($_SESSION['user_logged_in']);
        unset($_SESSION['session_token']);
        unset($_SESSION['user_id']);
        setcookie('session_token', '', time() - 3600, '/');
        header("Location: ../auth/loginForm.php?message=logout_all_success");
        exit();
    }
}

// Get semua active sessions user
$active_sessions = $sessionManager->getUserSessions($user_id);
$current_token = $_SESSION['session_token'] ?? null;

// Get user data
$sql = "SELECT nama_lengkap, email FROM user WHERE id_user = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Sesi - KosConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 900px;
            margin-top: 40px;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            margin-bottom: 30px;
        }

        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px;
        }

        .card-header h4 {
            margin: 0;
            font-weight: 600;
        }

        .session-item {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            background: #f8f9fa;
            transition: all 0.3s ease;
        }

        .session-item:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .session-item.current {
            border-left: 4px solid #28a745;
            background: #f0f8f5;
        }

        .session-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .device-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .device-badge.current {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }

        .current-badge {
            display: inline-block;
            background: #28a745;
            color: white;
            padding: 3px 8px;
            border-radius: 15px;
            font-size: 11px;
            margin-left: 10px;
        }

        .session-details {
            font-size: 14px;
            color: #666;
            margin: 8px 0;
        }

        .session-details i {
            color: #667eea;
            margin-right: 8px;
            width: 20px;
        }

        .btn-logout {
            padding: 6px 12px;
            font-size: 12px;
        }

        .info-alert {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            color: #0c5aa0;
        }

        .info-alert i {
            margin-right: 10px;
        }

        .btn-logout-all {
            margin-top: 20px;
        }

        .user-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .user-info h5 {
            margin-bottom: 5px;
            font-weight: 600;
        }

        .user-info p {
            margin: 0;
            opacity: 0.95;
            font-size: 14px;
        }

        .back-btn {
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .session-header {
                flex-direction: column !important;
                align-items: flex-start !important;
            }

            .device-badge {
                margin-top: 10px !important;
            }

            h1 {
                font-size: 1.75rem !important;
            }

            h2 {
                font-size: 1.25rem !important;
            }

            h3 {
                font-size: 1.1rem !important;
            }

            p {
                font-size: 0.9rem !important;
            }

            .container {
                padding: 1rem !important;
            }

            .px-4 {
                padding-left: 1rem !important;
                padding-right: 1rem !important;
            }

            .px-6 {
                padding-left: 1rem !important;
                padding-right: 1rem !important;
            }

            .py-4 {
                padding-top: 0.75rem !important;
                padding-bottom: 0.75rem !important;
            }

            .py-6 {
                padding-top: 1rem !important;
                padding-bottom: 1rem !important;
            }

            .gap-4 {
                gap: 0.75rem !important;
            }

            .gap-6 {
                gap: 0.75rem !important;
            }

            .space-y-4 > * + * {
                margin-top: 0.75rem !important;
            }

            .space-y-6 > * + * {
                margin-top: 1rem !important;
            }

            table {
                font-size: 0.8rem !important;
            }

            th,
            td {
                padding: 0.5rem !important;
            }

            .text-sm {
                font-size: 0.75rem !important;
            }

            .text-lg {
                font-size: 1.1rem !important;
            }

            .text-xl {
                font-size: 1.25rem !important;
            }

            .text-2xl {
                font-size: 1.5rem !important;
            }

            button {
                padding: 0.75rem 1rem !important;
                font-size: 0.9rem !important;
            }

            .btn {
                padding: 0.75rem 1rem !important;
                font-size: 0.9rem !important;
            }

            input,
            textarea,
            select {
                padding: 0.75rem !important;
                font-size: 1rem !important;
            }

            .flex {
                flex-direction: row !important;
                flex-wrap: wrap !important;
                gap: 0.75rem !important;
            }

            .flex.flex-col {
                flex-direction: column !important;
                gap: 0.75rem !important;
            }

            .grid {
                grid-template-columns: 1fr !important;
                gap: 0.75rem !important;
            }

            .grid.grid-cols-2 {
                grid-template-columns: repeat(2, 1fr) !important;
            }

            .rounded-lg {
                border-radius: 0.5rem !important;
            }

            .rounded-xl {
                border-radius: 0.75rem !important;
            }

            .shadow-lg {
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08) !important;
            }

            .m-4 {
                margin: 0.75rem !important;
            }

            .m-6 {
                margin: 1rem !important;
            }

            .mt-4 {
                margin-top: 0.75rem !important;
            }

            .mt-6 {
                margin-top: 1rem !important;
            }

            .mb-4 {
                margin-bottom: 0.75rem !important;
            }

            .mb-6 {
                margin-bottom: 1rem !important;
            }

            .w-full {
                width: 100% !important;
            }

            .back-btn {
                margin-bottom: 1rem !important;
            }

            .user-info {
                padding: 1rem !important;
            }

            .session-row {
                padding: 0.75rem !important;
                margin: 0.75rem 0 !important;
            }

            .device-badge {
                font-size: 0.85rem !important;
            }

            .status-badge {
                font-size: 0.75rem !important;
                padding: 0.25rem 0.5rem !important;
            }
        }

        @media (max-width: 640px) {
            h1 {
                font-size: 1.5rem !important;
            }

            h2 {
                font-size: 1.1rem !important;
            }

            h3 {
                font-size: 1rem !important;
            }

            p {
                font-size: 0.85rem !important;
            }

            .text-sm {
                font-size: 0.7rem !important;
            }

            .text-lg {
                font-size: 1rem !important;
            }

            .text-xl {
                font-size: 1.1rem !important;
            }

            .text-2xl {
                font-size: 1.25rem !important;
            }

            .container {
                padding: 0.75rem !important;
            }

            .px-4,
            .px-6 {
                padding-left: 0.75rem !important;
                padding-right: 0.75rem !important;
            }

            .py-4,
            .py-6 {
                padding-top: 0.5rem !important;
                padding-bottom: 0.5rem !important;
            }

            .gap-4,
            .gap-6 {
                gap: 0.5rem !important;
            }

            .space-y-4 > * + *,
            .space-y-6 > * + * {
                margin-top: 0.5rem !important;
            }

            .flex {
                flex-direction: column !important;
                gap: 0.5rem !important;
            }

            button,
            .btn {
                padding: 0.65rem 0.9rem !important;
                font-size: 0.85rem !important;
            }

            input,
            textarea,
            select {
                padding: 0.65rem !important;
                font-size: 1rem !important;
            }

            .grid {
                grid-template-columns: 1fr !important;
                gap: 0.5rem !important;
            }

            table {
                font-size: 0.75rem !important;
            }

            th,
            td {
                padding: 0.375rem !important;
            }

            .rounded-lg {
                border-radius: 0.375rem !important;
            }

            .rounded-xl {
                border-radius: 0.5rem !important;
            }

            .m-4,
            .m-6 {
                margin: 0.5rem !important;
            }

            .mt-4,
            .mt-6 {
                margin-top: 0.5rem !important;
            }

            .mb-4,
            .mb-6 {
                margin-bottom: 0.5rem !important;
            }

            .shadow-lg {
                box-shadow: 0 3px 10px rgba(0, 0, 0, 0.06) !important;
            }

            .session-row {
                padding: 0.5rem !important;
                margin: 0.5rem 0 !important;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="back-btn">
            <a href="javascript:history.back()" class="btn btn-outline-light">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>

        <!-- User Info -->
        <div class="user-info">
            <h5><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($user_data['nama_lengkap']); ?></h5>
            <p><?php echo htmlspecialchars($user_data['email']); ?></p>
        </div>

        <!-- Info Alert -->
        <div class="info-alert">
            <i class="fas fa-info-circle"></i>
            <strong>Multi-Login Aktif!</strong> Anda dapat login di beberapa device secara bersamaan. Halaman ini menampilkan semua device yang sedang aktif.
        </div>

        <!-- Active Sessions Card -->
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-laptop"></i> Device yang Sedang Aktif</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($active_sessions)): ?>
                    <?php foreach ($active_sessions as $session): ?>
                    <div class="session-item <?php echo ($session['session_token'] === $current_token) ? 'current' : ''; ?>">
                        <div class="session-header">
                            <div>
                                <strong><?php echo htmlspecialchars($session['device_name']); ?></strong>
                                <?php if ($session['session_token'] === $current_token): ?>
                                    <span class="current-badge"><i class="fas fa-check"></i> DEVICE INI</span>
                                <?php endif; ?>
                            </div>
                            <?php if ($session['session_token'] !== $current_token): ?>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Logout dari device ini?');">
                                <input type="hidden" name="action" value="logout_session">
                                <input type="hidden" name="session_token" value="<?php echo htmlspecialchars($session['session_token']); ?>">
                                <button type="submit" class="btn btn-sm btn-danger btn-logout">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                        
                        <div class="session-details">
                            <div><i class="fas fa-globe"></i> IP: <?php echo htmlspecialchars($session['ip_address']); ?></div>
                            <div><i class="fas fa-calendar"></i> Login: <?php echo date('d M Y - H:i', strtotime($session['login_time'])); ?></div>
                            <div><i class="fas fa-hourglass-end"></i> Akses Terakhir: <?php echo date('d M Y - H:i', strtotime($session['last_activity'])); ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <!-- Logout All Button -->
                    <form method="POST" onsubmit="return confirm('Logout dari SEMUA device? Anda harus login kembali.');" class="text-center btn-logout-all">
                        <input type="hidden" name="action" value="logout_all">
                        <button type="submit" class="btn btn-outline-danger">
                            <i class="fas fa-power-off"></i> Logout dari Semua Device
                        </button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-info" role="alert">
                        <i class="fas fa-info-circle"></i> Tidak ada session aktif
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tips Card -->
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-lightbulb"></i> Tips Keamanan</h4>
            </div>
            <div class="card-body">
                <ul class="mb-0">
                    <li>Periksa secara berkala device yang login untuk memastikan hanya device Anda saja yang aktif</li>
                    <li>Jika ada device yang tidak Anda kenal, segera logout dari device tersebut</li>
                    <li>Gunakan "Logout dari Semua Device" jika Anda merasa akun Anda tidak aman</li>
                    <li>Setiap session memiliki token unik yang tidak bisa dibagikan antar device</li>
                </ul>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show message jika ada
        <?php if (isset($_GET['message'])): ?>
        <?php
            $messages = [
                'session_removed' => ['type' => 'success', 'text' => 'Device berhasil dihapus dari akun Anda'],
                'logout_success' => ['type' => 'info', 'text' => 'Logout berhasil'],
                'logout_all_success' => ['type' => 'info', 'text' => 'Logout dari semua device berhasil']
            ];
            if (isset($messages[$_GET['message']])): 
                $msg = $messages[$_GET['message']];
        ?>
        Swal.fire({
            icon: '<?php echo $msg['type']; ?>',
            title: '<?php echo $msg['text']; ?>',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });
        <?php endif; ?>
        <?php endif; ?>
    </script>
</body>
</html>
