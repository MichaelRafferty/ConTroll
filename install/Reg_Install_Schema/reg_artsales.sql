-- MySQL dump 10.13  Distrib 8.0.34, for macos13 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.32


--
-- Table structure for table `artsales`
--

DROP TABLE IF EXISTS `artsales`;
CREATE TABLE `artsales` (
  `id` int NOT NULL AUTO_INCREMENT,
  `transid` int DEFAULT NULL,
  `artid` int DEFAULT NULL,
  `perid` int DEFAULT NULL,
  `amount` float DEFAULT NULL,
  `quantity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `artsales_transid_fk` (`transid`),
  KEY `artsales_artitem_fk` (`artid`),
  KEY `artsales_perinfo_fk` (`perid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


