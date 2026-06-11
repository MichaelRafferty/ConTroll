-- MySQL dump 10.13  Distrib 8.0.44, for macos15 (arm64)
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
  `notesPrompt` varchar(256) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'label for notes input field',
  `notifyList` varchar(512) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `endDate` int NOT NULL DEFAULT '0' COMMENT 'Number of days before start of convention that this interest becomes static',
  `sortOrder` int DEFAULT '0',
  `createDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updateDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updateBy` int DEFAULT NULL,
  `active` enum('Y','N') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Y',
  `csv` enum('Y','N') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'N',
  PRIMARY KEY (`interest`),
  KEY `interests_updatBy_fk` (`updateBy`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


