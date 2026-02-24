<?php
/**
 * Database Configuration
 * 
 * IMPORTANT: Ganti kredensial berikut dengan kredensial dari hosting Anda
 * Untuk InfinityFree, cek di cPanel -> MySQL Databases
 */

// === DETECT ENVIRONMENT & SET CREDENTIALS ===
if (getenv('MYSQLHOST')) {
    // Railway Configuration
    $host = getenv('MYSQLHOST');
    $user = getenv('MYSQLUSER');
    $pass = getenv('MYSQLPASSWORD');
    $dbname = getenv('MYSQLDATABASE');
    $port = getenv('MYSQLPORT') ?: "3306";
} else {
    // Local Development Configuration
    $host = "localhost";
    $user = "root";
    $pass = "";
    $dbname = "kosconnect";
    $port = "3306";
}


// === DATABASE CONNECTION ===
try {
    $conn = new mysqli($host, $user, $pass, $dbname, $port);
    
    // Check connection
    if ($conn->connect_error) {
        // Log error untuk debugging
        error_log("Database connection failed: " . $conn->connect_error);
        
        // Tampilkan pesan user-friendly
        http_response_code(500);
        die("
        <!DOCTYPE html>
        <html>
        <head>
            <title>Database Connection Error</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 50px; text-align: center; }
                .error-box { 
                    background: #fee; 
                    border: 2px solid #f00; 
                    padding: 20px; 
                    border-radius: 10px; 
                    max-width: 600px; 
                    margin: 0 auto; 
                }
                h1 { color: #c00; }
                .details { 
                    background: #fff; 
                    padding: 15px; 
                    margin-top: 20px; 
                    border-radius: 5px; 
                    text-align: left; 
                }
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
                    </ol>
                    <p><strong>Error:</strong> " . $conn->connect_error . "</p>
                </div>
            </div>
        </body>
        </html>
        ");
    }
    
    // Set charset untuk mendukung karakter Indonesia
    $conn->set_charset("utf8mb4");
    
} catch (Throwable $e) {
    error_log("Database error: " . $e->getMessage());
    http_response_code(500);
    die("Database connection error. Details: " . $e->getMessage());
}
