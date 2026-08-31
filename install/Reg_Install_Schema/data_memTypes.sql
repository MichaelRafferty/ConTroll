-- MySQL dump 10.13  Distrib 8.0.44, for macos15 (arm64)
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
('bundle', 'Req: Grouping for rules of membership bundles', 50,'Y'),
('donation','Req: Donation: both variable and fixed price',40,'Y'),
('full','Req: full \'run of convention\' badge-able membership',10,'Y'),
('oneday','Req: single day badge-able membership',30,'Y'),
('virtual','Req: virtual non badge-able membership',20,'N'),
('wsfs','WSFS Membership',80,'N');
ALTER TABLE `memTypes` ENABLE KEYS;
UNLOCK TABLES;
