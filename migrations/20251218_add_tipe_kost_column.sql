ALTER TABLE `kost` ADD COLUMN `tipe_kost` ENUM('Putra', 'Putri', 'Campuran') DEFAULT 'Campuran' AFTER `harga`;
