-- MySQL dump 10.13  Distrib 8.0.31, for macos12 (x86_64)
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
-- Table structure for table `artshow_reg`
--

DROP TABLE IF EXISTS `artshow_reg`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `artshow_reg` (
  `id` int NOT NULL AUTO_INCREMENT,
  `conid` int NOT NULL DEFAULT '1',
  `max_art` int NOT NULL DEFAULT '0',
  `max_print` int NOT NULL DEFAULT '0',
  `max_mailin` int NOT NULL DEFAULT '0',
  `max_table` int NOT NULL DEFAULT '0',
  `cur_art` int DEFAULT '0',
  `cur_print` int DEFAULT '0',
  `cur_mailin` int DEFAULT '0',
  `cur_table` int DEFAULT '0',
  `per_art` int NOT NULL DEFAULT '0',
  `per_print` int NOT NULL DEFAULT '0',
  `per_table` int NOT NULL DEFAULT '0',
  `art_full` int NOT NULL DEFAULT '0',
  `art_1` int NOT NULL DEFAULT '0',
  `art_2` int NOT NULL DEFAULT '0',
  `table_full` int NOT NULL DEFAULT '0',
  `table_1` int NOT NULL DEFAULT '0',
  `table_2` int NOT NULL DEFAULT '0',
  `table_3` int NOT NULL DEFAULT '0',
  `mailin` int NOT NULL DEFAULT '0',
  `print_full` int NOT NULL DEFAULT '0',
  `print_1` int NOT NULL DEFAULT '0',
  `print_2` int NOT NULL DEFAULT '0',
  `per_mailin` int DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `conid` (`conid`),
  CONSTRAINT `artshow_reg_conid_fk` FOREIGN KEY (`conid`) REFERENCES `conlist` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `conid_fkey` FOREIGN KEY (`conid`) REFERENCES `conlist` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2023-08-15 13:48:36
