-- Migration: Add Midtrans Transactions Table
-- Created: 2025-12-13
-- Description: Tabel untuk menyimpan data transaksi Midtrans

CREATE TABLE IF NOT EXISTS `midtrans_transactions` (
  `id_transaction` INT(11) NOT NULL AUTO_INCREMENT,
  `id_booking` INT(11) NOT NULL,
  `order_id` VARCHAR(100) NOT NULL UNIQUE,
  `transaction_id` VARCHAR(100) DEFAULT NULL,
  `gross_amount` DECIMAL(15,2) NOT NULL,
  `payment_type` VARCHAR(50) DEFAULT NULL COMMENT 'bank_transfer, gopay, qris, credit_card, etc',
  `transaction_status` VARCHAR(50) DEFAULT 'pending' COMMENT 'pending, settlement, expire, cancel, deny, refund',
  `transaction_time` DATETIME DEFAULT NULL,
  `settlement_time` DATETIME DEFAULT NULL,
  `snap_token` TEXT DEFAULT NULL,
  `snap_redirect_url` TEXT DEFAULT NULL,
  `va_number` VARCHAR(50) DEFAULT NULL COMMENT 'Virtual Account number jika menggunakan VA',
  `bank` VARCHAR(50) DEFAULT NULL COMMENT 'Bank yang digunakan untuk VA',
  `fraud_status` VARCHAR(50) DEFAULT NULL COMMENT 'accept, challenge, deny',
  `status_code` VARCHAR(10) DEFAULT NULL,
  `status_message` TEXT DEFAULT NULL,
  `metadata` TEXT DEFAULT NULL COMMENT 'JSON data tambahan dari Midtrans',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_transaction`),
  KEY `idx_booking` (`id_booking`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_transaction_id` (`transaction_id`),
  KEY `idx_status` (`transaction_status`),
  CONSTRAINT `fk_midtrans_booking` FOREIGN KEY (`id_booking`) REFERENCES `booking` (`id_booking`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add index for faster queries
CREATE INDEX idx_created_at ON midtrans_transactions(created_at);
CREATE INDEX idx_payment_type ON midtrans_transactions(payment_type);
