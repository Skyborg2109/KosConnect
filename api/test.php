<?php
// Simple test file to verify API directory is accessible
header('Content-Type: application/json');
echo json_encode([
    'status' => 'success',
    'message' => 'API directory is accessible',
    'timestamp' => date('Y-m-d H:i:s'),
    'file' => __FILE__
]);
?>
