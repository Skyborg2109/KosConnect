<?php
include 'config/db.php';

// 1. Find Kos ID
$search = "Halia Nur";
$stmt = $conn->prepare("SELECT id_kost, nama_kost FROM kost WHERE nama_kost LIKE ?");
$term = "%$search%";
$stmt->bind_param("s", $term);
$stmt->execute();
$res = $stmt->get_result();

$kosts = [];
while ($row = $res->fetch_assoc()) {
    $kosts[] = $row;
}

if (empty($kosts)) {
    echo "No kos found matching '$search'\n";
    // Try broader search
    $stmt = $conn->query("SELECT id_kost, nama_kost FROM kost LIMIT 10");
    echo "First 10 kosts:\n";
    while ($row = $stmt->fetch_assoc()) {
        echo "ID: " . $row['id_kost'] . " - " . $row['nama_kost'] . "\n";
    }
    exit;
}

foreach ($kosts as $k) {
    echo "Found Kos: " . $k['nama_kost'] . " (ID: " . $k['id_kost'] . ")\n";
    $id_kost = $k['id_kost'];
    
    // 2. List Photos
    echo "Photos:\n";
    $stmt_p = $conn->prepare("SELECT id_photo, file_name, category FROM kost_photos WHERE id_kost = ?");
    $stmt_p->bind_param("i", $id_kost);
    $stmt_p->execute();
    $res_p = $stmt_p->get_result();
    
    while ($p = $res_p->fetch_assoc()) {
        echo "  [ID: " . $p['id_photo'] . "] " . $p['file_name'] . " (" . $p['category'] . ")\n";
    }
    echo "-------------------\n";
}
?>
