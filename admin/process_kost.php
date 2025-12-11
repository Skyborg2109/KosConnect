<?php
// Error handling - no output before headers
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '../logs/process_kost_error.log');

session_start();
header('Content-Type: application/json; charset=utf-8');

include '../config/db.php';

// Response template
$response = ['success' => false, 'message' => 'Terjadi kesalahan yang tidak diketahui.', 'data' => null];

// Autentikasi
if (!isset($_SESSION['user_logged_in']) || $_SESSION['role'] !== 'admin') {
    $response['message'] = 'Akses tidak sah.';
    http_response_code(401);
    echo json_encode($response);
    exit();
}

// Validate method
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
    $response['message'] = 'Metode request tidak valid.';
    http_response_code(405);
    echo json_encode($response);
    exit();
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_details':
            // Get single kos details
            $kosId = intval($_GET['id'] ?? 0);
            if (!$kosId) {
                $response['message'] = 'ID kos tidak valid';
                break;
            }

            $sql = "SELECT * FROM kost WHERE id_kost = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $kosId);
            $stmt->execute();
            $result = $stmt->get_result();
            $kos = $result->fetch_assoc();

            if ($kos) {
                $response['success'] = true;
                $response['message'] = 'Data kos ditemukan';
                $response['data'] = $kos;
            } else {
                $response['message'] = 'Kos tidak ditemukan';
            }
            break;

        case 'add':
        case 'update':
            // Validate inputs
            $kosId = intval($_POST['kost_id'] ?? 0);
            $nama_kost = trim($_POST['nama_kost'] ?? '');
            $id_pemilik = intval($_POST['id_pemilik'] ?? 0);
            $alamat = trim($_POST['alamat'] ?? '');
            $harga = intval($_POST['harga'] ?? 0);
            $deskripsi = trim($_POST['deskripsi'] ?? '');
            $fasilitas = trim($_POST['fasilitas'] ?? '');

            if (empty($nama_kost) || !$id_pemilik || empty($alamat) || $harga <= 0 || empty($deskripsi)) {
                $response['message'] = 'Semua field wajib diisi dan harga harus valid.';
                break;
            }

            // Check if owner exists
            $sql_check = "SELECT id_user FROM user WHERE id_user = ? AND role = 'pemilik'";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bind_param('i', $id_pemilik);
            $stmt_check->execute();
            if (!$stmt_check->get_result()->fetch_assoc()) {
                $response['message'] = 'Pemilik tidak ditemukan atau bukan pemilik.';
                break;
            }

            $gambar_nama = null;

            // Handle file upload
            if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['gambar'];
                $upload_dir = '../uploads/kost/';
                
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $file_ext = strtolower($file_ext);
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];

                if (!in_array($file_ext, $allowed)) {
                    $response['message'] = 'Tipe file tidak didukung. Gunakan JPG, PNG, atau GIF.';
                    break;
                }

                if ($file['size'] > 5 * 1024 * 1024) { // 5MB
                    $response['message'] = 'Ukuran file terlalu besar. Maksimal 5MB.';
                    break;
                }

                $gambar_nama = 'kost_' . time() . '_' . uniqid() . '.' . $file_ext;
                $gambar_path = $upload_dir . $gambar_nama;

                if (!move_uploaded_file($file['tmp_name'], $gambar_path)) {
                    $response['message'] = 'Gagal mengupload file.';
                    break;
                }

                // Delete old image if updating and new image uploaded
                if ($action === 'update') {
                    $gambar_lama = $_POST['gambar_lama'] ?? '';
                    if ($gambar_lama && file_exists($upload_dir . $gambar_lama)) {
                        unlink($upload_dir . $gambar_lama);
                    }
                }
            } else if ($action === 'update') {
                // Keep old image if not updating
                $gambar_nama = $_POST['gambar_lama'] ?? null;
            }

            if ($action === 'add') {
                // Insert new kos
                $sql = "INSERT INTO kost (nama_kost, id_pemilik, alamat, harga, deskripsi, fasilitas, gambar, created_at, updated_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('sisisss', $nama_kost, $id_pemilik, $alamat, $harga, $deskripsi, $fasilitas, $gambar_nama);
                
                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = 'Kos berhasil ditambahkan.';
                } else {
                    $response['message'] = 'Gagal menambahkan kos: ' . $stmt->error;
                }
            } else {
                // Update existing kos
                if (!$kosId) {
                    $response['message'] = 'ID kos tidak valid untuk update.';
                    break;
                }

                // Verify kos exists first
                $sql_verify = "SELECT id_kost FROM kost WHERE id_kost = ?";
                $stmt_verify = $conn->prepare($sql_verify);
                $stmt_verify->bind_param('i', $kosId);
                $stmt_verify->execute();
                if (!$stmt_verify->get_result()->fetch_assoc()) {
                    $response['message'] = 'Kos tidak ditemukan.';
                    break;
                }

                if ($gambar_nama) {
                    $sql = "UPDATE kost SET nama_kost = ?, alamat = ?, harga = ?, deskripsi = ?, fasilitas = ?, id_pemilik = ?, gambar = ?, updated_at = NOW() 
                            WHERE id_kost = ?";
                    $stmt = $conn->prepare($sql);
                    // Parameter order: nama_kost(s), alamat(s), harga(i), deskripsi(s), fasilitas(s), id_pemilik(i), gambar_nama(s), kosId(i)
                    $stmt->bind_param('ssissssi', $nama_kost, $alamat, $harga, $deskripsi, $fasilitas, $id_pemilik, $gambar_nama, $kosId);
                } else {
                    $sql = "UPDATE kost SET nama_kost = ?, alamat = ?, harga = ?, deskripsi = ?, fasilitas = ?, id_pemilik = ?, updated_at = NOW() 
                            WHERE id_kost = ?";
                    $stmt = $conn->prepare($sql);
                    // Parameter order: nama_kost(s), alamat(s), harga(i), deskripsi(s), fasilitas(s), id_pemilik(i), kosId(i)
                    $stmt->bind_param('ssissii', $nama_kost, $alamat, $harga, $deskripsi, $fasilitas, $id_pemilik, $kosId);
                }

                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = 'Kos berhasil diperbarui.';
                } else {
                    $response['message'] = 'Gagal memperbarui kos: ' . $stmt->error;
                }
            }
            break;

        case 'delete':
            $kosId = intval($_POST['id_kost'] ?? 0);
            if (!$kosId) {
                $response['message'] = 'ID kos tidak valid';
                break;
            }

            // Get gambar first to delete file
            $sql_get = "SELECT gambar FROM kost WHERE id_kost = ?";
            $stmt_get = $conn->prepare($sql_get);
            $stmt_get->bind_param('i', $kosId);
            $stmt_get->execute();
            $kos = $stmt_get->get_result()->fetch_assoc();

            // Delete from database
            $sql = "DELETE FROM kost WHERE id_kost = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $kosId);

            if ($stmt->execute()) {
                // Delete gambar file if exists
                if ($kos['gambar'] && file_exists('../uploads/kost/' . $kos['gambar'])) {
                    unlink('../uploads/kost/' . $kos['gambar']);
                }

                $response['success'] = true;
                $response['message'] = 'Kos berhasil dihapus.';
            } else {
                $response['message'] = 'Gagal menghapus kos: ' . $stmt->error;
            }
            break;

        default:
            $response['message'] = 'Aksi tidak valid.';
    }
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = 'Terjadi kesalahan: ' . $e->getMessage();
    http_response_code(500);
}

// Ensure clean JSON output
ob_end_clean();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit();
