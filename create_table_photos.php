<?php
include 'config/db.php';

$sql = "CREATE TABLE IF NOT EXISTS kost_photos (
    id_photo INT AUTO_INCREMENT PRIMARY KEY,
    id_kost INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_kost) REFERENCES kost(id_kost) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Table kost_photos created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?>
