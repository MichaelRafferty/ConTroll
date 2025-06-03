-- MySQL dump 10.13  Distrib 8.0.34, for macos13 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.40

--
-- Dumping data for table `memCategories`
--

LOCK TABLES `memCategories` WRITE;
ALTER TABLE `memCategories` DISABLE KEYS;
INSERT INTO `memCategories` VALUES
('addon','Req: Add-on\'s to memberships','N','Y','N','N',70,'Y','X'),
('addonTaxable','Req: Taxable add-on\' to memberships','N','Y','N','Y',75,'Y','X'),
('artist','Req: Artist Memberships','Y','Y','N','N',50,'Y','Artist'),
('dealer','Req: Dealer/Vendor Memberships','Y','Y','N','N',60,'Y','Dealer'),
('donation','Req: Variable Price Donations','N','Y','Y','N',80,'Y','X'),
('freebie','Req: Comp memberships','Y','Y','N','N',40,'Y','Comp'),
('standard','Req: Paid badgable memberships','Y','Y','N','N',10,'Y','Std'),
('upgrade','Req: Upgrades to standard','Y','Y','N','N',20,'Y','Upg'),
('virtual','Req: Paid virtual memberships','Y','Y','N','N',90,'N','V'),
('yearahead','Req: Next Con Year Memberships','Y','Y','N','N',30,'Y','Y');
ALTER TABLE `memCategories` ENABLE KEYS;
UNLOCK TABLES;
