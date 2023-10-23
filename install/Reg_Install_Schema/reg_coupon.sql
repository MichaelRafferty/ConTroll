-- MySQL dump 10.13  Distrib 8.0.34, for macos13 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.32

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `coupon`
--

DROP TABLE IF EXISTS `coupon`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2023-10-23 18:40:45
