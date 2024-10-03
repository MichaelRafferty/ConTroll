-- MySQL dump 10.13  Distrib 8.0.34, for macos13 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.32

--
-- Dumping data for table `memCategories`
--

LOCK TABLES `memCategories` WRITE;
ALTER TABLE `memCategories` DISABLE KEYS ;
INSERT INTO `memCategories` VALUES
('addon','Req: Add-on\'s to memberips','N','N','N',80,'Y','X'),
('artist','Req: Artist Memberships','Y','Y','N',60,'Y','Artist'),
('dealer','Req: Dealer/Vendor Memberships','Y','Y','N',70,'Y','Dealer'),
('donation','Req: Variable Price Donations','N','Y','Y',90,'Y','X'),
('freebie','Req: Comp memberships','Y','N','N',50,'Y','Comp'),
('standard','Req: Paid badgable memberships','Y','Y','N',10,'Y','Std'),
('ticket','Not currently used','Y','Y','N',100,'N','X'),
('upgrade','Req: Upgrades to standard','Y','Y','N',30,'Y','Upg'),
('virtual','Req: Paid virtual memberships','Y','Y','N',20,'Y','V'),
('yearahead','Req: Next Con Year Memberships','Y','Y','N',40,'Y','Y');
ALTER TABLE `memCategories` ENABLE KEYS ;
UNLOCK TABLES;
