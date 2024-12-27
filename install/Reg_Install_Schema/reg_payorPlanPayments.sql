-- MySQL dump 10.13  Distrib 8.0.34, for macos13 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.40


--
-- Table structure for table `payorPlanPayments`
--

DROP TABLE IF EXISTS `payorPlanPayments`;
CREATE TABLE `payorPlanPayments` (
  `payorPlanId` int NOT NULL,
  `paymentNbr` int NOT NULL DEFAULT '0',
  `dueDate` datetime DEFAULT NULL,
  `payDate` datetime DEFAULT NULL,
  `planPaymentAmount` decimal(8,2) NOT NULL DEFAULT '0.00',
  `amount` decimal(8,2) NOT NULL DEFAULT '0.00',
  `paymentId` int DEFAULT NULL,
  `transactionId` int DEFAULT NULL,
  PRIMARY KEY (`payorPlanId`,`paymentNbr`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


