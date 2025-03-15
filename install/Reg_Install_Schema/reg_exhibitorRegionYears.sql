-- MySQL dump 10.13  Distrib 8.0.34, for macos13 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.40


--
-- Table structure for table `exhibitorRegionYears`
--

DROP TABLE IF EXISTS `exhibitorRegionYears`;
CREATE TABLE `exhibitorRegionYears` (
  `id` int NOT NULL AUTO_INCREMENT,
  `exhibitorYearId` int NOT NULL,
  `exhibitsRegionYearId` int NOT NULL,
  `exhibitorNumber` int DEFAULT NULL,
  `locations` varchar(256) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `agentPerid` int DEFAULT NULL,
  `agentNewperson` int DEFAULT NULL,
  `agentRequest` varchar(256) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `approval` enum('none','requested','approved','denied','hide') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'none',
  `specialRequests` text COLLATE utf8mb4_general_ci,
  `updateDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updateBy` int NOT NULL,
  `sortorder` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `exry_eyrid` (`exhibitsRegionYearId`),
  KEY `exry_eyid` (`exhibitorYearId`),
  KEY `exry_agentPerid` (`agentPerid`),
  KEY `exry_agentNewperon` (`agentNewperson`),
  KEY `ecry_updateby_fk` (`updateBy`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


