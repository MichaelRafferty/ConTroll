-- MySQL dump 10.13  Distrib 8.0.31, for macos12 (x86_64)
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
-- Final view structure for view `memLabel`
--

/*!50001 DROP VIEW IF EXISTS `memLabel`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50001 VIEW `memLabel` AS select `m`.`id` AS `id`,`m`.`conid` AS `conid`,`m`.`sort_order` AS `sort_order`,`m`.`memCategory` AS `memCategory`,`m`.`memType` AS `memType`,`m`.`memAge` AS `memAge`,`m`.`label` AS `shortname`,concat(`m`.`label`,' [',`a`.`label`,']') AS `label`,concat(`m`.`memCategory`,'_',`m`.`memType`,'_',`m`.`memAge`) AS `memGroup`,`m`.`price` AS `price`,`m`.`startdate` AS `startdate`,`m`.`enddate` AS `enddate`,`m`.`atcon` AS `atcon`,`m`.`online` AS `online` from (`memList` `m` join `ageList` `a` on(((`m`.`memAge` = `a`.`ageType`) and (`m`.`conid` = `a`.`conid`)))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Dumping events for database 'reg'
--

--
-- Dumping routines for database 'reg'
--
/*!50003 DROP PROCEDURE IF EXISTS `syncServerPrinters` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'REAL_AS_FLOAT,PIPES_AS_CONCAT,ANSI_QUOTES,IGNORE_SPACE,ONLY_FULL_GROUP_BY,ANSI,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE PROCEDURE "syncServerPrinters"()
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

-- Dump completed on 2023-03-27 14:20:14
