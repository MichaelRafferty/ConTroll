-- MySQL dump 10.13  Distrib 8.0.42, for macos15 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.40


--
-- Table structure for table `reg`
--

DROP TABLE IF EXISTS `reg`;
CREATE TABLE `reg` (
  `id` int NOT NULL AUTO_INCREMENT,
  `conid` int DEFAULT NULL,
  `perid` int DEFAULT NULL,
  `newperid` int DEFAULT NULL,
  `oldperid` int DEFAULT NULL,
  `priorRegId` int DEFAULT NULL,
  `create_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `change_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `pickup_date` datetime DEFAULT NULL,
  `price` decimal(8,2) NOT NULL,
  `couponDiscount` decimal(8,2) DEFAULT '0.00',
  `paid` decimal(8,2) DEFAULT '0.00',
  `create_trans` int DEFAULT NULL,
  `complete_trans` int DEFAULT NULL,
  `locked` enum('N','Y') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `create_user` int DEFAULT NULL,
  `updatedBy` int DEFAULT NULL,
  `memId` int DEFAULT NULL,
  `coupon` int DEFAULT NULL,
  `planId` int DEFAULT NULL,
  `printable` enum('N','Y') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'N',
  `status` enum('unpaid','plan','paid','cancelled','refunded','transfered','upgraded','rolled-over') COLLATE utf8mb4_general_ci DEFAULT 'unpaid',
  PRIMARY KEY (`id`),
  KEY `reg_perid_fk` (`perid`),
  KEY `reg_conid_fk` (`conid`),
  KEY `reg_oldperid_fk` (`oldperid`),
  KEY `reg_newperid_fk` (`newperid`),
  KEY `reg_create_trans_fk` (`create_trans`),
  KEY `reg_memId_fk` (`memId`),
  KEY `reg_coupon_fk` (`coupon`),
  KEY `reg_complete_fk` (`complete_trans`),
  KEY `reg_planid_fk` (`planId`),
  KEY `reg_priorRegId_fk` (`priorRegId`),
  KEY `regStatus_idx` (`status`,`conid`,`perid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DELIMITER ;;
CREATE DEFINER=CURRENT_USER  TRIGGER `reg_update` BEFORE UPDATE ON `reg` FOR EACH ROW BEGIN
    IF (OLD.id != NEW.id OR OLD.conid != NEW.conid OR OLD.perid != NEW.perid OR OLD.newperid != NEW.newperid
        OR OLD.oldperid != NEW.oldperid OR OLD.priorRegId != NEW.priorRegId OR OLD.create_date != NEW.create_date
        OR OLD.pickup_date != NEW.pickup_date OR OLD.price != NEW.price
        OR OLD.couponDiscount != NEW.couponDiscount OR OLD.paid != NEW.paid OR OLD.create_trans != NEW.create_trans
        OR OLD.complete_trans != NEW.complete_trans OR OLD.locked != NEW.locked OR OLD.create_user != NEW.create_user
        OR OLD.updatedBy != NEW.updatedBy OR OLD.memId != NEW.memId OR OLD.coupon != NEW.coupon
        OR OLD.planId != NEW.planId OR OLD.printable != NEW.printable OR OLD.status != NEW.status)
    THEN
        INSERT INTO regHistory(id, conid, perid, newperid, oldperid, create_date, change_date, pickup_date, price, couponDiscount,
                               paid, create_trans, complete_trans, locked, create_user, updatedBy, memId, coupon, planId, printable, status)
        VALUES (OLD.id, OLD.conid, OLD.perid, OLD.newperid, OLD.oldperid, OLD.create_date, OLD.change_date, OLD.pickup_date,
                OLD.price, OLD.couponDiscount, OLD.paid, OLD.create_trans, OLD.complete_trans, OLD.locked, OLD.create_user,
                OLD.updatedBy, OLD.memId, OLD.coupon, OLD.planId, OLD.printable, OLD.status);
    END IF;
END;;
DELIMITER ;


