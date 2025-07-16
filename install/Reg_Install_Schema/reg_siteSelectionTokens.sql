-- MySQL dump 10.13  Distrib 8.0.42, for macos15 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.40


--
-- Table structure for table `siteSelectionTokens`
--

DROP TABLE IF EXISTS `siteSelectionTokens`;
CREATE TABLE `siteSelectionTokens` (
  `id` int NOT NULL AUTO_INCREMENT,
  `encTokenKey` varbinary(256) NOT NULL,
  `perid` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sst_perinfo_fk` (`perid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


