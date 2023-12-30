-- MySQL dump 10.13  Distrib 8.0.34, for macos13 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.32


--
-- Table structure for table `club`
--

DROP TABLE IF EXISTS `club`;
CREATE TABLE `club` (
  `id` int NOT NULL AUTO_INCREMENT,
  `perid` int DEFAULT NULL,
  `type` enum('inactive','eternal','life','child','annual','none') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `year` year DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `psfs_perid_fk` (`perid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Dump completed on 2023-12-21 16:26:32
