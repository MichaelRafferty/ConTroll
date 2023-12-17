-- MySQL dump 10.13  Distrib 8.0.34, for macos13 (arm64)
--
-- Host: localhost    Database: reg
-- ------------------------------------------------------
-- Server version	8.0.32

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Temporary view structure for view `vw_VendorSpace`
--

DROP TABLE IF EXISTS `vw_VendorSpace`;
/*!50001 DROP VIEW IF EXISTS `vw_VendorSpace`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `vw_VendorSpace` AS SELECT 
 1 AS `id`,
 1 AS `conid`,
 1 AS `vendorId`,
 1 AS `spaceId`,
 1 AS `shortname`,
 1 AS `name`,
 1 AS `item_requested`,
 1 AS `time_requested`,
 1 AS `requested_code`,
 1 AS `requested_description`,
 1 AS `requested_units`,
 1 AS `requested_price`,
 1 AS `requested_sort`,
 1 AS `item_approved`,
 1 AS `time_approved`,
 1 AS `approved_code`,
 1 AS `approved_description`,
 1 AS `approved_units`,
 1 AS `approved_price`,
 1 AS `approved_sort`,
 1 AS `item_purchased`,
 1 AS `time_purchased`,
 1 AS `purchased_code`,
 1 AS `purchased_description`,
 1 AS `purchased_units`,
 1 AS `purchased_price`,
 1 AS `purchased_sort`,
 1 AS `price`,
 1 AS `paid`,
 1 AS `transid`,
 1 AS `membershipCredits`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `memLabel`
--

DROP TABLE IF EXISTS `memLabel`;
/*!50001 DROP VIEW IF EXISTS `memLabel`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `memLabel` AS SELECT 
 1 AS `id`,
 1 AS `conid`,
 1 AS `sort_order`,
 1 AS `memCategory`,
 1 AS `memType`,
 1 AS `memAge`,
 1 AS `shortname`,
 1 AS `label`,
 1 AS `memGroup`,
 1 AS `price`,
 1 AS `startdate`,
 1 AS `enddate`,
 1 AS `atcon`,
 1 AS `online`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `couponMemberships`
--

DROP TABLE IF EXISTS `couponMemberships`;
/*!50001 DROP VIEW IF EXISTS `couponMemberships`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `couponMemberships` AS SELECT 
 1 AS `regId`,
 1 AS `conid`,
 1 AS `perid`,
 1 AS `price`,
 1 AS `couponDiscount`,
 1 AS `paid`,
 1 AS `couponId`,
 1 AS `code`,
 1 AS `name`,
 1 AS `couponType`,
 1 AS `discount`,
 1 AS `oneUse`,
 1 AS `guid`,
 1 AS `useTS`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `couponUsage`
--

DROP TABLE IF EXISTS `couponUsage`;
/*!50001 DROP VIEW IF EXISTS `couponUsage`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `couponUsage` AS SELECT 
 1 AS `conid`,
 1 AS `transId`,
 1 AS `CouponId`,
 1 AS `perid`,
 1 AS `price`,
 1 AS `couponDiscount`,
 1 AS `paid`,
 1 AS `code`,
 1 AS `name`,
 1 AS `couponType`,
 1 AS `discount`,
 1 AS `oneUse`,
 1 AS `guid`,
 1 AS `useTS`*/;
SET character_set_client = @saved_cs_client;

--
-- Final view structure for view `vw_VendorSpace`
--

