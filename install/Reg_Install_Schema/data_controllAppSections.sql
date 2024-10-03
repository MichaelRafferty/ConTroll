-- MySQL dump 10.13  Distrib 8.0.34, for macos13 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.32


--
-- Dumping data for table `controllAppSections`
--

LOCK TABLES `controllAppSections` WRITE;
ALTER TABLE `controllAppSections` DISABLE KEYS ;
INSERT INTO `controllAppSections` VALUES ('portal','accountSettings','main','main body of the account settings page'),
('portal','addUpgrade','interests','data entry forms related to interests'),
('portal','addUpgrade','main','main body of the addUpgrade page'),
('portal','addUpgrade','portalForms','data entry forms shared with the portal page'),
('portal','index','loginItems','data entry for the login page'),
('portal','index','main','main body of the login page'),
('portal','index','portalForms','data entry forms shared with the portal page'),
('portal','membershipHistory','main','main body of the membership history page'),
('portal','portal','interests','data entry forms related to interests'),
('portal','portal','main','main body of the portal home page'),
('portal','portal','paymentPlamns','data entry forms related to payment plans'),
('portal','portal','portalForm','data entry forms used by the portal page');
ALTER TABLE `controllAppSections` ENABLE KEYS ;
UNLOCK TABLES;
