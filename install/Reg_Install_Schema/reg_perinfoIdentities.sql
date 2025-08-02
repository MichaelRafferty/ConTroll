-- MySQL dump 10.13  Distrib 8.0.42, for macos15 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.40


--
-- Table structure for table `perinfoIdentities`
--

DROP TABLE IF EXISTS `perinfoIdentities`;
CREATE TABLE `perinfoIdentities` (
  `perid` int NOT NULL,
  `provider` varchar(16) COLLATE utf8mb4_general_ci NOT NULL,
  `email_addr` varchar(254) COLLATE utf8mb4_general_ci NOT NULL,
  `subscriberID` varchar(254) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `creationTS` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `lastUseTS` timestamp NULL DEFAULT NULL,
  `useCount` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`perid`,`provider`,`email_addr`),
  KEY `perinfoIdent_idx_email` (`email_addr`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