/*!50001 DROP VIEW IF EXISTS `vw_VendorSpace`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY INVOKER */
/*!50001 VIEW `vw_VendorSpace` AS select `v`.`id` AS `id`,`v`.`conid` AS `conid`,`v`.`vendorId` AS `vendorId`,`v`.`spaceId` AS `spaceId`,`vs`.`shortname` AS `shortname`,`vs`.`name` AS `name`,`req`.`id` AS `item_requested`,`v`.`time_requested` AS `time_requested`,`req`.`code` AS `requested_code`,`req`.`description` AS `requested_description`,`req`.`units` AS `requested_units`,`req`.`price` AS `requested_price`,`req`.`sortOrder` AS `requested_sort`,`app`.`id` AS `item_approved`,`v`.`time_approved` AS `time_approved`,`app`.`code` AS `approved_code`,`app`.`description` AS `approved_description`,`app`.`units` AS `approved_units`,`app`.`price` AS `approved_price`,`app`.`sortOrder` AS `approved_sort`,`pur`.`id` AS `item_purchased`,`v`.`time_purchased` AS `time_purchased`,`pur`.`code` AS `purchased_code`,`pur`.`description` AS `purchased_description`,`pur`.`units` AS `purchased_units`,`pur`.`price` AS `purchased_price`,`pur`.`sortOrder` AS `purchased_sort`,`v`.`price` AS `price`,`v`.`paid` AS `paid`,`v`.`transid` AS `transid`,`v`.`membershipCredits` AS `membershipCredits` from ((((`vendor_space` `v` join `vendorSpaces` `vs` on((`vs`.`id` = `v`.`spaceId`))) left join `vendorSpacePrices` `req` on((`v`.`item_requested` = `req`.`id`))) left join `vendorSpacePrices` `app` on((`v`.`item_approved` = `app`.`id`))) left join `vendorSpacePrices` `pur` on((`v`.`item_purchased` = `pur`.`id`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `memLabel`
--

/*!50001 DROP VIEW IF EXISTS `memLabel`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`syd`@`%` SQL SECURITY INVOKER */
/*!50001 VIEW `memLabel` AS select `m`.`id` AS `id`,`m`.`conid` AS `conid`,`m`.`sort_order` AS `sort_order`,`m`.`memCategory` AS `memCategory`,`m`.`memType` AS `memType`,`m`.`memAge` AS `memAge`,`m`.`label` AS `shortname`,concat(`m`.`label`,' [',`a`.`label`,']') AS `label`,concat(`m`.`memCategory`,'_',`m`.`memType`,'_',`m`.`memAge`) AS `memGroup`,`m`.`price` AS `price`,`m`.`startdate` AS `startdate`,`m`.`enddate` AS `enddate`,`m`.`atcon` AS `atcon`,`m`.`online` AS `online` from (`memList` `m` join `ageList` `a` on(((`m`.`memAge` = `a`.`ageType`) and (`m`.`conid` = `a`.`conid`)))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `couponMemberships`
--

/*!50001 DROP VIEW IF EXISTS `couponMemberships`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY INVOKER */
/*!50001 VIEW `couponMemberships` AS select `r`.`id` AS `regId`,`r`.`conid` AS `conid`,`r`.`perid` AS `perid`,`r`.`price` AS `price`,`r`.`couponDiscount` AS `couponDiscount`,`r`.`paid` AS `paid`,`c`.`id` AS `couponId`,`c`.`code` AS `code`,`c`.`name` AS `name`,`c`.`couponType` AS `couponType`,`c`.`discount` AS `discount`,`c`.`oneUse` AS `oneUse`,`k`.`guid` AS `guid`,`k`.`useTS` AS `useTS` from ((`reg` `r` join `coupon` `c` on((`c`.`id` = `r`.`coupon`))) left join `couponKeys` `k` on((`k`.`usedBy` = `r`.`create_trans`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `couponUsage`
--

/*!50001 DROP VIEW IF EXISTS `couponUsage`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY INVOKER */
/*!50001 VIEW `couponUsage` AS select `t`.`conid` AS `conid`,`t`.`id` AS `transId`,`c`.`id` AS `CouponId`,`t`.`perid` AS `perid`,`t`.`price` AS `price`,`t`.`couponDiscount` AS `couponDiscount`,`t`.`paid` AS `paid`,`c`.`code` AS `code`,`c`.`name` AS `name`,`c`.`couponType` AS `couponType`,`c`.`discount` AS `discount`,`c`.`oneUse` AS `oneUse`,`k`.`guid` AS `guid`,`k`.`useTS` AS `useTS` from ((`transaction` `t` join `coupon` `c` on((`c`.`id` = `t`.`coupon`))) left join `couponKeys` `k` on((`k`.`usedBy` = `t`.`id`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Dumping events for database 'reg'
--

--
-- Dumping routines for database 'reg'
--
/*!50003 DROP FUNCTION IF EXISTS `uuid_v4s` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'REAL_AS_FLOAT,PIPES_AS_CONCAT,ANSI_QUOTES,IGNORE_SPACE,ONLY_FULL_GROUP_BY,ANSI,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE FUNCTION "uuid_v4s"() RETURNS char(36) CHARSET utf8mb4 COLLATE utf8mb4_general_ci
    NO SQL
    SQL SECURITY INVOKER
BEGIN
    -- 1th and 2nd block are made of 6 random bytes
    SET @h1 = HEX(RANDOM_BYTES(4));
    SET @h2 = HEX(RANDOM_BYTES(2));

    -- 3th block will start with a 4 indicating the version, remaining is random
    SET @h3 = SUBSTR(HEX(RANDOM_BYTES(2)), 2, 3);

    -- 4th block first nibble can only be 8, 9 A or B, remaining is random
    SET @h4 = CONCAT(HEX(FLOOR(ASCII(RANDOM_BYTES(1)) / 64)+8),
                SUBSTR(HEX(RANDOM_BYTES(2)), 2, 3));

    -- 5th block is made of 6 random bytes
    SET @h5 = HEX(RANDOM_BYTES(6));

    -- Build the complete UUID
    RETURN LOWER(CONCAT(
        @h1, '-', @h2, '-4', @h3, '-', @h4, '-', @h5
    ));
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `uuid_v4s2` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'REAL_AS_FLOAT,PIPES_AS_CONCAT,ANSI_QUOTES,IGNORE_SPACE,ONLY_FULL_GROUP_BY,ANSI,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE FUNCTION "uuid_v4s2"() RETURNS char(36) CHARSET utf8mb4 COLLATE utf8mb4_general_ci
    NO SQL
    SQL SECURITY INVOKER
BEGIN
    -- 1th and 2nd block are made of 6 random bytes
    SET @h1 = HEX(RANDOM_BYTES(4));
    SET @h2 = HEX(RANDOM_BYTES(2));

    -- 3th block will start with a 4 indicating the version, remaining is random
    SET @h3 = SUBSTR(HEX(RANDOM_BYTES(2)), 2, 3);

    -- 4th block first nibble can only be 8, 9 A or B, remaining is random
    SET @h4 = CONCAT(HEX(FLOOR(ASCII(RANDOM_BYTES(1)) / 64)+8),
                SUBSTR(HEX(RANDOM_BYTES(2)), 2, 3));

    -- 5th block is made of 6 random bytes
    SET @h5 = HEX(RANDOM_BYTES(6));

    -- Build the complete UUID
    RETURN LOWER(CONCAT(
        @h1, '-', @h2, '-4', @h3, '-', @h4, '-', @h5
    ));
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `mergePerid` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'REAL_AS_FLOAT,PIPES_AS_CONCAT,ANSI_QUOTES,IGNORE_SPACE,ONLY_FULL_GROUP_BY,ANSI,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE PROCEDURE "mergePerid"(IN userid INT, IN to_mergePID INT, IN to_survivePID INT, OUT statusmsg TEXT, OUT rollback_log TEXT)
    SQL SECURITY INVOKER
BEGIN
/* updates the database to change records with to_mergePID to to_survivePID to preserver referential integrity as it merges two perinfo records together
/* tables with perinfo refs:
	artist
	artsales
    artshow
    atcon_user
    badgeList
    club
    couponkeys
    newperson
    payments
    reg
    reg_history
    transaction
    
    Cannot do a simple update using ON UPDATE CASCADE as that will create a duplicate key in the perinfo table, so they need to be done separately.
    Can do in any order because the old record will still exist
    */
    
    DECLARE msg text;
    DECLARE rollback_stmts text;
    DECLARE stmt varchar(8192);
    DECLARE cnt int;
    DECLARE error_string TEXT;
    DECLARE trans_time VARCHAR(64);
    
	SET trans_time = CURRENT_TIMESTAMP;
	SET msg = '';
    SET rollback_stmts = '';
    
    START TRANSACTION;
    
