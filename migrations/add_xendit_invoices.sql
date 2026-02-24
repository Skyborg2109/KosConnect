-- Migration: Add Xendit Invoices Table
-- Created: 2025-12-13
-- Description: Tabel untuk menyimpan data invoice Xendit

CREATE TABLE IF NOT EXISTS `xendit_invoices` (
  `id_invoice` INT(11) NOT NULL AUTO_INCREMENT,
  `id_booking` INT(11) NOT NULL,
  `external_id` VARCHAR(100) NOT NULL UNIQUE COMMENT 'Unique ID untuk Xendit',
  `invoice_id` VARCHAR(100) DEFAULT NULL COMMENT 'Xendit invoice ID',
  `invoice_url` TEXT DEFAULT NULL COMMENT 'URL halaman pembayaran',
  `amount` DECIMAL(15,2) NOT NULL,
  `status` VARCHAR(50) DEFAULT 'PENDING' COMMENT 'PENDING, PAID, EXPIRED, CANCELLED',
  `payment_method` VARCHAR(50) DEFAULT NULL COMMENT 'BANK_TRANSFER, EWALLET, RETAIL_OUTLET, etc',
  `payment_channel` VARCHAR(50) DEFAULT NULL COMMENT 'BCA, MANDIRI, OVO, DANA, etc',
  `paid_amount` DECIMAL(15,2) DEFAULT NULL,
  `paid_at` DATETIME DEFAULT NULL,
  `expired_at` DATETIME DEFAULT NULL,
  `callback_data` TEXT DEFAULT NULL COMMENT 'JSON data dari callback',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_invoice`),
  KEY `idx_booking` (`id_booking`),
  KEY `idx_external_id` (`external_id`),
  KEY `idx_invoice_id` (`invoice_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_xendit_booking` FOREIGN KEY (`id_booking`) REFERENCES `booking` (`id_booking`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add indexes for faster queries
CREATE INDEX idx_created_at ON xendit_invoices(created_at);
CREATE INDEX idx_payment_method ON xendit_invoices(payment_method);
