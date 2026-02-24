<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
header('Content-Type: application/json');

include __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/cloudinary.php'; // Restore configuration!
use Cloudinary\Api\Upload\UploadApi;

// Inisialisasi response
$response = ['status' => 'error', 'message' => 'Terjadi kesalahan yang tidak diketahui.'];

// Ensure `tipe_kost` column exists in `kost`
try {
    $colCheck = $conn->query("SHOW COLUMNS FROM `kost` LIKE 'tipe_kost'");
    if ($colCheck && $colCheck->num_rows === 0) {
        $conn->query("ALTER TABLE `kost` ADD COLUMN `tipe_kost` ENUM('Putra', 'Putri', 'Campuran') DEFAULT 'Campuran' AFTER `harga` ");
    }
    
    // Ensure `jenis_kamar` column exists in `kost`
    $colCheckJenis = $conn->query("SHOW COLUMNS FROM `kost` LIKE 'jenis_kamar'");
    if ($colCheckJenis && $colCheckJenis->num_rows === 0) {
        $conn->query("ALTER TABLE `kost` ADD COLUMN `jenis_kamar` VARCHAR(100) DEFAULT 'Kamar Mandi Dalam' AFTER `tipe_kost` ");
    }
    
    // Ensure `peraturan` column exists in `kost`
    $colCheckPeraturan = $conn->query("SHOW COLUMNS FROM `kost` LIKE 'peraturan'");
    if ($colCheckPeraturan && $colCheckPeraturan->num_rows === 0) {
        $conn->query("ALTER TABLE `kost` ADD COLUMN `peraturan` TEXT DEFAULT NULL AFTER `fasilitas`");
    }
    
    // Ensure `category` column exists in `kost_photos`
    $colCheck2 = $conn->query("SHOW COLUMNS FROM `kost_photos` LIKE 'category'");
    if ($colCheck2 && $colCheck2->num_rows === 0) {
        $conn->query("ALTER TABLE `kost_photos` ADD COLUMN `category` VARCHAR(50) DEFAULT 'lainnya'");
    }

    // Ensure `kost_room_types` table exists
    $conn->query("CREATE TABLE IF NOT EXISTS `kost_room_types` (
        `id_tipe` INT(11) NOT NULL AUTO_INCREMENT,
        `id_kost` INT(11) NOT NULL,
        `nama_tipe` VARCHAR(100) NOT NULL,
        `foto_tipe` VARCHAR(255) DEFAULT NULL,
        PRIMARY KEY (`id_tipe`),
        KEY `id_kost` (`id_kost`),
        CONSTRAINT `fk_room_type_kost` FOREIGN KEY (`id_kost`) REFERENCES `kost` (`id_kost`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Ensure `peraturan_kamar` column exists in `kost_room_types`
    $colCheckRT = $conn->query("SHOW COLUMNS FROM `kost_room_types` LIKE 'peraturan_kamar'");
    if ($colCheckRT && $colCheckRT->num_rows === 0) {
        $conn->query("ALTER TABLE `kost_room_types` ADD COLUMN `peraturan_kamar` TEXT DEFAULT NULL AFTER `foto_tipe`");
    }

} catch (Exception $e) {
    error_log('Schema check failed: ' . $e->getMessage());
}

// Helper function to handle multiple file uploads by category
function uploadPhotos($conn, $files, $id_kost, $category) {
    global $allowed_types;
    if (empty($files['name'][0])) return;
    
    $count = count($files['name']);
    $sql_photo = "INSERT INTO kost_photos (id_kost, file_name, category) VALUES (?, ?, ?)";
    $stmt_photo = $conn->prepare($sql_photo);

    for ($i = 0; $i < $count; $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_OK) {
            $tmp_name = $files['tmp_name'][$i];
            $type = $files['type'][$i];

            if (in_array($type, $allowed_types)) {
                try {
                    $uploadApi = new Cloudinary\Api\Upload\UploadApi();
                    $uploadResult = $uploadApi->upload($tmp_name, [
                        'folder' => 'kosconnect/kosts', 
                        'public_id' => uniqid('kost_' . $category . '_'),
                        'resource_type' => 'image'
                    ]);
                    
                    $photo_url = $uploadResult['secure_url'];
                    $stmt_photo->bind_param("iss", $id_kost, $photo_url, $category);
                    $stmt_photo->execute();
                } catch (Exception $e) {
                    error_log("Failed to upload " . $category . " photo: " . $e->getMessage());
                }
            }
        }
    }
    $stmt_photo->close();
}

function processRoomTypes($conn, $id_kost, $post_ids, $post_names, $post_peraturan, $file_input_name) {
    $uploadApi = new Cloudinary\Api\Upload\UploadApi();
    $existing_ids = []; // IDs to keep

    // Loop through submitted types
    if (isset($post_names) && is_array($post_names)) {
        foreach ($post_names as $index => $nama_tipe) {
            $nama_tipe = trim($nama_tipe);
            if (empty($nama_tipe)) continue;

            $id_tipe = isset($post_ids[$index]) ? (int)$post_ids[$index] : 0;
            $peraturan = isset($post_peraturan[$index]) ? trim($post_peraturan[$index]) : '';
            $foto_url = null;

            // Handle File Upload
            if (isset($_FILES[$file_input_name]['name'][$index]) && $_FILES[$file_input_name]['error'][$index] === UPLOAD_ERR_OK) {
                try {
                    $tmp_name = $_FILES[$file_input_name]['tmp_name'][$index];
                    $uploadResult = $uploadApi->upload($tmp_name, [
                        'folder' => 'kosconnect/room_types',
                        'public_id' => uniqid('rt_' . $id_kost . '_'),
                        'resource_type' => 'image'
                    ]);
                    $foto_url = $uploadResult['secure_url'];
                } catch (Exception $e) {
                    error_log("Room Type Upload Error: " . $e->getMessage());
                }
            }

            if ($id_tipe > 0) {
                // Update existing
                $existing_ids[] = $id_tipe;
                if ($foto_url) {
                    $stmt = $conn->prepare("UPDATE kost_room_types SET nama_tipe = ?, peraturan_kamar = ?, foto_tipe = ? WHERE id_tipe = ? AND id_kost = ?");
                    $stmt->bind_param("sssii", $nama_tipe, $peraturan, $foto_url, $id_tipe, $id_kost);
                } else {
                    $stmt = $conn->prepare("UPDATE kost_room_types SET nama_tipe = ?, peraturan_kamar = ? WHERE id_tipe = ? AND id_kost = ?");
                    $stmt->bind_param("ssii", $nama_tipe, $peraturan, $id_tipe, $id_kost);
                }
                $stmt->execute();
            } else {
                // Insert new
                $stmt = $conn->prepare("INSERT INTO kost_room_types (id_kost, nama_tipe, peraturan_kamar, foto_tipe) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("isss", $id_kost, $nama_tipe, $peraturan, $foto_url);
                $stmt->execute();
                $existing_ids[] = $stmt->insert_id;
            }
        }
    }
    
    return $existing_ids;
    // Note: We are not deleting missing IDs here to simplify, or we can implement deletion logic if needed. 
    // Usually, strict sync deletes items not in the list. Let's do strict sync.
    /*
    if (!empty($existing_ids)) {
        $ids_str = implode(',', $existing_ids);
        $conn->query("DELETE FROM kost_room_types WHERE id_kost = $id_kost AND id_tipe NOT IN ($ids_str)");
    } else {
        // If list empty but user wants to clear all? Be careful.
    }
    */
}

// 1. Autentikasi & Otorisasi
if (!isset($_SESSION['user_logged_in']) || $_SESSION['role'] !== 'pemilik') {
    $response['message'] = 'Akses tidak sah. Silakan login kembali.';
    echo json_encode($response);
    exit();
}

$id_pemilik = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
    $response['message'] = 'Metode request tidak valid.';
    echo json_encode($response);
    exit();
}

switch ($action) {
    case 'add':
    case 'edit':
        // 2. Validasi Input
        $nama_kost = trim($_POST['nama_kost'] ?? '');
        $alamat = trim($_POST['alamat'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $fasilitas = trim($_POST['fasilitas'] ?? '');
        $peraturan = trim($_POST['peraturan'] ?? '');
        $harga = (int) filter_var($_POST['harga'] ?? 0, FILTER_VALIDATE_FLOAT);
        $tipe_kost = trim($_POST['tipe_kost'] ?? 'Campuran');
        $jenis_kamar = trim($_POST['jenis_kamar'] ?? 'Kamar Mandi Dalam');

        if (empty($nama_kost) || empty($alamat) || empty($deskripsi) || $harga === false || $harga <= 0) {
            $response['message'] = 'Semua field wajib diisi dan harga harus angka yang valid.';
            echo json_encode($response);
            exit();
        }

        $upload_dir = '../uploads/kost/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];

        // Handle Main File Upload
        $gambar_nama = $_POST['gambar_lama'] ?? null; // Default to old image if exists
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['gambar'];
            
            if (!in_array($file['type'], $allowed_types)) {
                $response['message'] = 'Tipe file gambar utama tidak valid. Hanya JPG, PNG, GIF yang diizinkan.';
                echo json_encode($response);
                exit();
            }

            $uploadApi = new UploadApi();
            try {
                $uploadResult = $uploadApi->upload($file['tmp_name'], [
                    'folder' => 'kosconnect/kosts', 
                    'public_id' => uniqid('kost_main_'),
                    'resource_type' => 'image'
                ]);
                $gambar_nama = $uploadResult['secure_url']; // Save URL
            } catch (Exception $e) {
                $response['message'] = 'Gagal mengunggah gambar utama ke Cloudinary: ' . $e->getMessage();
                echo json_encode($response);
                exit();
            }
        }

        $id_kost_inserted = 0;

        if ($action === 'add') {
            // 3. Proses INSERT ke Database
            $sql = "INSERT INTO kost (id_pemilik, nama_kost, alamat, deskripsi, fasilitas, peraturan, harga, gambar, tipe_kost, jenis_kamar) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                $response['message'] = 'Gagal mempersiapkan statement: ' . $conn->error;
            } else {
                $stmt->bind_param("isssssisss", $id_pemilik, $nama_kost, $alamat, $deskripsi, $fasilitas, $peraturan, $harga, $gambar_nama, $tipe_kost, $jenis_kamar);
                if ($stmt->execute()) {
                    $id_kost_inserted = $stmt->insert_id;
                    $response['status'] = 'success';
                    $response['message'] = 'Kos baru berhasil ditambahkan!';
                } else {
                    $response['message'] = 'Gagal menambahkan kos: ' . $stmt->error;
                }
                $stmt->close();
            }
        } else { // action === 'edit'
            // 4. Proses UPDATE ke Database
            $id_kost = filter_var($_POST['id_kost'] ?? 0, FILTER_VALIDATE_INT);
            if ($id_kost <= 0) {
                $response['message'] = 'ID Kos tidak valid.';
                echo json_encode($response);
                exit();
            }
            $id_kost_inserted = $id_kost;

            $sql = "UPDATE kost SET nama_kost=?, alamat=?, deskripsi=?, fasilitas=?, peraturan=?, harga=?, gambar=?, tipe_kost=?, jenis_kamar=? WHERE id_kost=? AND id_pemilik=?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                $response['message'] = 'Gagal mempersiapkan statement: ' . $conn->error;
            } else {
                $stmt->bind_param("sssssisssii", $nama_kost, $alamat, $deskripsi, $fasilitas, $peraturan, $harga, $gambar_nama, $tipe_kost, $jenis_kamar, $id_kost, $id_pemilik);
                if ($stmt->execute()) {
                    $response['status'] = 'success';
                    $response['message'] = 'Data kos berhasil diperbarui!';
                } else {
                    $response['message'] = 'Gagal memperbarui kos: ' . $stmt->error;
                }
                $stmt->close();
            }
        }

        // Handle Categorized Additional Photos
        if ($id_kost_inserted > 0) {
            $categories = [
                'foto_bangunan' => 'Bangunan',
                'foto_kamar' => 'Kamar',
                'foto_kamar_mandi' => 'Kamar Mandi',
                'foto_fasilitas' => 'Fasilitas Bersama',
                'foto_lainnya' => 'Lainnya'
            ];
            
            foreach ($categories as $inputName => $dbCategory) {
                if (isset($_FILES[$inputName])) {
                    uploadPhotos($conn, $_FILES[$inputName], $id_kost_inserted, $dbCategory);
                }
            }
            
            // Handle Room Types
            // Input arrays: type_id[], type_name[], type_image[] (files)
            if (isset($_POST['type_name'])) {
                processRoomTypes($conn, $id_kost_inserted, $_POST['type_id'] ?? [], $_POST['type_name'], $_POST['type_peraturan'] ?? [], 'type_image');
            }
        }
        
        // Handle Delete Photo Action if sent via this endpoint (though delete_photo uses a separate action block usually)
        break;



    case 'get_details':
        // 5. Proses Mengambil Detail untuk Form Edit
        $id_kost = filter_var($_GET['id_kost'] ?? 0, FILTER_VALIDATE_INT);
        if ($id_kost > 0) {
            $sql = "SELECT id_kost, nama_kost, alamat, deskripsi, fasilitas, peraturan, harga, gambar, tipe_kost, jenis_kamar FROM kost WHERE id_kost = ? AND id_pemilik = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $id_kost, $id_pemilik);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($data = $result->fetch_assoc()) {
                // Fetch additional photos
                $photos = [];
                // Check if category column exists before selecting it to avoid error if migration didn't run yet (though it should have)
                // We'll trust the migration ran.
                $sql_photos = "SELECT id_photo, file_name, category FROM kost_photos WHERE id_kost = ?";
                $stmt_photos = $conn->prepare($sql_photos);
                $stmt_photos->bind_param("i", $id_kost);
                $stmt_photos->execute();
                $res_photos = $stmt_photos->get_result();
                while ($row = $res_photos->fetch_assoc()) {
                    $photos[] = $row;
                }
                $stmt_photos->close();
                
                $data['photos'] = $photos;

                // Fetch Room Types
                $room_types = [];
                $res_types = $conn->query("SELECT * FROM kost_room_types WHERE id_kost = $id_kost");
                if ($res_types) {
                    while ($rt = $res_types->fetch_assoc()) {
                        $room_types[] = $rt;
                    }
                }
                $data['room_types'] = $room_types;
                
                $response['status'] = 'success';
                $response['data'] = $data;
            } else {
                $response['message'] = 'Data kos tidak ditemukan atau Anda tidak memiliki akses.';
            }
            $stmt->close();
        } else {
            $response['message'] = 'ID Kos tidak valid.';
        }
        break;

    case 'delete_photo':
        // Hapus foto tambahan
        $id_photo = filter_var($_POST['id_photo'] ?? 0, FILTER_VALIDATE_INT);
        $id_kost = filter_var($_POST['id_kost'] ?? 0, FILTER_VALIDATE_INT);

        if ($id_photo > 0 && $id_kost > 0) {
            // Verifikasi kepemilikan dan ambil nama file
            $check_sql = "SELECT p.file_name FROM kost_photos p JOIN kost k ON p.id_kost = k.id_kost WHERE p.id_photo = ? AND k.id_kost = ? AND k.id_pemilik = ?";
            $stmt_check = $conn->prepare($check_sql);
            $stmt_check->bind_param("iii", $id_photo, $id_kost, $id_pemilik);
            $stmt_check->execute();
            $res_check = $stmt_check->get_result();
            
            if ($row = $res_check->fetch_assoc()) {
                $file_name = $row['file_name'];
                
                // Hapus file fisik jika bukan URL eksternal (Cloudinary)
                // Catatan: Untuk Cloudinary, idealnya kita simpan public_id untuk menghapusnya via API.
                // Jika hanya URL, kita biarkan di cloud (orphan) atau handle terpisah.
                if (strpos($file_name, 'http') !== 0) {
                    $file_path = '../uploads/kost/' . $file_name;
                    if (file_exists($file_path)) {
                        unlink($file_path);
                    }
                }
                
                $del_sql = "DELETE FROM kost_photos WHERE id_photo = ?";
                $stmt_del = $conn->prepare($del_sql);
                $stmt_del->bind_param("i", $id_photo);
                if ($stmt_del->execute()) {
                    $response['status'] = 'success';
                    $response['message'] = 'Foto berhasil dihapus.';
                } else {
                    $response['message'] = 'Gagal menghapus foto dari database.';
                }
            } else {
                $response['message'] = 'Foto tidak ditemukan atau Anda tidak berhak menghapusnya.';
            }
        } else {
            $response['message'] = 'ID Foto tidak valid.';
        }
        break;

    case 'delete':
        // 6. Proses DELETE dari Database
        $id_kost = filter_var($_POST['id_kost'] ?? 0, FILTER_VALIDATE_INT);
        if ($id_kost <= 0) {
            $response['message'] = 'ID Kos tidak valid.';
            break;
        }

        // Hapus kos, pastikan hanya pemilik yang bisa menghapus
        // Trigger cascade di DB akan menghapus photos, tapi kita perlu hapus filenya juga
        
        // 1. Get all photos files to delete
        // First Main Image
        $sql1 = "SELECT gambar FROM kost WHERE id_kost = ? AND id_pemilik = ?";
        $stmt1 = $conn->prepare($sql1);
        $stmt1->bind_param("ii", $id_kost, $id_pemilik);
        $stmt1->execute();
        $res1 = $stmt1->get_result();
        if ($row = $res1->fetch_assoc()) {
            if ($row['gambar'] && file_exists('../uploads/kost/' . $row['gambar'])) {
                unlink('../uploads/kost/' . $row['gambar']);
            }
        }
        
        // Additional Photos
        $sql2 = "SELECT file_name FROM kost_photos WHERE id_kost = ?";
        $stmt2 = $conn->prepare($sql2);
        $stmt2->bind_param("i", $id_kost);
        $stmt2->execute();
        $res2 = $stmt2->get_result();
        while ($row = $res2->fetch_assoc()) {
             if ($row['file_name'] && file_exists('../uploads/kost/' . $row['file_name'])) {
                unlink('../uploads/kost/' . $row['file_name']);
            }
        }

        $sql = "DELETE FROM kost WHERE id_kost = ? AND id_pemilik = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $response['message'] = 'Gagal mempersiapkan statement: ' . $conn->error;
        } else {
            $stmt->bind_param("ii", $id_kost, $id_pemilik);
            if ($stmt->execute()) {
                $response['status'] = 'success';
                $response['message'] = 'Kos berhasil dihapus!';
            } else {
                $response['message'] = 'Gagal menghapus kos: ' . $stmt->error;
            }
            $stmt->close();
        }
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