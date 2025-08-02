-- MySQL dump 10.13  Distrib 8.0.42, for macos15 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.40


--
-- Table structure for table `interests`
--

DROP TABLE IF EXISTS `interests`;
CREATE TABLE `interests` (
  `interest` varchar(16) COLLATE utf8mb4_general_ci NOT NULL,
  `description` varchar(4096) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `notifyList` varchar(512) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sortOrder` int DEFAULT '0',
  `createDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updateDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updateBy` int DEFAULT NULL,
  `active` enum('Y','N') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Y',
  `csv` enum('Y','N') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'N',
  PRIMARY KEY (`interest`),
  KEY `interests_updatBy_fk` (`updateBy`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


