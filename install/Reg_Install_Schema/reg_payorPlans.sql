-- MySQL dump 10.13  Distrib 8.0.42, for macos15 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.40


--
-- Table structure for table `payorPlans`
--

DROP TABLE IF EXISTS `payorPlans`;
CREATE TABLE `payorPlans` (
  `id` int NOT NULL AUTO_INCREMENT,
  `planId` int NOT NULL,
  `conid` int DEFAULT NULL,
  `perid` int DEFAULT NULL,
  `newperid` int DEFAULT NULL,
  `initialAmt` decimal(8,2) NOT NULL,
  `nonPlanAmt` decimal(8,2) NOT NULL DEFAULT '0.00',
  `downPayment` decimal(8,2) NOT NULL DEFAULT '0.00',
  `minPayment` decimal(8,2) DEFAULT '10.00',
  `finalPayment` decimal(8,2) DEFAULT '10.00',
  `openingBalance` decimal(8,2) NOT NULL DEFAULT '0.00',
  `numPayments` int NOT NULL,
  `daysBetween` int NOT NULL DEFAULT '30',
  `payByDate` date NOT NULL,
  `payType` enum('manual','auto') COLLATE utf8mb4_general_ci DEFAULT 'manual',
  `reminders` enum('Y','N') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'N',
  `status` enum('active','paid','refunded','cancelled') COLLATE utf8mb4_general_ci DEFAULT 'active',
  `createTransaction` int DEFAULT NULL,
  `balanceDue` decimal(8,2) NOT NULL DEFAULT '0.00',
  `createDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updateDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updateBy` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pp_planid_fk` (`planId`),
  KEY `pp_newperid_fk` (`newperid`),
  KEY `pp_perid_fk` (`perid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


