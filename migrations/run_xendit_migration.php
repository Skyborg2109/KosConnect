<?php
/**
 * Run Xendit Database Migrations
 * Execute this file once to create necessary tables
 */

require_once __DIR__ . '/../config/db.php';

echo "=== Xendit Database Migration ===\n\n";

// Read and execute first migration
echo "1. Creating xendit_invoices table...\n";
$sql1 = file_get_contents(__DIR__ . '/add_xendit_invoices.sql');
if ($conn->multi_query($sql1)) {
    do {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->next_result());
    echo "✓ xendit_invoices table created successfully!\n\n";
} else {
    echo "✗ Error: " . $conn->error . "\n\n";
}

// Read and execute second migration
echo "2. Altering pembayaran table for Xendit...\n";
$sql2 = file_get_contents(__DIR__ . '/alter_pembayaran_for_xendit.sql');
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
echo "Xendit payment gateway is ready to use!\n";

$conn->close();
?>
