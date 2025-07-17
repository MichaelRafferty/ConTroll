-- MySQL dump 10.13  Distrib 8.0.42, for macos15 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.40


--
-- Table structure for table `controllAppPages`
--

DROP TABLE IF EXISTS `controllAppPages`;
CREATE TABLE `controllAppPages` (
  `appName` varchar(16) COLLATE utf8mb4_general_ci NOT NULL,
  `appPage` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `pageDescription` varchar(4096) COLLATE utf8mb4_general_ci DEFAULT '',
  PRIMARY KEY (`appName`,`appPage`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