procBlock: BEGIN
	DECLARE EXIT HANDLER FOR SQLEXCEPTION
	BEGIN
		GET DIAGNOSTICS CONDITION 1
			error_string = MESSAGE_TEXT;
		SET statusmsg = CONCAT(msg, CHAR(10), 'SQL Exception: ' , error_string, CHAR(10));
		ROLLBACK;
	END;

    SET cnt = (SELECT COUNT(*) FROM perinfo WHERE id = to_mergePID);
    IF cnt != 1 THEN
		SET msg = 'to_mergePID not found';
		LEAVE procBlock;
	END IF;
    
	SET cnt = (SELECT COUNT(*) FROM perinfo WHERE id = to_survivePID);
    IF cnt != 1 THEN
		SET msg = 'to_survivePID not found';
		LEAVE procBlock;
	END IF;
    
    /* artist */
    SET stmt = (SELECT CONCAT('UPDATE artist SET artist = ', to_mergePID, ' WHERE ID IN (', group_concat(id SEPARATOR ','), ');')
		FROM artist 
        WHERE artist = to_mergePID); 
    
    IF stmt is not null THEN
    	UPDATE artist SET artist = to_survivePID where artist = to_mergePID;
		SET msg = CONCAT(msg, 'artist:  ', CONVERT(ROW_COUNT(), char), CHAR(10));
    
		SET rollback_stmts = CONCAT(rollback_stmts, stmt, CHAR(10));
    END IF;
    
	/* artsales */
	SET stmt = (SELECT CONCAT('UPDATE artsales SET perid = ', to_mergePID, ' WHERE ID IN (', group_concat(id SEPARATOR ','), ');')
		FROM artsales 
        WHERE perid = to_mergePID); 
    
    IF stmt is not null THEN
    	UPDATE artsales SET perid = to_survivePID where perid = to_mergePID;
		SET msg = CONCAT(msg, 'artsales:  ', CONVERT(ROW_COUNT(), char), CHAR(10));
    
		SET rollback_stmts = CONCAT(rollback_stmts, stmt, CHAR(10));
    END IF;
    
    /* artshow */
    SET stmt = (SELECT CONCAT('UPDATE artshow SET perid = ', to_mergePID, ' WHERE ID IN (', group_concat(id SEPARATOR ','), ');')
		FROM artshow 
        WHERE perid = to_mergePID); 
    
    IF stmt is not null THEN
    	UPDATE artshow SET perid = to_survivePID where perid = to_mergePID;
		SET msg = CONCAT(msg, 'artshow:  ', CONVERT(ROW_COUNT(), char), CHAR(10));
    
		SET rollback_stmts = CONCAT(rollback_stmts, stmt, CHAR(10));
    END IF;
    
    /* atcon_user */
    SET stmt = (SELECT CONCAT('UPDATE atcon_user SET perid = ', to_mergePID, ' WHERE ID IN (', group_concat(id SEPARATOR ','), ');')
		FROM atcon_user 
        WHERE perid = to_mergePID); 
    
    IF stmt is not null THEN
    	UPDATE atcon_user SET perid = to_survivePID where perid = to_mergePID;
		SET msg = CONCAT(msg, 'atcon_user:  ', CONVERT(ROW_COUNT(), char), CHAR(10));
    
		SET rollback_stmts = CONCAT(rollback_stmts, stmt, CHAR(10));
    END IF;
    
    /* badgreList */
    SET stmt = (SELECT CONCAT('UPDATE badgeList SET perid = ', to_mergePID, ' WHERE ID IN (', group_concat(id SEPARATOR ','), ');')
		FROM badgeList 
        WHERE perid = to_mergePID); 
    
    IF stmt is not null THEN
    	UPDATE badgeList SET perid = to_survivePID where perid = to_mergePID;
		SET msg = CONCAT(msg, 'badgeList:  ', CONVERT(ROW_COUNT(), char), CHAR(10));
    
		SET rollback_stmts = CONCAT(rollback_stmts, stmt, CHAR(10));
    END IF;
    
    /* club */
    SET stmt = (SELECT CONCAT('UPDATE club SET perid = ', to_mergePID, ' WHERE ID IN (', group_concat(id SEPARATOR ','), ');')
		FROM club 
        WHERE perid = to_mergePID); 
    
    IF stmt is not null THEN
    	UPDATE club SET perid = to_survivePID where perid = to_mergePID;
		SET msg = CONCAT(msg, 'club:  ', CONVERT(ROW_COUNT(), char), CHAR(10));
    
		SET rollback_stmts = CONCAT(rollback_stmts, stmt, CHAR(10));
    END IF;
    
    /* couponKeys */
    SET stmt = (SELECT CONCAT('UPDATE couponKeys SET perid = ', to_mergePID, ' WHERE ID IN (', group_concat(id SEPARATOR ','), ');')
		FROM couponKeys 
        WHERE perid = to_mergePID); 
    
    IF stmt is not null THEN
    	UPDATE couponKeys SET perid = to_survivePID where perid = to_mergePID;
		SET msg = CONCAT(msg, 'couponKeys:  ', CONVERT(ROW_COUNT(), char), CHAR(10));
    
		SET rollback_stmts = CONCAT(rollback_stmts, stmt, CHAR(10));
    END IF;
    
    /* newperson */
    SET stmt = (SELECT CONCAT('UPDATE newperson SET perid = ', to_mergePID, ' WHERE ID IN (', group_concat(id SEPARATOR ','), ');')
		FROM newperson 
        WHERE perid = to_mergePID); 
    
    IF stmt is not null THEN
    	UPDATE newperson SET perid = to_survivePID where perid = to_mergePID;
		SET msg = CONCAT(msg, 'newperson:  ', CONVERT(ROW_COUNT(), char), CHAR(10));
    
		SET rollback_stmts = CONCAT(rollback_stmts, stmt, CHAR(10));
    END IF;
    
    /* payments */
    SET stmt = (SELECT CONCAT('UPDATE payments SET cashier = ', to_mergePID, ' WHERE ID IN (', group_concat(id SEPARATOR ','), ');')
		FROM payments 
        WHERE cashier = to_mergePID); 
    
    IF stmt is not null THEN
    	UPDATE payments SET cashier = to_survivePID where cashier = to_mergePID;
		SET msg = CONCAT(msg, 'payments:  ', CONVERT(ROW_COUNT(), char), CHAR(10));
    
		SET rollback_stmts = CONCAT(rollback_stmts, stmt, CHAR(10));
    END IF;
    
    /* reg */
    SET stmt = (SELECT CONCAT('UPDATE reg SET perid = ', to_mergePID, ' WHERE ID IN (', group_concat(id SEPARATOR ','), ');')
		FROM reg 
        WHERE perid = to_mergePID); 
    
    IF stmt is not null THEN
    	UPDATE reg SET perid = to_survivePID where perid = to_mergePID;
		SET msg = CONCAT(msg, 'reg:  ', CONVERT(ROW_COUNT(), char), CHAR(10));
    
		SET rollback_stmts = CONCAT(rollback_stmts, stmt, CHAR(10));
    END IF;
    
    /* reg_history */
    SET stmt = (SELECT CONCAT('UPDATE reg_history SET userid = ', to_mergePID, ' WHERE ID IN (', group_concat(id SEPARATOR ','), ');')
		FROM reg_history 
        WHERE userid = to_mergePID); 
    
    IF stmt is not null THEN
    	UPDATE reg_history SET userid = to_survivePID where userid = to_mergePID;
		SET msg = CONCAT(msg, 'reg_history:  ', CONVERT(ROW_COUNT(), char), CHAR(10));
    
		SET rollback_stmts = CONCAT(rollback_stmts, stmt, CHAR(10));
    END IF;
    
    /* transaction */
    SET stmt = (SELECT CONCAT('UPDATE transaction SET perid = ', to_mergePID, ' WHERE ID IN (', group_concat(id SEPARATOR ','), ');')
		FROM transaction 
        WHERE perid = to_mergePID); 
    
    IF stmt is not null THEN
		UPDATE transaction SET perid = to_survivePID where perid = to_mergePID;
		SET msg = CONCAT(msg, 'transaction:  ', CONVERT(ROW_COUNT(), char), CHAR(10));
    
		SET rollback_stmts = CONCAT(rollback_stmts, stmt, CHAR(10));
    END IF;
    
    /* perinfo */
    
	SET stmt = (SELECT CONCAT('UPDATE perinfo SET first_name = ''', REPLACE(IFNULL(first_name,''), '''', ''''''),
		''', middle_name = ''', REPLACE(IFNULL(middle_name,''), '''', ''''''),
		''', last_name = ''', REPLACE(IFNULL(last_name,''), '''', ''''''),
        ''', change_notes = CONCAT(''rollback merge'',CHAR(10),''', REPLACE(IFNULL(change_notes,''), '''', ''''''), ''')',
        ' where id = ', to_mergePID, ';')
        AS cmd
		FROM perinfo
		WHERE id = to_mergePID);
    
    SET rollback_stmts = CONCAT(rollback_stmts, stmt, CHAR(10));
    UPDATE perinfo
    SET
		change_notes = CONCAT(trans_time, ':  User ', userid, ' merged into ', to_survivePID, CHAR(10),
			'was: first_name: "', first_name, '", middle_name: "', middle_name, '", last_name: "', last_name, '"', CHAR(10),
            'rollback_stmts=', CHAR(10),
            REPLACE(rollback_stmts, '''', ''''''), '''', char(10)
            ),
		first_name = 'Merged', middle_name = 'into', last_name = to_survivePID
    WHERE id = to_mergePID;
    SET msg = CONCAT(msg, 'perinfo: ', to_mergePID, ': ', CONVERT(ROW_COUNT(), char), CHAR(10));
    
    COMMIT;
    
END procBlock;
    
/* SET statusmsg =  msg; */
SET statusmsg = msg;
SET rollback_log = CONCAT(trans_time, ':  User ', userid, ' merged ', to_mergePID, ' into ', to_survivePID, CHAR(10), rollback_stmts);

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `syncServerPrinters` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'REAL_AS_FLOAT,PIPES_AS_CONCAT,ANSI_QUOTES,IGNORE_SPACE,ONLY_FULL_GROUP_BY,ANSI,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE PROCEDURE "syncServerPrinters"()
    SQL SECURITY INVOKER
BEGIN
    UPDATE servers ls LEFT OUTER JOIN printservers.servers gs ON (gs.serverName = ls.serverName)
    SET local = CASE
            WHEN gs.serverName IS NULL THEN 1
            ELSE 0
        END;

    CREATE TEMPORARY TABLE del_printers
    SELECT lp.serverName, lp.printerName FROM printers lp
    JOIN printservers.servers gs ON (gs.serverName = lp.serverName)
    LEFT OUTER JOIN printservers.printers gp ON (lp.serverName = gp.serverName AND lp.printerName = gp.printerName)
    WHERE gp.serverName is null;

    DELETE p FROM printers p JOIN del_printers d
    WHERE p.serverName = d.serverName and p.printerName = d.printerName;

    DROP TEMPORARY TABLE del_printers;
    
    INSERT INTO servers(serverName, address, location, active, local)
    SELECT P.serverName, P.address, '', '0', 0
    FROM printservers.servers P
    LEFT OUTER JOIN servers S ON (P.servername = S.servername)
    WHERE S.servername IS NULL;

    INSERT INTO printers(serverName, printerName, printerType, active)
    SELECT s.serverName, s.printerName, s.printerType, 0
    FROM printservers.printers s
    LEFT OUTER JOIN printers p ON (p.serverName = s.serverName AND p.printerName = s.printerName)
    WHERE p.printerName IS NULL;

    UPDATE printers p
    JOIN printservers.printers s ON (p.serverName = s.serverName AND p.printerName = s.printerName)
    SET p.printerType = s.printerType
    WHERE s.printerType != p.printertype;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2023-10-26 13:36:44
