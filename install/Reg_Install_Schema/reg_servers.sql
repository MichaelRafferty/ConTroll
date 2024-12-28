-- MySQL dump 10.13  Distrib 8.0.34, for macos13 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.40


--
-- Table structure for table `servers`
--

DROP TABLE IF EXISTS `servers`;
CREATE TABLE `servers` (
  `serverName` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `address` varchar(64) COLLATE utf8mb4_general_ci NOT NULL,
  `location` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `active` int NOT NULL DEFAULT '0',
  `local` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`serverName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


