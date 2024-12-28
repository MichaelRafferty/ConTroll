-- MySQL dump 10.13  Distrib 8.0.34, for macos13 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.40


--
-- Table structure for table `exhibitsSpaces`
--

DROP TABLE IF EXISTS `exhibitsSpaces`;
CREATE TABLE `exhibitsSpaces` (
  `id` int NOT NULL AUTO_INCREMENT,
  `exhibitsRegionYear` int NOT NULL,
  `shortname` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `unitsAvailable` int NOT NULL DEFAULT '0',
  `unitsAvailableMailin` int NOT NULL DEFAULT '0',
  `sortorder` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `es_exhibitsRegionYears_fk` (`exhibitsRegionYear`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


