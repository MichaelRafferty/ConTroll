-- MySQL dump 10.13  Distrib 8.0.34, for macos13 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.32


--
-- Table structure for table `artshow`
--

DROP TABLE IF EXISTS `artshow`;
CREATE TABLE `artshow` (
  `id` int NOT NULL AUTO_INCREMENT,
  `artid` int DEFAULT NULL,
  `perid` int DEFAULT NULL,
  `agent` int DEFAULT NULL,
  `conid` int DEFAULT NULL,
  `art_key` int DEFAULT NULL,
  `a_panels` int DEFAULT NULL,
  `p_panels` int DEFAULT NULL,
  `a_tables` int DEFAULT NULL,
  `p_tables` int DEFAULT NULL,
  `a_panel_list` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `a_table_list` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `p_panel_list` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `p_table_list` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `assold_amt` float NOT NULL,
  `pssold_amt` float NOT NULL,
  `total` float NOT NULL,
  `fees` float NOT NULL,
  `chknum` int DEFAULT NULL,
  `chkdate` date DEFAULT NULL,
  `attending` enum('attending','agent','mailin') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `agent_request` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_person` (`artid`,`perid`,`conid`),
  KEY `artshow_perinfo_fk` (`perid`),
  KEY `artshow_conid_fk` (`conid`),
  KEY `artshow_agent_fk` (`agent`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


