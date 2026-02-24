-- Migration: Add fasilitas column to kamar table
-- Date: 2025-12-18

ALTER TABLE `kamar` 
ADD COLUMN `fasilitas` TEXT DEFAULT NULL 
AFTER `tipe_kamar`;
