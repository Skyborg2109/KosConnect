<?php
include 'config/db.php';

// Check and add updated_at column if it doesn't exist
$result = $conn->query("SHOW COLUMNS FROM kost LIKE 'updated_at'");
if ($result->num_rows === 0) {
    echo "Adding updated_at column...\n";
    $conn->query("ALTER TABLE kost ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
    echo "✓ Column added successfully\n";
} else {
    echo "✓ Column updated_at already exists\n";
}
