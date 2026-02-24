<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
header('Content-Type: application/json');

include __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/cloudinary.php';
use Cloudinary\Api\Upload\UploadApi;

$response = ['status' => 'error', 'message' => 'Terjadi kesalahan.'];

// Ensure `tipe_kamar` column exists
try {
    $colCheck = $conn->query("SHOW COLUMNS FROM `kamar` LIKE 'tipe_kamar'");
    if ($colCheck && $colCheck->num_rows === 0) {
        $conn->query("ALTER TABLE `kamar` ADD COLUMN `tipe_kamar` VARCHAR(100) DEFAULT 'Standard' AFTER `nama_kamar` ");
    }
} catch (Exception $e) {
    error_log('Column check for tipe_kamar failed: ' . $e->getMessage());
}

// Ensure `fasilitas` column exists
try {
    $colCheck = $conn->query("SHOW COLUMNS FROM `kamar` LIKE 'fasilitas'");
    if ($colCheck && $colCheck->num_rows === 0) {
        $conn->query("ALTER TABLE `kamar` ADD COLUMN `fasilitas` TEXT DEFAULT NULL AFTER `tipe_kamar`");
    }
} catch (Exception $e) {
    error_log('Column check for fasilitas failed: ' . $e->getMessage());
}

// Ensure `fasilitas` column exists in kost_room_types
try {
    $colCheck = $conn->query("SHOW COLUMNS FROM `kost_room_types` LIKE 'fasilitas'");
    if ($colCheck && $colCheck->num_rows === 0) {
        $conn->query("ALTER TABLE `kost_room_types` ADD COLUMN `fasilitas` TEXT NULL AFTER `foto_tipe`");
    }
} catch (Exception $e) {
    error_log('Column check for fasilitas in kost_room_types failed: ' . $e->getMessage());
}

// Autentikasi & Otorisasi
if (!isset($_SESSION['user_logged_in']) || $_SESSION['role'] !== 'pemilik') {
    $response['message'] = 'Akses tidak sah.';
    echo json_encode($response);
    exit();
}

$id_pemilik = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

// Helper function for Cloudinary upload
function uploadKamarPhotoToCloudinary($file) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($file['type'], $allowed_types)) {
        return null;
    }

    try {
        $uploadApi = new UploadApi();
        $uploadResult = $uploadApi->upload($file['tmp_name'], [
            'folder' => 'kosconnect/rooms',
            'public_id' => uniqid('room_'),
            'resource_type' => 'image'
        ]);
        return $uploadResult['secure_url'];
    } catch (Exception $e) {
        error_log("Cloudinary Upload Error: " . $e->getMessage());
        return null;
    }
}

