-- MySQL dump 10.13  Distrib 8.0.34, for macos13 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.32


--
-- Dumping data for table `patchLog`
--

LOCK TABLES `patchLog` WRITE;
ALTER TABLE `patchLog` DISABLE KEYS ;
INSERT INTO `patchLog` VALUES (1,'ATCON Auth Changes','2023-09-21 19:55:19'),(2,'Local Print Servers','2023-09-21 19:58:10'),(3,'Atcon History','2023-09-21 19:59:54'),
                              (4,'Rename BSFS to Club','2023-09-21 20:03:04'),(5,'memList  to DateTime','2023-09-21 20:03:22'),(6,'Foreign Keys','2023-09-21 20:06:43'),
                              (7,'volrollover auth','2023-09-21 20:07:56'),(8,'new vendor','2023-12-22 21:16:01'),(9,'coupons','2023-09-21 20:15:09'),
                              (10,'oldreg','2023-09-21 20:41:49'),(11,'reg_history','2023-10-27 20:03:26'),(12,'reg_complete_trans','2023-12-22 21:13:24'),
                              (13,'reg_history_actions','2023-12-22 21:14:32'),(14,'mergePerid_proc','2023-12-09 16:53:25'),(15,'badgeList_perid','2023-12-22 21:15:31');
ALTER TABLE `patchLog` ENABLE KEYS ;
UNLOCK TABLES;


-- Dump completed on 2023-12-21 16:30:05
