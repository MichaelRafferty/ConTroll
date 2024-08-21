-- MySQL dump 10.13  Distrib 8.0.34, for macos13 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.32


--
-- Table structure for table `memTypes`
--

DROP TABLE IF EXISTS `memTypes`;
CREATE TABLE `memTypes` (
  `memType` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `notes` varchar(1024) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sortorder` int NOT NULL DEFAULT '0',
  `active` enum('N','Y') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'Y',
  PRIMARY KEY (`memType`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


