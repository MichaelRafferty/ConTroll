-- MySQL dump 10.13  Distrib 8.0.42, for macos15 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.40


--
-- Table structure for table `taxList`
--

DROP TABLE IF EXISTS `taxList`;
CREATE TABLE `taxList` (
  `conid` int NOT NULL,
  `taxField` enum('tax1','tax2','tax3','tax4','tax5') COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Required name of field, not editable by users',
  `label` varchar(64) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT 'Receipt Label',
  `rate` decimal(8,6) NOT NULL DEFAULT '0.000000' COMMENT 'Tax Rate in percent',
  `active` enum('N','Y') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'N' COMMENT 'Allows for tax law that disables a tax on a sunset date',
  `glNum` varchar(16) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'General Ledger Account Number for Accounting',
  `glLabel` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'General Ledger Account Name for Accounting (For reference, only glNum is used)',
  `lastUpdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updatedBy` int DEFAULT NULL COMMENT 'perid of signed in user that made change, null if done directly in SQL',
  PRIMARY KEY (`conid`,`taxField`),
  KEY `taxC_perinfo` (`updatedBy`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


