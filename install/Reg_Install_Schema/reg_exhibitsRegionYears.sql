-- MySQL dump 10.13  Distrib 8.0.34, for macos13 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.40


--
-- Table structure for table `exhibitsRegionYears`
--

DROP TABLE IF EXISTS `exhibitsRegionYears`;
CREATE TABLE `exhibitsRegionYears` (
  `id` int NOT NULL AUTO_INCREMENT,
  `conid` int NOT NULL,
  `exhibitsRegion` int NOT NULL,
  `ownerName` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `ownerEmail` varchar(254) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `includedMemId` int DEFAULT NULL,
  `additionalMemId` int DEFAULT NULL,
  `totalUnitsAvailable` int NOT NULL DEFAULT '0',
  `atconIdBase` int NOT NULL DEFAULT '0',
  `mailinFee` decimal(8,2) NOT NULL DEFAULT '0.00',
  `mailinIdBase` int NOT NULL DEFAULT '0',
  `sortorder` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `ery_memList_a` (`additionalMemId`),
  KEY `ery_memList_i` (`includedMemId`),
  KEY `ery_conlist_fk` (`conid`),
  KEY `ery_exhibitsRegion_fk` (`exhibitsRegion`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


