-- MySQL dump 10.13  Distrib 8.0.34, for macos13 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.40


--
-- Table structure for table `exhibitsRegionTypes`
--

DROP TABLE IF EXISTS `exhibitsRegionTypes`;
CREATE TABLE `exhibitsRegionTypes` (
  `regionType` varchar(16) COLLATE utf8mb4_general_ci NOT NULL,
  `portalType` enum('vendor','artist') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'vendor',
  `requestApprovalRequired` enum('None','Once','Annual') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Once',
  `purchaseApprovalRequired` enum('Y','N') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Y',
  `purchaseAreaTotals` enum('unique','combined') COLLATE utf8mb4_general_ci DEFAULT 'combined',
  `inPersonMaxUnits` int NOT NULL DEFAULT '0',
  `mailinAllowed` enum('Y','N') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Y',
  `mailinMaxUnits` int NOT NULL DEFAULT '0',
  `sortorder` int NOT NULL DEFAULT '0',
  `active` enum('N','Y') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Y',
  `needW9` enum('N','Y') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'N',
  `usesInventory` enum('N','Y') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'N',
  PRIMARY KEY (`regionType`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


