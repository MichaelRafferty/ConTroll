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
                              (13,'reg_history_actions','2023-12-22 21:14:32'),(14,'mergePerid_proc','2023-12-09 16:53:25'),(15,'badgeList_perid','2023-12-22 21:15:31'),
                              (16,'badgePrn','2023-12-28 11:01:10'), (17,'exhibitor','2024-02-08 13:20:00'), (18,'legalname','2024-03-01 09:34:27'),
                              (19,'state','2024-03-02 18:50:13'),(20,'artitems','2024-03-23 13:36:57'),(21,'payment_types','2024-03-23 13:38:40'),
                              (22,'email_length','2024-03-24 20:22:49'),(23,'artitems_validate','2024-03-31 21:07:50'),
			      (25,'exhibitor shipState','2024-04-10 20:39:43'),(26,'userid fk to perid fk','2024-04-19 21:55:49');

ALTER TABLE `patchLog` ENABLE KEYS ;
UNLOCK TABLES;
