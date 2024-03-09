-- MySQL dump 10.13  Distrib 8.0.34, for macos13 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.32


--
-- Table structure for table `exhibitsSpacePrices`
--

DROP TABLE IF EXISTS `exhibitsSpacePrices`;
CREATE TABLE `exhibitsSpacePrices` (
  `id` int NOT NULL AUTO_INCREMENT,
  `spaceId` int NOT NULL,
  `code` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `units` decimal(4,2) DEFAULT '1.00',
  `price` decimal(8,2) NOT NULL,
  `includedMemberships` int NOT NULL DEFAULT '0',
  `additionalMemberships` int NOT NULL DEFAULT '0',
  `requestable` tinyint DEFAULT '1',
  `sortorder` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `esp_exhibitsspaceid_fk` (`spaceId`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


