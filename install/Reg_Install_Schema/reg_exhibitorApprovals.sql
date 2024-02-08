-- MySQL dump 10.13  Distrib 8.0.34, for macos13 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.32


--
-- Table structure for table `exhibitorApprovals`
--

DROP TABLE IF EXISTS `exhibitorApprovals`;
CREATE TABLE `exhibitorApprovals` (
  `id` int NOT NULL AUTO_INCREMENT,
  `exhibitorId` int NOT NULL,
  `exhibitsRegionYearId` int NOT NULL,
  `approval` enum('none','requested','approved','denied','hide') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'none',
  `updateDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updateBy` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ea_exhibitor_fk` (`exhibitorId`),
  KEY `ea_regionYear_fk` (`exhibitsRegionYearId`),
  KEY `ea_updateby_fk` (`updateBy`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


