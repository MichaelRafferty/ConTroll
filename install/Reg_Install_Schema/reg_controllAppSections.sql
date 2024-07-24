-- MySQL dump 10.13  Distrib 8.0.34, for macos13 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.32


--
-- Table structure for table `controllAppSections`
--

DROP TABLE IF EXISTS `controllAppSections`;
CREATE TABLE `controllAppSections` (
  `appName` varchar(16) COLLATE utf8mb4_general_ci NOT NULL,
  `appPage` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `appSection` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `sectionDescription` varchar(4096) COLLATE utf8mb4_general_ci DEFAULT '',
  PRIMARY KEY (`appName`,`appPage`,`appSection`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


