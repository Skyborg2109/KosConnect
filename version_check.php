<?php
// Script untuk cek environment hosting
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Hosting Check - KosConnect</title>
    <style>
        body { font-family: sans-serif; padding: 20px; line-height: 1.6; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .info { color: blue; }
        .box { border: 1px solid #ccc; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Hosting Environment Check</h1>
    
    <div class="box">
        <h3>1. PHP Version</h3>
        <p>Current Version: <strong><?php echo phpversion(); ?></strong></p>
        <?php if (version_compare(phpversion(), '7.4.0', '>=')): ?>
            <p class="success">✅ PHP Version OK (>= 7.4)</p>
        <?php else: ?>
            <p class="error">❌ PHP Version too old. Please upgrade to 7.4 or 8.x</p>
        <?php endif; ?>
    </div>

    <div class="box">
        <h3>2. Database Connection</h3>
        <?php
        $config_file = __DIR__ . '/config/db.php';
        if (file_exists($config_file)) {
            echo "<p class='success'>✅ Config file found: config/db.php</p>";
            
            // Try explicit connection
            // Note: We don't include db.php to avoid die() interruption if it fails
            // Instead we parse it or ask user to check
            echo "<p class='info'>ℹ️ To test connection, ensure your <code>config/db.php</code> has correct hosting credentials.</p>";
        } else {
            echo "<p class='error'>❌ Config file MISSING: config/db.php not found.</p>";
        }
        ?>
    </div>

    <div class="box">
        <h3>3. File Permissions</h3>
        <p>Current Directory: <?php echo __DIR__; ?></p>
        <p>Is Writable: <?php echo is_writable(__DIR__) ? '<span class="success">Yes</span>' : '<span class="error">No</span>'; ?></p>
    </div>
</body>
</html>
