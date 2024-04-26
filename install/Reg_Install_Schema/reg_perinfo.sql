-- MySQL dump 10.13  Distrib 8.0.34, for macos13 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.32


--
-- Table structure for table `perinfo`
--

DROP TABLE IF EXISTS `perinfo`;
CREATE TABLE `perinfo` (
  `id` int NOT NULL AUTO_INCREMENT,
  `last_name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `first_name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `middle_name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `suffix` varchar(4) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email_addr` varchar(254) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `badge_name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `legalName` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `address` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `addr_2` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `city` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `state` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `zip` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `country` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `banned` enum('N','Y') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `creation_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `change_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `active` enum('N','Y') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `open_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `admin_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `old_perid` int DEFAULT NULL,
  `contact_ok` enum('N','Y') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'N',
  `share_reg_ok` enum('N','Y') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'N',
  PRIMARY KEY (`id`),
  KEY `perinfi_old_perid_fk` (`old_perid`)
) ENGINE=InnoDB AUTO_INCREMENT=100 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DELIMITER ;;
CREATE DEFINER=CURRENT_USER  TRIGGER `perinfo_update` BEFORE UPDATE ON `perinfo` FOR EACH ROW BEGIN
    INSERT INTO perinfoHistory(id, last_name, first_name, middle_name, suffix, email_addr, phone, badge_name, legalName,
       address, addr_2, city, state, zip, country, banned, creation_date, update_date, change_notes, active,
       open_notes, admin_notes, old_perid, contact_ok, share_reg_ok)
    VALUES (OLD.id, OLD.last_name, OLD.first_name, OLD.middle_name, OLD.suffix, OLD.email_addr, OLD.phone, OLD.badge_name, OLD.legalName,
        OLD.address, OLD.addr_2, OLD.city, OLD.state, OLD.zip, OLD.country, OLD.banned, OLD.creation_date, OLD.update_date, OLD.change_notes,
        OLD.active, OLD.open_notes, OLD.admin_notes, OLD.old_perid, OLD.contact_ok, OLD.share_reg_ok);
END;;
DELIMITER ;


