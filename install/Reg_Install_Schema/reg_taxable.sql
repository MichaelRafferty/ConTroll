-- MySQL dump 10.13  Distrib 8.0.44, for macos15 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.40


--
-- Table structure for table `taxable`
--

DROP TABLE IF EXISTS `taxable`;
CREATE TABLE `taxable` (
  `item` varchar(16) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'type of item being sold',
  `label` varchar(64) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'longer name for item, more descriptive',
  `defaultValue` enum('N','Y') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'N' COMMENT 'default value for is this item taxable',
  `sortOrder` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`item`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


