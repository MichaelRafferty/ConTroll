-- MySQL dump 10.13  Distrib 8.0.44, for macos15 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.40

--
-- Dumping data for table `auth`
--

LOCK TABLES `auth` WRITE;
ALTER TABLE `auth` DISABLE KEYS;
INSERT INTO `auth` VALUES
(1,'overview','Y','Membership',100),
(2,'admin','Y','Admin',20),
(3,'people','Y','People',500),
(5,'registration','Y','Registration',400),
(6,'reg_staff','Y','Registration Admin',300),
(7,'finance','Y','Finance',800),
(8,'lookup','Y','Reg Lookup',700),
(9,'badge','Y','Free Badges',600),
(10,'atcon','N','N',10000),
(11,'art_control','Y','Art Control',1000),
(13,'club','Y','Club',1100),
(14,'monitor','Y','Attendance',1200),
(15,'reports','Y','Reports',1300),
(16,'search','N','N',16000),
(19,'coupon','N','N',800),
(20,'gen_rpts','N','N',1350),
(21,'reg_admin','N','N',1400),
(22,'reg_ad_menu','N','N',1450),
(32,'exhibitor','Y','Exhibitors',900);
ALTER TABLE `auth` ENABLE KEYS;
UNLOCK TABLES;
