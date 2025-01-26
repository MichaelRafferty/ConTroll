-- MySQL dump 10.13  Distrib 8.0.34, for macos13 (arm64)
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
(1,'overview','Y','Membership',20),
(2,'admin','Y','Admin',10),
(3,'people','Y','People',40),
(5,'registration','Y','Registration',50),
(6,'reg_admin','Y','Registration Admin',30),
(7,'finance','Y','Finance',55),
(9,'badge','Y','Free Badges',60),
(10,'atcon','N','N',1000),
(11,'art_control','Y','Art Control',90),
(13,'club','Y','Club',100),
(14,'monitor','Y','Attendance',110),
(15,'reports','Y','Reports',120),
(16,'search','N','N',1600),
(17,'atcon_checkin','N','N',1700),
(18,'atcon_register','N','N',1800),
(19,'coupon','Y','Coupon',70),
(32,'exhibitor','Y','Exhibitors',80);
ALTER TABLE `auth` ENABLE KEYS;
UNLOCK TABLES;
