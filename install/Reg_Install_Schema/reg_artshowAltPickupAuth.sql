-- MySQL dump 10.13  Distrib 8.0.44, for macos15 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.40


--
-- Table structure for table `artshowAltPickupAuth`
--

DROP TABLE IF EXISTS `artshowAltPickupAuth`;
CREATE TABLE `artshowAltPickupAuth` (
  `conid` int NOT NULL COMMENT 'valid year for this authorization',
  `bidderPerid` int NOT NULL COMMENT 'perid (badgeId) of the art show bidder',
  `pickupPerid` int NOT NULL COMMENT 'perid (badgeId) of someone who can pick up bidderPerids purchased art items',
  `createdBy` int NOT NULL COMMENT 'perid of the art show cashier who created the relationship',
  `createDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'date/time the relationship was created',
  `active` enum('N','Y') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Y' COMMENT 'Y=active, N=inactive - for tracking when/who inactivated a relationship',
  `deactivateDate` datetime DEFAULT NULL COMMENT 'date/time the relationship was deactivated',
  `deactivatedBy` int DEFAULT NULL COMMENT 'perid of the user who deactivated the relationship',
  PRIMARY KEY (`conid`,`bidderPerid`,`pickupPerid`),
  KEY `app_bidder` (`bidderPerid`),
  KEY `app_pickup` (`pickupPerid`),
  KEY `app_user` (`createdBy`),
  KEY `app_deactuser` (`deactivatedBy`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


