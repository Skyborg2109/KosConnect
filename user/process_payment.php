<?php
session_start();
include '../config/db.php';

// 1. Autentikasi: Pastikan pengguna adalah penyewa yang sudah login
if (!isset($_SESSION['user_logged_in']) || $_SESSION['role'] !== 'penyewa') {
    die("Akses tidak sah. Silakan login kembali.");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Metode request tidak valid.");
}

$id_penyewa = $_SESSION['user_id'];
$id_booking = filter_var($_POST['id_booking'] ?? 0, FILTER_VALIDATE_INT);
$jumlah = filter_var($_POST['jumlah'] ?? 0, FILTER_VALIDATE_FLOAT);
$metode_pembayaran = trim($_POST['metode_pembayaran'] ?? '');

// 2. Validasi Input
if ($id_booking <= 0 || $jumlah <= 0 || empty($metode_pembayaran)) {
    die("Data yang dikirim tidak lengkap atau tidak valid.");
}

// 3. Validasi Kepemilikan Booking
$stmt_check = $conn->prepare("SELECT status FROM booking WHERE id_booking = ? AND id_penyewa = ?");
$stmt_check->bind_param("ii", $id_booking, $id_penyewa);
$stmt_check->execute();
$result_check = $stmt_check->get_result();
if ($result_check->num_rows === 0) {
    die("Booking tidak ditemukan atau Anda tidak berhak mengaksesnya.");
}
$booking = $result_check->fetch_assoc();
if ($booking['status'] !== 'menunggu_pembayaran') {
    die("Booking ini tidak lagi menunggu pembayaran.");
}
$stmt_check->close();

// 4. Handle File Upload
$bukti_nama = null;
if (isset($_FILES['bukti_pembayaran']) && $_FILES['bukti_pembayaran']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['bukti_pembayaran'];
    $upload_dir = '../uploads/payments/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Validasi file
    $allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];
    $max_size = 2 * 1024 * 1024; // 2MB
    if (!in_array($file['type'], $allowed_types) || $file['size'] > $max_size) {
        die("Tipe file tidak valid atau ukuran terlalu besar. Hanya JPG, PNG, PDF (maks 2MB) yang diizinkan.");
    }

    // Generate unique name
    $bukti_nama = uniqid('payment_', true) . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
    $upload_path = $upload_dir . $bukti_nama;

    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        die("Gagal mengunggah file bukti pembayaran.");
    }
} else {
    die("Anda harus mengunggah bukti pembayaran.");
}

// 5. Simpan ke Database
$conn->begin_transaction();
try {
    // Masukkan data ke tabel pembayaran
    $stmt_insert = $conn->prepare("INSERT INTO pembayaran (id_booking, jumlah, metode_pembayaran, status_pembayaran, bukti_pembayaran, tanggal_pembayaran) VALUES (?, ?, ?, 'menunggu', ?, NOW())");
    $stmt_insert->bind_param("idss", $id_booking, $jumlah, $metode_pembayaran, $bukti_nama);
    $stmt_insert->execute();

    // Update status booking menjadi 'dibayar' (menunggu verifikasi admin)
    $stmt_update = $conn->prepare("UPDATE booking SET status = 'dibayar' WHERE id_booking = ?");
    $stmt_update->bind_param("i", $id_booking);
    $stmt_update->execute();

    // Ambil detail untuk notifikasi ke pemilik
    $stmt_details = $conn->prepare("
        SELECT t.id_pemilik, u.nama_lengkap, k.nama_kamar
        FROM booking b
        JOIN kamar k ON b.id_kamar = k.id_kamar
        JOIN kost t ON k.id_kost = t.id_kost
        JOIN user u ON b.id_penyewa = u.id_user
        WHERE b.id_booking = ?
    ");
    $stmt_details->bind_param("i", $id_booking);
    $stmt_details->execute();
    $details = $stmt_details->get_result()->fetch_assoc();

    // Kirim notifikasi ke pemilik
    $pesan_notif = "Pembayaran dari '{$details['nama_lengkap']}' untuk kamar '{$details['nama_kamar']}' telah diterima. Mohon segera verifikasi.";
    $link_notif = '/KosConnect/dashboard/dashboardpemilik.php?module=owner_manage_payments';
    $stmt_notif = $conn->prepare("INSERT INTO notifications (id_user, pesan, link) VALUES (?, ?, ?)");
    $stmt_notif->bind_param("iss", $details['id_pemilik'], $pesan_notif, $link_notif);
    $stmt_notif->execute();

    $conn->commit();

    // Redirect ke halaman dashboard user dengan pesan sukses
    $_SESSION['payment_success'] = "Pembayaran Anda telah berhasil dilakukan. Silakan tunggu konfirmasi dari admin setelah mereka memverifikasi bukti pembayaran yang Anda upload.";
    header("Location: user_dashboard.php");
    exit();

} catch (Exception $e) {
    $conn->rollback();
    die("Terjadi kesalahan database: " . $e->getMessage());
}

$conn->close();
?>