switch ($action) {
    case 'add':
        $id_kost = filter_var($_POST['id_kost'] ?? 0, FILTER_VALIDATE_INT);
        $nama_kamar = trim($_POST['nama_kamar'] ?? '');
        $harga = filter_var($_POST['harga'] ?? 0, FILTER_VALIDATE_FLOAT);
        $status = $_POST['status'] ?? 'tersedia';
        $tipe_kamar = trim($_POST['tipe_kamar'] ?? 'Standard');
        $fasilitas = trim($_POST['fasilitas'] ?? '');
        $jumlah_kamar = filter_var($_POST['jumlah_kamar'] ?? 1, FILTER_VALIDATE_INT);

        // Validate quantity
        if ($jumlah_kamar < 1 || $jumlah_kamar > 50) {
            $response['message'] = 'Jumlah kamar harus antara 1-50.';
            break;
        }

        if ($id_kost <= 0 || empty($nama_kamar) || $harga === false || $harga <= 0) {
            $response['message'] = 'Data tidak lengkap atau tidak valid.';
            break;
        }

        // Verifikasi kepemilikan kos
        $stmt_check = $conn->prepare("SELECT id_kost FROM kost WHERE id_kost = ? AND id_pemilik = ?");
        $stmt_check->bind_param("ii", $id_kost, $id_pemilik);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows === 0) {
            $response['message'] = 'Anda tidak memiliki akses ke kos ini.';
            break;
        }
        $stmt_check->close();

        // Generate room names
        $room_names = [];
        if ($jumlah_kamar == 1) {
            $room_names[] = $nama_kamar;
        } else {
            // Check if base name is purely numeric
            if (preg_match('/^\d+$/', $nama_kamar)) {
                $base_num = intval($nama_kamar);
                for ($i = 0; $i < $jumlah_kamar; $i++) {
                    $room_names[] = strval($base_num + $i);
                }
            }
            // Check if base name is a single letter
            else if (preg_match('/^[A-Za-z]$/', $nama_kamar)) {
                for ($i = 1; $i <= $jumlah_kamar; $i++) {
                    $room_names[] = $nama_kamar . '-' . $i;
                }
            }
            // Otherwise treat as text
            else {
                for ($i = 1; $i <= $jumlah_kamar; $i++) {
                    $room_names[] = $nama_kamar . ' ' . $i;
                }
            }
        }

        // Handle Photo Upload (only once for all rooms)
        $foto_url = null;
        if (isset($_FILES['foto_kamar']) && $_FILES['foto_kamar']['error'] === UPLOAD_ERR_OK) {
            $foto_url = uploadKamarPhotoToCloudinary($_FILES['foto_kamar']);
        }

        // Start transaction
        $conn->begin_transaction();
        
        try {
            $success_count = 0;
            $stmt_add = $conn->prepare("INSERT INTO kamar (id_kost, nama_kamar, tipe_kamar, fasilitas, harga, status, foto) VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            foreach ($room_names as $room_name) {
                $stmt_add->bind_param("isssdss", $id_kost, $room_name, $tipe_kamar, $fasilitas, $harga, $status, $foto_url);
                if ($stmt_add->execute()) {
                    $success_count++;
                } else {
                    throw new Exception("Gagal menambahkan kamar: " . $stmt_add->error);
                }
            }
            
            $stmt_add->close();
            
            // Sync facilities to room type and all rooms of same type
            if (!empty($fasilitas) && !empty($tipe_kamar)) {
                // Update room type facilities
                $stmt_update_type = $conn->prepare(
                    "UPDATE kost_room_types SET fasilitas = ? WHERE nama_tipe = ? AND id_kost = ?"
                );
                if ($stmt_update_type) {
                    $stmt_update_type->bind_param("ssi", $fasilitas, $tipe_kamar, $id_kost);
                    $stmt_update_type->execute();
                    $stmt_update_type->close();
                }
                
                // Update all rooms of this type
                $stmt_update_rooms = $conn->prepare(
                    "UPDATE kamar SET fasilitas = ? WHERE tipe_kamar = ? AND id_kost = ?"
                );
                if ($stmt_update_rooms) {
                    $stmt_update_rooms->bind_param("ssi", $fasilitas, $tipe_kamar, $id_kost);
                    $stmt_update_rooms->execute();
                    $stmt_update_rooms->close();
                }
            }
            
            // Commit transaction
            $conn->commit();
            
            $response['status'] = 'success';
            if ($jumlah_kamar == 1) {
                $response['message'] = 'Kamar baru berhasil ditambahkan! Fasilitas diterapkan ke semua kamar tipe ' . htmlspecialchars($tipe_kamar) . '.';
            } else {
                $response['message'] = $success_count . ' kamar berhasil ditambahkan! Fasilitas diterapkan ke semua kamar tipe ' . htmlspecialchars($tipe_kamar) . '.';
            }
            $response['rooms_created'] = $success_count;
            
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            $response['message'] = 'Gagal menambahkan kamar: ' . $e->getMessage();
        }
        break;

    case 'edit':
        $id_kamar = filter_var($_POST['id_kamar'] ?? 0, FILTER_VALIDATE_INT);
        $nama_kamar = trim($_POST['nama_kamar'] ?? '');
        $harga = filter_var($_POST['harga'] ?? 0, FILTER_VALIDATE_FLOAT);
        $status = $_POST['status'] ?? 'tersedia';
        $tipe_kamar = trim($_POST['tipe_kamar'] ?? 'Standard');
        $fasilitas = trim($_POST['fasilitas'] ?? '');

        if ($id_kamar <= 0 || empty($nama_kamar) || $harga === false || $harga <= 0) {
            $response['message'] = 'Data tidak lengkap atau tidak valid.';
            break;
        }

        // Verifikasi kepemilikan kamar
        $stmt_check = $conn->prepare("
            SELECT k.id_kamar, k.foto FROM kamar k
            JOIN kost t ON k.id_kost = t.id_kost
            WHERE k.id_kamar = ? AND t.id_pemilik = ?
        ");
        $stmt_check->bind_param("ii", $id_kamar, $id_pemilik);
        $stmt_check->execute();
        $res_check = $stmt_check->get_result();
        if ($res_check->num_rows === 0) {
            $response['message'] = 'Anda tidak memiliki akses untuk mengedit kamar ini.';
            break;
        }
        $existing_kamar = $res_check->fetch_assoc();
        $stmt_check->close();

        // Handle Photo Upload
        $foto_url = $existing_kamar['foto'];
        if (isset($_FILES['foto_kamar']) && $_FILES['foto_kamar']['error'] === UPLOAD_ERR_OK) {
            $new_foto_url = uploadKamarPhotoToCloudinary($_FILES['foto_kamar']);
            if ($new_foto_url) {
                $foto_url = $new_foto_url;
            }
        }

        // Update kamar
        $stmt_edit = $conn->prepare("UPDATE kamar SET nama_kamar = ?, tipe_kamar = ?, fasilitas = ?, harga = ?, status = ?, foto = ? WHERE id_kamar = ?");
        $stmt_edit->bind_param("sssdssi", $nama_kamar, $tipe_kamar, $fasilitas, $harga, $status, $foto_url, $id_kamar);
        if ($stmt_edit->execute()) {
            // Get id_kost for this room
            $stmt_get_kost = $conn->prepare("SELECT id_kost FROM kamar WHERE id_kamar = ?");
            $stmt_get_kost->bind_param("i", $id_kamar);
            $stmt_get_kost->execute();
            $result_kost = $stmt_get_kost->get_result();
            $id_kost = $result_kost->fetch_assoc()['id_kost'];
            $stmt_get_kost->close();
            
            // Sync facilities to room type and all rooms of same type
            if (!empty($fasilitas) && !empty($tipe_kamar) && $id_kost) {
                // Update room type facilities
                $stmt_update_type = $conn->prepare(
                    "UPDATE kost_room_types SET fasilitas = ? WHERE nama_tipe = ? AND id_kost = ?"
                );
                if ($stmt_update_type) {
                    $stmt_update_type->bind_param("ssi", $fasilitas, $tipe_kamar, $id_kost);
                    $stmt_update_type->execute();
                    $stmt_update_type->close();
                }
                
                // Update all rooms of this type
                $stmt_update_rooms = $conn->prepare(
                    "UPDATE kamar SET fasilitas = ? WHERE tipe_kamar = ? AND id_kost = ?"
                );
                if ($stmt_update_rooms) {
                    $stmt_update_rooms->bind_param("ssi", $fasilitas, $tipe_kamar, $id_kost);
                    $stmt_update_rooms->execute();
                    $affected_rooms = $stmt_update_rooms->affected_rows;
                    $stmt_update_rooms->close();
                    
                    $response['affected_rooms'] = $affected_rooms;
                }
            }
            
            $response['status'] = 'success';
            $response['message'] = 'Kamar berhasil diperbarui! Fasilitas diterapkan ke semua kamar tipe ' . htmlspecialchars($tipe_kamar) . '.';
        } else {
            $response['message'] = 'Gagal memperbarui kamar: ' . $stmt_edit->error;
        }
        $stmt_edit->close();
        break;

    case 'delete':
        $id_kamar = filter_var($_POST['id_kamar'] ?? 0, FILTER_VALIDATE_INT);
        if ($id_kamar <= 0) {
            $response['message'] = 'ID Kamar tidak valid.';
            break;
        }

        // Verifikasi kepemilikan kamar
        $stmt_check = $conn->prepare("
            SELECT k.id_kamar FROM kamar k
            JOIN kost t ON k.id_kost = t.id_kost
            WHERE k.id_kamar = ? AND t.id_pemilik = ?
        ");
        $stmt_check->bind_param("ii", $id_kamar, $id_pemilik);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows === 0) {
            $response['message'] = 'Anda tidak memiliki akses untuk menghapus kamar ini.';
            break;
        }
        $stmt_check->close();

        // Hapus kamar
        $stmt_delete = $conn->prepare("DELETE FROM kamar WHERE id_kamar = ?");
        $stmt_delete->bind_param("i", $id_kamar);
        if ($stmt_delete->execute()) {
            $response['status'] = 'success';
            $response['message'] = 'Kamar berhasil dihapus.';
        } else {
            $response['message'] = 'Gagal menghapus kamar: ' . $stmt_delete->error;
        }
        $stmt_delete->close();
        break;

    default:
        $response['message'] = 'Aksi tidak dikenali.';
        break;
}

$conn->close();
$debug_output = ob_get_clean();
if (!empty($debug_output)) {
    $response['debug'] = $debug_output;
}
echo json_encode($response);
?>
