-- Migration: add missing columns to kost table
-- Adds 'status_kos' and 'gambar' columns to the kost table
-- Run this in MySQL (phpMyAdmin, mysql CLI, or any DB admin tool) while the application is not performing writes.

ALTER TABLE kost
  ADD COLUMN status_kos ENUM('tersedia','tidak_tersedia') DEFAULT 'tersedia' AFTER fasilitas,
  ADD COLUMN gambar VARCHAR(255) DEFAULT NULL AFTER status_kos;

-- Note: Always back up your database before running ALTER statements.
