-- MySQL dump 10.13  Distrib 8.0.42, for macos15 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.40


--
-- Table structure for table `atcon_user`
--

DROP TABLE IF EXISTS `atcon_user`;
CREATE TABLE `atcon_user` (
  `id` int NOT NULL AUTO_INCREMENT,
  `perid` int DEFAULT NULL,
  `conid` int DEFAULT NULL,
  `passwd` varchar(256) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `userhash` varchar(256) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `atcon_user_conid_fk` (`conid`),
  KEY `atcon_user_perid_fk` (`perid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


