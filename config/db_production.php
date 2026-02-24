<?php
/**
 * Database Configuration for InfinityFree
 * 
 * IMPORTANT: Update kredensial dengan data dari cPanel Anda
 */

// === PRODUCTION CONFIGURATION (InfinityFree) ===
// Ganti dengan kredensial dari hosting Anda
$host = "sql###.infinityfree.com"; // Ganti ### dengan nomor server Anda (cek di cPanel)
$user = "epiz_xxxxx"; // Ganti dengan username database Anda
$pass = "your_password_here"; // Ganti dengan password database Anda
$dbname = "epiz_xxxxx_kosconnect"; // Ganti dengan nama database Anda

// === DATABASE CONNECTION ===
try {
    $conn = new mysqli($host, $user, $pass, $dbname);
    
    // Check connection
    if ($conn->connect_error) {
        // Log error untuk debugging
        error_log("Database connection failed: " . $conn->connect_error);
        
        // Tampilkan pesan user-friendly
        die("
        <!DOCTYPE html>
        <html>
        <head>
            <title>Database Connection Error</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 50px; text-align: center; background: #f5f5f5; }
                .error-box { 
                    background: #fff; 
                    border: 2px solid #e74c3c; 
                    padding: 30px; 
                    border-radius: 10px; 
                    max-width: 600px; 
                    margin: 0 auto;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                }
                h1 { color: #e74c3c; margin-bottom: 20px; }
                .details { 
                    background: #f8f9fa; 
                    padding: 20px; 
                    margin-top: 20px; 
                    border-radius: 5px; 
                    text-align: left; 
                }
                code { background: #e9ecef; padding: 2px 6px; border-radius: 3px; }
                ol { text-align: left; }
            </style>
        </head>
        <body>
            <div class='error-box'>
                <h1>⚠️ Database Connection Error</h1>
                <p>Tidak dapat terhubung ke database. Silakan periksa konfigurasi database Anda.</p>
                <div class='details'>
                    <h3>Langkah Troubleshooting:</h3>
                    <ol>
                        <li>Pastikan database sudah dibuat di cPanel</li>
                        <li>Verifikasi kredensial di file <code>config/db.php</code></li>
                        <li>Pastikan user database memiliki privileges</li>
                        <li>Import file SQL ke database</li>
                        <li>Cek hostname database (biasanya sql###.infinityfree.com)</li>
                    </ol>
                    <p><strong>Error:</strong> " . htmlspecialchars($conn->connect_error) . "</p>
                </div>
            </div>
        </body>
        </html>
        ");
    }
    
    // Set charset untuk mendukung karakter Indonesia
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    die("Database connection error. Please contact administrator.");
}
?>
