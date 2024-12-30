-- MySQL dump 10.13  Distrib 8.0.34, for macos13 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.40

--
-- Dumping data for table `controllAppItems`
--

LOCK TABLES `controllAppItems` WRITE;
ALTER TABLE `controllAppItems` DISABLE KEYS
INSERT INTO `controllAppItems` VALUES
('portal','accountSettings','main','bottom','Custom Text for the bottom of the page/section'),
('portal','accountSettings','main','identities','Custom Text for just after the identities header'),
('portal','accountSettings','main','managed','Custom Text for just after the managed header'),
('portal','accountSettings','main','top','Custom Text for the top of the page/section'),
('portal','addUpgrade','main','bottom','Custom Text for the bottom of the page/section'),
('portal','addUpgrade','main','step0','Custom Text for the email address (Step 0)'),
('portal','addUpgrade','main','step1','Custom Text for just after the Step 1 header'),
('portal','addUpgrade','main','step2','Custom Text for just after the Step 2 header'),
('portal','addUpgrade','main','step3','Custom Text for just after the Step 3 header'),
('portal','addUpgrade','main','step4','Custom Text for just after the Step 4 header'),
('portal','addUpgrade','main','step4bottom','Custom Text for just below step 4 (cart) and ahead of the HR (rule line)'),
('portal','addUpgrade','main','top','Custom Text for the top of the page/section'),
('portal','index','main','bottom','Custom Text for the bottom of the page/section'),
('portal','index','main','multiple','Custom Text for juat after the this email has multiple membership accounts'),
('portal','index','main','notloggedin','Text to show if not logged in and not returned from auth link for no account'),
('portal','index','main','top','Custom Text for the top of the page/section'),
('portal','membershipHistory','main','bottom','Custom Text for the bottom of the page/section'),
('portal','membershipHistory','main','top','Custom Text for the top of the page/section'),
('portal','portal','main','bottom','Custom Text for the bottom of the page/section'),
('portal','portal','main','changeEmail','Custom Text for bottom of change email address portal'),
('portal','portal','main','people','Custom Text for just after the people managed header'),
('portal','portal','main','plan','Custom Text for just after the plan header'),
('portal','portal','main','purchased','Custom Text for just aqfter the purchased header'),
('portal','portal','main','top','Custom Text for the top of the page/section');
ALTER TABLE `controllAppItems` ENABLE KEYS;
UNLOCK TABLES;
