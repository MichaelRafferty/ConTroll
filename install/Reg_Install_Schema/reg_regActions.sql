-- MySQL dump 10.13  Distrib 8.0.34, for macos13 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.32


--
-- Table structure for table `regActions`
--

DROP TABLE IF EXISTS `regActions`;
CREATE TABLE `regActions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `logdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `userid` int NOT NULL,
  `tid` int DEFAULT NULL,
  `regid` int NOT NULL,
  `action` enum('attach','print','notes','transfer','rollover','overpayment','refund') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `notes` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `regActions_tid_fk` (`tid`),
  KEY `regActions_regid_fk` (`regid`),
  KEY `regActions_userid_fk` (`userid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


