<?php
session_start();
include '../config/db.php';
include '../config/SessionManager.php';

// Get request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }
    
    $user_id = $_SESSION['user_id'];
    $sessionManager = new SessionManager($conn);
    
    if ($action === 'logout_device') {
        // Logout dari device tertentu
        $session_id = $_POST['session_id'] ?? null;
        
        if ($session_id) {
            // Get token dari id
            $sql = "SELECT session_token FROM user_sessions 
                    WHERE id_session = ? AND id_user = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ii", $session_id, $user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if ($row = mysqli_fetch_assoc($result)) {
                $sessionManager->logoutSession($row['session_token']);
                echo json_encode(['success' => true, 'message' => 'Device telah dilogout']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Session tidak ditemukan']);
            }
            mysqli_stmt_close($stmt);
        }
    } elseif ($action === 'logout_all') {
        // Logout dari semua devices
        $sessionManager->logoutAllSessions($user_id);
        session_destroy();
        echo json_encode(['success' => true, 'message' => 'Semua devices telah dilogout']);
    } elseif ($action === 'get_sessions') {
        // Get semua active sessions
        $sessions = $sessionManager->getUserSessions($user_id);
        
        // Render HTML
        $html = '';
        if (count($sessions) > 0) {
            foreach ($sessions as $session) {
                $is_current = ($session['session_token'] === ($_SESSION['session_token'] ?? ''));
                $html .= '<div class="bg-white border border-gray-200 rounded-lg p-4 mb-3">';
                $html .= '<div class="flex justify-between items-start">';
                $html .= '<div class="flex-1">';
                $html .= '<h4 class="font-semibold text-gray-800">' . htmlspecialchars($session['device_name']) . ($is_current ? ' <span class="text-green-600 text-sm">(Device ini)</span>' : '') . '</h4>';
                $html .= '<p class="text-sm text-gray-600 mt-1"><i class="fas fa-map-marker-alt mr-1"></i>' . htmlspecialchars($session['ip_address']) . '</p>';
                $html .= '<p class="text-xs text-gray-500 mt-1">Login: ' . date('d M Y H:i', strtotime($session['login_time'])) . '</p>';
                $html .= '<p class="text-xs text-gray-500">Aktivitas terakhir: ' . date('d M Y H:i', strtotime($session['last_activity'])) . '</p>';
                $html .= '</div>';
                if (!$is_current) {
                    $html .= '<button onclick="logoutDevice(' . $session['id_session'] . ')" class="text-red-500 hover:text-red-700 text-sm font-semibold">';
                    $html .= '<i class="fas fa-times mr-1"></i>Logout</button>';
                }
                $html .= '</div>';
                $html .= '</div>';
            }
        } else {
            $html = '<p class="text-gray-600">Tidak ada session aktif</p>';
        }
        
        echo json_encode(['success' => true, 'html' => $html, 'count' => count($sessions)]);
    }
    exit();
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid request']);
?>
