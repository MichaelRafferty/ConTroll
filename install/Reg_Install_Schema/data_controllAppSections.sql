-- MySQL dump 10.13  Distrib 8.0.42, for macos15 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.40

--
-- Dumping data for table `controllAppSections`
--

LOCK TABLES `controllAppSections` WRITE;
ALTER TABLE `controllAppSections` DISABLE KEYS;
INSERT INTO `controllAppSections` VALUES
('controll','emails','comeback','Comeback Email - Not bought insert into a few years'),
('controll','emails','marketing','Marketing Email - Not bought this year, bought last year'),
('controll','emails','reminder','Reminder Email - Reminder to attend - has membership'),
('exhibitor','index','email','exhibitor emails'),
('exhibitor','index','invoice','space invoice modal popup of the exhibitor portal'),
('exhibitor','index','items','art inventory modal popup of the exhibitor portal'),
('exhibitor','index','login','main body of the exhibitor portal'),
('exhibitor','index','main','main body of the exhibitor portal'),
('exhibitor','index','profile','profile modal popup of the exhibitor portal'),
('exhibitor','index','receipt','space payment receipt modal popup of the exhibitor portal'),
('exhibitor','index','request','space request modal popup of the exhibitor portal'),
('exhibitor','index','signup','signup modal popup of the exhibitor portal'),
('portal','accountSettings','main','main body of the account settings page'),
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
ALTER TABLE `controllAppSections` ENABLE KEYS;
UNLOCK TABLES;
