-- MySQL dump 10.13  Distrib 8.0.34, for macos13 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.40


--
-- Table structure for table `oauthAuthCodes`
--

DROP TABLE IF EXISTS `oauthAuthCodes`;
CREATE TABLE `oauthAuthCodes` (
  `id` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `userId` int DEFAULT NULL,
  `clientId` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `scopes` varchar(512) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `expiresAt` datetime NOT NULL,
  `revoked` tinyint(1) NOT NULL DEFAULT '0',
  `redirectUri` varchar(2048) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_oauth_auth_codes_user` (`userId`),
  KEY `idx_oauth_auth_codes_client` (`clientId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


