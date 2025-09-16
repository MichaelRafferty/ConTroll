-- MySQL dump 10.13  Distrib 8.0.42, for macos15 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.40


--
-- Table structure for table `artItems`
--

DROP TABLE IF EXISTS `artItems`;
CREATE TABLE `artItems` (
  `id` int NOT NULL AUTO_INCREMENT,
  `item_key` int NOT NULL,
  `title` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `type` enum('art','nfs','print') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('Entered','Not In Show','Checked In','Removed from Show','BID','Quicksale/Sold','To Auction','Sold Bid Sheet','Sold at Auction','Checked Out','Purchased/Released') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'Entered',
  `location` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `quantity` int NOT NULL,
  `original_qty` int NOT NULL,
  `min_price` decimal(11,2) NOT NULL DEFAULT '0.00',
  `sale_price` decimal(11,2) DEFAULT NULL,
  `final_price` decimal(11,2) DEFAULT NULL,
  `bidder` int DEFAULT NULL,
  `conid` int DEFAULT NULL,
  `artshow` int DEFAULT NULL,
  `time_updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updatedBy` int NOT NULL,
  `material` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `exhibitorRegionYearId` int DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`),
  KEY `artItems_artshow_fk` (`artshow`),
  KEY `artItemsHistory_conid_fk` (`conid`),
  KEY `aIH_exhibitorRegionYear_fk` (`exhibitorRegionYearId`),
  KEY `artItemsHistory_updatedBy_fk` (`updatedBy`),
  KEY `artItems_fk_bidder` (`bidder`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DELIMITER ;;
CREATE DEFINER=CURRENT_USER  TRIGGER `artItems_update` BEFORE UPDATE ON `artItems` FOR EACH ROW BEGIN
IF (OLD.id != NEW.id OR OLD.item_key != NEW.item_key OR OLD.title != NEW.title OR OLD.type != NEW.type OR OLD.status != NEW.status
OR OLD.location != NEW.location OR OLD.quantity != NEW.quantity OR OLD.original_qty != NEW.original_qty
OR OLD.min_price != NEW.min_price OR OLD.sale_price != NEW.sale_price OR OLD.final_price != NEW.final_price
OR OLD.bidder != NEW.bidder OR OLD.conid != NEW.conid OR OLD.artshow != NEW.artshow
OR OLD.updatedBy != NEW.updatedBy OR OLD.material != NEW.material OR OLD.exhibitorRegionYearId != NEW.exhibitorRegionYearId
OR OLD.notes != NEW.notes)
THEN
INSERT INTO artItemsHistory(id, item_key, title, type, status, location, quantity, original_qty, min_price, sale_price,
final_price, bidder, conid, artshow, time_updated, updatedBy, material, exhibitorRegionYearId, notes)
VALUES (OLD.id, OLD.item_key, OLD.title, OLD.type, OLD.status, OLD.location, OLD.quantity, OLD.original_qty, OLD.min_price, OLD.sale_price,
OLD.final_price, OLD.bidder, OLD.conid, OLD.artshow, OLD.time_updated, OLD.updatedBy, OLD.material, OLD.exhibitorRegionYearId, OLD.notes);
END IF;
END;;
DELIMITER ;


