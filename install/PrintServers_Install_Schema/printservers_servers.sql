-- MySQL dump 10.13  Distrib 8.0.42, for macos15 (arm64)
--
-- Host: localhost    Database: printservers
-- ------------------------------------------------------
-- Server version	8.0.40

--
-- Table structure for table `servers`
--

DROP TABLE IF EXISTS `servers`;
CREATE TABLE `servers` (
  `serverName` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `address` varchar(64) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`serverName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
