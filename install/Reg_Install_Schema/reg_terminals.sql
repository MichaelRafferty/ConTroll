-- MySQL dump 10.13  Distrib 8.0.42, for macos15 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.40


--
-- Table structure for table `terminals`
--

DROP TABLE IF EXISTS `terminals`;
CREATE TABLE `terminals` (
  `name` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `productType` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `locationId` varchar(16) COLLATE utf8mb4_general_ci NOT NULL,
  `squareId` varchar(32) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deviceId` varchar(32) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `squareCode` varchar(16) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `squareName` varchar(32) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `squareModel` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `version` varchar(16) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `terminalAPIVersion` varchar(16) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pairBy` datetime DEFAULT NULL,
  `pairedAt` datetime DEFAULT NULL,
  `batteryLevel` int DEFAULT NULL,
  `externalPower` varchar(32) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `wifiActive` tinyint(1) DEFAULT NULL,
  `wifiSSID` varchar(32) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `wifiIPAddressV4` varchar(32) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `wifiIPAddressV6` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `signalStrength` int DEFAULT NULL,
  `ethernetActive` tinyint(1) DEFAULT NULL,
  `ethernetIPAddressV4` varchar(32) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ethernetIPAddressV6` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `createDate` datetime NOT NULL,
  `status` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `statusChanged` datetime NOT NULL,
  `currentOrder` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `currentPayment` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `currentOperator` int DEFAULT NULL,
  `controllStatus` varchar(32) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `controllStatusChanged` datetime DEFAULT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


