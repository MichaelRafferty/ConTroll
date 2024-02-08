-- MySQL dump 10.13  Distrib 8.0.34, for macos13 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.32


--
-- Table structure for table `coupon`
--

DROP TABLE IF EXISTS `coupon`;
CREATE TABLE `coupon` (
  `id` int NOT NULL AUTO_INCREMENT,
  `conid` int NOT NULL,
  `oneUse` int NOT NULL DEFAULT '0',
  `code` varchar(16) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `name` varchar(32) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `startDate` datetime NOT NULL DEFAULT '1900-01-01 00:00:00',
  `endDate` datetime NOT NULL DEFAULT '2100-12-31 00:00:00',
  `couponType` enum('$off','%off','$mem','%mem','price') COLLATE utf8mb4_general_ci NOT NULL DEFAULT '$mem',
  `discount` decimal(8,2) NOT NULL DEFAULT '0.00',
  `memId` int DEFAULT NULL,
  `minMemberships` int DEFAULT NULL,
  `maxMemberships` int DEFAULT NULL,
  `limitMemberships` int DEFAULT NULL,
  `minTransaction` decimal(8,2) DEFAULT NULL,
  `maxTransaction` decimal(8,2) DEFAULT NULL,
  `maxRedemption` int DEFAULT NULL,
  `createTS` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `createBy` int DEFAULT NULL,
  `updateTS` timestamp NULL DEFAULT NULL,
  `updateBy` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `coupon_conid_fk` (`conid`),
  KEY `coupon_memid_fk` (`memId`),
  KEY `coupon_createby_fk` (`createBy`),
  KEY `coupon_updateby_fk` (`updateBy`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


