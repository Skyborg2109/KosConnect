<?php
session_start();
// Do not display PHP errors directly in responses; log them instead
ini_set('display_errors', '0');
error_reporting(E_ALL);

include '../config/db.php';

// Ensure `foto_profil` column exists to prevent SQL errors when selecting/updating profile photo
try {
    $colCheck = $conn->query("SHOW COLUMNS FROM `user` LIKE 'foto_profil'");
    if ($colCheck && $colCheck->num_rows === 0) {
        if (!$conn->query("ALTER TABLE `user` ADD COLUMN `foto_profil` VARCHAR(255) DEFAULT NULL")) {
            error_log('Failed to add foto_profil column: ' . $conn->error);
        } else {
            error_log('Added missing foto_profil column to user table.');
        }
    }
} catch (Throwable $e) {
    error_log('Column check/add failed: ' . $e->getMessage());
}

// Helper to send consistent JSON responses and exit
function send_json($response, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

$response = ['status' => 'error', 'message' => 'Terjadi kesalahan yang tidak diketahui.'];

if (!isset($_SESSION['user_logged_in']) || !in_array($_SESSION['role'], ['penyewa', 'admin'])) {
    $response['message'] = 'Akses tidak sah.';
    send_json($response, 403);
}

$id_user = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

// Simple debug logging for incoming requests (append-only). Useful to inspect why uploads fail.
// This log is safe for local debugging; remove or restrict in production.
$logPath = __DIR__ . '/../uploads/profiles/upload_debug.log';
$logEntry = sprintf("%s | user:%s | action:%s | has_file:%s | content_length:%s | remote_addr:%s\n",
    date('Y-m-d H:i:s'),
    isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'anon',
    $action,
    isset($_FILES['foto_profil']) ? 'yes' : 'no',
    $_SERVER['CONTENT_LENGTH'] ?? '0',
    $_SERVER['REMOTE_ADDR'] ?? 'cli'
);
@file_put_contents($logPath, $logEntry, FILE_APPEND | LOCK_EX);

if ($action === 'update_profile') {
    $fullname = trim($_POST['fullname'] ?? '');
    if (empty($fullname)) {
        $response['message'] = "Nama lengkap tidak boleh kosong.";
    } else {
        $stmt = $conn->prepare("UPDATE user SET nama_lengkap = ? WHERE id_user = ?");
        $stmt->bind_param("si", $fullname, $id_user);
        if ($stmt->execute()) {
            $_SESSION['fullname'] = $fullname;
            $response = [
                'status' => 'success',
                'message' => 'Nama lengkap berhasil diperbarui.',
                'new_name' => $fullname
            ];
        } else {
            error_log('Profile update failed: ' . $stmt->error);
            $response['message'] = "Gagal memperbarui nama.";
        }
        $stmt->close();
    }

    send_json($response, ($response['status'] === 'success') ? 200 : 400);

} elseif ($action === 'update_password') {
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
        $response['message'] = "Semua field password harus diisi.";
    } elseif ($new_password !== $confirm_password) {
        $response['message'] = "Password baru dan konfirmasi tidak cocok.";
    } elseif (strlen($new_password) < 6) {
        $response['message'] = "Password baru minimal harus 6 karakter.";
    } else {
        $stmt = $conn->prepare("SELECT password FROM user WHERE id_user = ?");
        $stmt->bind_param("i", $id_user);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($result && password_verify($old_password, $result['password'])) {
            $new_password_hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt_update = $conn->prepare("UPDATE user SET password = ? WHERE id_user = ?");
            $stmt_update->bind_param("si", $new_password_hashed, $id_user);
            if ($stmt_update->execute()) {
                $response['status'] = 'success';
                $response['message'] = "Password berhasil diubah.";
            } else {
                error_log('Password update failed: ' . $stmt_update->error);
                $response['message'] = "Gagal mengubah password.";
            }
            $stmt_update->close();
        } else {
            $response['message'] = "Password lama salah.";
        }
    }

    send_json($response, ($response['status'] === 'success') ? 200 : 400);

} elseif ($action === 'update_photo') {
    // Validate upload existence and errors
    if (!isset($_FILES['foto_profil'])) {
        $response['message'] = 'Tidak ada file yang diunggah.';
        send_json($response, 400);
    }

    $fileError = $_FILES['foto_profil']['error'];
    if ($fileError !== UPLOAD_ERR_OK) {
        $map = [
            UPLOAD_ERR_INI_SIZE => 'Ukuran file melebihi limit server (upload_max_filesize).',
            UPLOAD_ERR_FORM_SIZE => 'Ukuran file melebihi batas form.',
            UPLOAD_ERR_PARTIAL => 'File hanya terunggah sebagian.',
            UPLOAD_ERR_NO_FILE => 'Tidak ada file yang dipilih.',
            UPLOAD_ERR_NO_TMP_DIR => 'Folder temporer tidak tersedia di server.',
            UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk.',
            UPLOAD_ERR_EXTENSION => 'Upload dihentikan oleh ekstensi.'
        ];
        $response['message'] = $map[$fileError] ?? 'Terjadi kesalahan saat mengunggah file.';
        send_json($response, 400);
    }

    $file = $_FILES['foto_profil'];
    $upload_dir = __DIR__ . '/../uploads/profiles/';
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true) && !is_dir($upload_dir)) {
            $response['message'] = 'Error Server: Gagal membuat direktori unggah.';
            send_json($response, 500);
        }
    }

    if (!is_writable($upload_dir)) {
        $response['message'] = 'Error Server: Direktori unggah tidak dapat ditulis. Periksa izin folder.';
        send_json($response, 500);
    }

    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 2 * 1024 * 1024; // 2MB
    if (!in_array($file['type'], $allowed_types) || $file['size'] > $max_size) {
        $response['message'] = 'Tipe file tidak valid atau ukuran terlalu besar (Maks 2MB).';
        send_json($response, 400);
    }

    // Remove old photo safely
    $stmt_old = $conn->prepare("SELECT foto_profil FROM user WHERE id_user = ?");
    $old_photo = null;
    if ($stmt_old) {
        $stmt_old->bind_param("i", $id_user);
        if ($stmt_old->execute()) {
            $result_old = $stmt_old->get_result();
            if ($result_old) {
                $row = $result_old->fetch_assoc();
                if ($row) {
                    $old_photo = $row['foto_profil'];
                }
            }
        }
        $stmt_old->close();
    }
    if ($old_photo && file_exists($upload_dir . $old_photo)) {
        @unlink($upload_dir . $old_photo);
    }

    // Upload new photo
    $new_photo_name = uniqid('user_', true) . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
    if (move_uploaded_file($file['tmp_name'], $upload_dir . $new_photo_name)) {
        $stmt_update = $conn->prepare("UPDATE user SET foto_profil = ? WHERE id_user = ?");
        $stmt_update->bind_param("si", $new_photo_name, $id_user);
        if ($stmt_update->execute()) {
            $_SESSION['foto_profil'] = $new_photo_name;
            $response = ['status' => 'success', 'message' => 'Foto profil berhasil diperbarui.', 'new_photo' => $new_photo_name];
            send_json($response, 200);
        } else {
            error_log('DB update failed: ' . $stmt_update->error);
            $response['message'] = 'Gagal menyimpan nama file foto ke database.';
            send_json($response, 500);
        }
    } else {
        $last = error_get_last();
        error_log('move_uploaded_file failed: ' . print_r($last, true));
        $response['message'] = 'Gagal memindahkan file yang diunggah.';
        send_json($response, 500);
    }

} else {
    $response['message'] = 'Aksi tidak valid.';
    send_json($response, 400);
}

// Fallback - should not reach here because send_json exits
$conn->close();
send_json($response, ($response['status'] === 'success') ? 200 : 400);
?>