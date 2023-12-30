-- MySQL dump 10.13  Distrib 8.0.34, for macos13 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.32


--
-- Table structure for table `vendors`
--

DROP TABLE IF EXISTS `vendors`;
CREATE TABLE `vendors` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `website` varchar(256) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `email` varchar(64) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `need_new` tinyint(1) DEFAULT '1',
  `confirm` tinyint(1) DEFAULT '0',
  `publicity` tinyint(1) DEFAULT '0',
  `addr` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `addr2` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `city` varchar(32) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `state` varchar(2) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `zip` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Dump completed on 2023-12-21 16:26:31
