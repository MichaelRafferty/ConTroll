-- MySQL dump 10.13  Distrib 8.0.42, for macos15 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.40


--
-- Table structure for table `artSales`
--

DROP TABLE IF EXISTS `artSales`;
CREATE TABLE `artSales` (
  `id` int NOT NULL AUTO_INCREMENT,
  `transid` int DEFAULT NULL,
  `artid` int DEFAULT NULL,
  `unit` int DEFAULT NULL,
  `status` enum('Entered','Not In Show','Checked In','Removed from Show','BID','Quicksale/Sold','To Auction','Sold Bid Sheet','Sold at Auction','Checked Out','Purchased/Released') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `perid` int DEFAULT NULL,
  `amount` decimal(8,2) NOT NULL,
  `paid` decimal(8,2) NOT NULL DEFAULT '0.00',
  `quantity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `artSales_transid_fk` (`transid`),
  KEY `artSales_artitem_fk` (`artid`),
  KEY `artSales_perinfo_fk` (`perid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


