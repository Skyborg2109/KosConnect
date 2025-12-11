<?php
/**
 * Multi-Device Session Manager
 * Memungkinkan user untuk login di multiple devices/browsers secara bersamaan
 */

class SessionManager {
    private $conn;
    private $user_id;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Generate unique session token untuk device baru
     */
    public function createSessionToken($user_id, $device_name = null) {
        $this->user_id = $user_id;
        
        // Generate random token
        $token = bin2hex(random_bytes(32));
        $device_name = $device_name ?? $this->getDeviceName();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $ip_address = $this->getClientIP();
        
        // Insert ke database
        $sql = "INSERT INTO user_sessions (id_user, session_token, device_name, user_agent, ip_address) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "issss", $user_id, $token, $device_name, $user_agent, $ip_address);
        
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return $token;
        }
        
        mysqli_stmt_close($stmt);
        return false;
    }
    
    /**
     * Validate session token
     */
    public function validateSessionToken($token) {
        $sql = "SELECT id_user, is_active FROM user_sessions 
                WHERE session_token = ? AND is_active = 1 
                LIMIT 1";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $token);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        mysqli_stmt_close($stmt);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $session = mysqli_fetch_assoc($result);
            // Update last activity
            $this->updateLastActivity($token);
            return $session['id_user'];
        }
        
        return false;
    }
    
    /**
     * Update last activity time untuk token
     */
    private function updateLastActivity($token) {
        $sql = "UPDATE user_sessions SET last_activity = NOW() 
                WHERE session_token = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $token);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    
    /**
     * Get semua active sessions untuk user
     */
    public function getUserSessions($user_id) {
        $sql = "SELECT id_session, session_token, device_name, user_agent, ip_address, 
                login_time, last_activity 
                FROM user_sessions 
                WHERE id_user = ? AND is_active = 1 
                ORDER BY last_activity DESC";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        mysqli_stmt_close($stmt);
        
        $sessions = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $sessions[] = $row;
        }
        
        return $sessions;
    }
    
    /**
     * Logout dari session tertentu (logout dari device tertentu)
     */
    public function logoutSession($token) {
        $sql = "UPDATE user_sessions SET is_active = 0 
                WHERE session_token = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $token);
        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        return $result;
    }
    
    /**
     * Logout dari semua sessions (logout everywhere)
     */
    public function logoutAllSessions($user_id) {
        $sql = "UPDATE user_sessions SET is_active = 0 
                WHERE id_user = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        return $result;
    }
    
    /**
     * Get device name dari user agent
     */
    private function getDeviceName() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown Device';
        
        // Detect OS
        if (strpos($user_agent, 'Windows') !== false) {
            $os = 'Windows';
        } elseif (strpos($user_agent, 'Mac') !== false) {
            $os = 'macOS';
        } elseif (strpos($user_agent, 'Linux') !== false) {
            $os = 'Linux';
        } elseif (strpos($user_agent, 'iPhone') !== false) {
            $os = 'iPhone';
        } elseif (strpos($user_agent, 'Android') !== false) {
            $os = 'Android';
        } else {
            $os = 'Unknown OS';
        }
        
        // Detect Browser
        if (strpos($user_agent, 'Chrome') !== false) {
            $browser = 'Chrome';
        } elseif (strpos($user_agent, 'Safari') !== false) {
            $browser = 'Safari';
        } elseif (strpos($user_agent, 'Firefox') !== false) {
            $browser = 'Firefox';
        } elseif (strpos($user_agent, 'Edge') !== false) {
            $browser = 'Edge';
        } else {
            $browser = 'Unknown Browser';
        }
        
        return "$browser on $os";
    }
    
    /**
     * Get client IP address
     */
    private function getClientIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        }
        
        return trim($ip);
    }
    
    /**
     * Clean expired sessions (older than 30 days)
     */
    public function cleanExpiredSessions() {
        $sql = "DELETE FROM user_sessions 
                WHERE last_activity < DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $stmt = mysqli_prepare($this->conn, $sql);
        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        return $result;
    }
}
?>
