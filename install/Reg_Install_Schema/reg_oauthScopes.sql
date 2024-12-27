-- MySQL dump 10.13  Distrib 8.0.34, for macos13 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.40


--
-- Table structure for table `oauthScopes`
--

DROP TABLE IF EXISTS `oauthScopes`;
CREATE TABLE `oauthScopes` (
  `id` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `description` varchar(512) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


