-- MySQL dump 10.13  Distrib 8.0.34, for macos13 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.40


--
-- Table structure for table `oauthRefreshTokens`
--

DROP TABLE IF EXISTS `oauthRefreshTokens`;
CREATE TABLE `oauthRefreshTokens` (
  `id` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `accessTokenId` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `expiresAt` datetime NOT NULL,
  `revoked` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `fk_refresh_token_access` (`accessTokenId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


