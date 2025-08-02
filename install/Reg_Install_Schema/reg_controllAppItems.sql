-- MySQL dump 10.13  Distrib 8.0.42, for macos15 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.40


--
-- Table structure for table `controllAppItems`
--

DROP TABLE IF EXISTS `controllAppItems`;
CREATE TABLE `controllAppItems` (
  `appName` varchar(16) COLLATE utf8mb4_general_ci NOT NULL,
  `appPage` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `appSection` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `txtItem` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `txtItemDescription` varchar(4096) COLLATE utf8mb4_general_ci DEFAULT '',
  PRIMARY KEY (`appName`,`appPage`,`appSection`,`txtItem`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


