-- MySQL dump 10.13  Distrib 8.0.42, for macos15 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.40


--
-- Table structure for table `exhibitors`
--

DROP TABLE IF EXISTS `exhibitors`;
CREATE TABLE `exhibitors` (
  `id` int NOT NULL AUTO_INCREMENT,
  `perid` int DEFAULT NULL,
  `newperid` int DEFAULT NULL,
  `artistName` varchar(128) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `exhibitorName` varchar(64) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `exhibitorEmail` varchar(254) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `exhibitorPhone` varchar(32) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `salesTaxId` varchar(32) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `website` varchar(256) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `description` text COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(64) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `need_new` tinyint(1) DEFAULT '1',
  `publicity` tinyint(1) DEFAULT '0',
  `addr` varchar(64) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `addr2` varchar(64) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `city` varchar(32) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `state` varchar(16) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `zip` varchar(10) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `country` varchar(3) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `shipCompany` varchar(64) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `shipAddr` varchar(64) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `shipAddr2` varchar(64) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `shipCity` varchar(64) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `shipState` varchar(16) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `shipZip` varchar(10) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `shipCountry` varchar(3) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `archived` enum('N','Y') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'N',
  `notes` text COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `exhibitor_perid_fk` (`perid`),
  KEY `exhibitors_newperson_fk` (`newperid`),
  KEY `exhibitors_idx_email` (`exhibitorEmail`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


