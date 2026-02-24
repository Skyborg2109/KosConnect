-- Migration: Alter Pembayaran Table for Midtrans Support
-- Created: 2025-12-13
-- Description: Update tabel pembayaran untuk mendukung payment gateway

-- Tambah kolom payment_gateway
ALTER TABLE `pembayaran` 
ADD COLUMN `payment_gateway` ENUM('manual', 'midtrans') DEFAULT 'manual' 
COMMENT 'Metode pembayaran: manual upload atau via Midtrans' 
AFTER `metode_pembayaran`;

-- Tambah kolom id_midtrans_transaction (Foreign Key)
ALTER TABLE `pembayaran` 
ADD COLUMN `id_midtrans_transaction` INT(11) DEFAULT NULL 
COMMENT 'Reference ke tabel midtrans_transactions' 
AFTER `payment_gateway`;

-- Ubah bukti_pembayaran menjadi nullable (karena Midtrans tidak perlu bukti manual)
ALTER TABLE `pembayaran` 
MODIFY COLUMN `bukti_pembayaran` VARCHAR(255) DEFAULT NULL;

-- Tambah foreign key constraint
ALTER TABLE `pembayaran` 
ADD CONSTRAINT `fk_pembayaran_midtrans` 
FOREIGN KEY (`id_midtrans_transaction`) 
REFERENCES `midtrans_transactions` (`id_transaction`) 
ON DELETE SET NULL;

-- Tambah index untuk performa
CREATE INDEX idx_payment_gateway ON pembayaran(payment_gateway);
CREATE INDEX idx_midtrans_transaction ON pembayaran(id_midtrans_transaction);
