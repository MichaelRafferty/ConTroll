-- MySQL dump 10.13  Distrib 8.0.42, for macos15 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.40


--
-- Table structure for table `memRules`
--

DROP TABLE IF EXISTS `memRules`;
CREATE TABLE `memRules` (
  `name` varchar(16) COLLATE utf8mb4_general_ci NOT NULL,
  `conid` int NOT NULL,
  `optionName` varchar(64) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `typeList` varchar(1024) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `catList` varchar(1024) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ageList` varchar(1024) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `memList` varchar(1024) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`conid`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


