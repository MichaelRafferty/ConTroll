-- MySQL dump 10.13  Distrib 8.0.34, for macos13 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.40

--
-- Dumping data for table `policies`
--

LOCK TABLES `policies` WRITE;
ALTER TABLE `policies` DISABLE KEYS;
INSERT INTO `policies` VALUES
('conduct','Do you agree to the conventions code of conduct as listed at&nbsp;<a href=\"#POLICYLINK#\" target=\"_blank\" rel=\"noopener\">#POLICYTEXT#</a>?','You must aggreed to the code of conduct to continue. Please make sure you have read and understand it before answering.',10,'Y','N','2024-07-18 20:58:47','2024-12-20 20:24:03',null,'Y'),
('marketing','May we include you in our annual reminder postcards and future marketing or survey emails','We will not sell your contact information or use it for any purpose other than contacting you about this or future #CONNAME#\'s',20,'N','Y','2024-07-18 20:58:47','2024-08-17 14:40:34',null,'Y');
ALTER TABLE `policies` ENABLE KEYS;
UNLOCK TABLES;
