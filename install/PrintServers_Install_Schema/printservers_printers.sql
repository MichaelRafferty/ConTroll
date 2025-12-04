-- MySQL dump 10.13  Distrib 8.0.42, for macos15 (arm64)
--
-- Host: localhost    Database: printservers
-- ------------------------------------------------------
-- Server version	8.0.40

--
-- Table structure for table `printers`
--

DROP TABLE IF EXISTS `printers`;
CREATE TABLE `printers` (
  `serverName` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `printerName` varchar(16) COLLATE utf8mb4_general_ci NOT NULL,
  `printerType` enum('generic','receipt','badge') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'generic',
  `codePage` enum('HPCL','Dymo4xx','Dymo3xx','Windows-1252','ASCII','7bit','8bit','UTF-8','UTF-16') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Windows-1252',
  PRIMARY KEY (`serverName`,`printerName`),
  CONSTRAINT `printers_server` FOREIGN KEY (`serverName`) REFERENCES `servers` (`serverName`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
