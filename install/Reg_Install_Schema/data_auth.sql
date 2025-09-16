-- MySQL dump 10.13  Distrib 8.0.42, for macos15 (arm64)
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
(1,'overview','Y','Membership',10),
(2,'admin','Y','Admin',20),
(3,'people','Y','People',50),
(5,'registration','Y','Registration',40),
(6,'reg_staff','Y','Registration Admin',30),
(7,'finance','Y','Finance',80),
(8,'lookup','Y','Reg Lookup',70),
(9,'badge','Y','Free Badges',60),
(10,'atcon','N','N',1000),
(11,'art_control','Y','Art Control',100),
(13,'club','Y','Club',110),
(14,'monitor','Y','Attendance',120),
(15,'reports','Y','Reports',130),
(16,'search','N','N',1600),
(19,'coupon','N','N',80),
(20,'gen_rpts','N','N',135),
(21,'reg_admin','N','N',140),
(22,'reg_ad_menu','N','N',145),
(32,'exhibitor','Y','Exhibitors',90);
ALTER TABLE `auth` ENABLE KEYS;
UNLOCK TABLES;
