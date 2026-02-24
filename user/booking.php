<?php
session_start();
// Allow both 'penyewa' and 'user' roles to access this page
if (!isset($_SESSION['user_logged_in']) || !in_array($_SESSION['role'], ['penyewa', 'user'])) {
    header("Location: ../auth/loginForm.php");
    exit();
}

include '../config/db.php';

// =======================================================
// BAGIAN 1: PENGAMBILAN DATA (LOGIC)
// =======================================================

$id_penyewa = $_SESSION['user_id'];
$userName = htmlspecialchars($_SESSION['fullname'] ?? 'Penyewa Kos');
$userRole = htmlspecialchars($_SESSION['role'] ?? 'penyewa');
$userPhoto = $_SESSION['foto_profil'] ?? null;

$kost_details = null;
$available_rooms = [];

// --- Halaman ini sekarang hanya untuk detail kos ---
if (!isset($_GET['kostId']) || empty($_GET['kostId'])) {
    header("Location: ../dashboard/dashboarduser.php"); // Redirect jika tidak ada ID Kos
    exit();
}

    $id_kost = (int)$_GET['kostId'];

    // Ambil detail Kos
    // Ambil detail Kos
    $related_photos = []; // Deprecated flat array, kept for backward compatibility if needed
    $categorized_photos = [
        'Bangunan' => [],
        'Kamar' => [],
        'Kamar Mandi' => [],
        'Fasilitas Bersama' => [],
        'Lainnya' => []
    ];

    $stmt_photos = $conn->prepare("SELECT file_name, category FROM kost_photos WHERE id_kost = ?");
    $stmt_photos->bind_param("i", $id_kost);
    $stmt_photos->execute();
    $res_photos = $stmt_photos->get_result();
    while ($row = $res_photos->fetch_assoc()) {
        $fname = $row['file_name'];
        // Filter: Check if local file exists
        if (strpos($fname, 'http') !== 0 && !file_exists('../uploads/kost/' . $fname)) {
            continue; // Skip missing local files
        }
        $related_photos[] = $fname;
        
        // Group by category
        $cat = ucfirst(strtolower($row['category'] ?? 'Lainnya'));
        
        if ($cat == 'Bangunan' || $cat == 'Depan') $target = 'Bangunan';
        elseif ($cat == 'Kamar' || $cat == 'Dalam') $target = 'Kamar';
        elseif ($cat == 'Kamar_mandi' || $cat == 'Kamar Mandi') $target = 'Kamar Mandi';
        elseif ($cat == 'Fasilitas' || $cat == 'Fasilitas Umum' || $cat == 'Fasilitas Bersama' || $cat == 'Fasilitas') $target = 'Fasilitas Bersama';
        else $target = 'Lainnya';
        
        $categorized_photos[$target][] = $row['file_name'];
    }
    $stmt_photos->close();
    try {
        $stmt_kost = $conn->prepare("
            SELECT k.nama_kost, k.alamat, k.deskripsi, k.fasilitas, k.peraturan, k.gambar, k.harga, 
                   u.nama_lengkap, u.foto_profil
            FROM kost k
            JOIN user u ON k.id_pemilik = u.id_user
            WHERE k.id_kost = ?
        ");
        $stmt_kost->bind_param("i", $id_kost);
        $stmt_kost->execute();
        $kost_details = $stmt_kost->get_result()->fetch_assoc();
        $stmt_kost->close();
    } catch (mysqli_sql_exception $e) {
        // Fallback jika kolom 'gambar' tidak ada
        $stmt_kost = $conn->prepare("SELECT nama_kost, alamat, deskripsi, fasilitas, harga FROM kost WHERE id_kost = ?");
        $stmt_kost->bind_param("i", $id_kost);
        $stmt_kost->execute();
        $kost_details = $stmt_kost->get_result()->fetch_assoc();
        $stmt_kost->close();
    }

    // Validasi: Jika kos tidak ditemukan, redirect ke dashboard
    if (!$kost_details) {
        $_SESSION['error_message'] = 'Kos tidak ditemukan atau sudah tidak tersedia.';
        header("Location: ../dashboard/dashboarduser.php");
        exit();
    }

    // Ambil kamar yang tersedia untuk Kos tersebut
    if ($kost_details) {
        $sql_rooms = "SELECT k.id_kamar, k.nama_kamar, k.tipe_kamar, k.fasilitas, k.harga, k.status, k.foto, rt.foto_tipe as foto_tipe_master, rt.peraturan_kamar 
                      FROM kamar k 
                      LEFT JOIN kost_room_types rt ON k.tipe_kamar = rt.nama_tipe AND rt.id_kost = k.id_kost 
                      WHERE k.id_kost = ? AND k.status = 'tersedia'";
        $stmt_rooms = $conn->prepare($sql_rooms);
        $stmt_rooms->bind_param("i", $id_kost);
        $stmt_rooms->execute();
        $result_rooms = $stmt_rooms->get_result();
        while ($row = $result_rooms->fetch_assoc()) {
            $available_rooms[] = $row;
        }
        $stmt_rooms->close();
    } 


$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>Detail Kos - KosConnect</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        html { scroll-behavior: smooth; }
        
        .mobile-menu-link {
            transition: all 0.2s;
        }
        
        .mobile-menu-link:hover {
            background-color: #f3f4f6;
            color: #7c3aed;
            padding-left: 1.5rem;
        }
        

        
        /* Animations */
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes slideInRight { from { opacity: 0; transform: translateX(30px); } to { opacity: 1; transform: translateX(0); } }
        @keyframes pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.05); } }
        @keyframes bounce { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-5px); } }
        @keyframes shimmer { 0% { background-position: -1000px 0; } 100% { background-position: 1000px 0; } }
        
        /* User Info Box */
        .user-info-box {
            display: flex;
            align-items: center;
            padding: 8px 12px;
            border-radius: 9999px;
            background-color: #f3f4f6;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .user-info-box:hover {
            background-color: #e5e7eb;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        /* Hero Section */
        .hero-section {
            position: relative;
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.9) 0%, rgba(255, 255, 255, 0.9) 100%);
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at top right, rgba(255,255,255,0.1) 0%, transparent 60%);
        }
        
        /* Room Cards */
        .room-card {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            border: 1px solid #e5e7eb;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .room-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.6s;
            z-index: 1;
        }
        
        .room-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px rgba(147, 51, 234, 0.15);
            border-color: rgba(147, 51, 234, 0.2);
        }
        
        .room-card:hover::before {
            left: 100%;
        }
        
        .room-card:hover .room-image {
            transform: scale(1.1);
        }
        
        .room-image {
            transition: transform 0.6s ease;
            height: 200px;
            background: linear-gradient(135deg, #f3e8ff 0%, #e0e7ff 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #d8b4fe;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            animation: slideInRight 0.4s ease-out;
        }
        .status-aktif { 
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
        }
        .status-pending { 
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.2);
        }
        .status-menunggu_pembayaran { 
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1e40af;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
        }
        .status-selesai { 
            background: linear-gradient(135deg, #e5e7eb 0%, #d1d5db 100%);
            color: #374151;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        /* Price Tag */
        .price-tag {
            background: linear-gradient(135deg, #9333ea 0%, #7c3aed 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 1.75rem;
            font-weight: 800;
        }
        
        /* Booking Button */
        .book-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-sizing: border-box;
            width: 100%;
            justify-content: center;
            gap: 0.5rem;
            background: linear-gradient(135deg, #9333ea 0%, #7c3aed 100%);
            color: white;
            font-weight: 700;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 0.8rem 1rem;
            border-radius: 0.5rem;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
            box-shadow: 0 2px 6px rgba(147, 51, 234, 0.2);
            text-decoration: none;
            white-space: nowrap;
        }
        
        .book-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.1);
            transition: left 0.3s ease;
            z-index: 1;
        }
        
        .book-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(147, 51, 234, 0.25);
            background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
        }
        
        .book-btn:hover::before {
            left: 100%;
        }
        
        .book-btn:active {
            transform: translateY(0);
            box-shadow: 0 1px 3px rgba(147, 51, 234, 0.1);
        }
        
        .book-btn i {
            font-size: 1.1rem;
        }
        
        .book-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        
        /* Button Width Utilities */
        button.w-full.book-btn {
            display: flex;
            width: 100%;
        }
        
        .room-card .book-btn {
            width: 100%;
        }
        
        /* Fasilitas */
        .facilities-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .facility-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            background: linear-gradient(135deg, #f3e8ff 0%, #e0e7ff 100%);
            border-radius: 0.75rem;
            border-left: 4px solid #9333ea;
            transition: all 0.3s ease;
        }
        
        .facility-item:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(147, 51, 234, 0.2);
        }
        
        .facility-icon {
            color: #9333ea;
            font-size: 1.5rem;
            margin-right: 1rem;
            min-width: 1.5rem;
        }
        
        /* Gradient Text */
        .gradient-text {
            background: linear-gradient(135deg, #9333ea 0%, #7c3aed 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        /* Section */
        section {
            animation: fadeIn 0.6s ease-out;
        }
        
        /* Back Button */
        .back-btn {
            transition: all 0.3s ease;
        }
        
        .back-btn:hover {
            transform: translateX(-5px);
        }
        
        /* No Rooms */
        .no-rooms-state {
            text-align: center;
            padding: 3rem 1rem;
        }
        
        .no-rooms-icon {
            font-size: 4rem;
            color: #d1d5db;
            margin-bottom: 1rem;
            animation: bounce 2s ease-in-out infinite;
        }
        
        /* Room Card Button Wrapper */
        .room-card .book-btn {
            margin-top: auto;
            width: 100%;
        }
        
        /* Mobile Menu Drawer Styles - Enhanced */
        #mobileMenuPanel {
            background-color: #ffffff;
            box-shadow: -4px 0 24px rgba(0,0,0,0.15);
            display: flex;
            flex-direction: column;
        }

        .mobile-menu-link {
            display: flex;
            align-items: center;
            padding: 14px 20px;
            margin-bottom: 8px;
            border-radius: 12px;
            color: #4b5563;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .mobile-menu-link i {
            width: 24px;
            text-align: center;
            margin-right: 16px;
            font-size: 1.1rem;
            transition: transform 0.2s ease;
        }

        .mobile-menu-link:hover, .mobile-menu-link:active {
            background-color: #f5f3ff;
            color: #7c3aed;
            padding-left: 24px;
        }

        .mobile-menu-link:hover i {
            transform: scale(1.1);
        }
        
        /* Mobile Menu Bottom Buttons */
        .mobile-menu-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 12px;
            border-radius: 12px;
            font-weight: 600;
            transition: transform 0.2s, box-shadow 0.2s;
            margin-bottom: 12px;
        }
        
        .mobile-menu-btn:active {
            transform: scale(0.98);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            nav {
                padding: 0.5rem 0 !important;
            }

            nav .flex.justify-between {
                gap: 0.5rem !important;
                align-items: center !important;
                flex-wrap: nowrap !important;
            }
            
            /* Logo and Home Icon Container */
            nav .flex.items-center.group,
            nav a.flex.flex-row.items-center {
                display: flex !important;
                flex-direction: row !important;
                gap: 0.5rem !important;
                flex-shrink: 0 !important;
                flex-wrap: nowrap !important;
                align-items: center !important;
            }
            
            nav .flex.items-center.group .w-10.h-10,
            nav a.flex.flex-row.items-center .w-10.h-10 {
                width: 2.25rem !important;
                height: 2.25rem !important;
                min-width: 2.25rem !important;
                flex-shrink: 0 !important;
            }
            
            nav .flex.items-center.group h1,
            nav a.flex.flex-row.items-center h1 {
                font-size: 1.125rem !important;
                line-height: 1.2 !important;
                white-space: nowrap !important;
                margin: 0 !important;
                flex-shrink: 0 !important;
            }
            
            /* Mobile Right Side Container */
            nav .flex.md\\:hidden.items-center {
                gap: 0.375rem !important;
                flex-shrink: 0 !important;
                align-items: center !important;
            }
            
            /* Mobile Menu Button */
            #mobileMenuBtn {
                padding: 0.375rem !important;
                width: 2.25rem !important;
                height: 2.25rem !important;
                min-width: 2.25rem !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                flex-shrink: 0 !important;
            }
            
            #mobileMenuBtn i {
                font-size: 1.125rem !important;
            }
            
            /* Mobile Notification Button */
            #mobileNotifBtn {
                padding: 0.375rem !important;
                width: 2.25rem !important;
                height: 2.25rem !important;
                min-width: 2.25rem !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                flex-shrink: 0 !important;
            }
            
            #mobileNotifBtn i {
                font-size: 1.125rem !important;
            }
            
            /* Mobile Notification Badge */
            #mobileNotifBadge {
                top: 0px !important;
                right: 0px !important;
                height: 1rem !important;
                width: 1rem !important;
                font-size: 0.6rem !important;
            }
            
            /* Desktop Notification Button - hide in mobile */
            nav button[aria-label="Notifikasi"]:not(#mobileNotifBtn) {
                display: none !important;
            }
            
            /* Ensure navbar container doesn't wrap */
            nav .max-w-7xl {
                padding-left: 1rem !important;
                padding-right: 1rem !important;
            }
            
            nav .h-16 {
                height: 3.5rem !important;
            }

            h1 {
                font-size: 1.75rem !important;
            }

            h2 {
                font-size: 1.25rem !important;
            }

            h3 {
                font-size: 1.1rem !important;
            }

            p {
                font-size: 0.9rem !important;
            }

            .hidden.md\:flex {
                display: none !important;
            }

            #mobileMenuBtn {
                display: flex !important;
            }

            #mobileMenuPanel {
                width: 85vw !important;
                max-width: 340px !important;
                transform: translateX(100%);
            }

            .max-w-7xl {
                padding-left: 1.25rem !important;
                padding-right: 1.25rem !important;
            }

            .px-4 {
                padding-left: 1rem !important;
                padding-right: 1rem !important;
            }

            .px-6 {
                padding-left: 1rem !important;
                padding-right: 1rem !important;
            }

            .py-4 {
                padding-top: 0.75rem !important;
                padding-bottom: 0.75rem !important;
            }

            .py-6 {
                padding-top: 1rem !important;
                padding-bottom: 1rem !important;
            }

            .py-8 {
                padding-top: 1rem !important;
                padding-bottom: 1rem !important;
            }

            .gap-4 {
                gap: 0.75rem !important;
            }

            .gap-6 {
                gap: 0.75rem !important;
            }

            .space-y-4 > * + * {
                margin-top: 0.75rem !important;
            }

            .space-y-6 > * + * {
                margin-top: 1rem !important;
            }

            .space-x-4 > * + * {
                margin-left: 0.75rem !important;
            }

            .space-x-6 > * + * {
                margin-left: 0.75rem !important;
            }

            .hero-section {
                padding: 1.5rem 1rem !important;
            }

            .room-card {
                padding: 1rem !important;
                border-radius: 0.75rem !important;
            }

            .room-card:hover {
                transform: translateY(-4px) scale(1.01) !important;
                box-shadow: 0 10px 20px rgba(147, 51, 234, 0.1) !important;
            }

            .room-card img {
                height: 200px !important;
                object-fit: cover !important;
            }

            .room-card h3 {
                font-size: 1rem !important;
                line-height: 1.2 !important;
            }

            .room-card p {
                font-size: 0.85rem !important;
            }

            .price-tag {
                font-size: 1.3rem !important;
            }

            .facilities-container {
                grid-template-columns: 1fr !important;
                gap: 0.75rem !important;
            }

            .grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)) !important;
                gap: 0.75rem !important;
            }

            .grid.grid-cols-1 {
                grid-template-columns: 1fr !important;
            }

            .grid.grid-cols-2 {
                grid-template-columns: repeat(2, 1fr) !important;
            }

            .grid.grid-cols-3 {
                grid-template-columns: 1fr !important;
            }

            .text-sm {
                font-size: 0.75rem !important;
            }

            .text-lg {
                font-size: 1.1rem !important;
            }

            .text-xl {
                font-size: 1.25rem !important;
            }

            .text-2xl {
                font-size: 1.5rem !important;
            }

            .text-3xl {
                font-size: 1.75rem !important;
            }

            button {
                padding: 0.75rem 1rem !important;
                font-size: 0.9rem !important;
                width: auto !important;
            }

            .btn-primary,
            .bg-gradient-to-r.from-purple-600 {
                width: 100% !important;
                padding: 0.75rem 1rem !important;
            }

            input,
            textarea,
            select {
                padding: 0.75rem !important;
                font-size: 1rem !important;
                border-radius: 0.5rem !important;
            }

            .flex {
                flex-direction: row !important;
                flex-wrap: wrap !important;
                gap: 0.75rem !important;
            }

            .flex.flex-col {
                flex-direction: column !important;
                gap: 0.75rem !important;
            }

            .flex.justify-between {
                justify-content: space-between !important;
                gap: 0.5rem !important;
            }

            .flex.items-center {
                gap: 0.5rem !important;
            }

            .flex.space-x-4 {
                gap: 0.75rem !important;
            }

            .flex.space-x-6 {
                gap: 0.75rem !important;
            }

            .rounded-lg {
                border-radius: 0.5rem !important;
            }

            .rounded-xl {
                border-radius: 0.75rem !important;
            }

            .shadow-lg {
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08) !important;
            }

            .shadow-xl {
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08) !important;
            }

            .m-4 {
                margin: 0.75rem !important;
            }

            .m-6 {
                margin: 1rem !important;
            }

            .mt-4 {
                margin-top: 0.75rem !important;
            }

            .mt-6 {
                margin-top: 1rem !important;
            }

            .mb-4 {
                margin-bottom: 0.75rem !important;
            }

            .mb-6 {
                margin-bottom: 1rem !important;
            }

            .w-full {
                width: 100% !important;
            }

            .w-auto {
                width: auto !important;
            }

            table {
                font-size: 0.8rem !important;
            }

            th,
            td {
                padding: 0.5rem !important;
            }

            .badge,
            .tag {
                font-size: 0.75rem !important;
                padding: 0.25rem 0.5rem !important;
            }
        }

        @media (max-width: 640px) {
            /* Force horizontal layout for logo */
            nav a[href*="dashboarduser.php"] {
                display: flex !important;
                flex-direction: row !important;
                align-items: center !important;
            }
            
            h1 {
                font-size: 1.5rem !important;
            }

            h2 {
                font-size: 1.1rem !important;
            }

            h3 {
                font-size: 1rem !important;
            }

            p {
                font-size: 0.85rem !important;
            }

            .text-sm {
                font-size: 0.7rem !important;
            }

            .text-lg {
                font-size: 1rem !important;
            }

            .text-xl {
                font-size: 1.1rem !important;
            }

            .text-2xl {
                font-size: 1.25rem !important;
            }

            .text-3xl {
                font-size: 1.5rem !important;
            }

            .grid {
                grid-template-columns: 1fr !important;
                gap: 0.5rem !important;
            }

            .grid.grid-cols-2 {
                grid-template-columns: 1fr !important;
            }

            .room-card {
                padding: 0.75rem !important;
                border-radius: 0.5rem !important;
            }

            .room-card img {
                height: 150px !important;
            }

            .price-tag {
                font-size: 1.1rem !important;
            }

            .px-4,
            .px-6 {
                padding-left: 0.75rem !important;
                padding-right: 0.75rem !important;
            }

            .py-4,
            .py-6,
            .py-8 {
                padding-top: 0.5rem !important;
                padding-bottom: 0.5rem !important;
            }

            .gap-4,
            .gap-6 {
                gap: 0.5rem !important;
            }

            .space-y-4 > * + *,
            .space-y-6 > * + * {
                margin-top: 0.5rem !important;
            }

            .space-x-4 > * + *,
            .space-x-6 > * + * {
                margin-left: 0.5rem !important;
            }

            .flex {
                flex-direction: column !important;
                gap: 0.5rem !important;
            }

            button {
                padding: 0.65rem 0.9rem !important;
                font-size: 0.85rem !important;
            }

            input,
            textarea,
            select {
                padding: 0.65rem !important;
                font-size: 1rem !important;
            }

            #mobileMenuPanel {
                width: 90vw !important;
                max-width: 300px !important;
            }

            .rounded-lg {
                border-radius: 0.375rem !important;
            }

            .rounded-xl {
                border-radius: 0.5rem !important;
            }

            .m-4,
            .m-6 {
                margin: 0.5rem !important;
            }

            .mt-4,
            .mt-6 {
                margin-top: 0.5rem !important;
            }

            .mb-4,
            .mb-6 {
                margin-bottom: 0.5rem !important;
            }

            .shadow-lg,
            .shadow-xl {
                box-shadow: 0 3px 10px rgba(0, 0, 0, 0.06) !important;
            }

            table {
                font-size: 0.75rem !important;
            }

            th,
            td {
                padding: 0.375rem !important;
            }
        }

        /* DEFINITIVE MOBILE NAVBAR FIXES */
        @media (max-width: 768px) {
            /* Force Navbar Height & Padding */
            nav.fixed {
                height: 60px !important;
                padding: 0 !important;
                display: flex !important;
                align-items: center !important;
            }
            
            nav.fixed > div {
                height: 100% !important;
                width: 100% !important;
                display: flex !important;
                align-items: center !important;
            }
            
            nav.fixed > div > div {
                height: 100% !important;
                width: 100% !important;
                display: flex !important;
                flex-direction: row !important;
                justify-content: space-between !important;
                align-items: center !important;
                flex-wrap: nowrap !important;
            }

            /* FORCE LOGO ROW LAYOUT */
            nav a[href*="dashboarduser.php"] {
                display: flex !important;
                flex-direction: row !important;
                align-items: center !important;
                gap: 8px !important;
                height: 100% !important;
                flex-wrap: nowrap !important;
            }

            /* Logo Icon Size */
            nav a[href*="dashboarduser.php"] > div {
                width: 32px !important;
                height: 32px !important;
                min-width: 32px !important;
                max-width: 32px !important;
                margin: 0 !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
            }
            
            nav a[href*="dashboarduser.php"] > div i {
                font-size: 14px !important;
            }

            /* Logo Text Size */
            nav a[href*="dashboarduser.php"] h1 {
                font-size: 16px !important;
                line-height: 1 !important;
                margin: 0 !important;
                display: block !important;
                width: auto !important;
            }

            /* Right Side Buttons Container */
            .md\:hidden.flex {
                display: flex !important;
                flex-direction: row !important;
                align-items: center !important;
                gap: 8px !important;
                height: 100% !important;
            }

            /* Notification & Menu Buttons */
            #mobileNotifBtn, #mobileMenuBtn {
                width: 36px !important;
                height: 36px !important;
                padding: 0 !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                margin: 0 !important;
            }
            
            #mobileNotifBtn i, #mobileMenuBtn i {
                font-size: 18px !important;
                line-height: 1 !important;
            }
        }
    </style>
