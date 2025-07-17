-- MySQL dump 10.13  Distrib 8.0.42, for macos15 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.40


--
-- Table structure for table `exhibitsRegions`
--

DROP TABLE IF EXISTS `exhibitsRegions`;
CREATE TABLE `exhibitsRegions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `regionType` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `shortname` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `glNum` varchar(16) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `glLabel` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sortorder` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `er_regiontype_fk` (`regionType`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


