-- MySQL dump 10.13  Distrib 8.0.34, for macos13 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.40


--
-- Table structure for table `oauthClients`
--

DROP TABLE IF EXISTS `oauthClients`;
CREATE TABLE `oauthClients` (
  `clientId` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `clientSecret` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `redirectUri` varchar(2048) COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `alloweGrantTypes` varchar(512) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`clientId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


