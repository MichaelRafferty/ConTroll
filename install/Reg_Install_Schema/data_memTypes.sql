-- MySQL dump 10.13  Distrib 8.0.34, for macos13 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.32

--
-- Dumping data for table `memTypes`
--

LOCK TABLES `memTypes` WRITE;
ALTER TABLE `memTypes` DISABLE KEYS ;
INSERT INTO `memTypes` VALUES
('donation','Req: Donation both variable price and fixed',40,'Y'),
('full','Req: full run of convention badgable membership',10,'Y'),
('oneday','Req: single day badgerable membership',30,'Y'),
('virtual','Req: virtail non badgeable membership',20,'Y');
ALTER TABLE `memTypes` ENABLE KEYS ;
UNLOCK TABLES;
