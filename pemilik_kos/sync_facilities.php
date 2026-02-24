<?php
/**
 * Script untuk sinkronisasi fasilitas ke semua kamar dengan tipe yang sama
 * Jalankan sekali untuk migrasi data
 */

session_start();
include '../config/db.php';

// Check if user is admin or owner
if (!isset($_SESSION['user_logged_in']) || !in_array($_SESSION['role'], ['admin', 'pemilik'])) {
    die('Unauthorized access');
}

echo "<!DOCTYPE html>
<html>
<head>
    <title>Sinkronisasi Fasilitas Kamar</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #22c55e; padding: 10px; background: #f0fdf4; border-left: 4px solid #22c55e; margin: 10px 0; }
        .error { color: #ef4444; padding: 10px; background: #fef2f2; border-left: 4px solid #ef4444; margin: 10px 0; }
        .info { color: #3b82f6; padding: 10px; background: #eff6ff; border-left: 4px solid #3b82f6; margin: 10px 0; }
        h1 { color: #333; }
        .step { margin: 15px 0; padding: 10px; background: #f9fafb; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f3f4f6; font-weight: bold; }
    </style>
</head>
<body>
<div class='container'>
<h1>🔄 Sinkronisasi Fasilitas Kamar</h1>
<p>Script ini akan menyinkronkan fasilitas dari kamar pertama setiap tipe ke semua kamar lain dengan tipe yang sama.</p>
";

try {
    // Step 1: Ambil semua kos milik user
    $id_pemilik = $_SESSION['user_id'];
    $stmt_kos = $conn->prepare("SELECT id_kost, nama_kost FROM kost WHERE id_pemilik = ?");
    $stmt_kos->bind_param("i", $id_pemilik);
    $stmt_kos->execute();
    $result_kos = $stmt_kos->get_result();
    
    $total_synced = 0;
    $sync_details = [];
    
    while ($kos = $result_kos->fetch_assoc()) {
        $id_kost = $kos['id_kost'];
        $nama_kost = $kos['nama_kost'];
        
        echo "<div class='step'><strong>Memproses Kos:</strong> " . htmlspecialchars($nama_kost) . "</div>";
        
        // Step 2: Ambil semua tipe kamar yang ada di kos ini
        $stmt_types = $conn->prepare("SELECT DISTINCT tipe_kamar FROM kamar WHERE id_kost = ? AND tipe_kamar IS NOT NULL");
        $stmt_types->bind_param("i", $id_kost);
        $stmt_types->execute();
        $result_types = $stmt_types->get_result();
        
        while ($type_row = $result_types->fetch_assoc()) {
            $tipe_kamar = $type_row['tipe_kamar'];
            
            // Step 3: Ambil fasilitas dari kamar pertama dengan tipe ini yang punya fasilitas
            $stmt_get_fasilitas = $conn->prepare(
                "SELECT fasilitas FROM kamar 
                 WHERE id_kost = ? AND tipe_kamar = ? AND fasilitas IS NOT NULL AND fasilitas != '' 
                 LIMIT 1"
            );
            $stmt_get_fasilitas->bind_param("is", $id_kost, $tipe_kamar);
            $stmt_get_fasilitas->execute();
            $result_fasilitas = $stmt_get_fasilitas->get_result();
            
            if ($result_fasilitas->num_rows > 0) {
                $fasilitas_row = $result_fasilitas->fetch_assoc();
                $fasilitas = $fasilitas_row['fasilitas'];
                
                // Step 4: Update kost_room_types
                $stmt_update_type = $conn->prepare(
                    "UPDATE kost_room_types SET fasilitas = ? WHERE nama_tipe = ? AND id_kost = ?"
                );
                if ($stmt_update_type) {
                    $stmt_update_type->bind_param("ssi", $fasilitas, $tipe_kamar, $id_kost);
                    $stmt_update_type->execute();
                    $stmt_update_type->close();
                }
                
                // Step 5: Update semua kamar dengan tipe yang sama
                $stmt_update_rooms = $conn->prepare(
                    "UPDATE kamar SET fasilitas = ? WHERE tipe_kamar = ? AND id_kost = ?"
                );
                $stmt_update_rooms->bind_param("ssi", $fasilitas, $tipe_kamar, $id_kost);
                $stmt_update_rooms->execute();
                $affected = $stmt_update_rooms->affected_rows;
                $stmt_update_rooms->close();
                
                $total_synced += $affected;
                $sync_details[] = [
                    'kos' => $nama_kost,
                    'tipe' => $tipe_kamar,
                    'fasilitas' => $fasilitas,
                    'rooms' => $affected
                ];
                
                echo "<div class='info'>
                    ✓ Tipe: <strong>" . htmlspecialchars($tipe_kamar) . "</strong><br>
                    Fasilitas: " . htmlspecialchars($fasilitas) . "<br>
                    Kamar yang diupdate: $affected
                </div>";
            }
            
            $stmt_get_fasilitas->close();
        }
        
        $stmt_types->close();
    }
    
    $stmt_kos->close();
    
    // Summary
    echo "<div class='success'>
        <strong>✅ Sinkronisasi Selesai!</strong><br>
        Total kamar yang diupdate: $total_synced
    </div>";
    
    if (count($sync_details) > 0) {
        echo "<h3>Detail Sinkronisasi:</h3>";
        echo "<table>
            <tr>
                <th>Kos</th>
                <th>Tipe Kamar</th>
                <th>Fasilitas</th>
                <th>Kamar Diupdate</th>
            </tr>";
        
        foreach ($sync_details as $detail) {
            echo "<tr>
                <td>" . htmlspecialchars($detail['kos']) . "</td>
                <td>" . htmlspecialchars($detail['tipe']) . "</td>
                <td>" . htmlspecialchars(substr($detail['fasilitas'], 0, 50)) . "...</td>
                <td>" . $detail['rooms'] . "</td>
            </tr>";
        }
        
        echo "</table>";
    }
    
    echo "<div class='info'>
        <strong>Langkah Selanjutnya:</strong><br>
        1. Refresh halaman dashboard Anda<br>
        2. Buka modal 'Kelola Kamar'<br>
        3. Klik Edit pada kamar manapun<br>
        4. Fasilitas sekarang akan muncul di textarea
    </div>";
    
} catch (Exception $e) {
    echo "<div class='error'><strong>Error:</strong> " . $e->getMessage() . "</div>";
}

$conn->close();

echo "
<p style='margin-top: 30px; text-align: center;'>
    <a href='../dashboard/dashboardpemilik.php' style='display: inline-block; background: #9333ea; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: bold;'>
        ← Kembali ke Dashboard
    </a>
</p>
</div>
</body>
</html>";
?>
