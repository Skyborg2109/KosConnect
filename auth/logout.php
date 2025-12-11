<?php
session_start();
require_once '../config/db.php';
require_once '../config/SessionManager.php';

// Logout current session token
if (isset($_SESSION['session_token'])) {
    $sessionManager = new SessionManager($conn);
    $sessionManager->logoutSession($_SESSION['session_token']);
}

// Also clear cookie token
if (isset($_COOKIE['session_token'])) {
    setcookie('session_token', '', time() - 3600, '/');
}

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
    // Also set a general cookie clear
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: loginForm.php");
exit();
?>

