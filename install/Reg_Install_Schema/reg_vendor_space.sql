-- MySQL dump 10.13  Distrib 8.0.34, for macos13 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.32


--
-- Table structure for table `vendor_space`
--

DROP TABLE IF EXISTS `vendor_space`;
CREATE TABLE `vendor_space` (
  `id` int NOT NULL AUTO_INCREMENT,
  `conid` int NOT NULL,
  `vendorId` int NOT NULL,
  `spaceId` int NOT NULL,
  `item_requested` int DEFAULT NULL,
  `time_requested` timestamp NULL DEFAULT NULL,
  `item_approved` int DEFAULT NULL,
  `time_approved` timestamp NULL DEFAULT NULL,
  `item_purchased` int DEFAULT NULL,
  `time_purchased` timestamp NULL DEFAULT NULL,
  `price` decimal(8,2) DEFAULT NULL,
  `paid` decimal(8,2) DEFAULT NULL,
  `transid` int DEFAULT NULL,
  `membershipCredits` int DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `vendor_space_vendor` (`vendorId`),
  KEY `vendor_space_space` (`spaceId`),
  KEY `vendor_space_conid` (`conid`),
  KEY `vendor_space_trans` (`transid`),
  KEY `vendor_space_req` (`item_requested`),
  KEY `vendor_space_app` (`item_approved`),
  KEY `vendor_space_pur` (`item_purchased`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Dump completed on 2023-12-21 16:26:32
