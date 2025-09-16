-- MySQL dump 10.13  Distrib 8.0.42, for macos15 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.40

--
-- Dumping data for table `memTypes`
--

LOCK TABLES `memTypes` WRITE;
ALTER TABLE `memTypes` DISABLE KEYS;
INSERT INTO `memTypes` VALUES
('donation','Req: Donation: both variable and fixed price',40,'Y'),
('full','Req: full \'run of convention\' badge-able membership',10,'Y'),
('oneday','Req: single day badge-able membership',30,'Y'),
('sitesel','For site selection tokens',80,'Y'),
('virtual','Req: virtual non badge-able membership',20,'Y');
ALTER TABLE `memTypes` ENABLE KEYS;
UNLOCK TABLES;
