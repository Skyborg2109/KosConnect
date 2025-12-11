-- Migration: add missing booking status values used by application
-- Adds 'menunggu_pembayaran' and 'ditolak' to the booking.status enum
-- Run this in MySQL (phpMyAdmin, mysql CLI, or any DB admin tool) while the application is not performing writes.

ALTER TABLE booking
  MODIFY status ENUM('pending','menunggu_pembayaran','dibayar','selesai','ditolak','batal')
  DEFAULT 'pending';

-- Note: adjust the list order as needed. Always back up your database before running ALTER statements.
