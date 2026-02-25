<?php
// === DETECT ENVIRONMENT & SET CREDENTIALS ===
// Menambah metode $_SERVER untuk memastikan variabel terbaca di berbagai jenis server cloud
$host   = getenv('DB_HOST') ?: $_ENV['DB_HOST'] ?: (isset($_SERVER['DB_HOST']) ? $_SERVER['DB_HOST'] : "localhost");
$user   = getenv('DB_USER') ?: $_ENV['DB_USER'] ?: (isset($_SERVER['DB_USER']) ? $_SERVER['DB_USER'] : "root");
$pass   = getenv('DB_PASS') ?: $_ENV['DB_PASS'] ?: (isset($_SERVER['DB_PASS']) ? $_SERVER['DB_PASS'] : "");
$dbname = getenv('DB_NAME') ?: $_ENV['DB_NAME'] ?: (isset($_SERVER['DB_NAME']) ? $_SERVER['DB_NAME'] : "kosconnect");
$port   = getenv('DB_PORT') ?: $_ENV['DB_PORT'] ?: (isset($_SERVER['DB_PORT']) ? $_SERVER['DB_PORT'] : "3306");

// === DATABASE CONNECTION ===
try {
    $conn = new mysqli($host, $user, $pass, $dbname, $port);
    
    if ($conn->connect_error) {
        throw new Exception($conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    
} catch (Throwable $e) {
    http_response_code(500);
    // Bagian ini akan mencetak nilai variabel ke layar agar kita tahu apa yang salah terbaca
    die("
    <div style='font-family: Arial; padding: 20px; border: 2px solid red; background: #fee; max-width: 600px; margin: 20px auto;'>
        <h2 style='color: red;'>Koneksi Database Gagal</h2>
        <p><strong>Error MySQL:</strong> " . $e->getMessage() . "</p>
        <hr>
        <h3>🔍 Hasil Debugging Variabel:</h3>
        <p>Berikut adalah data yang sedang dicoba dibaca oleh sistem (Jika Host berisi 'localhost', berarti variabel Railway belum masuk/terbaca):</p>
        <ul>
            <li><strong>Host:</strong> <code>" . htmlspecialchars($host) . "</code></li>
            <li><strong>User:</strong> <code>" . htmlspecialchars($user) . "</code></li>
            <li><strong>Database:</strong> <code>" . htmlspecialchars($dbname) . "</code></li>
            <li><strong>Port:</strong> <code>" . htmlspecialchars($port) . "</code></li>
        </ul>
    </div>
    ");
}
?>