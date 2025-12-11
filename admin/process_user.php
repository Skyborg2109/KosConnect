<?php
session_start();
header('Content-Type: application/json');

include '../config/db.php';

$response = ['success' => false, 'message' => 'Aksi tidak valid.'];

// Autentikasi: Pastikan hanya admin yang bisa mengakses
if (!isset($_SESSION['user_logged_in']) || $_SESSION['role'] !== 'admin') {
    $response['message'] = 'Akses ditolak. Anda tidak memiliki izin.';
    http_response_code(403);
    echo json_encode($response);
    exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'get_details':
        $user_id = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
        if (!$user_id) {
            $response['message'] = 'ID Pengguna tidak valid.';
            break;
        }

        $stmt = $conn->prepare("SELECT id_user, nama_lengkap, email, role FROM user WHERE id_user = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($data = $result->fetch_assoc()) {
            $response['success'] = true;
            $response['data'] = $data;
            $response['message'] = 'Data pengguna berhasil diambil.';
        } else {
            $response['message'] = 'Pengguna tidak ditemukan.';
        }
        $stmt->close();
        break;

    case 'update':
        $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
        $role = trim($_POST['role'] ?? '');
        $allowed_roles = ['penyewa', 'pemilik', 'admin'];

        if (!$user_id || empty($nama_lengkap) || empty($role) || !in_array($role, $allowed_roles)) {
            $response['message'] = 'Data yang dikirim tidak lengkap atau tidak valid.';
            http_response_code(400);
            break;
        }

        // Keamanan: Admin tidak bisa mengubah role-nya sendiri menjadi non-admin
        if ($user_id === $_SESSION['user_id'] && $role !== 'admin') {
            $response['message'] = 'Anda tidak dapat mengubah role akun Anda sendiri menjadi non-admin.';
            break;
        }

        $stmt = $conn->prepare("UPDATE user SET nama_lengkap = ?, role = ? WHERE id_user = ?");
        $stmt->bind_param("ssi", $nama_lengkap, $role, $user_id);

        if ($stmt->execute()) {
            // Jika admin mengedit profilnya sendiri, update sesi
            if ($user_id === $_SESSION['user_id']) {
                $_SESSION['fullname'] = $nama_lengkap;
            }
            $response['success'] = true;
            $response['message'] = 'Data pengguna berhasil diperbarui.';
        } else {
            $response['message'] = 'Gagal memperbarui data di database.';
        }
        $stmt->close();
        break;

    case 'delete':
        $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

        if (!$user_id) {
            $response['message'] = 'ID Pengguna tidak valid.';
            http_response_code(400);
            break;
        }

        // Keamanan: Admin tidak bisa menghapus akunnya sendiri
        if ($user_id === $_SESSION['user_id']) {
            $response['message'] = 'Anda tidak dapat menghapus akun Anda sendiri.';
            break;
        }

        // Hapus data terkait terlebih dahulu (jika ada, contoh: data kost, booking, dll)
        // Ini adalah contoh sederhana. Dalam aplikasi nyata, Anda perlu menangani relasi database dengan lebih hati-hati.
        // Misalnya, apa yang terjadi pada kos jika pemiliknya dihapus?
        // Untuk saat ini, kita hanya akan menghapus user.

        $stmt = $conn->prepare("DELETE FROM user WHERE id_user = ?");
        $stmt->bind_param("i", $user_id);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $response['success'] = true;
                $response['message'] = 'Pengguna berhasil dihapus secara permanen.';
            } else {
                $response['message'] = 'Pengguna tidak ditemukan atau sudah dihapus.';
            }
        } else {
            // Error ini bisa terjadi jika ada foreign key constraint
            $response['message'] = 'Gagal menghapus pengguna. Mungkin ada data terkait (seperti data kos atau booking) yang perlu dihapus terlebih dahulu. Error: ' . $stmt->error;
        }
        $stmt->close();
        break;

    case 'add':
        $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';
        $role = trim($_POST['role'] ?? '');
        $allowed_roles = ['penyewa', 'pemilik', 'admin'];

        if (empty($nama_lengkap) || !$email || empty($password) || empty($role) || !in_array($role, $allowed_roles)) {
            $response['message'] = 'Semua field harus diisi dengan data yang valid.';
            http_response_code(400);
            break;
        }

        if (strlen($password) < 6) {
            $response['message'] = 'Password minimal harus 6 karakter.';
            http_response_code(400);
            break;
        }

        // Cek apakah email sudah ada
        $stmt_check = $conn->prepare("SELECT id_user FROM user WHERE email = ?");
        $stmt_check->bind_param("s", $email);
        $stmt_check->execute();
        $stmt_check->store_result();
        if ($stmt_check->num_rows > 0) {
            $response['message'] = 'Email sudah terdaftar. Gunakan email lain.';
            $stmt_check->close();
            break;
        }
        $stmt_check->close();

        // Hash password dan insert user baru
        $password_hashed = password_hash($password, PASSWORD_DEFAULT);
        // User yang dibuat admin langsung aktif dan tidak diblokir
        $is_active = 1;
        $is_blocked = 0;

        $stmt = $conn->prepare("INSERT INTO user (nama_lengkap, email, password, role, is_active, is_blocked) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssii", $nama_lengkap, $email, $password_hashed, $role, $is_active, $is_blocked);

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Pengguna baru berhasil ditambahkan.';
        } else {
            $response['message'] = 'Gagal menambahkan pengguna ke database. Error: ' . $stmt->error;
        }
        $stmt->close();
        break;

    default:
        http_response_code(400);
        break;
}

$conn->close();
echo json_encode($response);

?>