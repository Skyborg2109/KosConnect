<?php
session_start();
if (!isset($_SESSION['user_logged_in'])) {
    $_SESSION['user_logged_in'] = true;
    $_SESSION['fullname'] = 'Test User';
    $_SESSION['role'] = 'penyewa';
    $_SESSION['user_id'] = 1;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Button</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-50">
    <div class="p-8 max-w-md mx-auto mt-10">
        <h1 class="text-2xl font-bold mb-6">Test Button Mobile Drawer</h1>
        
        <!-- Test Buttons -->
        <div class="space-y-3">
            <button id="mobileProfileBtn" class="w-full px-4 py-3 bg-purple-600 text-white rounded-lg" type="button">
                <i class="fas fa-user mr-2"></i>Profil
            </button>
            <button id="mobileNotifBtn" class="w-full px-4 py-3 bg-blue-500 text-white rounded-lg" type="button">
                <i class="fas fa-bell mr-2"></i>Notifikasi
            </button>
            <button id="mobileLogoutBtn" class="w-full px-4 py-3 bg-red-500 text-white rounded-lg" type="button">
                <i class="fas fa-sign-out-alt mr-2"></i>Logout
            </button>
            
            <!-- Alternative Test Buttons -->
            <hr class="my-6">
            <button onclick="testAlert('onclick works')" class="w-full px-4 py-3 bg-green-600 text-white rounded-lg">
                Test onclick
            </button>
            <button onclick="directAction()" class="w-full px-4 py-3 bg-yellow-600 text-white rounded-lg">
                Test Direct Action
            </button>
        </div>
        
        <!-- Output -->
        <div class="mt-8 p-4 bg-white rounded-lg border border-gray-200">
            <h3 class="font-bold mb-2">Console Output:</h3>
            <div id="output" class="text-sm font-mono space-y-1"></div>
        </div>
    </div>

    <script>
        // Log function
        function log(msg) {
            console.log(msg);
            const output = document.getElementById('output');
            const line = document.createElement('div');
            line.textContent = `[${new Date().toLocaleTimeString()}] ${msg}`;
            line.className = 'text-gray-700';
            output.appendChild(line);
        }

        // Test function
        function testAlert(msg) {
            log(`testAlert called: ${msg}`);
            alert(msg);
        }

        function directAction() {
            log('directAction() called');
            Swal.fire('Direct Action Worked!');
        }

        // Setup DOM event listeners
        log('Script loading...');

        document.addEventListener('DOMContentLoaded', function() {
            log('DOMContentLoaded event fired');
            
            const profileBtn = document.getElementById('mobileProfileBtn');
            const notifBtn = document.getElementById('mobileNotifBtn');
            const logoutBtn = document.getElementById('mobileLogoutBtn');
            
            log(`profileBtn found: ${profileBtn ? 'YES' : 'NO'}`);
            log(`notifBtn found: ${notifBtn ? 'YES' : 'NO'}`);
            log(`logoutBtn found: ${logoutBtn ? 'YES' : 'NO'}`);
            
            if (profileBtn) {
                log('Adding mousedown listener to profileBtn');
                profileBtn.addEventListener('mousedown', function(e) {
                    log('profileBtn MOUSEDOWN event fired');
                    e.preventDefault();
                    e.stopPropagation();
                    alert('Profile button clicked!');
                });
                
                profileBtn.addEventListener('click', function(e) {
                    log('profileBtn CLICK event fired');
                    e.preventDefault();
                    e.stopPropagation();
                });
            }
            
            if (notifBtn) {
                log('Adding mousedown listener to notifBtn');
                notifBtn.addEventListener('mousedown', function(e) {
                    log('notifBtn MOUSEDOWN event fired');
                    e.preventDefault();
                    e.stopPropagation();
                    alert('Notifikasi button clicked!');
                });
            }
            
            if (logoutBtn) {
                log('Adding mousedown listener to logoutBtn');
                logoutBtn.addEventListener('mousedown', function(e) {
                    log('logoutBtn MOUSEDOWN event fired');
                    e.preventDefault();
                    e.stopPropagation();
                    alert('Logout button clicked!');
                });
            }
            
            log('Setup complete. Try clicking buttons.');
        });

        // Also setup on window load
        window.addEventListener('load', function() {
            log('Window load event fired');
        });

        log('Script ready');
    </script>
</body>
</html>
