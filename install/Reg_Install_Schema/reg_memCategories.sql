-- MySQL dump 10.13  Distrib 8.0.34, for macos13 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.40


--
-- Table structure for table `memCategories`
--

DROP TABLE IF EXISTS `memCategories`;
CREATE TABLE `memCategories` (
  `memCategory` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `notes` varchar(1024) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `onlyOne` enum('Y','N') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Y',
  `standAlone` enum('Y','N') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'N',
  `variablePrice` enum('Y','N') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'N',
  `sortorder` int NOT NULL DEFAULT '0',
  `active` enum('N','Y') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'Y',
  `badgeLabel` varchar(16) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'X',
  PRIMARY KEY (`memCategory`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


