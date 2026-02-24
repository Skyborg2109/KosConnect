-- Migration to add room photo column
ALTER TABLE `kamar` ADD COLUMN `foto` VARCHAR(255) DEFAULT NULL AFTER `status`;
