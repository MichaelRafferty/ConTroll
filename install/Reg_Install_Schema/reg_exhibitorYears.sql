-- MySQL dump 10.13  Distrib 8.0.34, for macos13 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.32


--
-- Table structure for table `exhibitorYears`
--

DROP TABLE IF EXISTS `exhibitorYears`;
CREATE TABLE `exhibitorYears` (
  `id` int NOT NULL AUTO_INCREMENT,
  `conid` int NOT NULL,
  `exhibitorId` int NOT NULL,
  `contactName` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `contactEmail` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `contactPhone` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `contactPassword` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mailin` enum('N','Y') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'N',
  `artistId` int DEFAULT NULL,
  `need_new` tinyint(1) DEFAULT '1',
  `confirm` tinyint(1) DEFAULT '0',
  `needReview` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `ey_exhibitors_fk` (`exhibitorId`),
  KEY `ey_conlist_fk` (`conid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


