-- MySQL dump 10.13  Distrib 8.0.34, for macos13 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.40


--
-- Table structure for table `memberInterests`
--

DROP TABLE IF EXISTS `memberInterests`;
CREATE TABLE `memberInterests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `perid` int DEFAULT NULL,
  `conid` int DEFAULT NULL,
  `newperid` int DEFAULT NULL,
  `interest` varchar(16) COLLATE utf8mb4_general_ci NOT NULL,
  `interested` enum('Y','N') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'N',
  `notifyDate` datetime DEFAULT NULL,
  `csvDate` datetime DEFAULT NULL,
  `createDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updateDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updateBy` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `memberInterestsInterest_fk` (`interest`),
  KEY `memberInterestsNewperson_fk` (`newperid`),
  KEY `memberInterestsPerinfo_fk` (`perid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


