<?php
/**
 * Database Migration Script
 * Jalankan file ini di browser untuk membuat tabel user_sessions
 * 
 * Akses di: http://localhost/KosConnect/migrate.php?create_sessions=true
 */

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['create_sessions'])) {
    require_once 'config/db.php';
    
    $sql = "CREATE TABLE IF NOT EXISTS user_sessions (
        id_session INT AUTO_INCREMENT PRIMARY KEY,
        id_user INT NOT NULL,
        session_token VARCHAR(255) NOT NULL UNIQUE,
        device_name VARCHAR(255),
        user_agent TEXT,
        ip_address VARCHAR(45),
        login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        is_active BOOLEAN DEFAULT 1,
        FOREIGN KEY (id_user) REFERENCES user(id_user) ON DELETE CASCADE,
        INDEX idx_user (id_user),
        INDEX idx_token (session_token),
        INDEX idx_active (is_active)
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo '<div style="padding: 20px; background: #d4edda; color: #155724; border-radius: 5px;">';
        echo '<h2>✅ Sukses!</h2>';
        echo '<p>Tabel <strong>user_sessions</strong> telah dibuat berhasil.</p>';
        echo '<p>Sekarang sistem sudah support multi-device login!</p>';
        echo '</div>';
    } else {
        echo '<div style="padding: 20px; background: #f8d7da; color: #721c24; border-radius: 5px;">';
        echo '<h2>❌ Error</h2>';
        echo '<p>Error: ' . $conn->error . '</p>';
        echo '</div>';
    }
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>KosConnect - Database Migration</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 40px;
            background: #f5f5f5;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
        }
        .info {
            background: #e7f3ff;
            padding: 15px;
            border-left: 4px solid #2196F3;
            margin: 20px 0;
        }
        .features {
            list-style: none;
            padding: 0;
        }
        .features li {
            padding: 8px 0;
            padding-left: 25px;
            position: relative;
        }
        .features li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #4CAF50;
            font-weight: bold;
        }
        .btn {
            background: #4CAF50;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            background: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Multi-Device Session System</h1>
        
        <div class="info">
            <strong>Fitur Baru:</strong> Pengguna sekarang dapat login di multiple devices/browsers secara bersamaan!
        </div>
        
        <h3>✨ Fitur yang Ditambahkan:</h3>
        <ul class="features">
            <li>Login di multiple devices/browsers secara bersamaan</li>
            <li>Automatic device detection (Windows, Mac, iOS, Android, etc.)</li>
            <li>Track IP address dan browser untuk keamanan</li>
            <li>Logout dari device tertentu tanpa logout dari devices lain</li>
            <li>Session token untuk persistent access</li>
            <li>Automatic cleanup old sessions (30 hari)</li>
        </ul>
        
        <h3>🚀 Cara Mengaktifkan:</h3>
        <p>Klik tombol di bawah untuk membuat tabel <strong>user_sessions</strong> di database:</p>
        
        <a href="?create_sessions=true" class="btn">Buat Tabel user_sessions</a>
        
        <div class="info" style="margin-top: 30px;">
            <strong>ℹ️ Info Teknis:</strong><br>
            - File utama: <code>/config/SessionManager.php</code><br>
            - Helper: <code>/config/SessionChecker.php</code><br>
            - Manage sessions: <code>/admin/manage_sessions.php</code><br>
            - Modified: loginForm.php, logout.php, dashboards
        </div>
    </div>
</body>
</html>
