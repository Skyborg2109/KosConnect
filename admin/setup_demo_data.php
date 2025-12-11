<?php
include '../config/db.php';
// Disable strict mode for this insertion just in case
$conn->query("SET sql_mode = ''");

echo "<h2>Setup Demo Data</h2>";

// 1. Get/Create User
$res_user = $conn->query("SELECT id_user FROM user WHERE role='penyewa' LIMIT 1");
if ($res_user && $res_user->num_rows > 0) {
    $id_penyewa = $res_user->fetch_assoc()['id_user'];
    echo "Using existing Penyewa ID: $id_penyewa<br>";
} else {
    $conn->query("INSERT INTO user (nama_lengkap, email, password, role) VALUES ('Penyewa Demo', 'demo@example.com', '123', 'penyewa')");
    $id_penyewa = $conn->insert_id;
    echo "Created Penyewa ID: $id_penyewa<br>";
}

// 2. Get/Create Kost & Kamar (Need for booking constraint)
// Check if table KAMAR exists and has data
$res_kamar = $conn->query("SELECT id_kamar FROM kamar LIMIT 1");
if ($res_kamar && $res_kamar->num_rows > 0) {
    $id_kamar = $res_kamar->fetch_assoc()['id_kamar'];
    echo "Using existing Kamar ID: $id_kamar<br>";
} else {
    // Need to create complete chain: User(Owner) -> Kost -> Kamar
    // Simplify: Assume at least 1 kamar exists or try to insert without constraints if loose
    die("Error: No Kamar found. Please create a Kost and Kamar manually first, or use the app.");
}

// 3. Insert Booking
// Schema indicates: id_booking, id_penyewa, id_kamar, tanggal_booking, status
$conn->query("INSERT INTO booking (id_penyewa, id_kamar, tanggal_booking, status) VALUES ($id_penyewa, $id_kamar, NOW(), 'dibayar')");
$id_booking = $conn->insert_id;
echo "Created Booking ID: $id_booking<br>";

// 4. Insert Payments
$amounts = [500000, 750000, 600000, 1000000, 850000, 1200000];
$monthsID = ['Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

echo "<h3>Inserting Payments...</h3>";
for ($i = 0; $i < 6; $i++) {
    $month_offset = 5 - $i; 
    $date = date('Y-m-15', strtotime("-$month_offset months"));
    $amount = $amounts[$i];
    
    // Schema: id_pembayaran, id_booking, bukti_pembayaran, jumlah, tanggal_pembayaran, status_pembayaran
    $sql = "INSERT INTO pembayaran (id_booking, bukti_pembayaran, jumlah, tanggal_pembayaran, status_pembayaran) 
            VALUES ($id_booking, 'demo.jpg', $amount, '$date', 'disetujui')";
            
    if ($conn->query($sql)) {
        echo "Inserted: Rp " . number_format($amount) . " on $date (OK)<br>";
    } else {
        echo "Error: " . $conn->error . "<br>";
    }
}

echo "<br><b>Selesai! Silakan refresh dashboard admin.</b>";
?>
