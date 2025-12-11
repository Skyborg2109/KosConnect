-- Create table untuk menyimpan session/devices user
-- Ini memungkinkan user login di multiple devices secara bersamaan

CREATE TABLE IF NOT EXISTS user_sessions (
    id_session INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    session_token VARCHAR(255) NOT NULL UNIQUE,
    device_name VARCHAR(255),
    user_agent TEXT,
    ip_address VARCHAR(45),
    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT 1,
    FOREIGN KEY (id_user) REFERENCES user(id_user) ON DELETE CASCADE,
    INDEX idx_user (id_user),
    INDEX idx_token (session_token),
    INDEX idx_active (is_active)
);
