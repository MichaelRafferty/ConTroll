-- MySQL dump 10.13  Distrib 8.0.34, for macos13 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.32


--
-- Dumping data for table `auth`
--

LOCK TABLES `auth` WRITE;
ALTER TABLE `auth` DISABLE KEYS ;
INSERT INTO `auth` VALUES
    (1,'overview','Y','Membership'),
    (2,'admin','Y','Admin'),
    (3,'people','Y','People'),
    (5,'registration','Y','Registration'),
    (6,'reg_admin','Y','Badge List'),
    (7,'artist','N','Artist'),
    (8,'artshow','N','Artshow'),
    (9,'badge','Y','Free Badges'),
    (10,'atcon','N','N'),
    (11,'art_control','Y','Art Control'),
    (12,'art_sales','N','N'),
    (13,'club','Y','Club'),
    (14,'monitor','Y','Attendance'),
    (15,'reports','Y','Reports'),
    (16,'search','N','N'),
    (17,'atcon_checkin','N','N'),
    (18,'atcon_register','N','N'),
    (19,'coupon','Y','Coupon'),
    (32,'vendor','Y','Exhibitors'),
    (999,'registration-old','N','Old Reg');
ALTER TABLE `auth` ENABLE KEYS ;
UNLOCK TABLES;
