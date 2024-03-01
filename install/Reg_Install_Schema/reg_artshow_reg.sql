-- MySQL dump 10.13  Distrib 8.0.34, for macos13 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.32


--
-- Table structure for table `artshow_reg`
--

DROP TABLE IF EXISTS `artshow_reg`;
CREATE TABLE `artshow_reg` (
  `id` int NOT NULL AUTO_INCREMENT,
  `conid` int NOT NULL DEFAULT '1',
  `max_art` int NOT NULL DEFAULT '0',
  `max_print` int NOT NULL DEFAULT '0',
  `max_mailin` int NOT NULL DEFAULT '0',
  `max_table` int NOT NULL DEFAULT '0',
  `cur_art` int DEFAULT '0',
  `cur_print` int DEFAULT '0',
  `cur_mailin` int DEFAULT '0',
  `cur_table` int DEFAULT '0',
  `per_art` int NOT NULL DEFAULT '0',
  `per_print` int NOT NULL DEFAULT '0',
  `per_table` int NOT NULL DEFAULT '0',
  `art_full` int NOT NULL DEFAULT '0',
  `art_1` int NOT NULL DEFAULT '0',
  `art_2` int NOT NULL DEFAULT '0',
  `table_full` int NOT NULL DEFAULT '0',
  `table_1` int NOT NULL DEFAULT '0',
  `table_2` int NOT NULL DEFAULT '0',
  `table_3` int NOT NULL DEFAULT '0',
  `mailin` int NOT NULL DEFAULT '0',
  `print_full` int NOT NULL DEFAULT '0',
  `print_1` int NOT NULL DEFAULT '0',
  `print_2` int NOT NULL DEFAULT '0',
  `per_mailin` int DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `conid` (`conid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


