-- MySQL dump 10.13  Distrib 8.0.42, for macos15 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.40


--
-- Table structure for table `regHistory`
--

DROP TABLE IF EXISTS `regHistory`;
CREATE TABLE `regHistory` (
  `historyId` int NOT NULL AUTO_INCREMENT,
  `id` int NOT NULL,
  `conid` int DEFAULT NULL,
  `perid` int DEFAULT NULL,
  `newperid` int DEFAULT NULL,
  `oldperid` int DEFAULT NULL,
  `priorRegId` int DEFAULT NULL,
  `create_date` datetime DEFAULT NULL,
  `change_date` timestamp NULL DEFAULT NULL,
  `pickup_date` datetime DEFAULT NULL,
  `price` decimal(8,2) DEFAULT NULL,
  `couponDiscount` decimal(8,2) DEFAULT NULL,
  `paid` decimal(8,2) DEFAULT NULL,
  `create_trans` int DEFAULT NULL,
  `complete_trans` int DEFAULT NULL,
  `locked` enum('N','Y') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `create_user` int DEFAULT NULL,
  `updatedBy` int DEFAULT NULL,
  `memId` int DEFAULT NULL,
  `coupon` int DEFAULT NULL,
  `planId` int DEFAULT NULL,
  `printable` enum('N','Y') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('unpaid','plan','paid','cancelled','refunded','transfered','upgraded','rolled-over') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`historyId`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


