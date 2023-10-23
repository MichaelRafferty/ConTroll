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
-- Table structure for table `perinfo_back`
--

DROP TABLE IF EXISTS `perinfo_back`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `perinfo_back` (
  `id` int NOT NULL AUTO_INCREMENT,
  `last_name` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `first_name` varchar(32) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `middle_name` varchar(32) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `suffix` varchar(4) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email_addr` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone` varchar(15) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `badge_name` varchar(32) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `address` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `addr_2` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `city` varchar(32) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `state` varchar(2) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `zip` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `country` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `banned` enum('N','Y') COLLATE utf8mb4_general_ci NOT NULL,
  `creation_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `change_notes` text COLLATE utf8mb4_general_ci,
  `active` enum('N','Y') COLLATE utf8mb4_general_ci NOT NULL,
  `open_notes` text COLLATE utf8mb4_general_ci,
  `admin_notes` text COLLATE utf8mb4_general_ci,
  `old_perid` int DEFAULT NULL,
  `contact_ok` enum('N','Y') COLLATE utf8mb4_general_ci DEFAULT 'N',
  `share_reg_ok` enum('N','Y') COLLATE utf8mb4_general_ci DEFAULT 'N',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21789 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2023-10-23 18:40:46
