<?php
session_start();
header('Content-Type: application/json');

include '../config/db.php';

// Inisialisasi response
$response = ['status' => 'error', 'message' => 'Terjadi kesalahan yang tidak diketahui.'];

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
        $harga = filter_var($_POST['harga'] ?? 0, FILTER_VALIDATE_INT);

        if (empty($nama_kost) || empty($alamat) || empty($deskripsi) || $harga === false || $harga <= 0) {
            $response['message'] = 'Semua field wajib diisi dan harga harus angka yang valid.';
            echo json_encode($response);
            exit();
        }

        // Handle File Upload
        $gambar_nama = $_POST['gambar_lama'] ?? null; // Default to old image if exists
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['gambar'];
            $upload_dir = '../uploads/kost/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // Validasi file
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($file['type'], $allowed_types)) {
                $response['message'] = 'Tipe file gambar tidak valid. Hanya JPG, PNG, GIF yang diizinkan.';
                echo json_encode($response);
                exit();
            }

            // Generate unique name
            $gambar_nama = uniqid('kost_', true) . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
            $upload_path = $upload_dir . $gambar_nama;

            if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
                $response['message'] = 'Gagal mengunggah gambar.';
                echo json_encode($response);
                exit();
            }

            // Hapus gambar lama jika ini adalah edit dan gambar lama ada
            if ($action === 'edit' && !empty($_POST['gambar_lama'])) {
                $old_image_path = $upload_dir . $_POST['gambar_lama'];
                if (file_exists($old_image_path)) {
                    unlink($old_image_path);
                }
            }
        }

        if ($action === 'add') {
            // 3. Proses INSERT ke Database
            $sql = "INSERT INTO kost (id_pemilik, nama_kost, alamat, deskripsi, fasilitas, harga, gambar) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                $response['message'] = 'Gagal mempersiapkan statement: ' . $conn->error;
            } else {
                $stmt->bind_param("issssis", $id_pemilik, $nama_kost, $alamat, $deskripsi, $fasilitas, $harga, $gambar_nama);
                if ($stmt->execute()) {
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
                break;
            }

            $sql = "UPDATE kost SET nama_kost=?, alamat=?, deskripsi=?, fasilitas=?, harga=?, gambar=? WHERE id_kost=? AND id_pemilik=?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                $response['message'] = 'Gagal mempersiapkan statement: ' . $conn->error;
            } else {
                $stmt->bind_param("ssssisii", $nama_kost, $alamat, $deskripsi, $fasilitas, $harga, $gambar_nama, $id_kost, $id_pemilik);
                if ($stmt->execute()) {
                    $response['status'] = 'success';
                    $response['message'] = 'Data kos berhasil diperbarui!';
                } else {
                    $response['message'] = 'Gagal memperbarui kos: ' . $stmt->error;
                }
                $stmt->close();
            }
        }
        break;

    case 'get_details':
        // 5. Proses Mengambil Detail untuk Form Edit
        $id_kost = filter_var($_GET['id_kost'] ?? 0, FILTER_VALIDATE_INT);
        if ($id_kost > 0) {
            $sql = "SELECT id_kost, nama_kost, alamat, deskripsi, fasilitas, harga, gambar FROM kost WHERE id_kost = ? AND id_pemilik = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $id_kost, $id_pemilik);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($data = $result->fetch_assoc()) {
                $response['status'] = 'success';
                $response['data'] = $data;
            } else {
                $response['message'] = 'Data kos tidak ditemukan atau Anda tidak memiliki akses.';
            }
            $stmt->close();
        } else {
            $response['message'] = 'ID Kos tidak valid.';
        }
        // Note: This case uses GET, so we need to adjust the server method check or handle it separately.
        // For simplicity, we'll call this via fetch with GET method.
        break;

    case 'delete':
        // 6. Proses DELETE dari Database
        $id_kost = filter_var($_POST['id_kost'] ?? 0, FILTER_VALIDATE_INT);
        if ($id_kost <= 0) {
            $response['message'] = 'ID Kos tidak valid.';
            break;
        }

        // Hapus kos, pastikan hanya pemilik yang bisa menghapus
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
echo json_encode($response);