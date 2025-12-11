-- Create wishlist table for saving favorite kos
CREATE TABLE IF NOT EXISTS wishlist (
    id_wishlist INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    id_kost INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_kost (id_user, id_kost),
    FOREIGN KEY (id_user) REFERENCES user(id_user) ON DELETE CASCADE,
    FOREIGN KEY (id_kost) REFERENCES kost(id_kost) ON DELETE CASCADE,
    INDEX idx_user (id_user),
    INDEX idx_kost (id_kost)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
