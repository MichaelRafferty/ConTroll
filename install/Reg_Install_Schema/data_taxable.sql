-- MySQL dump 10.13  Distrib 8.0.44, for macos15 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.40

--
-- Dumping data for table `taxable`
--

LOCK TABLES `taxable` WRITE;
ALTER TABLE `taxable` DISABLE KEYS;
INSERT INTO `taxable` VALUES
('artSales','Art Sales','Y',30),
('artShipping','Art Shipping Fees','N',50),
('artSpace','Art Space','N',40),
('exhibitSpace','Exhibits Space','N',70),
('fanSpace','Fan Table Space','N',80),
('nontaxMem','Non Taxable Memberships','N',20),
('otherFees','Other Fees','N',10000),
('taxableMem','Taxable Memberships','Y',10),
('vendorSpace','Vendor Space','N',60);
ALTER TABLE `taxable` ENABLE KEYS;
UNLOCK TABLES;
