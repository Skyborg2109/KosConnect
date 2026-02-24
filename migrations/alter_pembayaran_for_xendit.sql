-- Migration: Alter Pembayaran Table for Xendit Support
-- Created: 2025-12-13
-- Description: Update tabel pembayaran untuk mendukung Xendit

-- Update enum payment_gateway untuk tambah 'xendit'
ALTER TABLE `pembayaran` 
MODIFY COLUMN `payment_gateway` ENUM('manual', 'midtrans', 'xendit') DEFAULT 'manual' 
COMMENT 'Metode pembayaran: manual upload, Midtrans, atau Xendit';

-- Tambah kolom id_xendit_invoice (Foreign Key)
ALTER TABLE `pembayaran` 
ADD COLUMN `id_xendit_invoice` INT(11) DEFAULT NULL 
COMMENT 'Reference ke tabel xendit_invoices' 
AFTER `id_midtrans_transaction`;

-- Tambah foreign key constraint
ALTER TABLE `pembayaran` 
ADD CONSTRAINT `fk_pembayaran_xendit` 
FOREIGN KEY (`id_xendit_invoice`) 
REFERENCES `xendit_invoices` (`id_invoice`) 
ON DELETE SET NULL;

-- Tambah index untuk performa
CREATE INDEX idx_xendit_invoice ON pembayaran(id_xendit_invoice);
