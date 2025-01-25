-- MySQL dump 10.13  Distrib 8.0.34, for macos13 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.40

--
-- Dumping data for table `controllAppPages`
--

LOCK TABLES `controllAppPages` WRITE;
ALTER TABLE `controllAppPages` DISABLE KEYS;
INSERT INTO `controllAppPages` VALUES
('exhibitor','index','Exhibitor Portal Main Page - artist/vendor/fan/exhibits'),
('portal','accountSettings','Sets up management associations and identities for the Registation Portal'),
('portal','addUpgrade','Adds / Updates members including profile, interests and memberships for the Registration Portal'),
('portal','index','Login page for the Registration Portal'),
('portal','membershipHistory','Displays past memberships for the Registation Portal'),
('portal','portal','Home page for the Registration Portal');
ALTER TABLE `controllAppPages` ENABLE KEYS;
UNLOCK TABLES;
