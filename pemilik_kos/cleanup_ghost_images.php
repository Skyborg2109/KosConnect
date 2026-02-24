<?php
include __DIR__ . '/../config/db.php';

header('Content-Type: text/plain');

echo "Cleaning up ghost images...\n";

// 1. Get all photos that are NOT Cloudinary URLs (don't start with http)
$sql = "SELECT id_photo, file_name, category FROM kost_photos WHERE file_name NOT LIKE 'http%'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $id = $row['id_photo'];
        $file = $row['file_name'];
        $localPath = __DIR__ . '/../uploads/kost/' . $file;

        if (!file_exists($localPath)) {
            // File doesn't exist locally, and isn't a URL -> It's a ghost record
            echo "Deleting ID $id ($file) - File not found locally.\n";
            $del = $conn->query("DELETE FROM kost_photos WHERE id_photo = $id");
            if ($del) {
                echo "  -> OK: Deleted.\n";
            } else {
                echo "  -> ERROR: " . $conn->error . "\n";
            }
        } else {
            echo "Skipping ID $id ($file) - File exists.\n";
        }
    }
} else {
    echo "No potential ghost images found (all seem to be URLs).\n";
}

echo "\nDone.";
?>
