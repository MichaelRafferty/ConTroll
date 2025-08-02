-- MySQL dump 10.13  Distrib 8.0.42, for macos15 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.40


--
-- Table structure for table `portalTokenLinks`
--

DROP TABLE IF EXISTS `portalTokenLinks`;
CREATE TABLE `portalTokenLinks` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(254) COLLATE utf8mb4_general_ci NOT NULL,
  `action` enum('login','attach','identity','other') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'other',
  `source_ip` varchar(16) COLLATE utf8mb4_general_ci NOT NULL,
  `createdTS` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `useCnt` int NOT NULL DEFAULT '0',
  `useIP` varchar(16) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `useTS` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ptlEmail_idx` (`email`,`createdTS` DESC)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