</head>
<body class="bg-gray-50 font-sans">

    <!-- Navigasi Konsisten -->
    <nav class="bg-white shadow-lg fixed w-full top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <a href="../dashboard/dashboarduser.php" class="flex flex-row items-center gap-2 group">
                    <div class="w-10 h-10 bg-gradient-to-br from-purple-600 to-indigo-600 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform flex-shrink-0">
                        <i class="fas fa-home text-white"></i>
                    </div>
                    <h1 class="font-bold bg-gradient-to-r from-purple-600 to-indigo-600 bg-clip-text text-transparent whitespace-nowrap logo-text">Kos<span class="text-purple-600">Connect</span></h1>
                </a>
                <div class="hidden md:flex items-center space-x-6">
                    <nav class="flex space-x-8">
                        <a href="../dashboard/dashboarduser.php" class="text-gray-700 font-medium hover:text-purple-600 py-2">Beranda</a>
                        <a href="user_dashboard.php" class="text-gray-700 font-medium hover:text-purple-600 py-2">Dashboard</a>
                        <a href="../dashboard/dashboarduser.php#pilihan-kos" class="text-gray-700 font-medium hover:text-purple-600 py-2">Pilihan Kos</a>
                        <a href="wishlist.php" class="text-gray-700 font-medium hover:text-purple-600 py-2">Favorit</a>
                        <a href="feedback.php" class="text-gray-700 font-medium hover:text-purple-600 py-2">Feedback</a>
                    </nav>
                    <div class="flex items-center space-x-4 pl-6 border-l-2 border-gray-200">
                        <button id="notifBtn" onclick="showNotifications()" class="relative text-gray-600 hover:text-purple-600 p-2 rounded-lg hover:bg-purple-50 transition-all" aria-label="Notifikasi" title="Notifikasi">
                            <i class="fas fa-bell text-xl" aria-hidden="true"></i>
                            <?php 
                            include '../config/db.php';
                            $stmt_notif = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE id_user = ? AND is_read = 0");
                            $stmt_notif->bind_param("i", $id_penyewa);
                            $stmt_notif->execute();
                            $notif_result = $stmt_notif->get_result()->fetch_assoc();
                            $notif_count = $notif_result['count'];
                            $stmt_notif->close();
                            $conn->close();
                            ?>
                            <?php if ($notif_count > 0): ?>
                                <span id="notifBadge" class="absolute top-0 right-0 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center shadow-lg"><?php echo $notif_count; ?></span>
                            <?php endif; ?>
                        </button>
                        <button onclick="showProfileModal()" class="flex items-center space-x-2 p-2 rounded-full hover:bg-gray-100 transition">
                            <?php if ($userPhoto): 
                                $isUrl = strpos($userPhoto, 'http') === 0;
                                $photoSrc = $isUrl ? $userPhoto : "../uploads/profiles/" . htmlspecialchars($userPhoto);
                            ?>
                                <img id="headerUserPhoto" src="<?php echo $photoSrc; ?>" alt="Foto Profil" class="w-9 h-9 rounded-full object-cover">
                            <?php else: ?>
                                <div id="headerUserPhoto" class="w-9 h-9 rounded-full bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center text-white font-bold">
                                    <?php echo strtoupper(substr($userName, 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                        </button>
                        <a href="../auth/logout.php" onclick="confirmLogout(event)" class="bg-gradient-to-r from-red-500 to-red-600 text-white px-4 py-2 rounded-lg font-medium text-sm hover:from-red-600 hover:to-red-700 shadow-md hover:shadow-lg cursor-pointer">
                            <i class="fas fa-sign-out-alt mr-1"></i>Logout
                        </a>
                    </div>
                </div>
                
                <!-- Mobile Menu Button di Navbar -->
                <div class="flex md:hidden items-center space-x-2">
                    <button id="mobileNotifBtn" onclick="showNotifications()" class="relative text-gray-600 hover:text-purple-600 p-2 rounded-lg hover:bg-purple-50 transition-all" aria-label="Notifikasi" title="Notifikasi">
                        <i class="fas fa-bell text-xl" aria-hidden="true"></i>
                        <?php if ($notif_count > 0): ?>
                            <span id="mobileNotifBadge" class="absolute top-0 right-0 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center shadow-lg" aria-live="polite"><?php echo ($notif_count > 99) ? '99+' : $notif_count; ?></span>
                        <?php endif; ?>
                    </button>
                    <button id="mobileMenuBtn" class="p-2 text-gray-600 hover:text-purple-600 hover:bg-purple-50 rounded-lg transition-colors" onclick="toggleMobileMenu()" title="Menu">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Mobile Navigation Drawer -->
    <!-- Mobile Navigation Drawer -->
    <div id="mobileMenuDrawer" class="fixed inset-0 z-50 md:hidden pointer-events-none" style="pointer-events: none;">
        <!-- Backdrop -->
        <div id="mobileMenuBackdrop" class="absolute inset-0 bg-black bg-opacity-50 transition-opacity duration-300 opacity-0 pointer-events-none" onclick="toggleMobileMenu()" style="pointer-events: none;"></div>
        
        <!-- Drawer -->
        <div class="absolute right-0 top-0 h-full w-full max-w-sm bg-white shadow-2xl transform translate-x-full transition-transform duration-300 flex flex-col" id="mobileMenuPanel" style="pointer-events: auto;">
            <!-- Close Button & Header -->
            <div class="flex items-center justify-between p-6 border-b border-gray-100 bg-white sticky top-0 z-10">
                <h2 class="text-2xl font-bold bg-gradient-to-r from-purple-600 to-indigo-600 bg-clip-text text-transparent">Menu</h2>
                <button onclick="toggleMobileMenu()" class="p-2 text-gray-500 hover:text-red-500 hover:bg-red-50 rounded-full transition-colors focus:outline-none">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <!-- Navigation Links Wrapper -->
            <div class="flex-1 overflow-y-auto p-6 scroll-smooth">
                <nav class="space-y-2">
                    <a href="../dashboard/dashboarduser.php" class="mobile-menu-link" onclick="handleMobileMenuClick(event)">
                        <i class="fas fa-home text-purple-600"></i>Beranda
                    </a>
                    <a href="user_dashboard.php" class="mobile-menu-link" onclick="handleMobileMenuClick(event)">
                        <i class="fas fa-chart-line text-blue-600"></i>Dashboard
                    </a>
                    <a href="../dashboard/dashboarduser.php#pilihan-kos" class="mobile-menu-link" onclick="handleMobileMenuClick(event)">
                        <i class="fas fa-building text-orange-600"></i>Pilihan Kos
                    </a>
                    <a href="wishlist.php" class="mobile-menu-link" onclick="handleMobileMenuClick(event)">
                        <i class="fas fa-heart text-red-600"></i>Favorit
                    </a>
                    <a href="feedback.php" class="mobile-menu-link" onclick="handleMobileMenuClick(event)">
                        <i class="fas fa-comment text-green-600"></i>Feedback
                    </a>
                    <a href="../dashboard/dashboarduser.php#kontak" class="mobile-menu-link" onclick="handleMobileMenuClick(event)">
                        <i class="fas fa-phone text-cyan-600"></i>Kontak
                    </a>
                </nav>
            </div>
            
            <!-- User Actions Footer -->
            <div class="p-6 border-t border-gray-100 bg-gray-50">
                <div class="space-y-3">
                    <button id="drawerProfileBtn" onclick="showProfileModal()" class="mobile-menu-btn bg-gradient-to-r from-purple-600 to-indigo-600 text-white shadow-lg hover:shadow-indigo-500/30">
                        <i class="fas fa-user mr-2"></i>Profil
                    </button>
                    <button id="drawerNotifBtn" onclick="showNotifications()" class="mobile-menu-btn bg-white text-gray-700 border border-gray-200 hover:bg-gray-50" style="width: 100% !important; display: flex !important;">
                        <i class="fas fa-bell mr-2 text-yellow-500"></i>Notifikasi
                    </button>
                    <a href="../auth/logout.php" onclick="confirmLogout(event)" id="drawerLogoutBtn" class="mobile-menu-btn bg-red-50 text-red-600 hover:bg-red-100">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <main class="pt-20 pb-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <?php if ($kost_details): ?>
            
            <!-- Back Button -->
            <a href="../dashboard/dashboarduser.php#pilihan-kos" class="inline-flex items-center text-purple-600 hover:text-purple-700 mb-8 back-btn group">
                <i class="fas fa-arrow-left mr-2 group-hover:mr-3 transition-all"></i>
                <span class="font-medium">Kembali ke Daftar Kos</span>
            </a>

            <!-- Hero Section -->
            <?php 
            $gambar_kost = $kost_details['gambar'] ?? '';
            $bgImage = '../img/kost4.jpg'; // Default
            
            if (!empty($gambar_kost)) {
                if (strpos($gambar_kost, 'http') === 0) {
                    $bgImage = $gambar_kost;
                } elseif (file_exists('../uploads/kost/' . $gambar_kost)) {
                    $bgImage = '../uploads/kost/' . $gambar_kost;
                }
            }
            ?>
            <section class="hero-section rounded-3xl shadow-2xl mb-16 p-12 text-white relative overflow-hidden" style="animation: slideUp 0.6s ease-out; background-image: linear-gradient(135deg, rgba(37, 99, 235, 0.85) 0%, rgba(30, 64, 175, 0.85) 100%), url('<?php echo htmlspecialchars($bgImage); ?>'); background-size: cover; background-position: center;">
                <div class="relative z-10">
                    <div class="inline-block bg-white bg-opacity-20 backdrop-blur-sm text-white px-4 py-2 rounded-full text-sm font-semibold mb-4 animation: slideInRight 0.8s ease-out;">
                        <i class="fas fa-star mr-1 text-yellow-300"></i>Pilihan Unggulan
                    </div>
                    <h1 class="text-5xl md:text-6xl font-extrabold mb-4 leading-tight" style="animation: slideUp 0.8s ease-out;">
                        <?php echo htmlspecialchars($kost_details['nama_kost']); ?>
                    </h1>
                    <p class="text-xl md:text-2xl text-purple-100 mb-6 flex items-center" style="animation: slideUp 1s ease-out;">
                        <i class="fas fa-map-marker-alt mr-3 text-2xl"></i>
                        <?php echo htmlspecialchars($kost_details['alamat']); ?>
                    </p>
                    
                    <!-- Quick Stats -->
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mt-8" style="animation: slideUp 1.2s ease-out;">
                        <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl p-4 border border-white border-opacity-20">
                            <div class="text-sm text-purple-100 mb-1">Kamar Tersedia</div>
                            <div class="text-3xl font-bold"><?php echo count($available_rooms); ?></div>
                        </div>
                        <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl p-4 border border-white border-opacity-20">
                            <div class="text-sm text-purple-100 mb-1">Rating</div>
                            <div class="text-3xl font-bold flex items-center">
                                <i class="fas fa-star text-yellow-300 mr-2"></i>4.8
                            </div>
                        </div>
                        <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl p-4 border border-white border-opacity-20 col-span-2 md:col-span-1">
                            <div class="text-sm text-purple-100 mb-1">Harga Mulai</div>
                            <div class="text-2xl font-bold">Rp <?php echo !empty($available_rooms) ? number_format(min(array_column($available_rooms, 'harga')), 0, ',', '.') : '-'; ?></div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Gallery Section -->
            <?php if (!empty($related_photos)): ?>
            <div class="mb-12" style="animation: slideUp 0.6s ease-out;">
                <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                    <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center text-indigo-600 mr-3">
                        <i class="fas fa-images"></i>
                    </div>
                    Foto-Foto Kost
                </h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <?php 
                    $max_display = 4;
                    $total_photos = count($related_photos);
                    $display_photos = array_slice($related_photos, 0, $max_display);
                    $remaining = $total_photos - $max_display;

                    foreach($display_photos as $index => $photo): 
                        $is_last = ($index === $max_display - 1) && ($remaining > 0);
                        
                        $isUrl = strpos($photo, 'http') === 0;
                        $photoSrc = $isUrl ? $photo : "../uploads/kost/$photo";
                        $modalSrc = $isUrl ? $photo : "../uploads/kost/$photo"; // Used for onclick
                    ?>
                    <div class="aspect-video rounded-xl overflow-hidden shadow-md hover:shadow-xl transition-all duration-300 transform hover:scale-105 cursor-pointer group relative" onclick="<?php echo $is_last ? 'openFullGallery()' : "showImageModal('$modalSrc')"; ?>">
                        <img src="<?php echo $photoSrc; ?>" alt="Foto Kos" class="w-full h-full object-cover group-hover:brightness-110 transition-all" onerror="this.closest('.aspect-video').style.display='none'">
                        
                        <?php if ($is_last): ?>
                            <div class="absolute inset-0 bg-black bg-opacity-60 flex flex-col items-center justify-center text-white transition-all group-hover:bg-opacity-70">
                                <span class="text-3xl font-bold mb-1">+<?php echo $remaining; ?></span>
                                <span class="text-sm font-medium">Foto Lainnya</span>
                            </div>
                        <?php else: ?>
                            <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition-all flex items-center justify-center">
                                <i class="fas fa-search-plus text-white opacity-0 group-hover:opacity-100 transform scale-50 group-hover:scale-100 transition-all duration-300 drop-shadow-lg text-2xl"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Content Section -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-16">
                <!-- Main Content (2/3) -->
                <div class="lg:col-span-2 space-y-8 order-last lg:order-first">
                    <!-- Tentang Kos -->
                    <div class="bg-white rounded-2xl shadow-lg p-8 border border-gray-100" style="animation: slideUp 0.8s ease-out;">
                        <div class="flex items-center mb-6">
                            <div class="w-12 h-12 bg-gradient-to-br from-purple-400 to-purple-600 rounded-lg flex items-center justify-center text-white text-xl mr-4">
                                <i class="fas fa-info-circle"></i>
                            </div>
                            <h2 class="text-3xl font-bold text-gray-900">Tentang Kos Ini</h2>
                        </div>
                        <p class="text-gray-600 leading-relaxed text-lg">
                            <?php echo nl2br(htmlspecialchars($kost_details['deskripsi'])); ?>
                        </p>
                    </div>

                    <!-- Info Box: Profil Pemilik -->
                    <div class="bg-blue-50 rounded-2xl shadow-lg p-6 border border-blue-100 transition-transform hover:-translate-y-1 duration-300 transform scale-100 hover:scale-[1.01]">
                        <div class="flex items-center mb-4">
                            <?php 
                                $ownerPhoto = $kost_details['foto_profil'] ?? '';
                                $ownerName = $kost_details['nama_lengkap'] ?? 'Pemilik';
                                $ownerInitials = strtoupper(substr($ownerName, 0, 1));
                                
                                if (!empty($ownerPhoto)) {
                                    $isUrl = strpos($ownerPhoto, 'http') === 0;
                                    $photoSrc = $isUrl ? $ownerPhoto : "../uploads/profiles/" . htmlspecialchars($ownerPhoto);
                                    echo '<img src="' . $photoSrc . '" alt="' . htmlspecialchars($ownerName) . '" class="w-14 h-14 rounded-full object-cover border-2 border-white shadow-md mr-4">';
                                } else {
                                    echo '<div class="w-14 h-14 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white font-bold text-xl border-2 border-white shadow-md mr-4">' . $ownerInitials . '</div>';
                                }
                            ?>
                            <div>
                                <h4 class="font-bold text-gray-900 text-lg leading-tight"><?php echo htmlspecialchars($ownerName); ?></h4>
                                <p class="text-xs text-blue-600 font-semibold uppercase tracking-wide bg-blue-100 px-2 py-0.5 rounded-full inline-block mt-1">Pemilik Kos</p>
                            </div>
                        </div>
                        
                        <p class="text-base text-blue-800 mb-4 leading-relaxed">
                            Hubungi pemilik kos untuk menanyakan ketersediaan kamar, menegosiasikan harga, atau info fasilitas lebih lanjut.
                        </p>
                        
                        <button class="w-full bg-white text-blue-600 hover:text-white hover:bg-blue-600 border border-blue-200 font-bold py-2.5 px-4 rounded-xl transition-all shadow-sm hover:shadow-md flex items-center justify-start pl-6 group">
                            <i class="fab fa-whatsapp text-lg mr-2 group-hover:text-white duration-300"></i>
                            Hubungi via WhatsApp
                        </button>
                    </div>

                    <!-- Fasilitas -->
                    <div class="bg-white rounded-2xl shadow-lg p-8 border border-gray-100" style="animation: slideUp 1s ease-out;">
                        <div class="flex items-center mb-6">
                            <div class="w-12 h-12 bg-gradient-to-br from-indigo-400 to-indigo-600 rounded-lg flex items-center justify-center text-white text-xl mr-4">
                                <i class="fas fa-home"></i>
                            </div>
                            <h3 class="text-3xl font-bold text-gray-900">Fasilitas Utama</h3>
                        </div>
                        <div class="facilities-container">
                            <?php 
                            $fasilitas = array_filter(array_map('trim', explode(',', $kost_details['fasilitas'])));
                            $icons = ['WiFi' => 'fas fa-wifi', 'Listrik' => 'fas fa-plug', 'Air' => 'fas fa-droplet', 
                                     'Kamar Mandi' => 'fas fa-bath', 'Dapur' => 'fas fa-utensils', 'Parkir' => 'fas fa-car',
                                     'AC' => 'fas fa-snowflake', 'Ranjang' => 'fas fa-bed', 'Lemari' => 'fas fa-cabinet',
                                     'Meja' => 'fas fa-table', 'Kursi' => 'fas fa-chair', 'Pintu' => 'fas fa-door-open'];
                            $fasilitas_list = [];
                            foreach ($fasilitas as $f) {
                                foreach ($icons as $key => $icon) {
                                    if (stripos($f, $key) !== false) {
                                        $fasilitas_list[] = ['name' => $f, 'icon' => $icon];
                                        break;
                                    }
                                }
                                if (count($fasilitas_list) === count($fasilitas)) break;
                            }
                            foreach ($fasilitas_list as $item):
                            ?>
                            <div class="facility-item">
                                <i class="<?php echo $item['icon']; ?> facility-icon"></i>
                                <span class="text-gray-700 font-medium"><?php echo $item['name']; ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Peraturan Kos -->
                    <?php if (!empty($kost_details['peraturan'])): ?>
                    <div class="bg-white rounded-2xl shadow-lg p-8 border border-gray-100" style="animation: slideUp 1.05s ease-out;">
                        <div class="flex items-center mb-6">
                            <div class="w-12 h-12 bg-gradient-to-br from-red-400 to-red-600 rounded-lg flex items-center justify-center text-white text-xl mr-4">
                                <i class="fas fa-gavel"></i>
                            </div>
                            <h3 class="text-3xl font-bold text-gray-900">Peraturan Kos</h3>
                        </div>
                        <div class="prose text-gray-600 leading-relaxed font-medium">
                            <?php echo nl2br(htmlspecialchars($kost_details['peraturan'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Lokasi Section -->
                    <?php 
                        // Use kost name as primary location identifier
                        $location_query = htmlspecialchars($kost_details['nama_kost']);
                        $maps_url = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($location_query);
                    ?>
                    
                    <!-- Enhanced Location Card with Red Icon Header -->
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-200" style="animation: slideUp 1.1s ease-out;">
                        <!-- Header with Red Icon -->
                        <div class="bg-gradient-to-r from-red-50 to-pink-50 px-6 py-4 border-b border-red-100">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-red-600 rounded-xl flex items-center justify-center shadow-lg">
                                        <i class="fas fa-map-marked-alt text-white text-xl"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-2xl font-bold text-gray-800">Lokasi Kos</h3>
                                        <p class="text-sm text-gray-600">Temukan lokasi kos dengan mudah</p>
                                    </div>
                                </div>
                                <a href="<?php echo $maps_url; ?>" target="_blank" 
                                   class="hidden md:inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2.5 rounded-lg hover:bg-blue-700 transition-all shadow-md hover:shadow-lg transform hover:-translate-y-0.5 font-medium text-sm">
                                    <i class="fas fa-external-link-alt"></i>
                                    Buka di Google Maps
                                </a>
                            </div>
                        </div>
                        
                        <!-- Map Container -->
                        <div class="relative h-96 md:h-[450px] bg-gray-100">
                            <iframe 
                                width="100%" 
                                height="100%" 
                                frameborder="0" 
                                scrolling="no" 
                                marginheight="0" 
                                marginwidth="0" 
                                loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade"
                                src="https://maps.google.com/maps?q=<?php echo urlencode($location_query); ?>&t=&z=17&ie=UTF8&iwloc=&output=embed"
                                allowfullscreen
                                class="w-full h-full">
                            </iframe>
                            
                            <!-- Loading Placeholder -->
                            <div class="absolute inset-0 bg-gradient-to-br from-gray-100 to-gray-200 animate-pulse pointer-events-none" id="mapLoader">
                                <div class="flex flex-col items-center justify-center h-full">
                                    <i class="fas fa-spinner fa-spin text-4xl text-gray-400 mb-3"></i>
                                    <p class="text-gray-500 text-sm">Memuat peta...</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Location Info Card -->
                        <div class="bg-gradient-to-br from-white to-gray-50 p-6 border-t border-gray-200">
                            <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                                <div class="flex-1">
                                    <!-- Kost Name with Pin Icon -->
                                    <div class="flex items-start gap-3 mb-3">
                                        <div class="w-10 h-10 bg-red-500 rounded-lg flex items-center justify-center flex-shrink-0 shadow-md">
                                            <i class="fas fa-map-pin text-white text-lg"></i>
                                        </div>
                                        <div class="flex-1">
                                            <h4 class="font-bold text-gray-900 text-lg leading-tight mb-1">
                                                <?php echo htmlspecialchars($kost_details['nama_kost']); ?>
                                            </h4>
                                            <p class="text-gray-600 text-sm md:text-base leading-relaxed">
                                                <?php echo htmlspecialchars($kost_details['alamat']); ?>
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <!-- Additional Info -->
                                    <div class="flex flex-wrap gap-2 mt-4">
                                        <span class="inline-flex items-center gap-1.5 bg-blue-50 text-blue-700 px-3 py-1.5 rounded-lg text-xs font-medium border border-blue-200">
                                            <i class="fas fa-location-crosshairs"></i>
                                            Lihat di Peta
                                        </span>
                                        <a href="<?php echo $maps_url; ?>" target="_blank" 
                                           class="inline-flex items-center gap-1.5 bg-green-50 text-green-700 px-3 py-1.5 rounded-lg text-xs font-medium border border-green-200 hover:bg-green-100 transition-colors">
                                            <i class="fas fa-route"></i>
                                            Petunjuk Arah
                                        </a>
                                    </div>
                                </div>
                                
                                <!-- Mobile "Open in Maps" Button -->
                                <a href="<?php echo $maps_url; ?>" target="_blank" 
                                   class="md:hidden flex items-center justify-center gap-2 bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-3.5 rounded-xl hover:from-blue-700 hover:to-blue-800 transition-all shadow-md hover:shadow-lg font-semibold">
                                    <i class="fas fa-directions text-lg"></i>
                                    Buka di Google Maps
                                </a>
                            </div>
                            
                            <!-- Map Tips -->
                            <div class="mt-5 pt-5 border-t border-gray-200">
                                <div class="flex items-start gap-3 bg-blue-50 p-4 rounded-lg border border-blue-100">
                                    <i class="fas fa-info-circle text-blue-600 mt-0.5"></i>
                                    <div class="flex-1">
                                        <p class="text-sm text-blue-900 font-medium mb-1">Tips Menggunakan Peta:</p>
                                        <ul class="text-xs text-blue-700 space-y-1">
                                            <li>• Gunakan scroll mouse atau pinch untuk zoom in/out</li>
                                            <li>• Klik dan drag untuk menggeser peta</li>
                                            <li>• Klik "Buka di Google Maps" untuk navigasi lengkap</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <script>
                        // Hide loading placeholder when iframe loads
                        window.addEventListener('load', function() {
                            setTimeout(function() {
                                const loader = document.getElementById('mapLoader');
                                if (loader) {
                                    loader.style.opacity = '0';
                                    loader.style.transition = 'opacity 0.5s ease';
                                    setTimeout(() => loader.remove(), 500);
                                }
                            }, 1500);
                        });
                    </script>
                </div>

                <!-- Sidebar (1/3) -->
                <div class="space-y-6 order-first lg:order-last" style="animation: slideInRight 0.8s ease-out;">
                    <!-- Price Card -->
                    <!-- Price Card -->
                    <div class="bg-white rounded-3xl shadow-xl p-8 border border-gray-100 sticky top-24 overflow-hidden relative">
                        <!-- Decorative BG -->
                        <div class="absolute top-0 right-0 w-32 h-32 bg-purple-100 rounded-full mix-blend-multiply filter blur-3xl opacity-50 -mr-10 -mt-10"></div>
                        <div class="absolute bottom-0 left-0 w-32 h-32 bg-indigo-100 rounded-full mix-blend-multiply filter blur-3xl opacity-50 -ml-10 -mb-10"></div>

                        <div class="relative z-10 mb-8">
                            <p class="text-gray-500 font-medium text-sm tracking-wide uppercase mb-1">Harga Mulai Dari</p>
                            <div class="flex items-baseline">
                                <span class="text-3xl font-extrabold bg-clip-text text-transparent bg-gradient-to-r from-purple-600 to-indigo-600">
                                    Rp <?php 
                                        $display_price = '-';
                                        if (!empty($available_rooms)) {
                                            $display_price = number_format(min(array_column($available_rooms, 'harga')), 0, ',', '.');
                                        } elseif (isset($kost_details['harga_per_bulan'])) {
                                            $display_price = number_format($kost_details['harga_per_bulan'], 0, ',', '.');
                                        } elseif (isset($kost_details['harga'])) {
                                            $display_price = number_format($kost_details['harga'], 0, ',', '.');
                                        }
                                        echo $display_price;
                                    ?>
                                </span>
                                <span class="text-gray-400 text-sm ml-2">/ bulan</span>
                            </div>
                        </div>
                        
                        <!-- Quick Stats Grid -->
                        <!-- Quick Stats Grid -->
                        <div class="relative z-10 grid grid-cols-1 md:grid-cols-3 gap-3 mb-8">
                            <!-- Kamar -->
                            <div class="flex flex-col items-start justify-start p-3 pl-4 rounded-2xl bg-purple-50 border border-purple-100 transition-transform hover:scale-105">
                                <i class="fas fa-door-open text-purple-600 text-xl mb-1"></i>
                                <span class="text-lg font-bold text-gray-800"><?php echo count($available_rooms); ?></span>
                                <span class="text-[10px] uppercase tracking-wider text-purple-600 font-semibold">Kamar</span>
                            </div>
                            <!-- Rating -->
                            <div class="flex flex-col items-start justify-start p-3 pl-4 rounded-2xl bg-yellow-50 border border-yellow-100 transition-transform hover:scale-105">
                                <i class="fas fa-star text-yellow-500 text-xl mb-1"></i>
                                <span class="text-lg font-bold text-gray-800">4.8</span>
                                <span class="text-[10px] uppercase tracking-wider text-yellow-600 font-semibold">Rating</span>
                            </div>
                            <!-- Users -->
                            <div class="flex flex-col items-start justify-start p-3 pl-4 rounded-2xl bg-blue-50 border border-blue-100 transition-transform hover:scale-105">
                                <i class="fas fa-users text-blue-600 text-xl mb-1"></i>
                                <span class="text-lg font-bold text-gray-800">200+</span>
                                <span class="text-[10px] uppercase tracking-wider text-blue-600 font-semibold">Aktif</span>
                            </div>
                        </div>

                        <!-- CTA -->
                        <button onclick="document.getElementById('kamar-section').scrollIntoView({behavior: 'smooth'})" class="w-full relative z-10 bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-bold py-4 px-6 rounded-xl shadow-lg shadow-indigo-200 transform transition-all duration-300 hover:scale-[1.02] hover:shadow-indigo-300 active:scale-95 flex items-center justify-start pl-8 group">
                            <i class="fas fa-arrow-down mr-3 group-hover:translate-y-1 transition-transform"></i>
                            <span>Lihat Kamar</span>
                        </button>
                    </div>


                </div>
            </div>

            <!-- Available Rooms Section -->
            <section id="kamar-section" class="mb-12" style="animation: slideUp 1.2s ease-out;">
                <div class="mb-10">
                    <h2 class="text-4xl font-extrabold text-gray-900 mb-3">
                        <i class="fas fa-door-open text-purple-600 mr-3"></i>Kamar yang Tersedia
                    </h2>
                    <p class="text-gray-600 text-lg">Pilih kamar favorit Anda dan lakukan booking sekarang</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <?php if (!empty($available_rooms)): ?>
                        <?php foreach ($available_rooms as $index => $room): ?>
                        <div class="bg-white rounded-2xl shadow-xl overflow-hidden room-card group/card" style="animation: slideUp <?php echo 0.8 + ($index * 0.15); ?>s ease-out;">
                            <!-- Room Image with Overlay -->
                            <div class="relative room-image overflow-hidden">
                                <?php 
                                    $room_foto = $room['foto'] ?? '';
                                    $room_foto_master = $room['foto_tipe_master'] ?? '';
                                    
                                    $final_img = '';
                                    $show_room_img = false;
                                    
                                    // 1. Cek foto kamar spesifik
                                    if ($room_foto) {
                                        $isUrl = strpos($room_foto, 'http') === 0;
                                        if ($isUrl || file_exists(__DIR__ . '/../uploads/rooms/' . $room_foto)) {
                                            $final_img = $isUrl ? $room_foto : '../uploads/rooms/' . $room_foto;
                                            $show_room_img = true;
                                        }
                                    }
                                    
                                    // 2. Fallback ke foto tipe (master)
                                    if (!$show_room_img && $room_foto_master) {
                                        $final_img = $room_foto_master;
                                        $show_room_img = true;
                                    }

                                    if ($show_room_img): 
                                ?>
                                    <img src="<?php echo htmlspecialchars($final_img); ?>" alt="<?php echo htmlspecialchars($room['nama_kamar']); ?>" class="w-full h-full object-cover transition-transform duration-500 group-hover/card:scale-110">
                                    <!-- Gradient overlay -->
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent"></div>
                                <?php else: ?>
                                    <i class="fas fa-bed"></i>
                                <?php endif; ?>
                                
                                <!-- Status badge on image -->
                                <div class="absolute top-4 right-4 z-10">
                                    <span class="bg-green-500 text-white px-3 py-1.5 rounded-full text-xs font-bold shadow-lg inline-flex items-center gap-1.5">
                                        <i class="fas fa-circle text-[6px] animate-pulse"></i>
                                        TERSEDIA
                                    </span>
                                </div>
                            </div>

                            <!-- Room Info -->
                            <div class="p-6 flex flex-col h-full">
                                <!-- Room name and badges -->
                                <div class="mb-4">
                                    <h3 class="text-2xl font-bold text-gray-900 mb-2"><?php echo htmlspecialchars($room['nama_kamar']); ?></h3>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="inline-flex items-center gap-1.5 bg-gray-50 text-gray-600 px-3 py-1 rounded-full text-xs font-medium border border-gray-200">
                                            <i class="fas fa-id-card text-[10px]"></i>
                                            ID: <?php echo $room['id_kamar']; ?>
                                        </span>
                                        <?php if(!empty($room['tipe_kamar'])): ?>
                                            <span class="inline-flex items-center gap-1.5 bg-gradient-to-r from-purple-50 to-indigo-50 text-purple-700 px-3 py-1 rounded-full text-xs font-bold border border-purple-200">
                                                <i class="fas fa-certificate text-[10px]"></i>
                                                <?php echo strtoupper(htmlspecialchars($room['tipe_kamar'])); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Price -->
                                <div class="mb-4 pb-4 border-b-2 border-gray-100">
                                    <p class="text-xs text-gray-500 mb-1 uppercase tracking-wide font-semibold">Harga Sewa Per Bulan</p>
                                    <p class="text-3xl font-extrabold bg-gradient-to-r from-purple-600 to-blue-600 bg-clip-text text-transparent">
                                        Rp <?php echo number_format($room['harga'], 0, ',', '.'); ?>
                                    </p>
                                </div>


                                <!-- Room Facilities -->
                                <!-- Room Facilities -->
                                <div class="grid grid-cols-2 gap-x-4 gap-y-1 mb-6 flex-grow">
                                    <?php 
                                    $fasilitas_list = [];
                                    if (!empty($room['fasilitas'])) {
                                        $fasilitas_list = array_map('trim', explode(',', $room['fasilitas']));
                                    }
                                    
                                    if (!empty($fasilitas_list)): 
                                        foreach ($fasilitas_list as $fasilitas): 
                                            $icon = 'fa-check-circle';
                                            $f_lower = strtolower($fasilitas);
                                            if (strpos($f_lower, 'kasur') !== false || strpos($f_lower, 'bed') !== false) $icon = 'fa-bed';
                                            elseif (strpos($f_lower, 'kamar mandi') !== false || strpos($f_lower, 'wc') !== false) $icon = 'fa-bath';
                                            elseif (strpos($f_lower, 'ac') !== false || strpos($f_lower, 'pendingin') !== false) $icon = 'fa-snowflake';
                                            elseif (strpos($f_lower, 'wifi') !== false || strpos($f_lower, 'internet') !== false) $icon = 'fa-wifi';
                                            elseif (strpos($f_lower, 'tv') !== false) $icon = 'fa-tv';
                                            elseif (strpos($f_lower, 'lemari') !== false || strpos($f_lower, 'storage') !== false) $icon = 'fa-door-closed';
                                            elseif (strpos($f_lower, 'meja') !== false) $icon = 'fa-table';
                                            elseif (strpos($f_lower, 'kursi') !== false) $icon = 'fa-chair';
                                            elseif (strpos($f_lower, 'kipas') !== false || strpos($f_lower, 'fan') !== false) $icon = 'fa-fan';
                                            elseif (strpos($f_lower, 'jendela') !== false) $icon = 'fa-columns';
                                            elseif (strpos($f_lower, 'cermin') !== false) $icon = 'fa-magic';
                                            elseif (strpos($f_lower, 'bantal') !== false || strpos($f_lower, 'guling') !== false) $icon = 'fa-cloud';
                                            elseif (strpos($f_lower, 'dispenser') !== false) $icon = 'fa-tint';
                                    ?>
                                        <div class="flex items-start text-base font-medium text-gray-800">
                                            <i class="fas <?php echo $icon; ?> text-green-500 mr-3 mt-1 text-sm flex-shrink-0"></i>
                                            <span class="leading-tight"><?php echo htmlspecialchars($fasilitas); ?></span>
                                        </div>
                                    <?php 
                                        endforeach;
                                    else: 
                                        // Default facilities if none specified
                                        $default_facilities = ['Fasilitas lengkap', 'Lokasi strategis', 'Aman dan nyaman', 'Bebas banjir', 'Akses 24 Jam'];
                                        foreach ($default_facilities as $fasilitas): 
                                    ?>
                                        <div class="flex items-start text-base font-medium text-gray-800">
                                            <i class="fas fa-check-circle text-green-500 mr-2 mt-0.5 text-xs flex-shrink-0"></i>
                                            <span class="leading-tight"><?php echo htmlspecialchars($fasilitas); ?></span>
                                        </div>
                                    <?php 
                                        endforeach;
                                    endif; ?>
                                </div>

                                <!-- Room Rules -->
                                <?php if (!empty($room['peraturan_kamar'])): ?>
                                <div class="mt-4 pt-4 border-t border-gray-100">
                                    <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3 flex items-center gap-2">
                                        <i class="fas fa-file-signature text-purple-500"></i> Peraturan Kamar
                                    </h4>
                                    <ul class="space-y-1">
                                        <?php 
                                        $rules = array_filter(array_map('trim', explode("\n", $room['peraturan_kamar'])));
                                        if (empty($rules)) {
                                            $rules = [$room['peraturan_kamar']];
                                        }
                                        foreach ($rules as $rule):
                                        ?>
                                        <li class="text-base font-medium text-gray-700 flex items-start">
                                            <span class="leading-relaxed"><?php echo htmlspecialchars($rule); ?></span>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                <?php endif; ?>

                                <!-- Action Button -->
                                <div class="mt-auto pt-4 border-t border-gray-100">
                                    <button onclick="bookRoom(<?php echo $room['id_kamar']; ?>)" class="w-full bg-gradient-to-r from-purple-600 to-blue-600 text-white py-3.5 rounded-xl font-bold hover:from-purple-700 hover:to-blue-700 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 inline-flex items-center justify-center gap-2">
                                        <i class="fas fa-calendar-check"></i>
                                        <span>BOOK SEKARANG</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-rooms-state md:col-span-3">
                            <i class="fas fa-door-open no-rooms-icon"></i>
                            <h3 class="text-2xl font-bold text-gray-700 mb-2">Tidak Ada Kamar Tersedia</h3>
                            <p class="text-gray-500 text-lg mb-6">
                                Mohon maaf, saat ini belum ada kamar yang tersedia di kos ini.
                            </p>
                            <p class="text-gray-400 mb-6">
                                Silakan periksa kembali nanti atau lihat pilihan kos lainnya.
                            </p>
                            <a href="../dashboard/dashboarduser.php#pilihan-kos" class="inline-block bg-gradient-to-r from-purple-600 to-indigo-600 text-white px-8 py-3 rounded-xl font-bold hover:from-purple-700 hover:to-indigo-700 transition-all shadow-lg">
                                <i class="fas fa-search mr-2"></i>Lihat Kos Lain
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <?php endif; ?>

        </div>
    </main>

    <?php include '_user_profile_modal.php'; ?>

    <script>
            (function(){
                try {
                    const newPhoto = localStorage.getItem('newProfilePhoto');
                    if (newPhoto) {
                        const ts = Date.now();
                        const url = newPhoto.startsWith('http') ? newPhoto : `../uploads/profiles/${newPhoto}?t=${ts}`;
                        document.querySelectorAll('img').forEach(img => {
                            try { if (img.src && img.src.indexOf('/uploads/profiles/') !== -1) img.src = url; } catch(e){}
                        });
                        const headerNode = document.getElementById('headerUserPhoto');
                        if (headerNode) {
                            if (headerNode.tagName === 'IMG') {
                                headerNode.src = url;
                            } else {
                                const img = document.createElement('img');
                                img.id = 'headerUserPhoto';
                                img.className = 'w-9 h-9 rounded-full object-cover';
                                img.src = url;
                                img.alt = 'Foto Profil';
                                headerNode.parentNode.replaceChild(img, headerNode);
                            }
                        }
                        localStorage.removeItem('newProfilePhoto');
                    }
                } catch(e){}
            })();
        
        // Mobile Menu Script
        let mobileMenuActive = false;

        document.addEventListener('DOMContentLoaded', function() {
            const profileBtn = document.getElementById('drawerProfileBtn');
            const notifBtn = document.getElementById('drawerNotifBtn');
            const logoutBtn = document.getElementById('drawerLogoutBtn');
            
            if (profileBtn) {
                profileBtn.addEventListener('mousedown', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    closeMobileMenu();
                    setTimeout(() => {
                        if (typeof showProfileModal === 'function') {
                            showProfileModal();
                        }
                    }, 100);
                });
            }
            
            if (notifBtn) {
                notifBtn.addEventListener('mousedown', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    closeMobileMenu();
                    setTimeout(() => {
                        if (typeof showNotifications === 'function') {
                            showNotifications();
                        }
                    }, 100);
                });
            }
            
            if (logoutBtn) {
                logoutBtn.addEventListener('mousedown', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    closeMobileMenu();
                    setTimeout(() => {
                        if (typeof confirmLogout === 'function') {
                            confirmLogout(e);
                        }
                    }, 100);
                });
            }

            const drawer = document.getElementById('mobileMenuDrawer');
            const backdrop = document.getElementById('mobileMenuBackdrop');
            
            if (drawer && backdrop) {
                backdrop.addEventListener('click', function() {
                    if (mobileMenuActive) {
                        toggleMobileMenu();
                    }
                });
            }
        });

        function toggleMobileMenu() {
            const drawer = document.getElementById('mobileMenuDrawer');
            const panel = document.getElementById('mobileMenuPanel');
            const backdrop = document.getElementById('mobileMenuBackdrop');
            
            if (!drawer || !panel || !backdrop) return;

            if (mobileMenuActive) {
                panel.style.transform = 'translateX(100%)';
                backdrop.style.opacity = '0';
                drawer.style.pointerEvents = 'none';
                setTimeout(() => {
                    mobileMenuActive = false;
                }, 300);
            } else {
                mobileMenuActive = true;
                drawer.style.pointerEvents = 'auto';
                panel.style.transform = 'translateX(0)';
                backdrop.style.opacity = '1';
            }
        }

        function closeMobileMenu() {
            const drawer = document.getElementById('mobileMenuDrawer');
            const panel = document.getElementById('mobileMenuPanel');
            const backdrop = document.getElementById('mobileMenuBackdrop');
            
            if (!drawer || !panel || !backdrop) return;

            if (mobileMenuActive) {
                panel.style.transform = 'translateX(100%)';
                backdrop.style.opacity = '0';
                drawer.style.pointerEvents = 'none';
                setTimeout(() => {
                    mobileMenuActive = false;
                }, 300);
            }
        }

        function handleMobileMenuClick(event) {
            if (mobileMenuActive) {
                closeMobileMenu();
            }
        }

        function bookRoom(id_kamar) {
            Swal.fire({
                title: 'Konfirmasi Booking',
                text: 'Anda akan memesan kamar ini. Lanjutkan?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Book Sekarang',
                cancelButtonText: 'Batal',
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    // Kirim request AJAX ke backend untuk memproses booking
                    const formData = new FormData();
                    formData.append('id_kamar', id_kamar);

                    return fetch('process_booking.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                            // Jika server mengembalikan error (misal: kamar sudah dipesan)
                            return response.json().then(err => { throw new Error(err.message) });
                        }
                        return response.json();
                    })
                    .catch(error => {
                        Swal.showValidationMessage(`Request gagal: ${error}`);
                    });
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
                if (result.isConfirmed) {
                    // Jika booking berhasil, tampilkan notifikasi sukses dan redirect
                    Swal.fire({
                        title: '<strong class="text-2xl">🎉 Booking Berhasil!</strong>',
                        html: `
                            <div class="text-left space-y-4 p-4">
                                <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-lg">
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-info-circle text-blue-500 text-xl mt-1"></i>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm text-blue-800 font-medium">
                                                Pesanan Anda sedang menunggu konfirmasi dari pemilik kos
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-lg">
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-bell text-green-500 text-xl mt-1"></i>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm text-green-800 font-medium">
                                                Kami akan memberitahu Anda melalui notifikasi segera setelah pesanan dikonfirmasi
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-center pt-2">
                                    <p class="text-gray-600 text-sm">
                                        <i class="fas fa-clock mr-1"></i>
                                        Biasanya konfirmasi memakan waktu kurang dari 24 jam
                                    </p>
                                </div>
                            </div>
                        `,
                        icon: 'success',
                        confirmButtonText: '<i class="fas fa-history mr-2"></i>Lihat Riwayat Booking',
                        confirmButtonColor: '#9333ea',
                        customClass: {
                            popup: 'rounded-2xl',
                            confirmButton: 'rounded-xl font-semibold px-6 py-3',
                            htmlContainer: 'p-0'
                        },
                        showClass: {
                            popup: 'animate__animated animate__fadeInDown'
                        }
                    }).then(() => {
                        // Arahkan ke halaman riwayat booking
                        window.location.href = 'booking.php';
                    });
                }
            });
        }

        function confirmLogout(event) {
            event.preventDefault();
            Swal.fire({
                title: 'Konfirmasi Logout',
                text: 'Apakah Anda yakin ingin logout?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Logout',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Ambil URL dari elemen yang diklik dan arahkan ke sana
                    window.location.href = event.target.closest('a').href;
                }
            });
        }

        function showNotifications() {
            const paymentSuccessMessage = <?php echo isset($_SESSION['payment_success']) ? json_encode($_SESSION['payment_success']) : 'null'; ?>;
            const notifCount = <?php echo isset($_SESSION['notif_count']) ? (int)$_SESSION['notif_count'] : 0; ?>;

            if (paymentSuccessMessage) {
                Swal.fire({
                    title: 'Pembayaran Berhasil!',
                    text: paymentSuccessMessage,
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then(() => {
                    // Hapus pesan spesifik dan reset notifikasi umum
                    fetch('reset_notifications.php', { method: 'POST', body: new URLSearchParams({ specific: 'payment_success' }) })
                        .then(() => { location.reload(); }); // Reload untuk membersihkan state
                });
            } else if (notifCount > 0) {
                Swal.fire({
                    title: 'Notifikasi Baru',
                    text: 'Anda memiliki ' + notifCount + ' notifikasi belum dibaca.',
                    icon: 'info',
                    confirmButtonText: 'OK'
                }).then(() => {
                    fetch('reset_notifications.php', { method: 'POST' }).then(() => document.getElementById('notifBadge')?.remove());
                });
            } else {
                Swal.fire('Notifikasi', 'Tidak ada notifikasi baru.', 'info');
            }
        }
    </script>

    <script>
        // Pass PHP array to JS
        const categorizedPhotos = <?php echo json_encode($categorized_photos); ?>;

        function showImageModal(src) {
            Swal.fire({
                imageUrl: src,
                imageAlt: 'Foto Kos',
                width: 'auto',
                padding: 0,
                showConfirmButton: false,
                showCloseButton: true,
                background: 'transparent',
                backdrop: 'rgba(0,0,0,0.95)',
                customClass: {
                    popup: 'bg-transparent shadow-none',
                    image: 'rounded-xl max-h-[90vh] shadow-2xl border-4 border-white'
                }
            });
        }

        function openFullGallery() {
            // Helper to process image path
            const getImgSrc = (photo) => {
                return photo.startsWith('http') ? photo : `../uploads/kost/${photo}`;
            };

            let galleryHtml = '<div class="text-left max-h-[70vh] overflow-y-auto p-4 space-y-8 custom-scrollbar">';
            
            let hasContent = false;

            // Iterate through categories
            for (const [category, photos] of Object.entries(categorizedPhotos)) {
                if (photos.length > 0) {
                    hasContent = true;
                    
                    // Icon mapping
                    let icon = 'fa-image';
                    if (category === 'Bangunan') icon = 'fa-building';
                    else if (category === 'Kamar') icon = 'fa-bed';
                    else if (category === 'Kamar Mandi') icon = 'fa-bath';
                    else if (category === 'Fasilitas Bersama') icon = 'fa-users';

                    galleryHtml += `
                        <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
                            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center sticky top-0 bg-gray-50 py-2 z-10">
                                <i class="fas ${icon} mr-2 text-purple-600"></i>
                                Foto ${category}
                                <span class="ml-2 text-xs bg-purple-100 text-purple-600 px-2 py-0.5 rounded-full">${photos.length}</span>
                            </h3>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    `;
                    
                    photos.forEach(photo => {
                        const src = getImgSrc(photo);
                        galleryHtml += `
                            <div class="aspect-video rounded-lg overflow-hidden cursor-pointer hover:opacity-90 transition-all shadow-sm hover:shadow-md transform hover:scale-[1.02] group relative" 
                                 onclick="Swal.close(); setTimeout(() => showImageModal('${src}'), 300)">
                                <img src="${src}" class="w-full h-full object-cover" loading="lazy">
                                <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-10 transition-all"></div>
                            </div>
                        `;
                    });

                    galleryHtml += `
                            </div>
                        </div>
                    `;
                }
            }
            
            if (!hasContent) {
                galleryHtml += `
                    <div class="text-center py-12 text-gray-500">
                        <i class="fas fa-images text-4xl mb-3 opacity-30"></i>
                        <p>Belum ada foto tambahan</p>
                    </div>
                `;
            }

            galleryHtml += '</div>';

            Swal.fire({
                title: '<span class="text-2xl font-bold bg-gradient-to-r from-purple-600 to-indigo-600 bg-clip-text text-transparent">Galeri Foto</span>',
                html: galleryHtml,
                width: '900px',
                showCloseButton: true,
                showConfirmButton: false,
                focusConfirm: false,
                customClass: {
                    popup: 'rounded-2xl',
                    content: 'p-0'
                }
            });
        }
    </script>
</body>
</html>