-- MySQL dump 10.13  Distrib 8.0.34, for macos13 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.32


--
-- Table structure for table `memberPolicies`
--

DROP TABLE IF EXISTS `memberPolicies`;
CREATE TABLE `memberPolicies` (
  `id` int NOT NULL AUTO_INCREMENT,
  `perid` int DEFAULT NULL,
  `conid` int DEFAULT NULL,
  `newperid` int DEFAULT NULL,
  `policy` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `response` enum('Y','N') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'N',
  `createDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updateDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updateBy` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `updateBy` (`updateBy`),
  KEY `perid` (`perid`),
  KEY `newperid` (`newperid`),
  KEY `policy` (`policy`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


