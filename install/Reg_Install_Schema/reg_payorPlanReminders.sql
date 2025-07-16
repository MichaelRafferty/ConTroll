-- MySQL dump 10.13  Distrib 8.0.42, for macos15 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.40


--
-- Table structure for table `payorPlanReminders`
--

DROP TABLE IF EXISTS `payorPlanReminders`;
CREATE TABLE `payorPlanReminders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sentDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `perid` int NOT NULL,
  `payorPlanId` int NOT NULL,
  `conid` int NOT NULL,
  `emailAddr` varchar(256) COLLATE utf8mb4_general_ci NOT NULL,
  `dueDate` datetime NOT NULL,
  `minAmt` decimal(8,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_ppr_perid` (`perid`),
  KEY `fk_ppr_payorPlan` (`payorPlanId`),
  KEY `fk_ppr_conid` (`conid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


