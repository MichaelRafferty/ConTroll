-- MySQL dump 10.13  Distrib 8.0.44, for macos15 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.40


--
-- Table structure for table `taxItems`
--

DROP TABLE IF EXISTS `taxItems`;
CREATE TABLE `taxItems` (
  `conid` int NOT NULL COMMENT 'applicable convention year',
  `taxField` enum('tax1','tax2','tax3','tax4','tax5') COLLATE utf8mb4_general_ci NOT NULL COMMENT 'referecne to taxList',
  `item` varchar(16) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'type of item being sold',
  `taxable` enum('N','Y') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'N' COMMENT 'default value for is this item taxable',
  `lastUpdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updatedBy` int DEFAULT NULL COMMENT 'perid of signed in user that made change, null if done directly in SQL',
  `sortOrder` int NOT NULL DEFAULT '0' COMMENT 'Copied from taxable table',
  PRIMARY KEY (`conid`,`taxField`,`item`),
  KEY `ti_item_taxable` (`item`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


