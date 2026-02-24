<?php
/**
 * Session Checker - Helper untuk multi-device session validation
 */

function checkMultiDeviceSession($conn) {
    $session_token = $_SESSION['session_token'] ?? $_COOKIE['session_token'] ?? null;
    
    if (!$session_token) {
        return false;
    }
    
    require_once __DIR__ . '/SessionManager.php';
    $sessionManager = new SessionManager($conn);
    $user_id = $sessionManager->validateSessionToken($session_token);
    
    if ($user_id) {
        // Restore session data if missing (Role, Fullname, Logged In status)
        if (!isset($_SESSION['user_logged_in']) || !isset($_SESSION['role'])) {
            $stmt = $conn->prepare("SELECT role, nama_lengkap FROM user WHERE id_user = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($user = $res->fetch_assoc()) {
                $_SESSION['user_logged_in'] = true;
                $_SESSION['role'] = $user['role'];
                $_SESSION['fullname'] = $user['nama_lengkap'];
            }
            $stmt->close();
        }

        if (!isset($_SESSION['user_id'])) {
            $_SESSION['user_id'] = $user_id;
        }
        if (!isset($_SESSION['session_token'])) {
            $_SESSION['session_token'] = $session_token;
        }
        return true;
    }
    
    return false;
}

/**
 * Get current session info
 */
function getCurrentSessionInfo($conn, $token) {
    $sql = "SELECT id_session, id_user, device_name, user_agent, ip_address, login_time, last_activity
            FROM user_sessions 
            WHERE session_token = ? AND is_active = 1
            LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $token);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
    
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    
    return null;
}

/**
 * Format device info untuk display
 */
function getDeviceIcon($device_name) {
    if (stripos($device_name, 'windows') !== false) {
        return '<i class="fas fa-window-maximize text-blue-500"></i>';
    } elseif (stripos($device_name, 'mac') !== false || stripos($device_name, 'iphone') !== false) {
        return '<i class="fas fa-apple text-gray-700"></i>';
    } elseif (stripos($device_name, 'android') !== false) {
        return '<i class="fas fa-android text-green-500"></i>';
    } elseif (stripos($device_name, 'linux') !== false) {
        return '<i class="fab fa-linux text-red-500"></i>';
    }
    return '<i class="fas fa-desktop text-gray-500"></i>';
}
?>
