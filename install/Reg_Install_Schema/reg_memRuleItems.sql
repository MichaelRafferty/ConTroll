-- MySQL dump 10.13  Distrib 8.0.34, for macos13 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.32


--
-- Table structure for table `memRuleItems`
--

DROP TABLE IF EXISTS `memRuleItems`;
CREATE TABLE `memRuleItems` (
  `name` varchar(16) COLLATE utf8mb4_general_ci NOT NULL,
  `step` int NOT NULL,
  `ruleType` enum('needAny','needAll','notAny','notAll','limitAge','currentAge') COLLATE utf8mb4_general_ci NOT NULL,
  `applyTo` enum('person','all') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'person',
  `typeList` varchar(1024) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `catList` varchar(1024) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ageList` varchar(1024) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `memList` varchar(1024) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`name`,`step`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


