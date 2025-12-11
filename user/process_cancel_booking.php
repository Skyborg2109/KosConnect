<?php
session_start();
// Start output buffering to capture any stray HTML/warnings and ensure we always return JSON
ob_start(); // Mulai output buffering
header('Content-Type: application/json');

include '../config/db.php';

$response = ['status' => 'error', 'message' => 'Terjadi kesalahan yang tidak diketahui.'];

// 1. Autentikasi: Pastikan pengguna adalah penyewa yang sudah login
if (!isset($_SESSION['user_logged_in']) || $_SESSION['role'] !== 'penyewa') {
    $response['message'] = 'Akses tidak sah. Silakan login kembali.';
    http_response_code(403);
    echo json_encode($response);
    exit();
}

$id_penyewa = $_SESSION['user_id'];
$id_booking = filter_var($_POST['id_booking'] ?? 0, FILTER_VALIDATE_INT);

if ($id_booking <= 0) {
    $response['message'] = 'ID Booking tidak valid.';
    http_response_code(400);
    echo json_encode($response);
    exit();
}

// 2. Gunakan Transaksi Database untuk memastikan integritas data
$conn->begin_transaction();

try {
    // 3. Ambil detail booking, pastikan milik penyewa dan statusnya bisa dibatalkan
    $stmt_check = $conn->prepare("SELECT id_kamar, status FROM booking WHERE id_booking = ? AND id_penyewa = ? FOR UPDATE");
    $stmt_check->bind_param("ii", $id_booking, $id_penyewa);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows === 0) {
        throw new Exception("Booking tidak ditemukan atau Anda tidak berhak mengaksesnya.");
    }

    $booking = $result_check->fetch_assoc();
    $id_kamar = $booking['id_kamar'];

    // Hanya status 'pending' atau 'menunggu_pembayaran' yang bisa dibatalkan oleh user
    if (!in_array($booking['status'], ['pending', 'menunggu_pembayaran'])) {
        throw new Exception("Pesanan ini tidak dapat dibatalkan karena sudah diproses.");
    }

    // 4. Update status booking menjadi 'batal'
    $new_booking_status = 'batal';
    $stmt_cancel = $conn->prepare("UPDATE booking SET status = ? WHERE id_booking = ?");
    $stmt_cancel->bind_param("si", $new_booking_status, $id_booking);
    $stmt_cancel->execute();

    // 5. Update status kamar kembali menjadi 'tersedia'
    $new_kamar_status = 'tersedia';
    $stmt_kamar = $conn->prepare("UPDATE kamar SET status = ? WHERE id_kamar = ?");
    $stmt_kamar->bind_param("si", $new_kamar_status, $id_kamar);
    $stmt_kamar->execute();

    // 6. Jika semua berhasil, commit transaksi
    $conn->commit();

    $response['status'] = 'success';
    $response['message'] = 'Pesanan berhasil dibatalkan.';

} catch (Exception $e) {
    // 7. Jika ada kesalahan, rollback semua perubahan
    $conn->rollback();
    $response['message'] = $e->getMessage();
    http_response_code(500);
}

$conn->close();

// Capture and sanitize any stray output (warnings, HTML) so we never return raw HTML
$buffer = ob_get_clean();
if (!empty($buffer)) {
    // Remove tags and trim — keep a short debug snippet
    $plain = trim(strip_tags($buffer));
    if (!empty($plain)) {
        // Limit debug text length to avoid huge responses
        $response['debug_output'] = substr($plain, 0, 1000);
    }
}

echo json_encode($response);
?>