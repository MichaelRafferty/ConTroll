-- MySQL dump 10.13  Distrib 8.0.34, for macos13 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.32


--
-- Table structure for table `vendorSpaces`
--

DROP TABLE IF EXISTS `vendorSpaces`;
CREATE TABLE `vendorSpaces` (
  `id` int NOT NULL AUTO_INCREMENT,
  `conid` int NOT NULL,
  `spaceType` enum('artshow','dealers','fan','virtual') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'dealers',
  `shortname` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(128) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `unitsAvailable` int NOT NULL DEFAULT '0',
  `includedMemId` int NOT NULL,
  `additionalMemId` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `vendorSpace_memList_i` (`includedMemId`),
  KEY `vendorSpace_memList_a` (`additionalMemId`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Dump completed on 2023-12-21 16:26:32
