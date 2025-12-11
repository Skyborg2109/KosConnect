<?php
session_start();
header('Content-Type: application/json');

include '../config/db.php';

$response = ['status' => 'error', 'message' => 'Terjadi kesalahan.'];

// Autentikasi & Otorisasi
if (!isset($_SESSION['user_logged_in']) || $_SESSION['role'] !== 'pemilik') {
    $response['message'] = 'Akses tidak sah.';
    echo json_encode($response);
    exit();
}

$id_pemilik = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'add':
        $id_kost = filter_var($_POST['id_kost'] ?? 0, FILTER_VALIDATE_INT);
        $nama_kamar = trim($_POST['nama_kamar'] ?? '');
        $harga = filter_var($_POST['harga'] ?? 0, FILTER_VALIDATE_FLOAT);
        $status = $_POST['status'] ?? 'tersedia';

        if ($id_kost <= 0 || empty($nama_kamar) || $harga <= 0) {
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

        // Insert kamar baru
        $stmt_add = $conn->prepare("INSERT INTO kamar (id_kost, nama_kamar, harga, status) VALUES (?, ?, ?, ?)");
        $stmt_add->bind_param("isds", $id_kost, $nama_kamar, $harga, $status);
        if ($stmt_add->execute()) {
            $response['status'] = 'success';
            $response['message'] = 'Kamar baru berhasil ditambahkan!';
        } else {
            $response['message'] = 'Gagal menambahkan kamar: ' . $stmt_add->error;
        }
        $stmt_add->close();
        break;

    case 'edit':
        $id_kamar = filter_var($_POST['id_kamar'] ?? 0, FILTER_VALIDATE_INT);
        $nama_kamar = trim($_POST['nama_kamar'] ?? '');
        $harga = filter_var($_POST['harga'] ?? 0, FILTER_VALIDATE_FLOAT);
        $status = $_POST['status'] ?? 'tersedia';

        if ($id_kamar <= 0 || empty($nama_kamar) || $harga <= 0) {
            $response['message'] = 'Data tidak lengkap atau tidak valid.';
            break;
        }

        // Verifikasi kepemilikan kamar melalui join dengan tabel kost
        $stmt_check = $conn->prepare("
            SELECT k.id_kamar FROM kamar k
            JOIN kost t ON k.id_kost = t.id_kost
            WHERE k.id_kamar = ? AND t.id_pemilik = ?
        ");
        $stmt_check->bind_param("ii", $id_kamar, $id_pemilik);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows === 0) {
            $response['message'] = 'Anda tidak memiliki akses untuk mengedit kamar ini.';
            break;
        }
        $stmt_check->close();

        // Update kamar
        $stmt_edit = $conn->prepare("UPDATE kamar SET nama_kamar = ?, harga = ?, status = ? WHERE id_kamar = ?");
        $stmt_edit->bind_param("sdsi", $nama_kamar, $harga, $status, $id_kamar);
        if ($stmt_edit->execute()) {
            $response['status'] = 'success';
            $response['message'] = 'Kamar berhasil diperbarui!';
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

        // Verifikasi kepemilikan kamar melalui join dengan tabel kost
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
echo json_encode($response);
