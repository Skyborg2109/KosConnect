<?php
/**
 * Midtrans Payment Gateway Configuration
 * 
 * IMPORTANT: Untuk mendapatkan API Keys:
 * 1. Daftar di https://dashboard.midtrans.com/register
 * 2. Login ke dashboard
 * 3. Pilih environment (Sandbox untuk testing, Production untuk live)
 * 4. Copy Server Key dan Client Key dari Settings > Access Keys
 * 
 * SANDBOX (Testing):
 * - Server Key: SB-Mid-server-xxxxx
 * - Client Key: SB-Mid-client-xxxxx
 * 
 * PRODUCTION (Live):
 * - Server Key: Mid-server-xxxxx
 * - Client Key: Mid-client-xxxxx
 */

// Load environment variables (jika menggunakan .env file)
// Atau langsung set di sini untuk development

define('MIDTRANS_SERVER_KEY', 'SB-Mid-server-YOUR_SERVER_KEY_HERE');
define('MIDTRANS_CLIENT_KEY', 'SB-Mid-client-YOUR_CLIENT_KEY_HERE');
define('MIDTRANS_MERCHANT_ID', 'YOUR_MERCHANT_ID');
define('MIDTRANS_IS_PRODUCTION', false);

define('MIDTRANS_IS_SANITIZED', true);
define('MIDTRANS_IS_3DS', true);

// === PAYMENT SETTINGS ===
// Enabled payment methods (bisa disesuaikan)
define('MIDTRANS_ENABLED_PAYMENTS', [
    'credit_card',
    'bca_va',
    'bni_va',
    'bri_va',
    'permata_va',
    'other_va',
    'gopay',
    'shopeepay',
    'qris',
]);

// Payment expiry: 1440 = 24 jam
define('MIDTRANS_EXPIRY_DURATION', 1440);

$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") 
            . "://" . $_SERVER['HTTP_HOST'];

// Detect path prefix (handles /KosConnect on localhost vs root on hosting)
$is_localhost = strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false;
$path_prefix = $is_localhost ? '/KosConnect' : '';

define('MIDTRANS_NOTIFICATION_URL', $base_url . $path_prefix . '/api/midtrans_notification.php');

define('MIDTRANS_FINISH_URL', $base_url . $path_prefix . '/user/user_dashboard.php?payment=success');
define('MIDTRANS_UNFINISH_URL', $base_url . $path_prefix . '/user/user_dashboard.php?payment=pending');
define('MIDTRANS_ERROR_URL', $base_url . $path_prefix . '/user/user_dashboard.php?payment=error');

/**
 * Initialize Midtrans Configuration
 * Call this function before using Midtrans
 */
function initMidtransConfig() {
    require_once __DIR__ . '/../vendor/autoload.php';
    \Midtrans\Config::$serverKey = MIDTRANS_SERVER_KEY;
    \Midtrans\Config::$clientKey = MIDTRANS_CLIENT_KEY;
    \Midtrans\Config::$isProduction = MIDTRANS_IS_PRODUCTION;
    \Midtrans\Config::$isSanitized = MIDTRANS_IS_SANITIZED;
    \Midtrans\Config::$is3ds = MIDTRANS_IS_3DS;
}

/**
 * Get Midtrans Client Key for frontend
 */
function getMidtransClientKey() {
    return MIDTRANS_CLIENT_KEY;
}

/**
 * Check if Midtrans is properly configured
 */
function isMidtransConfigured() {
    return MIDTRANS_SERVER_KEY !== 'SB-Mid-server-YOUR_SERVER_KEY_HERE' 
        && MIDTRANS_CLIENT_KEY !== 'SB-Mid-client-YOUR_CLIENT_KEY_HERE';
}

/**
 * Get environment name
 */
function getMidtransEnvironment() {
    return MIDTRANS_IS_PRODUCTION ? 'Production' : 'Sandbox';
}
?>
