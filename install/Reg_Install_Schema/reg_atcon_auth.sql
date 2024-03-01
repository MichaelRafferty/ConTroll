-- MySQL dump 10.13  Distrib 8.0.34, for macos13 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.32


--
-- Table structure for table `atcon_auth`
--

DROP TABLE IF EXISTS `atcon_auth`;
CREATE TABLE `atcon_auth` (
  `id` int NOT NULL AUTO_INCREMENT,
  `authuser` int NOT NULL,
  `auth` enum('data_entry','cashier','manager','artinventory','artsales','artshow','vol_roll') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `atcon_authuser_fk` (`authuser`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


