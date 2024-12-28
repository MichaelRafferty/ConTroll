-- MySQL dump 10.13  Distrib 8.0.34, for macos13 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.40


--
-- Table structure for table `badgeList`
--

DROP TABLE IF EXISTS `badgeList`;
CREATE TABLE `badgeList` (
  `user_perid` int DEFAULT NULL,
  `conid` int DEFAULT NULL,
  `perid` int DEFAULT NULL,
  `id` int NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  KEY `badgeList_conid_fk` (`conid`),
  KEY `badgeList_perid_fk` (`perid`),
  KEY `badgeList_user_perid_fk` (`user_perid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


