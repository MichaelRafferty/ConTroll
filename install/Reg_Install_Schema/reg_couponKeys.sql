-- MySQL dump 10.13  Distrib 8.0.34, for macos13 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.32


--
-- Table structure for table `couponKeys`
--

DROP TABLE IF EXISTS `couponKeys`;
CREATE TABLE `couponKeys` (
  `id` int NOT NULL AUTO_INCREMENT,
  `couponId` int NOT NULL,
  `guid` varchar(36) COLLATE utf8mb4_general_ci NOT NULL,
  `perid` int DEFAULT NULL,
  `notes` varchar(256) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `usedBy` int DEFAULT NULL,
  `createTS` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `createBy` int DEFAULT NULL,
  `useTS` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `couponkeys_couponid_fk` (`couponId`),
  KEY `couponkey_usedby_fk` (`usedBy`),
  KEY `couponkeys_createby_fk` (`createBy`),
  KEY `couponkeys_perid_fk` (`perid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


