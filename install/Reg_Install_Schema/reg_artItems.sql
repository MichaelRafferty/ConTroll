-- MySQL dump 10.13  Distrib 8.0.34, for macos13 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.32


--
-- Table structure for table `artItems`
--

DROP TABLE IF EXISTS `artItems`;
CREATE TABLE `artItems` (
  `id` int NOT NULL AUTO_INCREMENT,
  `item_key` int NOT NULL,
  `title` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `type` enum('art','nfs','print') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('Not In Show','Checked In','NFS','BID','Quicksale/Sold','Removed from Show','purchased/released','To Auction','Sold Bid Sheet','Checked Out') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `location` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `quantity` int NOT NULL,
  `original_qty` int NOT NULL,
  `min_price` float NOT NULL,
  `sale_price` float DEFAULT NULL,
  `final_price` float DEFAULT NULL,
  `bidder` int DEFAULT NULL,
  `conid` int DEFAULT NULL,
  `artshow` int DEFAULT NULL,
  `time_updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `material` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `artItems_conid_fk` (`conid`),
  KEY `artItems_artshow_fk` (`artshow`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


