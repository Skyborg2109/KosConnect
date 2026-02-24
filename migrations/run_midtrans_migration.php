<?php
/**
 * Run Midtrans Database Migrations
 * Execute this file once to create necessary tables
 */

require_once __DIR__ . '/../config/db.php';

echo "=== Midtrans Database Migration ===\n\n";

// Read and execute first migration
echo "1. Creating midtrans_transactions table...\n";
$sql1 = file_get_contents(__DIR__ . '/add_midtrans_transactions.sql');
if ($conn->multi_query($sql1)) {
    do {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->next_result());
    echo "✓ midtrans_transactions table created successfully!\n\n";
} else {
    echo "✗ Error: " . $conn->error . "\n\n";
}

// Read and execute second migration
echo "2. Altering pembayaran table...\n";
$sql2 = file_get_contents(__DIR__ . '/alter_pembayaran_table.sql');
if ($conn->multi_query($sql2)) {
    do {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->next_result());
    echo "✓ pembayaran table altered successfully!\n\n";
} else {
    echo "✗ Error: " . $conn->error . "\n\n";
}

echo "=== Migration Completed ===\n";
echo "You can now use Midtrans payment gateway!\n";

$conn->close();
?>
