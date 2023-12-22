-- MySQL dump 10.13  Distrib 8.0.34, for macos13 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.32


--
-- Table structure for table `vendorSpacePrices`
--

DROP TABLE IF EXISTS `vendorSpacePrices`;
CREATE TABLE `vendorSpacePrices` (
  `id` int NOT NULL AUTO_INCREMENT,
  `spaceId` int NOT NULL,
  `code` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `description` varchar(64) COLLATE utf8mb4_general_ci NOT NULL,
  `units` decimal(4,2) DEFAULT '1.00',
  `price` decimal(8,2) NOT NULL,
  `includedMemberships` int NOT NULL DEFAULT '0',
  `additionalMemberships` int NOT NULL DEFAULT '0',
  `requestable` tinyint DEFAULT '1',
  `sortOrder` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `vendorSpacePrices_space` (`spaceId`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Dump completed on 2023-12-21 16:26:31
