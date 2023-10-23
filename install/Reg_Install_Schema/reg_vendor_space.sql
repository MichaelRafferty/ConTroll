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
-- Table structure for table `vendor_space`
--

DROP TABLE IF EXISTS `vendor_space`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2023-10-23 18:40:44
