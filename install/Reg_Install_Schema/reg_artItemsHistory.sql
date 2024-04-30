-- MySQL dump 10.13  Distrib 8.0.34, for macos13 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.32


--
-- Table structure for table `artItemsHistory`
--

DROP TABLE IF EXISTS `artItemsHistory`;
CREATE TABLE `artItemsHistory` (
  `historyId` int NOT NULL AUTO_INCREMENT,
  `id` int NOT NULL,
  `item_key` int NOT NULL,
  `title` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `type` enum('art','nfs','print') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('Entered','Not In Show','Checked In','Removed from Show','BID','Quicksale/Sold','To Auction','Sold Bid Sheet','Sold at Auction','Checked Out','Purchased/Released') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `location` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `quantity` int NOT NULL,
  `original_qty` int NOT NULL,
  `min_price` decimal(8,2) NOT NULL,
  `sale_price` decimal(8,2) DEFAULT NULL,
  `final_price` decimal(8,2) DEFAULT NULL,
  `bidder` int DEFAULT NULL,
  `conid` int DEFAULT NULL,
  `artshow` int DEFAULT NULL,
  `time_updated` timestamp NULL DEFAULT NULL,
  `updatedBy` int NOT NULL,
  `material` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `exhibitorRegionYearId` int DEFAULT NULL,
  `historyDate` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`historyId`),
  KEY `artItemsHistory_conid_fk` (`conid`),
  KEY `aIH_exhibitorRegionYear_fk` (`exhibitorRegionYearId`),
  KEY `artItemsHistory_updatedBy_fk` (`updatedBy`),
  KEY `artItemsHistory_id_fk` (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


