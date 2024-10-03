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
INSERT INTO `patchLog` VALUES
(1,'ATCON Auth Changes','2023-09-21 19:55:19'),
(2,'Local Print Servers','2023-09-21 19:58:10'),
(3,'Atcon History','2023-09-21 19:59:54'),
(4,'Rename BSFS to Club','2023-09-21 20:03:04'),
(5,'memList  to DateTime','2023-09-21 20:03:22'),
(6,'Foreign Keys','2023-09-21 20:06:43'),
(7,'volrollover auth','2023-09-21 20:07:56'),
(8,'new vendor','2023-12-22 21:16:01'),
(9,'coupons','2023-09-21 20:15:09'),
(10,'oldreg','2023-09-21 20:41:49'),
(11,'reg_history','2023-10-27 20:03:26'),
(12,'reg_complete_trans','2023-12-22 21:13:24'),
(13,'reg_history_actions','2023-12-22 21:14:32'),
(14,'mergePerid_proc','2023-12-09 16:53:25'),
(15,'badgeList_perid','2023-12-22 21:15:31'),
(16,'badgePrn','2023-12-28 16:01:10'),
(18,'legalname','2024-03-01 14:34:27'),
(19,'state','2024-03-02 23:50:13'),
(20,'artitems','2024-03-23 17:36:57'),
(21,'payment_types','2024-03-23 17:38:40'),
(22,'email_length','2024-03-25 00:22:49'),
(23,'artitems_validate','2024-03-31 21:07:50'),
(25,'exhibitor shipState','2024-04-10 20:39:43'),
(26,'userid fk to perid fk','2024-04-19 21:55:49'),
(27,'logging triggers','2024-04-26 18:38:03'),
(28,'artist name','2024-05-31 12:59:33'),
(30,'Portal Changes','2024-08-03 01:05:13'),
(32,'regHistory','2024-08-12 15:49:17'),
(33,'paymentplans','2024-09-27 15:24:42');
ALTER TABLE `patchLog` ENABLE KEYS ;
UNLOCK TABLES;
