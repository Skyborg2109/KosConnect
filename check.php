<?php
echo "<h1>System Check</h1>";
echo "<p>Directory: " . getcwd() . "</p>";
echo "<p>PHP Version: " . phpversion() . "</p>";

if (file_exists('config/db.php')) {
    echo "<p>✅ config/db.php FOUND</p>";
    include 'config/db.php';
    if (isset($conn)) {
        echo "<p>✅ Database Connected Successfully!</p>";
    } else {
        echo "<p>❌ Database Connection Failed (Variable \$conn not set)</p>";
    }
} else {
    echo "<p>❌ config/db.php NOT FOUND</p>";
}
?>
