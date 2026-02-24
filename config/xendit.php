<?php
/**
 * Xendit Payment Gateway Configuration
 * 
 * IMPORTANT: Untuk mendapatkan API Key:
 * 1. Daftar di https://dashboard.xendit.co/register
 * 2. Login ke dashboard
 * 3. Pilih Test Mode (toggle di pojok kanan atas)
 * 4. Klik Settings > Developers > API Keys
 * 5. Copy Secret API Key
 * 
 * TEST MODE:
 * - Secret Key: xnd_development_xxxxx
 * 
 * LIVE MODE:
 * - Secret Key: xnd_production_xxxxx
 */

define('XENDIT_SECRET_KEY', 'xnd_development_Pd3VYU64CkzPW76O1AKwQsdeHjWyZdxjUVDNLOXl8WDVdBdKdNhFVAm5HEaZugyw');
define('XENDIT_IS_PRODUCTION', false);

// Invoice expiry: 86400 = 24 jam
define('XENDIT_INVOICE_EXPIRY', 86400);
define('XENDIT_ENABLED_PAYMENTS', [
    'BANK_TRANSFER',  // Virtual Account
    'EWALLET',        // OVO, DANA, LinkAja, ShopeePay
    'RETAIL_OUTLET',  // Alfamart, Indomaret
    'CREDIT_CARD',    // Kartu Kredit
    'QR_CODE',        // QRIS
]);

$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
$base_url = $protocol . "://" . $host;

$is_virtual_host = strpos($host, 'localhost') === false && strpos($host, '127.0.0.1') === false;
$path_prefix = $is_virtual_host ? '' : '/KosConnect';

define('XENDIT_CALLBACK_URL', $base_url . $path_prefix . '/api/xendit_callback.php');

define('XENDIT_SUCCESS_URL', $base_url . $path_prefix . '/user/user_dashboard.php?payment=success');
define('XENDIT_FAILURE_URL', $base_url . $path_prefix . '/user/user_dashboard.php?payment=failed');

/**
 * Initialize Xendit Configuration
 * Call this function before using Xendit
 */
function initXenditConfig() {
    require_once __DIR__ . '/../vendor/autoload.php';
    \Xendit\Configuration::setXenditKey(XENDIT_SECRET_KEY);
}

/**
 * Get Xendit Secret Key
 */
function getXenditSecretKey() {
    return XENDIT_SECRET_KEY;
}

/**
 * Check if Xendit is properly configured
 */
function isXenditConfigured() {
    return XENDIT_SECRET_KEY !== 'xnd_development_YOUR_SECRET_KEY_HERE' 
        && XENDIT_SECRET_KEY !== 'xnd_production_YOUR_SECRET_KEY_HERE';
}

/**
 * Get environment name
 */
function getXenditEnvironment() {
    return XENDIT_IS_PRODUCTION ? 'Production' : 'Test Mode';
}

/**
 * Generate unique external ID for invoice
 */
function generateXenditExternalId($bookingId) {
    return 'KOS-' . $bookingId . '-' . time();
}
?>
