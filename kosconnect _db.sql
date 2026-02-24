-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Waktu pembuatan: 17 Des 2025 pada 15.54
-- Versi server: 8.0.30
-- Versi PHP: 8.3.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `kosconnect`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `booking`
--

CREATE TABLE `booking` (
  `id_booking` int NOT NULL,
  `id_penyewa` int NOT NULL,
  `id_kamar` int NOT NULL,
  `tanggal_booking` date NOT NULL,
  `status` enum('pending','menunggu_pembayaran','dibayar','selesai','ditolak','batal') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `booking`
--

INSERT INTO `booking` (`id_booking`, `id_penyewa`, `id_kamar`, `tanggal_booking`, `status`) VALUES
(10, 145, 3, '2025-11-01', 'dibayar'),
(11, 145, 12, '2025-11-03', 'dibayar'),
(12, 146, 17, '2025-11-03', 'dibayar'),
(13, 146, 18, '2025-11-03', 'dibayar'),
(14, 145, 19, '2025-11-03', 'dibayar'),
(15, 145, 20, '2025-11-06', 'ditolak'),
(16, 145, 20, '2025-11-10', 'ditolak'),
(17, 145, 20, '2025-11-10', 'ditolak'),
(18, 145, 20, '2025-11-10', 'ditolak'),
(19, 145, 20, '2025-11-10', 'ditolak'),
(20, 145, 20, '2025-11-10', 'dibayar'),
(21, 145, 22, '2025-11-10', 'dibayar'),
(22, 145, 23, '2025-11-10', 'pending'),
(23, 145, 21, '2025-11-13', 'dibayar'),
(24, 145, 24, '2025-11-13', 'dibayar'),
(25, 148, 1, '2025-11-15', 'dibayar'),
(26, 149, 3, '2025-11-17', 'menunggu_pembayaran'),
(27, 145, 18, '2025-11-19', 'dibayar'),
(28, 145, 1, '2025-11-27', 'dibayar'),
(29, 145, 24, '2025-11-27', 'dibayar'),
(30, 145, 17, '2025-11-27', 'dibayar'),
(31, 145, 20, '2025-12-10', 'dibayar'),
(32, 145, 8, '2025-12-12', 'dibayar'),
(33, 145, 7, '2025-12-14', 'dibayar'),
(34, 145, 19, '2025-12-15', 'dibayar'),
(35, 145, 25, '2025-12-16', 'dibayar'),
(36, 145, 5, '2025-12-17', 'dibayar'),
(37, 145, 9, '2025-12-17', 'dibayar'),
(38, 145, 10, '2025-12-17', 'dibayar'),
(39, 145, 22, '2025-12-17', 'dibayar');

-- --------------------------------------------------------

--
-- Struktur dari tabel `complaint`
--

CREATE TABLE `complaint` (
  `id_complaint` int NOT NULL,
  `id_penyewa` int NOT NULL,
  `id_kost` int NOT NULL,
  `pesan` text NOT NULL,
  `status` enum('baru','diproses','selesai') DEFAULT 'baru',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `complaint`
--

INSERT INTO `complaint` (`id_complaint`, `id_penyewa`, `id_kost`, `pesan`, `status`, `created_at`) VALUES
(1, 145, 3, 'Ac sudah tidak dingin, mohon perbaiki', 'baru', '2025-11-03 07:15:57'),
(2, 145, 4, 'Keran Air sudah rusak, mohon perbaiki', 'baru', '2025-11-03 16:10:00');

-- --------------------------------------------------------

--
-- Struktur dari tabel `feedback`
--

CREATE TABLE `feedback` (
  `id_feedback` int NOT NULL,
  `id_penyewa` int NOT NULL,
  `pesan` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `feedback`
--

INSERT INTO `feedback` (`id_feedback`, `id_penyewa`, `pesan`, `created_at`) VALUES
(1, 145, 'Saran saya tambahkan Fitur notifikasi di dashboard penyewa', '2025-11-03 07:32:08'),
(2, 146, 'Saran saya tambahkan beberapa fitur notifikasi agar lebih baik lagi', '2025-11-03 07:51:45'),
(3, 145, 'jbyhvjvkhvjkvhjkvjkj', '2025-11-06 01:40:13'),
(4, 145, 'tambahkan fitur notifikasi', '2025-11-27 03:08:15');

-- --------------------------------------------------------

--
-- Struktur dari tabel `kamar`
--

CREATE TABLE `kamar` (
  `id_kamar` int NOT NULL,
  `id_kost` int NOT NULL,
  `nama_kamar` varchar(100) NOT NULL,
  `harga` decimal(12,2) NOT NULL,
  `status` enum('tersedia','terisi','dipesan','perbaikan') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'tersedia',
  `foto` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `kamar`
--

INSERT INTO `kamar` (`id_kamar`, `id_kost`, `nama_kamar`, `harga`, `status`, `created_at`) VALUES
(1, 3, '101', 700000.00, 'dipesan', '2025-10-16 06:50:07'),
(3, 3, '102', 700000.00, 'dipesan', '2025-10-16 06:53:47'),
(5, 3, '103', 700000.00, 'dipesan', '2025-10-16 06:56:21'),
(7, 3, '104', 700000.00, 'dipesan', '2025-10-16 07:04:51'),
(8, 3, '105', 700000.00, 'dipesan', '2025-10-16 07:05:03'),
(9, 3, '106 - Kamar Deluxe', 1000000.00, 'dipesan', '2025-10-16 07:05:49'),
(10, 3, '107 - Kamar Deluxe', 1000000.00, 'dipesan', '2025-10-16 07:06:25'),
(11, 3, '108 - Kamar Deluxe', 1000000.00, 'tersedia', '2025-10-16 07:06:56'),
(12, 3, '109 - Kamar VIP', 1500000.00, 'tersedia', '2025-10-16 07:07:21'),
(13, 3, '110 - Kamar VIP', 1500000.00, 'tersedia', '2025-10-16 07:07:41'),
(17, 4, 'Kamar Biasa', 700000.00, 'dipesan', '2025-11-03 07:45:17'),
(18, 4, 'Kamar Reguler', 750000.00, 'dipesan', '2025-11-03 07:45:35'),
(19, 4, 'Kamar VIP', 1500000.00, 'dipesan', '2025-11-03 15:54:32'),
(20, 4, 'Kamar VIP', 1500000.00, 'dipesan', '2025-11-06 00:25:20'),
(21, 3, '111', 700000.00, 'tersedia', '2025-11-10 02:30:49'),
(22, 3, '112', 700000.00, 'dipesan', '2025-11-10 02:31:07'),
(23, 3, '113', 700000.00, 'tersedia', '2025-11-10 02:31:22'),
(24, 4, 'Superior Double', 1500000.00, 'dipesan', '2025-11-13 02:19:53'),
(25, 5, 'Kamar 01', 700000.00, 'dipesan', '2025-12-15 03:37:59');

-- --------------------------------------------------------

--
-- Struktur dari tabel `kost`
--

CREATE TABLE `kost` (
  `id_kost` int NOT NULL,
  `id_pemilik` int NOT NULL,
  `nama_kost` varchar(100) NOT NULL,
  `alamat` text NOT NULL,
  `deskripsi` text,
  `fasilitas` text,
  `status_kos` enum('tersedia','tidak_tersedia') DEFAULT 'tersedia',
  `gambar` varchar(255) DEFAULT NULL,
  `harga` decimal(12,2) NOT NULL,
  `tipe_kost` enum('Putra','Putri','Campuran') COLLATE utf8mb4_general_ci DEFAULT 'Campuran',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `kost`
--

INSERT INTO `kost` (`id_kost`, `id_pemilik`, `nama_kost`, `alamat`, `deskripsi`, `fasilitas`, `status_kos`, `gambar`, `harga`, `tipe_kost`, `created_at`, `updated_at`) VALUES
(3, 141, 'Kost Pondok Pelangi Tipe A1 Tamalanrea Makassar', 'Tamalanrea', 'Kost Lengkap dengan kamar yang estetik dan fitur yang lengkap', 'AC, Wifi, Kamar mandi dalam, Westafel, Dll', 'tersedia', 'https://res.cloudinary.com/dxhbbcpvz/image/upload/v1765909317/kosconnect/kosts/kost_main_6941a3453cf81.jpg', 550000.00, 'Campuran', '2025-10-16 06:41:21', '2025-12-16 18:21:59'),
(4, 141, 'Kost Pondok Tirta Tamalanrea Makassar', 'Tamalanrea', 'Booking Langsung\r\nKos ini bisa di-booking dan dibayar di situs dan aplikasi KosConnect, tanpa harus ketemuan dengan pemilik.', 'AC, Kasur, Meja, Lemari / Storage  Ventilasi, Jendela, Cermin, Bantal, TV Kabel, Kursi', 'tersedia', 'https://res.cloudinary.com/dxhbbcpvz/image/upload/v1765981659/kosconnect/kosts/kost_main_6942bdd895e81.jpg', 1300000.00, 'Campuran', '2025-10-16 06:42:06', '2025-12-17 14:27:43'),
(5, 141, 'Kost Halia Nur Tipe A Biringkanaya Makassar', 'Biring Kanaya', 'Kos ini bisa di-booking dan dibayar di situs dan aplikasi KosConnect, tanpa harus ketemuan dengan pemilik.', 'AC, Kasur, Meja, Lemari, Jendela, Guling, Cermin, Bantal, Wastafel, Kursi', 'tersedia', 'https://res.cloudinary.com/dxhbbcpvz/image/upload/v1765982341/kosconnect/kosts/kost_main_6942c08681af4.jpg', 1500000.00, 'Campuran', '2025-12-15 03:37:12', '2025-12-17 14:39:04');

-- --------------------------------------------------------

--
-- Struktur dari tabel `kost_photos`
--

CREATE TABLE `kost_photos` (
  `id_photo` int NOT NULL,
  `id_kost` int NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `category` varchar(50) DEFAULT 'lainnya'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `kost_photos`
--

INSERT INTO `kost_photos` (`id_photo`, `id_kost`, `file_name`, `created_at`, `category`) VALUES
(6, 3, 'https://res.cloudinary.com/dxhbbcpvz/image/upload/v1765909319/kosconnect/kosts/kost_Bangunan_6941a3478a870.jpg', '2025-12-16 18:22:01', 'Bangunan'),
(7, 3, 'https://res.cloudinary.com/dxhbbcpvz/image/upload/v1765909321/kosconnect/kosts/kost_Bangunan_6941a349ae047.jpg', '2025-12-16 18:22:04', 'Bangunan'),
(8, 3, 'https://res.cloudinary.com/dxhbbcpvz/image/upload/v1765909324/kosconnect/kosts/kost_Bangunan_6941a34c8fde9.jpg', '2025-12-16 18:22:06', 'Bangunan'),
(9, 3, 'https://res.cloudinary.com/dxhbbcpvz/image/upload/v1765909326/kosconnect/kosts/kost_Kamar_6941a34e7e2b1.jpg', '2025-12-16 18:22:08', 'Kamar'),
(10, 3, 'https://res.cloudinary.com/dxhbbcpvz/image/upload/v1765909327/kosconnect/kosts/kost_Kamar_6941a35033b33.jpg', '2025-12-16 18:22:09', 'Kamar'),
(11, 3, 'https://res.cloudinary.com/dxhbbcpvz/image/upload/v1765909329/kosconnect/kosts/kost_Kamar%20Mandi_6941a351d7a7e.jpg', '2025-12-16 18:22:11', 'Kamar Mandi'),
(12, 3, 'https://res.cloudinary.com/dxhbbcpvz/image/upload/v1765909331/kosconnect/kosts/kost_Fasilitas%20Bersama_6941a353a565e.jpg', '2025-12-16 18:22:14', 'Fasilitas Bersama'),
(13, 3, 'https://res.cloudinary.com/dxhbbcpvz/image/upload/v1765909334/kosconnect/kosts/kost_Fasilitas%20Bersama_6941a35625389.jpg', '2025-12-16 18:22:16', 'Fasilitas Bersama'),
(14, 3, 'https://res.cloudinary.com/dxhbbcpvz/image/upload/v1765909336/kosconnect/kosts/kost_Lainnya_6941a35843778.jpg', '2025-12-16 18:22:18', 'Lainnya'),
(15, 4, 'https://res.cloudinary.com/dxhbbcpvz/image/upload/v1765981662/kosconnect/kosts/kost_Bangunan_6942bddf465da.jpg', '2025-12-17 14:27:45', 'Bangunan'),
(16, 4, 'https://res.cloudinary.com/dxhbbcpvz/image/upload/v1765981664/kosconnect/kosts/kost_Bangunan_6942bde199db5.jpg', '2025-12-17 14:27:47', 'Bangunan'),
(17, 4, 'https://res.cloudinary.com/dxhbbcpvz/image/upload/v1765981666/kosconnect/kosts/kost_Kamar_6942bde35dd4a.jpg', '2025-12-17 14:27:49', 'Kamar'),
(18, 4, 'https://res.cloudinary.com/dxhbbcpvz/image/upload/v1765981667/kosconnect/kosts/kost_Kamar%20Mandi_6942bde509f76.jpg', '2025-12-17 14:27:50', 'Kamar Mandi'),
(19, 4, 'https://res.cloudinary.com/dxhbbcpvz/image/upload/v1765981669/kosconnect/kosts/kost_Lainnya_6942bde6b37ee.jpg', '2025-12-17 14:27:52', 'Lainnya'),
(20, 5, 'https://res.cloudinary.com/dxhbbcpvz/image/upload/v1765982343/kosconnect/kosts/kost_Bangunan_6942c088ae033.jpg', '2025-12-17 14:39:06', 'Bangunan'),
(21, 5, 'https://res.cloudinary.com/dxhbbcpvz/image/upload/v1765982345/kosconnect/kosts/kost_Bangunan_6942c08ab064c.jpg', '2025-12-17 14:39:08', 'Bangunan'),
(22, 5, 'https://res.cloudinary.com/dxhbbcpvz/image/upload/v1765982347/kosconnect/kosts/kost_Kamar_6942c08cb0cd1.jpg', '2025-12-17 14:39:10', 'Kamar'),
(23, 5, 'https://res.cloudinary.com/dxhbbcpvz/image/upload/v1765982349/kosconnect/kosts/kost_Kamar%20Mandi_6942c08e7cc84.jpg', '2025-12-17 14:39:12', 'Kamar Mandi'),
(24, 5, 'https://res.cloudinary.com/dxhbbcpvz/image/upload/v1765982351/kosconnect/kosts/kost_Fasilitas%20Bersama_6942c0908476d.jpg', '2025-12-17 14:39:14', 'Fasilitas Bersama'),
(25, 5, 'https://res.cloudinary.com/dxhbbcpvz/image/upload/v1765982353/kosconnect/kosts/kost_Lainnya_6942c092c7a0e.jpg', '2025-12-17 14:39:17', 'Lainnya');

-- --------------------------------------------------------

--
-- Struktur dari tabel `midtrans_transactions`
--

CREATE TABLE `midtrans_transactions` (
  `id_transaction` int NOT NULL,
  `id_booking` int NOT NULL,
  `order_id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `transaction_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gross_amount` decimal(15,2) NOT NULL,
  `payment_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'bank_transfer, gopay, qris, credit_card, etc',
  `transaction_status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'pending' COMMENT 'pending, settlement, expire, cancel, deny, refund',
  `transaction_time` datetime DEFAULT NULL,
  `settlement_time` datetime DEFAULT NULL,
  `snap_token` text COLLATE utf8mb4_unicode_ci,
  `snap_redirect_url` text COLLATE utf8mb4_unicode_ci,
  `va_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Virtual Account number jika menggunakan VA',
  `bank` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Bank yang digunakan untuk VA',
  `fraud_status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'accept, challenge, deny',
  `status_code` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_message` text COLLATE utf8mb4_unicode_ci,
  `metadata` text COLLATE utf8mb4_unicode_ci COMMENT 'JSON data tambahan dari Midtrans',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `notifications`
--

CREATE TABLE `notifications` (
  `id_notification` int NOT NULL,
  `id_user` int NOT NULL,
  `pesan` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `link` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `notifications`
--

INSERT INTO `notifications` (`id_notification`, `id_user`, `pesan`, `link`, `is_read`, `created_at`) VALUES
(51, 141, 'Pesanan baru untuk kamar \'Kamar Biasa\' di \'Capital Kost\' telah masuk. Mohon segera dikonfirmasi.', '/KosConnect/dashboard/dashboardpemilik.php?module=owner_manage_booking&status=pending', 1, '2025-11-27 05:44:27'),
(52, 145, 'Booking Anda untuk kamar \'Kamar Biasa\' di \'Capital Kost\' berhasil dibuat. Silakan tunggu konfirmasi dari pemilik kos.', '/KosConnect/user/user_dashboard.php', 1, '2025-11-27 05:44:27'),
(53, 141, 'Pembayaran dari \'Thabrani\' untuk kamar \'111\' telah diterima. Mohon segera verifikasi.', '/KosConnect/dashboard/dashboardpemilik.php?module=owner_manage_payments', 1, '2025-12-06 08:54:51'),
(54, 141, 'Pembayaran dari \'Thabrani\' untuk kamar \'Superior Double\' telah diterima. Mohon segera verifikasi.', '/KosConnect/dashboard/dashboardpemilik.php?module=owner_manage_payments', 1, '2025-12-10 00:06:42'),
(55, 145, 'Pembayaran Anda untuk kamar \'Superior Double\' telah dikonfirmasi. Pesanan Anda sekarang aktif.', '/KosConnect/user/user_dashboard.php', 1, '2025-12-10 00:07:42'),
(56, 141, 'Pesanan baru untuk kamar \'Kamar VIP\' di \'Capital Kost\' telah masuk. Mohon segera dikonfirmasi.', '/KosConnect/dashboard/dashboardpemilik.php?module=owner_manage_booking&status=pending', 1, '2025-12-10 00:37:25'),
(57, 145, 'Booking Anda untuk kamar \'Kamar VIP\' di \'Capital Kost\' berhasil dibuat. Silakan tunggu konfirmasi dari pemilik kos.', '/KosConnect/user/user_dashboard.php', 1, '2025-12-10 00:37:25'),
(58, 141, 'Pembayaran dari \'Thabrani\' untuk kamar \'Kamar Reguler\' telah diterima. Mohon segera verifikasi.', '/KosConnect/dashboard/dashboardpemilik.php?module=owner_manage_payments', 1, '2025-12-10 00:37:48'),
(59, 145, 'Pembayaran Anda untuk kamar \'Kamar Reguler\' telah dikonfirmasi. Pesanan Anda sekarang aktif.', '/KosConnect/user/user_dashboard.php', 1, '2025-12-10 00:38:11'),
(60, 145, 'Pembayaran Anda untuk kamar \'111\' telah dikonfirmasi. Pesanan Anda sekarang aktif.', '/KosConnect/user/user_dashboard.php', 1, '2025-12-10 00:38:15'),
(61, 145, 'Pesanan Anda untuk kamar \'Kamar VIP\' di \'Capital Kost\' telah dikonfirmasi. Silakan lanjutkan ke pembayaran.', '/KosConnect/user/user_dashboard.php', 1, '2025-12-10 00:39:16'),
(62, 141, 'Pembayaran dari \'Thabrani\' untuk kamar \'Kamar VIP\' telah diterima. Mohon segera verifikasi.', '/KosConnect/dashboard/dashboardpemilik.php?module=owner_manage_payments', 1, '2025-12-10 00:39:58'),
(63, 145, 'Pembayaran Anda untuk kamar \'Kamar VIP\' telah dikonfirmasi. Pesanan Anda sekarang aktif.', '/KosConnect/user/user_dashboard.php', 1, '2025-12-10 00:40:15'),
(64, 141, 'Pembayaran dari \'Thabrani\' untuk kamar \'112\' telah diterima. Mohon segera verifikasi.', '/KosConnect/dashboard/dashboardpemilik.php?module=owner_manage_payments', 1, '2025-12-11 02:20:07'),
(65, 141, 'Pesanan baru untuk kamar \'105\' di \'Royal Kost\' telah masuk. Mohon segera dikonfirmasi.', '/KosConnect/dashboard/dashboardpemilik.php?module=owner_manage_booking&status=pending', 1, '2025-12-12 08:03:12'),
(66, 145, 'Booking Anda untuk kamar \'105\' di \'Royal Kost\' berhasil dibuat. Silakan tunggu konfirmasi dari pemilik kos.', '/KosConnect/user/user_dashboard.php', 1, '2025-12-12 08:03:12'),
(67, 145, 'Pesanan Anda untuk kamar \'105\' di \'Royal Kost\' telah dikonfirmasi. Silakan lanjutkan ke pembayaran.', '/KosConnect/user/user_dashboard.php', 1, '2025-12-12 08:03:50'),
(68, 141, 'Pembayaran dari \'Thabrani\' untuk kamar \'105\' telah diterima. Mohon segera verifikasi.', '/KosConnect/dashboard/dashboardpemilik.php?module=owner_manage_payments', 1, '2025-12-13 16:23:01'),
(69, 145, 'Pembayaran Anda untuk kamar \'105\' telah dikonfirmasi. Pesanan Anda sekarang aktif.', '/KosConnect/user/user_dashboard.php', 1, '2025-12-13 16:23:26'),
(70, 141, 'Pesanan baru untuk kamar \'104\' di \'Royal Kost\' telah masuk. Mohon segera dikonfirmasi.', '/KosConnect/dashboard/dashboardpemilik.php?module=owner_manage_booking&status=pending', 1, '2025-12-14 07:55:35'),
(71, 145, 'Booking Anda untuk kamar \'104\' di \'Royal Kost\' berhasil dibuat. Silakan tunggu konfirmasi dari pemilik kos.', '/KosConnect/user/user_dashboard.php', 1, '2025-12-14 07:55:35'),
(72, 145, 'Pesanan Anda untuk kamar \'104\' di \'Royal Kost\' telah dikonfirmasi. Silakan lanjutkan ke pembayaran.', '/KosConnect/user/user_dashboard.php', 1, '2025-12-14 07:56:07'),
(73, 145, 'Pesanan Anda untuk kamar \'Kamar Biasa\' di \'Capital Kost\' telah dikonfirmasi. Silakan lanjutkan ke pembayaran.', '/KosConnect/user/user_dashboard.php', 1, '2025-12-14 17:27:17'),
(74, 149, 'Pesanan Anda untuk kamar \'102\' di \'Royal Kost\' telah dikonfirmasi. Silakan lanjutkan ke pembayaran.', '/KosConnect/user/user_dashboard.php', 0, '2025-12-15 01:46:57'),
(75, 141, 'Pesanan baru untuk kamar \'Kamar VIP\' di \'Capital Kost\' telah masuk. Mohon segera dikonfirmasi.', '/KosConnect/dashboard/dashboardpemilik.php?module=owner_manage_booking&status=pending', 1, '2025-12-15 01:47:39'),
(76, 145, 'Booking Anda untuk kamar \'Kamar VIP\' di \'Capital Kost\' berhasil dibuat. Silakan tunggu konfirmasi dari pemilik kos.', '/KosConnect/user/user_dashboard.php', 1, '2025-12-15 01:47:39'),
(77, 145, 'Pesanan Anda untuk kamar \'Kamar VIP\' di \'Capital Kost\' telah dikonfirmasi. Silakan lanjutkan ke pembayaran.', '/KosConnect/user/user_dashboard.php', 1, '2025-12-15 01:48:07'),
(78, 145, 'Mohon maaf, pembayaran Anda untuk kamar \'112\' ditolak. Silakan unggah ulang bukti pembayaran yang valid.', '/KosConnect/user/user_dashboard.php', 1, '2025-12-15 08:28:40'),
(79, 145, 'Pesanan Anda untuk kamar \'Superior Double\' di \'Capital Kost\' telah dikonfirmasi. Silakan lanjutkan ke pembayaran.', '/KosConnect/user/user_dashboard.php', 1, '2025-12-16 04:28:37'),
(80, 101, 'Test Notification 05:01:10 - Admin Panel Check', '/KosConnect/dashboard/dashboardadmin.php?module=admin_dashboard_summary', 1, '2025-12-16 05:01:10'),
(81, 141, 'Pesanan baru untuk kamar \'Kamar 01\' di \'Kost Halia Nur Tipe A Biringkanaya Makassar\' telah masuk. Mohon segera dikonfirmasi.', '/KosConnect/dashboard/dashboardpemilik.php?module=owner_manage_booking&status=pending', 1, '2025-12-16 05:33:25'),
(82, 145, 'Booking Anda untuk kamar \'Kamar 01\' di \'Kost Halia Nur Tipe A Biringkanaya Makassar\' berhasil dibuat. Silakan tunggu konfirmasi dari pemilik kos.', '/KosConnect/user/user_dashboard.php', 1, '2025-12-16 05:33:25'),
(83, 101, 'Booking baru: Kamar 01 di Kost Halia Nur Tipe A Biringkanaya Makassar oleh user ID 145.', '/KosConnect/dashboard/dashboardadmin.php?module=admin_manage_transactions', 1, '2025-12-16 05:33:25'),
(84, 145, 'Pesanan Anda untuk kamar \'Kamar 01\' di \'Kost Halia Nur Tipe A Biringkanaya Makassar\' telah dikonfirmasi. Silakan lanjutkan ke pembayaran.', '/KosConnect/user/user_dashboard.php', 1, '2025-12-16 05:38:43'),
(85, 141, 'Pembayaran dari \'Wilhelmus\' untuk kamar \'112\' telah diterima. Mohon segera verifikasi.', '/KosConnect/dashboard/dashboardpemilik.php?module=owner_manage_payments', 1, '2025-12-16 05:42:23'),
(86, 101, 'Pembayaran Manual Baru: Wilhelmus - 112 (Rp 700.000). Perlu verifikasi.', '/KosConnect/dashboard/dashboardadmin.php?module=admin_manage_transactions', 1, '2025-12-16 05:42:23'),
(87, 141, 'Pesanan baru untuk kamar \'103\' di \'Kost Pondok Pelangi Tipe A1 Tamalanrea Makassar\' telah masuk. Mohon segera dikonfirmasi.', '/KosConnect/dashboard/dashboardpemilik.php?module=owner_manage_booking&status=pending', 1, '2025-12-17 15:11:12'),
(88, 145, 'Booking Anda untuk kamar \'103\' di \'Kost Pondok Pelangi Tipe A1 Tamalanrea Makassar\' berhasil dibuat. Silakan tunggu konfirmasi dari pemilik kos.', '/KosConnect/user/user_dashboard.php', 1, '2025-12-17 15:11:12'),
(89, 101, 'Booking baru: 103 di Kost Pondok Pelangi Tipe A1 Tamalanrea Makassar oleh user ID 145.', '/KosConnect/dashboard/dashboardadmin.php?module=admin_manage_transactions', 1, '2025-12-17 15:11:12'),
(90, 145, 'Pesanan Anda untuk kamar \'103\' di \'Kost Pondok Pelangi Tipe A1 Tamalanrea Makassar\' telah dikonfirmasi. Silakan lanjutkan ke pembayaran.', '/KosConnect/user/user_dashboard.php', 1, '2025-12-17 15:11:34'),
(91, 141, 'Pesanan baru untuk kamar \'106 - Kamar Deluxe\' di \'Kost Pondok Pelangi Tipe A1 Tamalanrea Makassar\' telah masuk. Mohon segera dikonfirmasi.', '/KosConnect/dashboard/dashboardpemilik.php?module=owner_manage_booking&status=pending', 0, '2025-12-17 15:19:54'),
(92, 145, 'Booking Anda untuk kamar \'106 - Kamar Deluxe\' di \'Kost Pondok Pelangi Tipe A1 Tamalanrea Makassar\' berhasil dibuat. Silakan tunggu konfirmasi dari pemilik kos.', '/KosConnect/user/user_dashboard.php', 1, '2025-12-17 15:19:54'),
(93, 101, 'Booking baru: 106 - Kamar Deluxe di Kost Pondok Pelangi Tipe A1 Tamalanrea Makassar oleh user ID 145.', '/KosConnect/dashboard/dashboardadmin.php?module=admin_manage_transactions', 1, '2025-12-17 15:19:54'),
(94, 145, 'Pesanan Anda untuk kamar \'106 - Kamar Deluxe\' di \'Kost Pondok Pelangi Tipe A1 Tamalanrea Makassar\' telah dikonfirmasi. Silakan lanjutkan ke pembayaran.', '/KosConnect/user/user_dashboard.php', 1, '2025-12-17 15:20:11'),
(95, 141, 'Pesanan baru untuk kamar \'107 - Kamar Deluxe\' di \'Kost Pondok Pelangi Tipe A1 Tamalanrea Makassar\' telah masuk. Mohon segera dikonfirmasi.', '/KosConnect/dashboard/dashboardpemilik.php?module=owner_manage_booking&status=pending', 0, '2025-12-17 15:22:13'),
(96, 145, 'Booking Anda untuk kamar \'107 - Kamar Deluxe\' di \'Kost Pondok Pelangi Tipe A1 Tamalanrea Makassar\' berhasil dibuat. Silakan tunggu konfirmasi dari pemilik kos.', '/KosConnect/user/user_dashboard.php', 1, '2025-12-17 15:22:13'),
(97, 101, 'Booking baru: 107 - Kamar Deluxe di Kost Pondok Pelangi Tipe A1 Tamalanrea Makassar oleh user ID 145.', '/KosConnect/dashboard/dashboardadmin.php?module=admin_manage_transactions', 1, '2025-12-17 15:22:13'),
(98, 145, 'Pesanan Anda untuk kamar \'107 - Kamar Deluxe\' di \'Kost Pondok Pelangi Tipe A1 Tamalanrea Makassar\' telah dikonfirmasi. Silakan lanjutkan ke pembayaran.', '/KosConnect/user/user_dashboard.php', 1, '2025-12-17 15:22:27'),
(99, 141, 'Pembayaran dari \'Wilhelmus\' untuk kamar \'107 - Kamar Deluxe\' telah diterima. Mohon segera verifikasi.', '/KosConnect/dashboard/dashboardpemilik.php?module=owner_manage_payments', 0, '2025-12-17 15:23:37'),
(100, 101, 'Pembayaran Manual Baru: Wilhelmus - 107 - Kamar Deluxe (Rp 1.000.000). Perlu verifikasi.', '/KosConnect/dashboard/dashboardadmin.php?module=admin_manage_transactions', 1, '2025-12-17 15:23:37'),
(101, 145, 'Pembayaran Anda untuk kamar \'107 - Kamar Deluxe\' telah dikonfirmasi. Pesanan Anda sekarang aktif.', '/KosConnect/user/user_dashboard.php', 1, '2025-12-17 15:24:18'),
(102, 141, 'Pesanan baru untuk kamar \'112\' di \'Kost Pondok Pelangi Tipe A1 Tamalanrea Makassar\' telah masuk. Mohon segera dikonfirmasi.', '/KosConnect/dashboard/dashboardpemilik.php?module=owner_manage_booking&status=pending', 0, '2025-12-17 15:39:28'),
(103, 145, 'Booking Anda untuk kamar \'112\' di \'Kost Pondok Pelangi Tipe A1 Tamalanrea Makassar\' berhasil dibuat. Silakan tunggu konfirmasi dari pemilik kos.', '/KosConnect/user/user_dashboard.php', 0, '2025-12-17 15:39:28'),
(104, 101, 'Booking baru: 112 di Kost Pondok Pelangi Tipe A1 Tamalanrea Makassar oleh user ID 145.', '/KosConnect/dashboard/dashboardadmin.php?module=admin_manage_transactions', 0, '2025-12-17 15:39:28'),
(105, 145, 'Pesanan Anda untuk kamar \'112\' di \'Kost Pondok Pelangi Tipe A1 Tamalanrea Makassar\' telah dikonfirmasi. Silakan lanjutkan ke pembayaran.', '/KosConnect/user/user_dashboard.php', 0, '2025-12-17 15:39:44'),
(106, 141, 'Pembayaran dari \'Wilhelmus\' untuk kamar \'112\' telah diterima. Mohon segera verifikasi.', '/KosConnect/dashboard/dashboardpemilik.php?module=owner_manage_payments', 0, '2025-12-17 15:40:36'),
(107, 101, 'Pembayaran Manual Baru: Wilhelmus - 112 (Rp 700.000). Perlu verifikasi.', '/KosConnect/dashboard/dashboardadmin.php?module=admin_manage_transactions', 0, '2025-12-17 15:40:36');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pembayaran`
--

CREATE TABLE `pembayaran` (
  `id_payment` int NOT NULL,
  `id_booking` int NOT NULL,
  `jumlah` decimal(12,2) NOT NULL,
  `metode_pembayaran` varchar(50) NOT NULL,
  `payment_gateway` enum('manual','midtrans','xendit') DEFAULT 'manual' COMMENT 'Metode pembayaran: manual upload, Midtrans, atau Xendit',
  `id_midtrans_transaction` int DEFAULT NULL COMMENT 'Reference ke tabel midtrans_transactions',
  `id_xendit_invoice` int DEFAULT NULL COMMENT 'Reference ke tabel xendit_invoices',
  `status_pembayaran` enum('menunggu','berhasil','gagal') DEFAULT 'menunggu',
  `bukti_pembayaran` varchar(255) DEFAULT NULL,
  `tanggal_pembayaran` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pembayaran`
--

INSERT INTO `pembayaran` (`id_payment`, `id_booking`, `jumlah`, `metode_pembayaran`, `payment_gateway`, `id_midtrans_transaction`, `id_xendit_invoice`, `status_pembayaran`, `bukti_pembayaran`, `tanggal_pembayaran`) VALUES
(5, 10, 700000.00, 'Mandiri', 'manual', NULL, NULL, 'berhasil', 'payment_69081ed8248c11.26027148.jpg', '2025-11-03 03:17:44'),
(6, 11, 1500000.00, 'Mandiri', 'manual', NULL, NULL, 'gagal', 'payment_690828fe913b10.57956446.jpg', '2025-11-03 04:01:02'),
(7, 12, 700000.00, 'Mandiri', 'manual', NULL, NULL, 'berhasil', 'payment_69085e549c31e2.19803091.png', '2025-11-03 07:48:36'),
(8, 13, 750000.00, 'Mandiri', 'manual', NULL, NULL, 'berhasil', 'payment_69086482bf2c61.67600189.png', '2025-11-03 08:14:58'),
(9, 14, 1500000.00, 'Mandiri', 'manual', NULL, NULL, 'berhasil', 'payment_6908d1897d16b3.21905250.png', '2025-11-03 16:00:09'),
(10, 11, 1500000.00, 'BCA', 'manual', NULL, NULL, 'berhasil', 'payment_690be68a7b0e51.64930073.png', '2025-11-06 00:06:34'),
(11, 20, 1500000.00, 'Mandiri', 'manual', NULL, NULL, 'berhasil', 'payment_69114eaee8fb10.81093482.jpg', '2025-11-10 02:32:14'),
(12, 21, 700000.00, 'Lainnya', 'manual', NULL, NULL, 'gagal', 'payment_6911626be13a12.81972005.jpg', '2025-11-10 03:56:27'),
(13, 23, 700000.00, 'BNI', 'manual', NULL, NULL, 'gagal', 'payment_6916b157ee2085.39395384.png', '2025-11-14 04:34:31'),
(14, 25, 700000.00, 'BRI', 'manual', NULL, NULL, 'berhasil', 'payment_6917d299554689.04028881.png', '2025-11-15 01:08:41'),
(15, 28, 700000.00, 'Mandiri', 'manual', NULL, NULL, 'berhasil', 'payment_6927c8808646f4.41480681.png', '2025-11-27 03:41:52'),
(16, 29, 1500000.00, 'Mandiri', 'manual', NULL, NULL, 'gagal', 'payment_6927e1be77cce4.04862932.png', '2025-11-27 05:29:34'),
(17, 23, 700000.00, 'QRIS', 'manual', NULL, NULL, 'berhasil', 'payment_6933ef5bad36b9.73124088.png', '2025-12-06 08:54:51'),
(18, 29, 1500000.00, 'Transfer_Bank', 'manual', NULL, NULL, 'berhasil', 'payment_6938b992367fc3.15386818.jpg', '2025-12-10 00:06:42'),
(19, 27, 750000.00, 'OVO', 'manual', NULL, NULL, 'berhasil', 'payment_6938c0dcae0299.71699714.jpg', '2025-12-10 00:37:48'),
(20, 31, 1500000.00, 'GoPay', 'manual', NULL, NULL, 'berhasil', 'payment_6938c15ee283e7.88642827.jpg', '2025-12-10 00:39:58'),
(21, 21, 700000.00, 'Transfer_Bank', 'manual', NULL, NULL, 'gagal', 'payment_693a2a57ab4246.68981433.jpg', '2025-12-11 02:20:07'),
(22, 32, 700000.00, 'Transfer_Bank', 'manual', NULL, NULL, 'berhasil', 'payment_693d92e542e645.01260163.png', '2025-12-13 16:23:01'),
(23, 21, 700000.00, 'DANA', 'manual', NULL, NULL, 'menunggu', 'payment_6940f13f94a288.26679241.jpg', '2025-12-16 05:42:23'),
(24, 38, 1000000.00, 'Transfer_Bank', 'manual', NULL, NULL, 'berhasil', 'https://res.cloudinary.com/dxhbbcpvz/image/upload/v1765985014/kosconnect/payments/payment_6942caf3b10bb.jpg', '2025-12-17 15:23:37'),
(25, 39, 700000.00, 'OVO', 'manual', NULL, NULL, 'menunggu', 'https://res.cloudinary.com/dxhbbcpvz/image/upload/v1765986033/kosconnect/payments/payment_6942cef26e326.png', '2025-12-17 15:40:36');

-- --------------------------------------------------------

--
-- Struktur dari tabel `review`
--

CREATE TABLE `review` (
  `id_review` int NOT NULL,
  `id_penyewa` int NOT NULL,
  `id_kost` int NOT NULL,
  `rating` int DEFAULT NULL,
  `komentar` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `user`
--

CREATE TABLE `user` (
  `id_user` int NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `no_telepon` varchar(20) DEFAULT NULL,
  `role` enum('admin','pemilik','penyewa') DEFAULT 'penyewa',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `activation_token` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '0',
  `is_blocked` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0=not blocked, 1=blocked',
  `foto_profil` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `user`
--

INSERT INTO `user` (`id_user`, `nama_lengkap`, `email`, `password`, `no_telepon`, `role`, `created_at`, `activation_token`, `is_active`, `is_blocked`, `foto_profil`) VALUES
(101, 'Administrator Sistem', 'willyjuaness@gmail.com', '$2y$10$hcPWR8C6feyn/Fq592odSelDEpxLqhMtPy33.Gysx7xyIM53gIL8K', '082226506445', 'admin', '2025-10-05 06:08:14', NULL, 1, 0, NULL),
(141, 'Budi', 'skylark210905@gmail.com', '$2y$10$s9yGVSDy8bVTnbfWnP4rR.Egk0by6KF/KLTPLheN69VgYKPWMBOS2', NULL, 'pemilik', '2025-10-16 06:38:03', NULL, 1, 0, 'https://res.cloudinary.com/dxhbbcpvz/image/upload/v1765904098/kosconnect/profiles/user_69418ee22c77d.png'),
(145, 'Wilhelmus', 'wilhelmusjuaness@gmail.com', '$2y$10$TRn36S6Ze1rS..90lcs9eOB2OrBzFvxGvAyGMsOWx5Ju0fBaO4xzO', NULL, 'penyewa', '2025-10-27 01:08:43', NULL, 1, 0, 'https://res.cloudinary.com/dxhbbcpvz/image/upload/v1765902976/kosconnect/profiles/user_69418a7f46956.jpg'),
(146, 'James', 'skyborg036@gmail.com', '$2y$10$BZMrWCYHKh6fShvqsXGJxeALQGX15YHJmB7xDI14IRIYJnjHRXRei', NULL, 'penyewa', '2025-11-03 07:34:09', NULL, 1, 1, NULL),
(148, 'yoel', 'rosi@gmail.com', '$2y$10$2aDjKoIXSl3e0qx1ecVf2ODtBV.7QsLBM5w5SOy4f2PSrtvHWVbfm', NULL, 'penyewa', '2025-11-15 00:41:26', NULL, 1, 0, NULL),
(149, 'Eva Sirampun', 'evasirampun20@gmail.com', '$2y$10$L36.H5OasFUPMGUt7fyYsuXPW6RZaoJNCP7OTZO65nlyxf/pHPL5e', NULL, 'penyewa', '2025-11-17 14:09:15', NULL, 1, 0, NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id_session` int NOT NULL,
  `id_user` int NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `device_name` varchar(255) DEFAULT NULL,
  `user_agent` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `login_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_activity` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_active` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `user_sessions`
--

INSERT INTO `user_sessions` (`id_session`, `id_user`, `session_token`, `device_name`, `user_agent`, `ip_address`, `login_time`, `last_activity`, `is_active`) VALUES
(1, 101, 'f501b5f552966556bb4556a9860123c57b721fd4f25c7f73824fdec9f35877ce', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '127.0.0.1', '2025-11-15 00:52:20', '2025-11-15 00:54:27', 0),
(2, 148, 'bb3c2a267ef74810d7b3a8389e9305a38ae1d89797298f50a9b87574f16b51de', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '127.0.0.1', '2025-11-15 00:54:53', '2025-11-15 00:57:14', 0),
(3, 148, '1eeba39bc7b56d2003608c6d32aae1d21058e618fc970215b4d48bd361fd3237', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '127.0.0.1', '2025-11-15 00:57:21', '2025-11-15 00:59:50', 0),
(4, 148, 'a449b0cb1ff0671345eda84a48e2d15d149c937f8f54ad22ca60158456163208', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '127.0.0.1', '2025-11-15 01:00:06', '2025-11-15 01:03:29', 0),
(5, 101, '3527c4dc893306b26afc6b1617d8c4455bbcdba1cc912f9cd3ae9ee764782f4f', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '127.0.0.1', '2025-11-15 01:01:46', '2025-11-15 01:03:24', 0),
(6, 141, '45e7408310eff76fbd24b825a3261fb9325dd78e33ef5ccbc57f4784b6f3e1f2', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '127.0.0.1', '2025-11-15 01:03:45', '2025-11-15 01:07:18', 0),
(7, 148, '8670b03e1211b11854e9ca194d581f5cd5cde15a482e16a0674108594f74f0ab', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '127.0.0.1', '2025-11-15 01:03:58', '2025-11-15 01:10:47', 0),
(8, 101, '5a220eefde8be076bf9ecb327389f2b4f036da61172bb783044ac7713c5529e6', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '127.0.0.1', '2025-11-15 01:07:23', '2025-11-15 01:07:28', 0),
(9, 141, '81eca50d45b93ca633dffae7eafb62d3a33f6ba09258491613c6adf6a4fad76d', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '127.0.0.1', '2025-11-15 01:07:38', '2025-11-15 01:10:44', 0),
(10, 141, 'fca03ca92d41dddad2c6b67847bfb8d85a2d0e9cb1eec601307625866c9f2c51', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '127.0.0.1', '2025-11-15 01:20:11', '2025-11-15 01:22:19', 0),
(11, 141, '688322f47de034f87fbf23b09ec5f750c74dac894d544bba8737d3a5cdf0c719', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '127.0.0.1', '2025-11-15 01:22:16', '2025-11-15 01:22:59', 0),
(12, 145, '8d2f8b3095c0905469578f70633d836af73f350459189c7374bcc1875329ef89', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '127.0.0.1', '2025-11-15 01:22:25', '2025-11-15 01:23:06', 0),
(13, 101, '60c37d520a587b5aa53b7fb9e4b15f903ab3ed4d676acd0fdeab9ab47ca7e837', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '127.0.0.1', '2025-11-15 01:23:23', '2025-11-15 02:23:35', 0),
(14, 141, 'dce111316d0f1fa9b97219042cad118da77ef207ea86c05b236e79bbf10aaf13', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '127.0.0.1', '2025-11-15 02:23:39', '2025-11-15 03:10:35', 0),
(15, 148, '669b32d7e335d639957eb919ff75261655b68ccbff88c5673647371a1e052b01', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '127.0.0.1', '2025-11-15 07:54:37', '2025-11-15 08:12:38', 0),
(16, 141, '90b5d5f6ccb1d44cce3c4a81a2126c5041965e10034f71234b5e440bb0e39079', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '127.0.0.1', '2025-11-15 08:12:45', '2025-11-15 08:14:54', 0),
(17, 148, 'e915b1ee1b8194a7c4ba8a1dc8af029f7dd31520ee67c207e365efffc18327ab', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '127.0.0.1', '2025-11-15 08:14:58', '2025-11-15 08:25:08', 0),
(18, 145, 'bde8ef3994e2b9569f885539b4659b4ea82e7c5c97241d78a899f22e576ee565', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '127.0.0.1', '2025-11-15 08:25:26', '2025-11-15 08:25:39', 0),
(19, 141, 'f8140671e7ecc7eef4c107032b71cbea006f2cc285969976c222d7a580d28365', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '127.0.0.1', '2025-11-17 01:23:39', '2025-11-17 01:31:30', 0),
(20, 145, '11aa9a6776ac1ffe8610d54d05a1691040c0879c4e7d835f8e7b9f6beb0bd65f', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '127.0.0.1', '2025-11-17 01:33:21', '2025-11-17 01:33:29', 0),
(21, 145, 'cb922b6946b5611130c732e4fd85b94c1ae768179ea16f5e9277fa5f622d5357', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '127.0.0.1', '2025-11-17 07:23:50', '2025-11-17 07:24:42', 0),
(22, 149, '2e6b3471fbb377698849089edea6ef86d1ecc563b922e66764365cba0687b10d', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', '2025-11-17 14:26:41', '2025-11-17 14:27:23', 0),
(23, 145, '934358f0188bb85075795ad8f4279c6286a6d22b786fc1b365122e7aba4f7dd6', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '127.0.0.1', '2025-11-19 04:49:18', '2025-11-19 04:55:17', 0),
(24, 148, 'f880941c81ae3830dd36900aa15a4fe1474687bb8a98d30e86fe2ac694b672eb', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '127.0.0.1', '2025-11-19 04:56:48', '2025-11-19 04:56:54', 1),
(25, 101, '011cdd3f9b7b97414da5f71a3eebec753201c2a509c7a1cd3cd0890f64d4e01f', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '127.0.0.1', '2025-11-20 02:12:31', '2025-11-20 02:16:53', 0),
(26, 141, 'debfba6ffe0a5886530392a1c1570e66a2691d5fcd61fce9fa8a5fb499f3640c', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '127.0.0.1', '2025-11-20 02:17:01', '2025-11-20 02:17:53', 0),
(27, 141, 'af1c8c20326c51ae4f721b14406a6e3dee363c837a36283f60954c4a1d3cfa94', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '127.0.0.1', '2025-11-20 02:18:15', '2025-11-20 02:29:25', 0),
(28, 141, '3f03f8d737c6a32a9b220c0b26058fd6db53af4f1033c8c3f7468c2c106d00e0', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '127.0.0.1', '2025-11-20 02:42:42', '2025-11-20 02:53:23', 0),
(29, 145, '669327664756fdd13a9a5624767c0fd6e1bb5b4043ede7e18fc46cbe6b49919a', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '127.0.0.1', '2025-11-27 03:07:17', '2025-11-27 03:08:23', 0),
(30, 101, '58bffd98b4e71541106682650dad2ed3e022aec48b2fdacecf29da991c69516e', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '127.0.0.1', '2025-11-27 03:08:38', '2025-11-27 03:08:51', 0),
(31, 145, 'a66ba278238bbd273d55cc60d5e51cb9d15d05fd119fb5c9a1c3055408b42540', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '127.0.0.1', '2025-11-27 03:09:52', '2025-11-27 03:35:45', 0),
(32, 145, '0644d5c6d21037a5255df7634645a6536f8ded2c8d3639cd4de883104bbe1d7d', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '127.0.0.1', '2025-11-27 03:40:18', '2025-11-27 03:40:54', 0),
(33, 141, 'b34fa8c8b9851b58614c2fbb34832dada22908c7ff37fca3df2faf53292e2d7b', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '127.0.0.1', '2025-11-27 03:41:00', '2025-11-27 03:41:15', 0),
(34, 145, '69f0f714e2ac9b928a5ef32a431716d224ec6ca2633643fb94f6eb754861aa2f', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '127.0.0.1', '2025-11-27 03:41:21', '2025-11-27 03:42:32', 0),
(35, 141, '85cbecffad1040f2ea738080887f1d634d803c14ddcf588d1cd4d1c0a0c889fb', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '127.0.0.1', '2025-11-27 03:43:38', '2025-11-27 03:44:04', 0),
(36, 145, '6c9ff15e66f3d4923d5ee9672762650a1e8b5693247af8ab11bbd35b61773d85', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '127.0.0.1', '2025-11-27 03:44:13', '2025-11-27 03:46:57', 0),
(37, 145, '00118557bd44e23a2ce42987cc5a9df29b3a85293191b94838ddb8327685435e', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '127.0.0.1', '2025-11-27 05:19:32', '2025-11-27 05:20:45', 0),
(38, 145, '8149aa91c739be6beda7090155f11f4078dac2dcf6c53fd7c6b560e8349bd0cb', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '127.0.0.1', '2025-11-27 05:20:54', '2025-11-27 05:21:05', 0),
(39, 145, '80b75c31c303f4d4b0f885f05eb00b75748cd6e0ef4da495af15d79d3cf2e5ac', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '127.0.0.1', '2025-11-27 05:21:15', '2025-11-27 05:21:19', 0),
(40, 145, '95a554068d48f2a311016f794de03f617fc46f85b617186fdf8f185e769b6eab', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '127.0.0.1', '2025-11-27 05:21:29', '2025-11-27 05:21:44', 0),
(41, 145, '75981b4568feed7ec8c6444daa44140f398f37db2ca35617f2e697e19db192e1', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '127.0.0.1', '2025-11-27 05:21:56', '2025-11-27 05:45:29', 0),
(42, 141, '828cb16c9dc0d035841336814405fc9d6077c662cfa7ebb23894718c488ce342', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '127.0.0.1', '2025-11-27 05:21:57', '2025-11-27 05:45:22', 0),
(43, 145, 'd677d823e6bffdda157f7f7053c6cf4dc9774da803a74fabd2a8f507bb715187', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '127.0.0.1', '2025-11-27 05:45:35', '2025-11-27 05:45:43', 0),
(44, 145, 'd045d71128c742c8cc090bf385972136b86f67680a397c62c28d2b5b8a29ed96', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '127.0.0.1', '2025-11-27 05:46:10', '2025-11-27 05:56:35', 0),
(45, 101, '2f8aa5a31d94407127c38040156330497ba6b4d162c8603c34f318f205f7b330', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '127.0.0.1', '2025-11-27 05:46:13', '2025-11-27 05:46:13', 1),
(46, 145, '94baf2a539ea8ba94f28f8df454cfe86a5339b32872599b62f2ead2612fd1c80', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '127.0.0.1', '2025-11-27 05:56:39', '2025-12-15 01:38:31', 0),
(47, 145, 'd13330f1ac8f2f2e07b9f52e46a3f105b1cb7c250c097efbf6fe9e440d50a800', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '::1', '2025-12-01 01:51:19', '2025-12-15 01:38:31', 0),
(48, 145, 'edb3bc61dea7ecca25ee6385c1fba7afa9e66b10fa172e293bff94a87f0b3cbb', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-06 06:35:13', '2025-12-06 07:34:48', 0),
(49, 141, '2b8686a88166795862a7354676c6ea79e44d938344d3e164d2c3a66c074118ce', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-06 07:35:10', '2025-12-06 07:35:43', 0),
(50, 145, '81b77e358e3d12a44b43117b1e4cc927a6ed9fd4ab16fe168372b7f0f889401d', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-06 07:35:48', '2025-12-06 07:35:52', 0),
(51, 101, '9c91ebbbf2ffcd88d2d3fdb9a5518a7f638453a09fcb8f2464d95081248a908b', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-06 07:36:06', '2025-12-06 07:54:02', 0),
(52, 145, '0fde4d46f2b09a8076aacca6bd1a1fcfbabeb33a2f18a3ba7d062bbcafa26a8b', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-06 07:54:06', '2025-12-06 08:55:12', 0),
(53, 145, '8fdc86d64d929f460b73b935fbcdb68cb455f61bd1dfe81c0055a15dc7f7fb09', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-06 08:55:32', '2025-12-15 01:38:31', 0),
(54, 145, '9f999ae7d7418de42b72f1dd20c54a5912d775ce170b1d7e23b76a77704c9594', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-06 09:34:40', '2025-12-06 09:37:51', 0),
(55, 145, '6defefbb7fbbf1c3087ee1080a0c50295c1663850d18f7671337b83cfea955cf', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-06 09:39:51', '2025-12-06 09:40:03', 0),
(56, 141, 'f491bf41c6b9f22b83466a4f04403f7fb88f1e78078da49eca3c6feb54f5bfea', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-06 09:41:08', '2025-12-06 09:48:16', 0),
(57, 145, '210222373b913a8b25a8b3cf7aeb953cc6e25d2c2021604192c3eed614a266db', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-06 09:48:19', '2025-12-15 01:38:31', 0),
(58, 145, 'd10606dac7462845f7294172c3250f87d5169802cd51aadc63d08afcd106b29d', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-08 07:37:24', '2025-12-15 01:38:31', 0),
(59, 145, '6731d0926ef07473890698a20528f1e657e79f800aee3783cc2237052139b344', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-08 23:20:40', '2025-12-09 00:18:26', 0),
(60, 141, '47df99a6525c9108b8ad0ffe2d72069204e78b0174f814c66ae4ce923c616b15', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-09 16:34:13', '2025-12-09 17:20:56', 0),
(61, 101, '62a78c6fd0c6320f63e8a792b24e59d19100e44f322318bc44ec1ff021fa4b4a', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-09 17:21:02', '2025-12-09 17:28:14', 0),
(62, 145, '046eec11f046e98d12106a0029355ede9f5cb399756dafd1a832e6ef71a7007e', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-09 17:28:23', '2025-12-09 17:28:49', 0),
(63, 101, '873ec4e83f4ae69f83060ba882974599a28157e975e56d5291a7e034b1c1f2c2', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-09 23:47:47', '2025-12-10 00:06:11', 0),
(64, 145, '553d14f332a1abbf5d80a07bd9c28969b484587ae5e8402f28f0a0376f74ee84', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-10 00:06:16', '2025-12-10 00:07:00', 0),
(65, 141, 'b63e04aedf2400e7a33ae268801adc500b36521b3b784c26f25ca0c305822139', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-10 00:07:21', '2025-12-10 00:08:07', 0),
(66, 145, '6ab4a354f9ac8dce0250fcb0345b7eb8720294b7b8e673323cacc17e46a1ab2e', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-10 00:08:10', '2025-12-10 00:08:39', 0),
(67, 101, '5bb23794704cc495640014f053fcd21ee4541f11ec5b27e384cfbfd3c11d4319', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-10 00:08:42', '2025-12-10 00:37:13', 0),
(68, 145, '45b1358b1161e4bef7fe690b383d7f12a6553fe6fa997e4fe5b0119b0ac705f7', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-10 00:37:16', '2025-12-10 00:37:53', 0),
(69, 141, '9b2220f72871d102797c5e64032ea2fd5bbee7b9cd64abb33a1858c423f7171e', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-10 00:37:59', '2025-12-10 00:38:26', 0),
(70, 101, '12b79399cb3a5d5cd098f4484a3e4c20f913680f56d7c48ffdac467c27c2c3be', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-10 00:38:38', '2025-12-10 00:38:56', 0),
(71, 141, '8d176a648a204f914ecaaf229626a6b2b81244c248ba28487d61aeb533b6a5d2', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-10 00:39:08', '2025-12-10 00:39:19', 0),
(72, 145, '0b20ced68d2a565e58fcdb38d657933709caedbc7e10055fa22228e8601f7966', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-10 00:39:24', '2025-12-10 00:40:03', 0),
(73, 141, '58981de9dad58be5cb6a3784cc46965c94ee1458bc99d81b11ea4c8c10a03efe', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-10 00:40:09', '2025-12-10 00:40:23', 0),
(74, 145, 'af61d6243ed6d32e71e482f4d1dd66e7b0445c7ed5b120a8ad29ab8b5675c3ae', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-10 00:40:25', '2025-12-10 00:40:43', 0),
(75, 145, '9567ee58cf7715b753dcb3d0ed0659a49ff5f9f4ae85f740379c0e47421b7425', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-10 00:40:46', '2025-12-10 00:40:49', 0),
(76, 101, '3b95c9b058472e2b0f652dc7ad2330af7f8d8b18b612428c4f237c66a4866120', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-10 00:40:52', '2025-12-10 01:05:02', 0),
(77, 101, 'afa38bb3a107186011949f10dbd79666a392d113feb08d2345656d96b3c30425', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-10 02:00:26', '2025-12-10 02:09:47', 0),
(78, 141, '66a4d15f89bbd86abe768ad91b7c0b0c832425418a51b4b5724b63c045a97a4b', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-10 02:09:49', '2025-12-10 02:10:05', 0),
(79, 145, 'f14111ca1ff7c13b80e0894eb526473c9f9b533511ac53b8661f492f7e83644c', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-10 02:21:06', '2025-12-15 01:38:31', 0),
(80, 101, '34f789ee2537fe87e4f839e023097cd461119d4e9da699943e6556694735cd3f', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-11 02:17:20', '2025-12-11 02:17:43', 0),
(81, 141, '161efb66b8220256a4adeb70d94cc46c26183305695554603a65baa07c0ab2c6', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-11 02:17:49', '2025-12-11 02:18:09', 0),
(82, 145, '7b2092bdf4ae533125383baad90aacb478d06842fbf1124dea8784f4296e32a2', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-11 02:18:16', '2025-12-11 02:20:12', 0),
(83, 101, '66e24c4851ff49bd7a7383bdee9069f2321f9155d104a64eb61dc169c9dd8bbb', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-11 02:20:22', '2025-12-11 02:20:39', 0),
(84, 141, '01023c9680d596f933028bb278949f0f918f661c198913c144650a12a0d4d8e0', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-11 02:20:43', '2025-12-11 02:20:58', 0),
(85, 145, '4b6c9b48ecbdbbb59ef34b560c3068188eb9113a7c27914ebfe418471a730616', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-12 05:14:56', '2025-12-12 08:03:31', 0),
(86, 141, '431f7636fed554dda80a62a6af8c42bcbb9642e43be8c0a0cb336674ae2df5f5', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-12 08:03:42', '2025-12-12 08:04:02', 0),
(87, 145, 'c0c9c9909101f9a6df75fca098443d6b99483419ce46f17f4117b5bcd684543d', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-12 08:04:07', '2025-12-15 01:38:31', 0),
(88, 145, '8bc9311fc88fcd86733b88cb4aefa3750728a544352184d6510bb0531ac24ede', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-12 14:27:09', '2025-12-12 14:28:28', 0),
(89, 145, 'c717d85cb58b55f507cfc369b8a93ce406a43b8199ef020cc5bb61443eee00ab', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-12 16:37:09', '2025-12-12 16:37:19', 0),
(90, 145, 'b1ac7309688fac9b460a7f213573cf990aeb8b44c3f0e250c45c001be4ce377c', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-13 10:18:59', '2025-12-13 16:23:05', 0),
(91, 141, 'ea412c88cce6c4d3f5f071e1217c6417dd01a09e9a399f5c74e4442a72af3ef2', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-13 16:23:13', '2025-12-13 16:23:29', 0),
(92, 145, 'e33b4cd25ec591bb7ea41a890a1b8256e45b4a0ab64841951b40031127a85fa5', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-13 16:23:36', '2025-12-15 01:38:31', 0),
(93, 145, '56697492ccbd5e2076e11328ba6943c3cdec916370f45a9a758225c2654c549b', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-14 07:55:18', '2025-12-14 07:55:41', 0),
(94, 141, 'ea8d848988c4fae84a0373a20c31e0702192ef44426d14c5f9233381ccd3819a', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-14 07:55:48', '2025-12-14 07:56:12', 0),
(95, 145, 'e3e9784557eb4fecc3c485be8f97e8da879a2db88fd6c02a5c648fa802fd379f', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-14 07:56:18', '2025-12-15 01:38:31', 0),
(96, 145, 'c9640baae9c24d994c3162d643cf1f655d711ce26e3cbdf2a701ca1c9a232608', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-14 17:26:40', '2025-12-14 17:27:07', 0),
(97, 141, '13441c9c1112b43c24f712f42c3d36da49ea904f51e358932288d3ea394529a0', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-14 17:27:11', '2025-12-14 17:27:20', 0),
(98, 145, 'cc76465b542e854e7c15778cb505db2047331cb110dfa97aaf1716885a5037d5', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-14 17:27:25', '2025-12-14 17:39:57', 0),
(99, 101, '458e1605246409aa74c049e970a4b3a6926baadfaca9c453f73bd526c317e211', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-14 17:40:02', '2025-12-14 17:41:47', 0),
(100, 141, 'a2b1ee7e27ea371746650853bb50ab2fec25cc7c8deabf401417ea374438b700', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-14 17:41:52', '2025-12-14 17:50:56', 0),
(101, 145, '94f626ca1fb1223f52dc2c17b00302b30f3d6c02c479bbaa441034d75eb271ba', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-14 18:09:45', '2025-12-15 01:38:31', 0),
(102, 145, 'af3ab37b73450487c59ad5659deaaf6386ccee0b93e53e6529265e5ce1bbd422', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-15 01:11:32', '2025-12-15 01:14:51', 0),
(103, 145, '6e3bf37ad934cbe0df95f2f2fd5d046b8c15d0204b4746651a37c4fb808816e4', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-15 01:14:58', '2025-12-15 01:38:31', 0),
(104, 145, '00cdf5ad28147cb8cc028a53cd05e6284b47eb32ad9c1eb0c51445f093382d2e', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-15 01:15:23', '2025-12-15 01:24:05', 0),
(105, 145, 'be3bc5751a950ce5f29e6d214d9ab09d64b57908fc95cebbceb3be611152639a', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-15 01:24:18', '2025-12-15 01:38:31', 0),
(106, 145, '5366c9da66a0f9a1166b5c2dd9193c0cd1b78a41767aa5d27683d0f62b6060cb', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-15 01:38:35', '2025-12-15 01:38:43', 0),
(107, 145, '532edd678e479614fcb240dc6e44479b99bfd3595aaf39563b0060017a58489e', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-15 01:40:57', '2025-12-15 01:41:02', 0),
(108, 145, '9c46e7585441e9f4c3e845dadf5274b14b63d6211cbe3472f65d6a76620b3712', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-15 01:45:14', '2025-12-15 01:45:18', 0),
(109, 141, '58cb3a91f645d498b7076d6d399ec3cb93fed43239a0946d45063212c79f9055', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-15 01:45:22', '2025-12-15 01:47:03', 0),
(110, 145, '8e90c4e0fcc93c612e2d61c5584be1ed102ae752d6ac95f17c73a82f0ad960ba', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-15 01:47:07', '2025-12-15 01:47:52', 0),
(111, 141, '0aef303281699f1279f8c011ad61aa4ec3bf1855abc4300c2f099d167c293e96', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-15 01:47:57', '2025-12-15 01:48:17', 0),
(112, 145, 'b748330ac6d20bdcbefdb71a60ddfeae0cb55e119dc65a201d28c3c3fb6a49af', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-15 01:48:20', '2025-12-15 01:53:32', 0),
(113, 145, '8ac6c16d7a381863544f87b7d18fcc9f225aafcdf9abc1f6f93bb541ba128102', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-15 01:53:46', '2025-12-15 01:56:01', 0),
(114, 141, '6f1fefb824819db9afcef99e9180bc66425c4368864f547fb58b67db90627050', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-15 01:56:04', '2025-12-15 02:09:39', 0),
(115, 145, '07e146579d96786de48faf2017331c521030e7f69f7bbaca4b5572c4152cca4f', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-15 02:09:45', '2025-12-15 02:14:40', 0),
(116, 145, '3394cb742bfd155b50fe7720d3fe574e99bd4c845218d64b479475f577c04f5f', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-15 02:15:00', '2025-12-15 02:22:52', 0),
(117, 101, '089a4f5f723086700f1bd24d04074dbda22126dd6638eb152904701c5e05ec0a', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-15 02:22:58', '2025-12-15 02:29:17', 0),
(118, 101, 'ec9198c3d677b1b009a3ac4b280fceecd5305ffcb4a3fb0dfc597cf75f0a95cd', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-15 02:29:21', '2025-12-15 02:51:26', 0),
(119, 145, 'd771bbabda776f2a626bca966a3a7087973bbe7f5b8cf37fe4b443e54c959062', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-15 02:51:31', '2025-12-15 02:54:34', 0),
(120, 141, '1538227f9e0224950c1f96cac7e9f5d8444805d0b38b2859826729ea799a2ea1', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-15 02:54:46', '2025-12-15 02:57:07', 0),
(121, 145, '9bba7086a029255ba39f6a3dd0cdb1f88cd2f60ef6295b8895695283be557f05', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-15 02:57:13', '2025-12-15 03:20:52', 0),
(122, 141, '9006c287e73331a4c1309e74b93699ef746b1e98dc88b3c559275dd17e2b031b', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-15 03:20:58', '2025-12-15 03:21:22', 0),
(123, 101, 'c30f1a4a997b04eb904b7800788773a42721c8b1304cfd3e5ff9103a119964f8', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-15 03:21:28', '2025-12-15 03:28:38', 0),
(124, 141, 'cb2d34ee11f4e0de5513834a9735e30218a011303b549525d6edfcc8716e9d4f', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-15 03:28:42', '2025-12-15 03:38:12', 0),
(125, 145, 'c6f5f41df7cf128bbfe917d16269e72ae4e606ed433a43b317e180925b4ecc25', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-15 03:38:16', '2025-12-15 03:39:11', 0),
(126, 141, '08d60ef7acc16e7d38dc3ea17b6b476f4b76f1e700684f66f6a26af6c8882816', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-15 03:39:20', '2025-12-15 03:43:11', 0),
(127, 145, 'e6e39ba8539f1d114243c17c2462b1306e35adfcf699e1ed25eda3e84e517d4a', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-15 03:43:20', '2025-12-15 03:46:04', 0),
(128, 145, '4ce48e6bf03bc48d72e1590a6397566bc15b46d78c183943c40dc3f6124571e3', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-15 03:47:03', '2025-12-15 03:49:09', 1),
(129, 145, '1341a2427abbaeafc6af24ca0bbaf2c41c116beebfb662d2d5b321c10d5e96b0', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-15 05:56:07', '2025-12-15 06:24:50', 1),
(130, 145, '7119bdaff746565ea10d13d833dba9aded81c85e6c0d24879d1cd42163a7c26b', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-15 07:27:23', '2025-12-15 07:41:58', 0),
(131, 141, '9f32c6db57ea87629cd21917fbd2b2d936c8b893ceadff829aabcea1582071eb', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-15 07:42:03', '2025-12-15 07:50:35', 0),
(132, 145, '2301558537aed8bcdfd8ea10ab41ae426bfc09f87743ecd22639413cb406b954', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-15 07:50:38', '2025-12-15 07:51:02', 0),
(133, 141, '9a21fac9f90411be445b81ebd236861628727c8ea660caa7740cf42c16487f5e', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-15 07:51:06', '2025-12-15 08:02:38', 0),
(134, 145, '4201bcd67e9504fc0ac5b680b3b61f71b6919190d128c2cd72428cddfd247bb0', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-15 08:02:42', '2025-12-15 08:06:04', 0),
(135, 141, 'c1b279666c2029bee2d1a34c270367753c2bf905591a196352f4eb9e554637cc', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-15 08:06:13', '2025-12-15 08:06:26', 0),
(136, 145, 'fa7f6cdfa304c41f7d977b3fee5dd2f820c3d33aafeedbdf64f51661cc565dfc', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-15 08:06:29', '2025-12-15 08:13:48', 0),
(137, 141, 'b9708bb3ade22e36e92235198a4cb777f8eb9df11c6fbcebf330216a06aa3bbe', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-15 08:13:52', '2025-12-15 08:16:44', 0),
(138, 101, '382cbf4e9c8c595e8d155250cb1112980ddcb4ce5e26575643fa3a6a42a0f950', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-15 08:16:52', '2025-12-15 08:24:46', 0),
(139, 141, 'a5a5554938710e66924b619d925d3a94ef8e8408a3acbd7f2ee845bd99c344da', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-15 08:24:54', '2025-12-15 08:49:15', 1),
(140, 145, 'eee7868ae4d1ff4d6358ef2ce84614b37b183fb0bd10fbfd5021d12817fb0c82', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-16 01:55:50', '2025-12-16 04:09:03', 0),
(141, 101, '300db5cae28ce80e04ab0ea4bdcd3462d13725603f5bd050c366f8649133ab0c', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-16 04:28:09', '2025-12-16 04:28:22', 0),
(142, 141, '3dd92725095257d22cfdb28aae20037a2c74aaa9cd921b52fc435f9f18159f7d', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-16 04:28:29', '2025-12-16 04:28:42', 0),
(143, 145, 'e1d0aac8ca214350ac1ab41fcadaa87a1b38dc339efeaebbe73f0bc7a2edfe98', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-16 04:28:49', '2025-12-16 04:30:03', 0),
(144, 101, '2f44a2eccf2c5920cd10e8806e320f5d6107f5bc83a853c890ed0d7c05a43304', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-16 04:30:17', '2025-12-16 04:30:18', 1),
(145, 101, '3c841c483f96f01fd3865cc2d25a730c2d7385dedfb1c2c90c47483b251151d4', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-16 04:52:54', '2025-12-16 05:32:51', 0),
(146, 145, '5467eb5f2beb5613def4e9cffe3b2a561647ba35aaf8517ec40666560c5b49ea', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-16 05:33:04', '2025-12-16 05:33:40', 0),
(147, 101, '172ea1496172467f51e168143aec3b11331936f7dd194491ee5f1d8c8363fbde', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-16 05:33:50', '2025-12-16 05:38:08', 0),
(148, 145, 'e99c63b52d32b8196b09575c1d92545595775f2fa041cb66aa96fbc9cafad9dd', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-16 05:38:13', '2025-12-16 05:38:26', 0),
(149, 141, 'd65845afd0fadeb01ebafd58ddbe5d7eb365b3ca76aee0d3536e1cc2004f8ff2', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-16 05:38:36', '2025-12-16 05:38:47', 0),
(150, 145, 'f8f7692616a5e368947dbd49d6f5aab777b18fb704e327b9e4f410e0b0e8ad4c', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-16 05:38:51', '2025-12-16 05:39:35', 0),
(151, 101, '1e56aacb6917be9e33fa00492d432daa784a9abfd2434e1d54200ee9c7252acf', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-16 05:39:39', '2025-12-16 05:41:57', 0),
(152, 145, '616bab2ad76712b253273cffb0a4a00cb83e38018e0ea087230e5867559fdc56', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-16 05:42:02', '2025-12-16 05:42:28', 0),
(153, 101, '1930fb55b16738abec5b2a55e10646b8bfbf8e56cede9ca398ca8d8cdce0c32d', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-16 05:42:35', '2025-12-16 05:42:47', 0),
(154, 101, '84a38c8d81cca5afbd7bfed51c3d38f00d87a4b63c882d50db2b86fe6daa4357', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-16 15:42:34', '2025-12-16 15:59:09', 0),
(155, 101, '471e2084837b4c6fe68c7dd3c169e154a15b88f2c473a1be127fe8b6c346cbcf', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-16 16:10:32', '2025-12-16 16:17:36', 0),
(156, 145, 'afca7b3228539ae1ed4bdf6b67b09e766d037c034966804d205a0d67d768ad69', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-16 16:17:49', '2025-12-16 16:54:18', 0),
(157, 141, '3b09648939f83c61b82eaea87410743762f104fcc5711ea460ca0844c531d1be', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-16 16:54:24', '2025-12-16 17:02:50', 0),
(158, 101, '1ab4bc4111d64b968f0666fe6e5bdd2238a7d146466b69918ca9c4dab3349482', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-16 17:02:55', '2025-12-16 17:08:42', 0),
(159, 141, '28f2337ddef03c8a53ffa12b7389db81c09e6ebfdd98aabf283e23e0e45b2053', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-16 17:08:45', '2025-12-16 18:22:34', 0),
(160, 145, '064bc10f2f0556a3967a612d80b213da92105cabb71197e953948f64c30425be', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-16 18:22:59', '2025-12-16 20:03:30', 1),
(161, 145, 'c889161abd9731ba4a782bc5336e6ac3e289ca966b9b699112973e4855917960', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-17 04:12:29', '2025-12-17 04:12:29', 1),
(162, 145, '61fd9ef77b848fee21f9bb769c8c0712cf28b3dacbeb6e36ce199b06a545e4df', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-17 13:43:00', '2025-12-17 14:11:06', 0),
(163, 101, '17fce0c969805d67c59f0917842e36be5d1b3d854a7db5ab8338376705c7b863', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-17 14:11:11', '2025-12-17 14:21:06', 0),
(164, 141, '9806e11289f918a45c11d55a0ff1b2e46a8870b30501f6073c3e4b925c616deb', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-17 14:21:10', '2025-12-17 14:28:42', 0),
(165, 145, 'b9c67c8357cb4464978c8cf3bee71c6d0bbbbdd8bf336c474ab866610600a8a8', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-17 14:28:46', '2025-12-17 14:30:38', 0),
(166, 141, 'd4a03e188c403b6d293968a4df233a144af862cc3f4ac95dbaede592a7249a07', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-17 14:30:43', '2025-12-17 14:31:15', 0),
(167, 101, 'fbcdfed969c6c851c9384ec0b90807b6d2c22cb9fcf87c7c28140a29bda98508', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-17 14:31:20', '2025-12-17 14:32:00', 0),
(168, 141, 'dd44778df697342984dd0d94814afdd50fc2a9a78845e0f3ff428194b18d9f1c', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-17 14:32:03', '2025-12-17 14:39:29', 0),
(169, 145, '82c84d5445039528f981f5449780eeadd54f1f7427d9dc3dc1bc94d4f0703e40', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-17 14:39:33', '2025-12-17 14:43:04', 0),
(170, 141, 'c0006261dad5d1925e1a06c58d7d707213efe69fd08f044a0e09aa2301c1fda7', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-17 14:43:14', '2025-12-17 14:53:11', 0),
(171, 145, '4d78b71094a21629f519527f32f0d507e01ccb007d5ae9027dde2e90f5970406', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-17 14:53:15', '2025-12-17 14:57:36', 0),
(172, 141, 'e71068d60225e7bf73c36ceae04d88a33956f18c30c29eed9f27c8ad6c6965d4', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-17 14:57:38', '2025-12-17 14:58:06', 0),
(173, 145, '09666eb23b48f601dc69bb2953db464317b74c00a719a0ebfa19d1f5c7c47d6e', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-17 14:58:10', '2025-12-17 15:10:11', 0),
(174, 145, '937a137a03106addf18ae1c1b1087d15c66a555ba253bfc6740e11c66695abbd', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-17 15:10:36', '2025-12-17 15:11:21', 0),
(175, 141, 'c3af0b1bcde99dd7df29ba2eef654d91f68a201b3ba694a77b569e15e1daf460', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-17 15:11:27', '2025-12-17 15:11:40', 0),
(176, 145, '94bb7d1a52cdf5d5556a0d34e98331be1e41ef7e60b5601913e1bf75262bc289', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-17 15:11:43', '2025-12-17 15:18:49', 0),
(177, 141, '7d570ec5ce08108462ce4d7debbcb995c789174eaaca716d6380751aa4c42c34', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-17 15:18:53', '2025-12-17 15:19:04', 0),
(178, 145, '0bb417fb9544ab2b723e024d0e1ea7db125dd7b7b3ae43e7084263037c5f96d7', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-17 15:19:08', '2025-12-17 15:19:23', 0),
(179, 141, 'c1a29dfb199698fe61d5a3f2d949adc458df2701405a391506c61049d8f4ba36', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-17 15:19:27', '2025-12-17 15:19:41', 0),
(180, 145, 'fa2c4b5162b37dd084cc7937e2bde9b32db747e85b2058f39a8a9f34d7288bf3', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-17 15:19:45', '2025-12-17 15:20:02', 0);
INSERT INTO `user_sessions` (`id_session`, `id_user`, `session_token`, `device_name`, `user_agent`, `ip_address`, `login_time`, `last_activity`, `is_active`) VALUES
(181, 141, '5a165ea8789861952f607cee7c730a6338da65464c0225306afd95075adb235c', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-17 15:20:05', '2025-12-17 15:20:14', 0),
(182, 145, '085c48f2f4a5acf5fa481aebd9d5f3c74c5cb8a4e58b6a60e3f8b4a44a60b5f9', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-17 15:20:17', '2025-12-17 15:21:41', 0),
(183, 145, 'a967a5de26a95be2ed41b0b891569c00917722f34f06977e645f1c836db6467e', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-17 15:21:52', '2025-12-17 15:22:18', 0),
(184, 141, '6f9a26a18dcb69e16a229e8d67bd2347356f60bf71a20f220a3550c65425b6c6', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-17 15:22:22', '2025-12-17 15:22:32', 0),
(185, 101, 'f0c5aa57d1bd3096ce71e80923a327a3da97ae454d18818099458f9d59483d70', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-17 15:22:38', '2025-12-17 15:22:45', 0),
(186, 145, 'eaffef3aa7630b5d181170fd1be480e40ee34de1ab2241b58a94a77c43a73d53', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-17 15:22:53', '2025-12-17 15:23:43', 0),
(187, 141, 'b06168f2cdb67d8b7f1645c730f9dee6499a53904d0e8610a3369a87308b2217', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-17 15:23:46', '2025-12-17 15:24:21', 0),
(188, 101, '1dd381cdb8a3b0261543032c5262d7be8e4cd8ba4a43c9c763a824b6aae7e7e1', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-17 15:24:24', '2025-12-17 15:24:36', 0),
(189, 145, 'af36fb828f1eaad1b18f1856a4048e23c474307910fccf3fdf583995a696fbe0', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-17 15:24:40', '2025-12-17 15:25:18', 0),
(190, 101, 'ebd13d9e1c8310f85a01a828ad8d54b0cd0fa9fd108601e5a843a10bf4282bd8', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-17 15:25:21', '2025-12-17 15:25:55', 0),
(191, 141, 'fa7d725040668fb3d5a5856de71b6d22e298891ab0ab0db286d161e74b894c2b', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-17 15:37:50', '2025-12-17 15:38:04', 0),
(192, 141, 'aac960affc3261d5565c6b892f6f14184aa1749a157cb186a7252c2ff32ffe89', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-17 15:38:16', '2025-12-17 15:39:08', 0),
(193, 145, 'eca10e2eb22bc02241270e4bd0eec98bf97e7caa92ffe5ab8b67b474beaece8d', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-17 15:39:17', '2025-12-17 15:39:32', 0),
(194, 141, '0b88c66d576e897dbdd9c300de44f0a061c49a46967503c674b346d676e7e57e', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-17 15:39:37', '2025-12-17 15:39:47', 0),
(195, 145, '5896071f1c5e911dafe963280b7e976760bed802429a3973812e0b33b7f9b894', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-17 15:39:51', '2025-12-17 15:40:40', 0),
(196, 141, '6fe82755a0cd5f637438695dbb966b6b64e54c8a718dd5012d615c938ae15e7d', 'Chrome on Windows', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '127.0.0.1', '2025-12-17 15:40:43', '2025-12-17 15:47:24', 1);

-- --------------------------------------------------------

--
-- Struktur dari tabel `wishlist`
--

CREATE TABLE `wishlist` (
  `id_wishlist` int NOT NULL,
  `id_user` int NOT NULL,
  `id_kost` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `wishlist`
--

INSERT INTO `wishlist` (`id_wishlist`, `id_user`, `id_kost`, `created_at`) VALUES
(5, 145, 4, '2025-12-09 17:28:42');

-- --------------------------------------------------------

--
-- Struktur dari tabel `xendit_invoices`
--

CREATE TABLE `xendit_invoices` (
  `id_invoice` int NOT NULL,
  `id_booking` int NOT NULL,
  `external_id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Unique ID untuk Xendit',
  `invoice_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Xendit invoice ID',
  `invoice_url` text COLLATE utf8mb4_unicode_ci COMMENT 'URL halaman pembayaran',
  `amount` decimal(15,2) NOT NULL,
  `status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING' COMMENT 'PENDING, PAID, EXPIRED, CANCELLED',
  `payment_method` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'BANK_TRANSFER, EWALLET, RETAIL_OUTLET, etc',
  `payment_channel` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'BCA, MANDIRI, OVO, DANA, etc',
  `paid_amount` decimal(15,2) DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `expired_at` datetime DEFAULT NULL,
  `callback_data` text COLLATE utf8mb4_unicode_ci COMMENT 'JSON data dari callback',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `xendit_invoices`
--

INSERT INTO `xendit_invoices` (`id_invoice`, `id_booking`, `external_id`, `invoice_id`, `invoice_url`, `amount`, `status`, `payment_method`, `payment_channel`, `paid_amount`, `paid_at`, `expired_at`, `callback_data`, `created_at`, `updated_at`) VALUES
(1, 33, 'KOS-33-1765700042', '693e71c96720fe660ae47846', 'https://checkout-staging.xendit.co/web/693e71c96720fe660ae47846', 700000.00, 'PAID', 'EWALLET', NULL, 0.00, NULL, '2025-12-15 08:14:07', '{\"id\":\"693e71c96720fe660ae47846\",\"external_id\":\"KOS-33-1765700042\",\"status\":\"PAID\",\"merchant_name\":\"KosConnect\",\"merchant_profile_picture_url\":\"https:\\/\\/du8nwjtfkinx.cloudfront.net\\/xendit.png\",\"amount\":700000,\"payer_email\":null,\"description\":\"Pembayaran Kos - 104 di Royal Kost\",\"expiry_date\":{\"date\":\"2025-12-15 08:14:02.646000\",\"timezone_type\":2,\"timezone\":\"Z\"},\"invoice_url\":\"https:\\/\\/checkout-staging.xendit.co\\/web\\/693e71c96720fe660ae47846\",\"should_exclude_credit_card\":false,\"should_send_email\":false,\"created\":{\"date\":\"2025-12-14 08:14:02.922000\",\"timezone_type\":2,\"timezone\":\"Z\"},\"updated\":{\"date\":\"2025-12-14 08:16:35.143000\",\"timezone_type\":2,\"timezone\":\"Z\"},\"currency\":\"IDR\",\"payment_method\":\"EWALLET\",\"payment_channel\":null,\"paid_amount\":0,\"paid_at\":null}', '2025-12-14 08:14:07', '2025-12-14 08:28:08'),
(2, 33, 'KOS-33-1765700495', '693e738d6720fe660ae47999', 'https://checkout-staging.xendit.co/web/693e738d6720fe660ae47999', 700000.00, 'PAID', 'EWALLET', NULL, 0.00, NULL, '2025-12-15 08:21:36', '{\"id\":\"693e738d6720fe660ae47999\",\"external_id\":\"KOS-33-1765700495\",\"status\":\"PAID\",\"merchant_name\":\"KosConnect\",\"merchant_profile_picture_url\":\"https:\\/\\/du8nwjtfkinx.cloudfront.net\\/xendit.png\",\"amount\":700000,\"payer_email\":null,\"description\":\"Pembayaran Kos - 104 di Royal Kost\",\"expiry_date\":{\"date\":\"2025-12-15 08:21:33.397000\",\"timezone_type\":2,\"timezone\":\"Z\"},\"invoice_url\":\"https:\\/\\/checkout-staging.xendit.co\\/web\\/693e738d6720fe660ae47999\",\"should_exclude_credit_card\":false,\"should_send_email\":false,\"created\":{\"date\":\"2025-12-14 08:21:33.559000\",\"timezone_type\":2,\"timezone\":\"Z\"},\"updated\":{\"date\":\"2025-12-14 08:22:09.258000\",\"timezone_type\":2,\"timezone\":\"Z\"},\"currency\":\"IDR\",\"payment_method\":\"EWALLET\",\"payment_channel\":null,\"paid_amount\":0,\"paid_at\":null}', '2025-12-14 08:21:36', '2025-12-14 08:28:09'),
(3, 33, 'KOS-33-1765700568', '693e73d68b4c49fa0fee0148', 'https://checkout-staging.xendit.co/web/693e73d68b4c49fa0fee0148', 700000.00, 'SETTLED', 'EWALLET', NULL, 0.00, NULL, '2025-12-15 08:22:49', '{\"id\":\"693e73d68b4c49fa0fee0148\",\"external_id\":\"KOS-33-1765700568\",\"status\":\"SETTLED\",\"merchant_name\":\"KosConnect\",\"merchant_profile_picture_url\":\"https:\\/\\/du8nwjtfkinx.cloudfront.net\\/xendit.png\",\"amount\":700000,\"payer_email\":null,\"description\":\"Pembayaran Kos - 104 di Royal Kost\",\"expiry_date\":{\"date\":\"2025-12-15 08:22:46.312000\",\"timezone_type\":2,\"timezone\":\"Z\"},\"invoice_url\":\"https:\\/\\/checkout-staging.xendit.co\\/web\\/693e73d68b4c49fa0fee0148\",\"should_exclude_credit_card\":false,\"should_send_email\":false,\"created\":{\"date\":\"2025-12-14 08:22:46.457000\",\"timezone_type\":2,\"timezone\":\"Z\"},\"updated\":{\"date\":\"2025-12-14 08:24:32.353000\",\"timezone_type\":2,\"timezone\":\"Z\"},\"currency\":\"IDR\",\"payment_method\":\"EWALLET\",\"payment_channel\":null,\"paid_amount\":0,\"paid_at\":null}', '2025-12-14 08:22:49', '2025-12-14 08:28:10'),
(4, 33, 'KOS-33-1765700694', '693e74556720fe660ae47a27', 'https://checkout-staging.xendit.co/web/693e74556720fe660ae47a27', 700000.00, 'PAID', 'EWALLET', NULL, 0.00, NULL, '2025-12-15 08:24:55', '{\"id\":\"693e74556720fe660ae47a27\",\"external_id\":\"KOS-33-1765700694\",\"status\":\"PAID\",\"merchant_name\":\"KosConnect\",\"merchant_profile_picture_url\":\"https:\\/\\/du8nwjtfkinx.cloudfront.net\\/xendit.png\",\"amount\":700000,\"payer_email\":null,\"description\":\"Pembayaran Kos - 104 di Royal Kost\",\"expiry_date\":{\"date\":\"2025-12-15 08:24:53.027000\",\"timezone_type\":2,\"timezone\":\"Z\"},\"invoice_url\":\"https:\\/\\/checkout-staging.xendit.co\\/web\\/693e74556720fe660ae47a27\",\"should_exclude_credit_card\":false,\"should_send_email\":false,\"created\":{\"date\":\"2025-12-14 08:24:53.195000\",\"timezone_type\":2,\"timezone\":\"Z\"},\"updated\":{\"date\":\"2025-12-14 08:25:29.015000\",\"timezone_type\":2,\"timezone\":\"Z\"},\"currency\":\"IDR\",\"payment_method\":\"EWALLET\",\"payment_channel\":null,\"paid_amount\":0,\"paid_at\":null}', '2025-12-14 08:24:55', '2025-12-14 08:28:10'),
(5, 33, 'KOS-33-1765700841', '693e74e76720fe660ae47a9d', 'https://checkout-staging.xendit.co/web/693e74e76720fe660ae47a9d', 700000.00, 'PAID', 'EWALLET', NULL, 0.00, NULL, '2025-12-15 08:27:22', '{\"id\":\"693e74e76720fe660ae47a9d\",\"external_id\":\"KOS-33-1765700841\",\"status\":\"PAID\",\"merchant_name\":\"KosConnect\",\"merchant_profile_picture_url\":\"https:\\/\\/du8nwjtfkinx.cloudfront.net\\/xendit.png\",\"amount\":700000,\"payer_email\":null,\"description\":\"Pembayaran Kos - 104 di Royal Kost\",\"expiry_date\":{\"date\":\"2025-12-15 08:27:19.411000\",\"timezone_type\":2,\"timezone\":\"Z\"},\"invoice_url\":\"https:\\/\\/checkout-staging.xendit.co\\/web\\/693e74e76720fe660ae47a9d\",\"should_exclude_credit_card\":false,\"should_send_email\":false,\"created\":{\"date\":\"2025-12-14 08:27:19.684000\",\"timezone_type\":2,\"timezone\":\"Z\"},\"updated\":{\"date\":\"2025-12-14 08:27:53.741000\",\"timezone_type\":2,\"timezone\":\"Z\"},\"currency\":\"IDR\",\"payment_method\":\"EWALLET\",\"payment_channel\":null,\"paid_amount\":0,\"paid_at\":null}', '2025-12-14 08:27:22', '2025-12-14 08:28:11'),
(6, 30, 'KOS-30-1765733339', '693ef3dd6720fe660ae4e028', 'https://checkout-staging.xendit.co/web/693ef3dd6720fe660ae4e028', 700000.00, 'PAID', 'EWALLET', NULL, 0.00, NULL, '2025-12-15 17:29:07', '{\"id\":\"693ef3dd6720fe660ae4e028\",\"external_id\":\"KOS-30-1765733339\",\"status\":\"PAID\",\"merchant_name\":\"KosConnect\",\"merchant_profile_picture_url\":\"https:\\/\\/du8nwjtfkinx.cloudfront.net\\/xendit.png\",\"amount\":700000,\"payer_email\":null,\"description\":\"Pembayaran Kos - Kamar Biasa di Capital Kost\",\"expiry_date\":{\"date\":\"2025-12-15 17:29:02.044000\",\"timezone_type\":2,\"timezone\":\"Z\"},\"invoice_url\":\"https:\\/\\/checkout-staging.xendit.co\\/web\\/693ef3dd6720fe660ae4e028\",\"should_exclude_credit_card\":false,\"should_send_email\":false,\"created\":{\"date\":\"2025-12-14 17:29:02.232000\",\"timezone_type\":2,\"timezone\":\"Z\"},\"updated\":{\"date\":\"2025-12-14 17:29:45.162000\",\"timezone_type\":2,\"timezone\":\"Z\"},\"currency\":\"IDR\",\"payment_method\":\"EWALLET\",\"payment_channel\":null,\"paid_amount\":0,\"paid_at\":null}', '2025-12-14 17:29:07', '2025-12-14 17:29:53'),
(7, 34, 'KOS-34-1765763318', '693f68f56720fe660ae576aa', 'https://checkout-staging.xendit.co/web/693f68f56720fe660ae576aa', 1500000.00, 'PAID', 'QR_CODE', NULL, 0.00, NULL, '2025-12-16 01:48:41', '{\"id\":\"693f68f56720fe660ae576aa\",\"external_id\":\"KOS-34-1765763318\",\"status\":\"PAID\",\"merchant_name\":\"KosConnect\",\"merchant_profile_picture_url\":\"https:\\/\\/du8nwjtfkinx.cloudfront.net\\/xendit.png\",\"amount\":1500000,\"payer_email\":null,\"description\":\"Pembayaran Kos - Kamar VIP di Capital Kost\",\"expiry_date\":{\"date\":\"2025-12-16 01:48:37.517000\",\"timezone_type\":2,\"timezone\":\"Z\"},\"invoice_url\":\"https:\\/\\/checkout-staging.xendit.co\\/web\\/693f68f56720fe660ae576aa\",\"should_exclude_credit_card\":false,\"should_send_email\":false,\"created\":{\"date\":\"2025-12-15 01:48:37.539000\",\"timezone_type\":2,\"timezone\":\"Z\"},\"updated\":{\"date\":\"2025-12-15 01:48:58.840000\",\"timezone_type\":2,\"timezone\":\"Z\"},\"currency\":\"IDR\",\"payment_method\":\"QR_CODE\",\"payment_channel\":null,\"paid_amount\":0,\"paid_at\":null}', '2025-12-15 01:48:41', '2025-12-15 01:49:09'),
(8, 24, 'KOS-24-1765859344', '6940e0106720fe660ae8175b', 'https://checkout-staging.xendit.co/web/6940e0106720fe660ae8175b', 1500000.00, 'SETTLED', 'BANK_TRANSFER', NULL, 0.00, NULL, '2025-12-17 04:29:06', '{\"id\":\"6940e0106720fe660ae8175b\",\"external_id\":\"KOS-24-1765859344\",\"status\":\"SETTLED\",\"merchant_name\":\"KosConnect\",\"merchant_profile_picture_url\":\"https:\\/\\/du8nwjtfkinx.cloudfront.net\\/xendit.png\",\"amount\":1500000,\"payer_email\":null,\"description\":\"Pembayaran Kos - Superior Double di Capital Kost\",\"expiry_date\":{\"date\":\"2025-12-17 04:29:04.735000\",\"timezone_type\":2,\"timezone\":\"Z\"},\"invoice_url\":\"https:\\/\\/checkout-staging.xendit.co\\/web\\/6940e0106720fe660ae8175b\",\"should_exclude_credit_card\":false,\"should_send_email\":false,\"created\":{\"date\":\"2025-12-16 04:29:04.856000\",\"timezone_type\":2,\"timezone\":\"Z\"},\"updated\":{\"date\":\"2025-12-16 04:29:31.569000\",\"timezone_type\":2,\"timezone\":\"Z\"},\"currency\":\"IDR\",\"payment_method\":\"BANK_TRANSFER\",\"payment_channel\":null,\"paid_amount\":0,\"paid_at\":null}', '2025-12-16 04:29:06', '2025-12-16 04:29:46'),
(9, 35, 'KOS-35-1765863545', '6940f0796720fe660ae829c2', 'https://checkout-staging.xendit.co/web/6940f0796720fe660ae829c2', 700000.00, 'PAID', 'EWALLET', NULL, 0.00, NULL, '2025-12-17 05:39:09', '{\"id\":\"6940f0796720fe660ae829c2\",\"external_id\":\"KOS-35-1765863545\",\"status\":\"PAID\",\"merchant_name\":\"KosConnect\",\"merchant_profile_picture_url\":\"https:\\/\\/du8nwjtfkinx.cloudfront.net\\/xendit.png\",\"amount\":700000,\"payer_email\":null,\"description\":\"Pembayaran Kos - Kamar 01 di Kost Halia Nur Tipe A Biringkanaya Makassar\",\"expiry_date\":{\"date\":\"2025-12-17 05:39:05.409000\",\"timezone_type\":2,\"timezone\":\"Z\"},\"invoice_url\":\"https:\\/\\/checkout-staging.xendit.co\\/web\\/6940f0796720fe660ae829c2\",\"should_exclude_credit_card\":false,\"should_send_email\":false,\"created\":{\"date\":\"2025-12-16 05:39:05.774000\",\"timezone_type\":2,\"timezone\":\"Z\"},\"updated\":{\"date\":\"2025-12-16 05:39:20.097000\",\"timezone_type\":2,\"timezone\":\"Z\"},\"currency\":\"IDR\",\"payment_method\":\"EWALLET\",\"payment_channel\":null,\"paid_amount\":0,\"paid_at\":null}', '2025-12-16 05:39:09', '2025-12-16 05:39:26'),
(10, 36, 'KOS-36-1765984663', '6942c995bcf52f63c4d19868', 'https://checkout-staging.xendit.co/web/6942c995bcf52f63c4d19868', 700000.00, 'PAID', 'EWALLET', NULL, 0.00, NULL, '2025-12-18 15:17:45', '{\"id\":\"6942c995bcf52f63c4d19868\",\"external_id\":\"KOS-36-1765984663\",\"status\":\"PAID\",\"merchant_name\":\"KosConnect\",\"merchant_profile_picture_url\":\"https:\\/\\/du8nwjtfkinx.cloudfront.net\\/xendit.png\",\"amount\":700000,\"payer_email\":null,\"description\":\"Pembayaran Kos - 103 di Kost Pondok Pelangi Tipe A1 Tamalanrea Makassar\",\"expiry_date\":{\"date\":\"2025-12-18 15:17:42.113000\",\"timezone_type\":2,\"timezone\":\"Z\"},\"invoice_url\":\"https:\\/\\/checkout-staging.xendit.co\\/web\\/6942c995bcf52f63c4d19868\",\"should_exclude_credit_card\":false,\"should_send_email\":false,\"created\":{\"date\":\"2025-12-17 15:17:42.140000\",\"timezone_type\":2,\"timezone\":\"Z\"},\"updated\":{\"date\":\"2025-12-17 15:18:02.597000\",\"timezone_type\":2,\"timezone\":\"Z\"},\"currency\":\"IDR\",\"payment_method\":\"EWALLET\",\"payment_channel\":null,\"paid_amount\":0,\"paid_at\":null}', '2025-12-17 15:17:45', '2025-12-17 15:18:10'),
(11, 37, 'KOS-37-1765984865', '6942ca5fbcf52f63c4d1992e', 'https://checkout-staging.xendit.co/web/6942ca5fbcf52f63c4d1992e', 1000000.00, 'PAID', 'EWALLET', NULL, 0.00, NULL, '2025-12-18 15:21:06', '{\"id\":\"6942ca5fbcf52f63c4d1992e\",\"external_id\":\"KOS-37-1765984865\",\"status\":\"PAID\",\"merchant_name\":\"KosConnect\",\"merchant_profile_picture_url\":\"https:\\/\\/du8nwjtfkinx.cloudfront.net\\/xendit.png\",\"amount\":1000000,\"payer_email\":null,\"description\":\"Pembayaran Kos - 106 - Kamar Deluxe di Kost Pondok Pelangi Tipe A1 Tamalanrea Makassar\",\"expiry_date\":{\"date\":\"2025-12-18 15:21:03.553000\",\"timezone_type\":2,\"timezone\":\"Z\"},\"invoice_url\":\"https:\\/\\/checkout-staging.xendit.co\\/web\\/6942ca5fbcf52f63c4d1992e\",\"should_exclude_credit_card\":false,\"should_send_email\":false,\"created\":{\"date\":\"2025-12-17 15:21:03.901000\",\"timezone_type\":2,\"timezone\":\"Z\"},\"updated\":{\"date\":\"2025-12-17 15:21:20.595000\",\"timezone_type\":2,\"timezone\":\"Z\"},\"currency\":\"IDR\",\"payment_method\":\"EWALLET\",\"payment_channel\":null,\"paid_amount\":0,\"paid_at\":null}', '2025-12-17 15:21:06', '2025-12-17 15:21:33');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `booking`
--
ALTER TABLE `booking`
  ADD PRIMARY KEY (`id_booking`),
  ADD KEY `id_penyewa` (`id_penyewa`),
  ADD KEY `id_kamar` (`id_kamar`);

--
-- Indeks untuk tabel `complaint`
--
ALTER TABLE `complaint`
  ADD PRIMARY KEY (`id_complaint`),
  ADD KEY `id_penyewa` (`id_penyewa`),
  ADD KEY `id_kost` (`id_kost`);

--
-- Indeks untuk tabel `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id_feedback`),
  ADD KEY `id_penyewa` (`id_penyewa`);

--
-- Indeks untuk tabel `kamar`
--
ALTER TABLE `kamar`
  ADD PRIMARY KEY (`id_kamar`),
  ADD KEY `id_kost` (`id_kost`);

--
-- Indeks untuk tabel `kost`
--
ALTER TABLE `kost`
  ADD PRIMARY KEY (`id_kost`),
  ADD KEY `id_pemilik` (`id_pemilik`);

--
-- Indeks untuk tabel `kost_photos`
--
ALTER TABLE `kost_photos`
  ADD PRIMARY KEY (`id_photo`),
  ADD KEY `id_kost` (`id_kost`);

--
-- Indeks untuk tabel `midtrans_transactions`
--
ALTER TABLE `midtrans_transactions`
  ADD PRIMARY KEY (`id_transaction`),
  ADD UNIQUE KEY `order_id` (`order_id`),
  ADD KEY `idx_booking` (`id_booking`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_transaction_id` (`transaction_id`),
  ADD KEY `idx_status` (`transaction_status`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_payment_type` (`payment_type`);

--
-- Indeks untuk tabel `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id_notification`),
  ADD KEY `id_user` (`id_user`);

--
-- Indeks untuk tabel `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD PRIMARY KEY (`id_payment`),
  ADD KEY `id_booking` (`id_booking`),
  ADD KEY `idx_payment_gateway` (`payment_gateway`),
  ADD KEY `idx_midtrans_transaction` (`id_midtrans_transaction`),
  ADD KEY `idx_xendit_invoice` (`id_xendit_invoice`);

--
-- Indeks untuk tabel `review`
--
ALTER TABLE `review`
  ADD PRIMARY KEY (`id_review`),
  ADD KEY `id_penyewa` (`id_penyewa`),
  ADD KEY `id_kost` (`id_kost`);

--
-- Indeks untuk tabel `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indeks untuk tabel `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id_session`),
  ADD UNIQUE KEY `session_token` (`session_token`),
  ADD KEY `idx_user` (`id_user`),
  ADD KEY `idx_token` (`session_token`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indeks untuk tabel `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id_wishlist`),
  ADD UNIQUE KEY `unique_user_kost` (`id_user`,`id_kost`),
  ADD KEY `idx_user` (`id_user`),
  ADD KEY `idx_kost` (`id_kost`);

--
-- Indeks untuk tabel `xendit_invoices`
--
ALTER TABLE `xendit_invoices`
  ADD PRIMARY KEY (`id_invoice`),
  ADD UNIQUE KEY `external_id` (`external_id`),
  ADD KEY `idx_booking` (`id_booking`),
  ADD KEY `idx_external_id` (`external_id`),
  ADD KEY `idx_invoice_id` (`invoice_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_payment_method` (`payment_method`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `booking`
--
ALTER TABLE `booking`
  MODIFY `id_booking` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT untuk tabel `complaint`
--
ALTER TABLE `complaint`
  MODIFY `id_complaint` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id_feedback` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `kamar`
--
ALTER TABLE `kamar`
  MODIFY `id_kamar` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT untuk tabel `kost`
--
ALTER TABLE `kost`
  MODIFY `id_kost` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `kost_photos`
--
ALTER TABLE `kost_photos`
  MODIFY `id_photo` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT untuk tabel `midtrans_transactions`
--
ALTER TABLE `midtrans_transactions`
  MODIFY `id_transaction` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id_notification` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=108;

--
-- AUTO_INCREMENT untuk tabel `pembayaran`
--
ALTER TABLE `pembayaran`
  MODIFY `id_payment` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT untuk tabel `review`
--
ALTER TABLE `review`
  MODIFY `id_review` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `user`
--
ALTER TABLE `user`
  MODIFY `id_user` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=150;

--
-- AUTO_INCREMENT untuk tabel `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id_session` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=197;

--
-- AUTO_INCREMENT untuk tabel `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id_wishlist` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `xendit_invoices`
--
ALTER TABLE `xendit_invoices`
  MODIFY `id_invoice` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `booking`
--
ALTER TABLE `booking`
  ADD CONSTRAINT `booking_ibfk_1` FOREIGN KEY (`id_penyewa`) REFERENCES `user` (`id_user`) ON DELETE CASCADE,
  ADD CONSTRAINT `booking_ibfk_2` FOREIGN KEY (`id_kamar`) REFERENCES `kamar` (`id_kamar`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `complaint`
--
ALTER TABLE `complaint`
  ADD CONSTRAINT `complaint_ibfk_1` FOREIGN KEY (`id_penyewa`) REFERENCES `user` (`id_user`) ON DELETE CASCADE,
  ADD CONSTRAINT `complaint_ibfk_2` FOREIGN KEY (`id_kost`) REFERENCES `kost` (`id_kost`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`id_penyewa`) REFERENCES `user` (`id_user`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `kamar`
--
ALTER TABLE `kamar`
  ADD CONSTRAINT `kamar_ibfk_1` FOREIGN KEY (`id_kost`) REFERENCES `kost` (`id_kost`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `kost`
--
ALTER TABLE `kost`
  ADD CONSTRAINT `kost_ibfk_1` FOREIGN KEY (`id_pemilik`) REFERENCES `user` (`id_user`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `kost_photos`
--
ALTER TABLE `kost_photos`
  ADD CONSTRAINT `kost_photos_ibfk_1` FOREIGN KEY (`id_kost`) REFERENCES `kost` (`id_kost`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `midtrans_transactions`
--
ALTER TABLE `midtrans_transactions`
  ADD CONSTRAINT `fk_midtrans_booking` FOREIGN KEY (`id_booking`) REFERENCES `booking` (`id_booking`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD CONSTRAINT `fk_pembayaran_midtrans` FOREIGN KEY (`id_midtrans_transaction`) REFERENCES `midtrans_transactions` (`id_transaction`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_pembayaran_xendit` FOREIGN KEY (`id_xendit_invoice`) REFERENCES `xendit_invoices` (`id_invoice`) ON DELETE SET NULL,
  ADD CONSTRAINT `pembayaran_ibfk_1` FOREIGN KEY (`id_booking`) REFERENCES `booking` (`id_booking`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `review`
--
ALTER TABLE `review`
  ADD CONSTRAINT `review_ibfk_1` FOREIGN KEY (`id_penyewa`) REFERENCES `user` (`id_user`) ON DELETE CASCADE,
  ADD CONSTRAINT `review_ibfk_2` FOREIGN KEY (`id_kost`) REFERENCES `kost` (`id_kost`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`id_kost`) REFERENCES `kost` (`id_kost`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `xendit_invoices`
--
ALTER TABLE `xendit_invoices`
  ADD CONSTRAINT `fk_xendit_booking` FOREIGN KEY (`id_booking`) REFERENCES `booking` (`id_booking`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
