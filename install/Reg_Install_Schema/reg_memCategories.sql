-- MySQL dump 10.13  Distrib 8.0.34, for macos13 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.32


--
-- Table structure for table `memCategories`
--

DROP TABLE IF EXISTS `memCategories`;
CREATE TABLE `memCategories` (
  `memCategory` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `sortorder` int NOT NULL DEFAULT '0',
  `active` enum('N','Y') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'Y',
  `badgeLabel` varchar(16) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'X',
  PRIMARY KEY (`memCategory`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


