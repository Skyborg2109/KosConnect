CREATE TABLE IF NOT EXISTS `kost_room_types` (
    `id_tipe` INT(11) NOT NULL AUTO_INCREMENT,
    `id_kost` INT(11) NOT NULL,
    `nama_tipe` VARCHAR(100) NOT NULL,
    `foto_tipe` VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (`id_tipe`),
    KEY `id_kost` (`id_kost`),
    CONSTRAINT `fk_room_type_kost` FOREIGN KEY (`id_kost`) REFERENCES `kost` (`id_kost`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
