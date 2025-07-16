-- MySQL dump 10.13  Distrib 8.0.42, for macos15 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.40


--
-- Table structure for table `exhibitorSpaces`
--

DROP TABLE IF EXISTS `exhibitorSpaces`;
CREATE TABLE `exhibitorSpaces` (
  `id` int NOT NULL AUTO_INCREMENT,
  `exhibitorRegionYear` int NOT NULL,
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
  `locations` varchar(265) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `es_transaction_fk` (`transid`),
  KEY `es_space_req_fk` (`item_requested`),
  KEY `es_space_app_fk` (`item_approved`),
  KEY `es_space_pur_fk` (`item_purchased`),
  KEY `es_spaceid_fk` (`spaceId`),
  KEY `es_exRY_fk` (`exhibitorRegionYear`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


