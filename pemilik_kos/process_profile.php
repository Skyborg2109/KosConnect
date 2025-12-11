<?php
session_start();
ob_start(); // Mulai output buffering untuk menangkap output yang tidak diinginkan
header('Content-Type: application/json');

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

$response = ['status' => 'error', 'message' => 'Terjadi kesalahan yang tidak diketahui.'];

// Validasi peran sebagai 'pemilik'
if (!isset($_SESSION['user_logged_in']) || $_SESSION['role'] !== 'pemilik') {
    $response['message'] = 'Akses tidak sah.';
    http_response_code(403);
    echo json_encode($response);
    exit();
}

$id_pemilik = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

if ($action === 'update_profile') {
    $fullname = trim($_POST['fullname']);
    if (empty($fullname)) {
        $response['message'] = "Nama lengkap tidak boleh kosong.";
    } else {
        $stmt = $conn->prepare("UPDATE user SET nama_lengkap = ? WHERE id_user = ?");
        $stmt->bind_param("si", $fullname, $id_pemilik);
        if ($stmt->execute()) {
            $_SESSION['fullname'] = $fullname;
            $response = [
                'status' => 'success',
                'message' => 'Nama lengkap berhasil diperbarui.',
                'new_name' => $fullname
            ];
        } else {
            $response['message'] = "Gagal memperbarui nama.";
        }
        $stmt->close();
    }
} elseif ($action === 'update_password') {
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
        $response['message'] = "Semua field password harus diisi.";
    } elseif ($new_password !== $confirm_password) {
        $response['message'] = "Password baru dan konfirmasi tidak cocok.";
    } elseif (strlen($new_password) < 6) {
        $response['message'] = "Password baru minimal harus 6 karakter.";
    } else {
        $stmt = $conn->prepare("SELECT password FROM user WHERE id_user = ?");
        $stmt->bind_param("i", $id_pemilik);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($result && password_verify($old_password, $result['password'])) {
            $new_password_hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt_update = $conn->prepare("UPDATE user SET password = ? WHERE id_user = ?");
            $stmt_update->bind_param("si", $new_password_hashed, $id_pemilik);
            if ($stmt_update->execute()) {
                $response['status'] = 'success';
                $response['message'] = "Password berhasil diubah.";
            } else {
                $response['message'] = "Gagal mengubah password.";
            }
            $stmt_update->close();
        } else {
            $response['message'] = "Password lama salah.";
        }
    }
} elseif ($action === 'update_photo') {
    if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['foto_profil'];
        $upload_dir = '../uploads/profiles/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        if (!is_writable($upload_dir)) {
            $response['message'] = 'Error Server: Direktori unggah tidak dapat ditulis.';
            http_response_code(500);
        } else {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 2 * 1024 * 1024; // 2MB
            if (!in_array($file['type'], $allowed_types) || $file['size'] > $max_size) {
                $response['message'] = 'Tipe file tidak valid atau ukuran terlalu besar (Maks 2MB).';
            } else {
                // Hapus foto lama jika ada
                $stmt_old = $conn->prepare("SELECT foto_profil FROM user WHERE id_user = ?");
                $old_photo_filename = null;
                if ($stmt_old) {
                    $stmt_old->bind_param("i", $id_pemilik);
                    if ($stmt_old->execute()) {
                        $result_old = $stmt_old->get_result();
                        if ($result_old) {
                            $row = $result_old->fetch_assoc();
                            if ($row) {
                                $old_photo_filename = $row['foto_profil'];
                            }
                        }
                    }
                    $stmt_old->close();
                }
                if ($old_photo_filename && file_exists($upload_dir . $old_photo_filename)) {
                    unlink($upload_dir . $old_photo_filename);
                }

                // Unggah foto baru
                $new_photo_name = uniqid('user_', true) . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
                if (move_uploaded_file($file['tmp_name'], $upload_dir . $new_photo_name)) {
                    $stmt_update = $conn->prepare("UPDATE user SET foto_profil = ? WHERE id_user = ?");
                    $stmt_update->bind_param("si", $new_photo_name, $id_pemilik);
                    if ($stmt_update->execute()) {
                        $_SESSION['foto_profil'] = $new_photo_name;
                        $response = ['status' => 'success', 'message' => 'Foto profil berhasil diperbarui.', 'new_photo' => $new_photo_name];
                    } else {
                        $response['message'] = 'Gagal menyimpan nama file foto ke database.';
                    }
                    $stmt_update->close();
                } else {
                    $response['message'] = 'Gagal memindahkan file yang diunggah.';
                }
            }
        }
    } else {
        $response['message'] = 'Tidak ada file yang diunggah atau terjadi error.';
    }
} else {
    $response['message'] = 'Aksi tidak valid.';
    http_response_code(400);
}

$conn->close();
ob_end_clean(); // Hapus semua output yang mungkin ada di buffer
echo json_encode($response);
?>