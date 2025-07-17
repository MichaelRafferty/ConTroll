-- MySQL dump 10.13  Distrib 8.0.42, for macos15 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.40


--
-- Table structure for table `patchLog`
--

DROP TABLE IF EXISTS `patchLog`;
CREATE TABLE `patchLog` (
  `id` int NOT NULL,
  `name` varchar(256) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `installDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


