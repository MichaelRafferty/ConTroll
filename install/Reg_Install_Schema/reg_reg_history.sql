-- MySQL dump 10.13  Distrib 8.0.34, for macos13 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.32


--
-- Table structure for table `reg_history`
--

DROP TABLE IF EXISTS `reg_history`;
CREATE TABLE `reg_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `logdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `userid` int NOT NULL,
  `tid` int NOT NULL,
  `regid` int NOT NULL,
  `action` enum('attach','print','notes','transfer','rollover','overpayment','refund') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `notes` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `atcon_history_tid_fk` (`tid`),
  KEY `atcon_history_regid_fk` (`regid`),
  KEY `atcon_history_userid_fk` (`userid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


