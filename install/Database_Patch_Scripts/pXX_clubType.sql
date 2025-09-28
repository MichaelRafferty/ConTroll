CREATE TABLE `clubTypes` (
    `id` int NOT NULL AUTO_INCREMENT,
    `clubMemType`  varchar(16)  CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    `description` varchar(4096)  CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    `expires` enum ('No','Years') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    `nYears` int DEFAULT 0,
    `flag` varchar(4) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    `memLabel` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    `sortorder` int NOT NULL DEFAULT '0',
    PRIMARY KEY (id),
    KEY `ct_clubmemtype_fk` (`clubMemType`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `newClub` (
    `id` int NOT NULL AUTO_INCREMENT,
    `perid` int NOT NULL,
    `clubMemType` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    `lastYear` int DEFAULT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `newClub` ADD CONSTRAINT `c_perid_fk` FOREIGN KEY (`perid`) REFERENCES `perinfo` (`id`) ON UPDATE CASCADE ON DELETE CASCADE;
ALTER TABLE `newClub` ADD CONSTRAINT `c_clubmemtype_fk` FOREIGN KEY (`clubMemType`) REFERENCES `clubTypes` (`clubMemType`) ON UPDATE CASCADE;