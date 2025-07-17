-- MySQL dump 10.13  Distrib 8.0.42, for macos15 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.40


--
-- Table structure for table `exhibitorYears`
--

DROP TABLE IF EXISTS `exhibitorYears`;
CREATE TABLE `exhibitorYears` (
  `id` int NOT NULL AUTO_INCREMENT,
  `conid` int NOT NULL,
  `exhibitorId` int NOT NULL,
  `contactName` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `contactEmail` varchar(254) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `contactPhone` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `contactPassword` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mailin` enum('N','Y') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'N',
  `mailinFeePaidAmount` decimal(8,2) DEFAULT NULL,
  `mailinFeeTransaction` int DEFAULT NULL,
  `need_new` tinyint(1) DEFAULT '1',
  `lastVerified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`),
  KEY `ey_exhibitors_fk` (`exhibitorId`),
  KEY `ey_conlist_fk` (`conid`),
  KEY `ey_mailintrans` (`mailinFeeTransaction`),
  KEY `exhibitorYears_idx_email` (`contactEmail`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


