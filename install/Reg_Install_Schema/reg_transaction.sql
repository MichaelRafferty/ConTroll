-- MySQL dump 10.13  Distrib 8.0.34, for macos13 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.32


--
-- Table structure for table `transaction`
--

DROP TABLE IF EXISTS `transaction`;
CREATE TABLE `transaction` (
  `id` int NOT NULL AUTO_INCREMENT,
  `conid` int DEFAULT NULL,
  `perid` int DEFAULT NULL,
  `newperid` int DEFAULT NULL,
  `userid` int DEFAULT NULL,
  `create_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `complete_date` datetime DEFAULT NULL,
  `price` decimal(8,2) DEFAULT NULL,
  `couponDiscount` decimal(8,2) DEFAULT '0.00',
  `paid` decimal(8,2) DEFAULT NULL,
  `withtax` decimal(8,2) DEFAULT NULL,
  `tax` decimal(8,2) DEFAULT NULL,
  `type` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `ticket_num` int DEFAULT NULL,
  `change_due` decimal(8,2) DEFAULT NULL,
  `coupon` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `transaction_conid_fk` (`conid`),
  KEY `transaction_newperid_fk` (`newperid`),
  KEY `transaction_perid_fk` (`perid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Dump completed on 2023-12-21 16:26:31
