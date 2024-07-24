-- MySQL dump 10.13  Distrib 8.0.34, for macos13 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.32


--
-- Table structure for table `reg`
--

DROP TABLE IF EXISTS `reg`;
CREATE TABLE `reg` (
  `id` int NOT NULL AUTO_INCREMENT,
  `conid` int DEFAULT NULL,
  `perid` int DEFAULT NULL,
  `newperid` int DEFAULT NULL,
  `oldperid` int DEFAULT NULL,
  `create_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `change_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `pickup_date` datetime DEFAULT NULL,
  `price` decimal(8,2) NOT NULL,
  `couponDiscount` decimal(8,2) DEFAULT '0.00',
  `paid` decimal(8,2) DEFAULT '0.00',
  `create_trans` int DEFAULT NULL,
  `complete_trans` int DEFAULT NULL,
  `locked` enum('N','Y') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `create_user` int DEFAULT NULL,
  `memId` int DEFAULT NULL,
  `coupon` int DEFAULT NULL,
  `planId` int DEFAULT NULL,
  `printable` enum('N','Y') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'N',
  `status` enum('unpaid','plan','paid','cancelled','refunded','transfered','upgraded','rolled-over') COLLATE utf8mb4_general_ci DEFAULT 'unpaid',
  PRIMARY KEY (`id`),
  KEY `reg_perid_fk` (`perid`),
  KEY `reg_conid_fk` (`conid`),
  KEY `reg_oldperid_fk` (`oldperid`),
  KEY `reg_newperid_fk` (`newperid`),
  KEY `reg_create_trans_fk` (`create_trans`),
  KEY `reg_memId_fk` (`memId`),
  KEY `reg_coupon_fk` (`coupon`),
  KEY `reg_complete_fk` (`complete_trans`),
  KEY `reg_planid_fk` (`planId`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


