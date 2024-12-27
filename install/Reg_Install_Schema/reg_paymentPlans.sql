-- MySQL dump 10.13  Distrib 8.0.34, for macos13 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.40


--
-- Table structure for table `paymentPlans`
--

DROP TABLE IF EXISTS `paymentPlans`;
CREATE TABLE `paymentPlans` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(16) COLLATE utf8mb4_general_ci NOT NULL,
  `description` varchar(1024) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `catList` varchar(1024) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `memList` varchar(1024) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `excludeList` varchar(1024) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `portalList` varchar(1024) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `downPercent` decimal(8,2) DEFAULT '0.00',
  `downAmt` decimal(8,2) DEFAULT '25.00',
  `minPayment` decimal(8,2) DEFAULT '10.00',
  `numPaymentMax` int DEFAULT '4',
  `payByDate` date NOT NULL,
  `payType` enum('manual','auto') COLLATE utf8mb4_general_ci DEFAULT 'manual',
  `modify` enum('Y','N') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'N',
  `reminders` enum('Y','N') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'N',
  `downIncludeNonPlan` enum('Y','N') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'N',
  `lastPaymentPartial` enum('Y','N') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'N',
  `active` enum('Y','N') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Y',
  `sortorder` int NOT NULL DEFAULT '0',
  `createDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updateDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updateBy` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pp_updateBy_fk` (`updateBy`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


