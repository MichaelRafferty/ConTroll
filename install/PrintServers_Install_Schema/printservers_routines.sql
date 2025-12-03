-- MySQL dump 10.13  Distrib 8.0.42, for macos15 (arm64)
--
-- Host: localhost    Database: printservers
-- ------------------------------------------------------
-- Server version	8.0.40

--
-- Dumping routines for database 'printservers'
--

DROP PROCEDURE IF EXISTS syncServerPrinters
DELIMITER ;;
CREATE PROCEDURE syncServerPrinters()
SQL SECURITY INVOKER
BEGIN

    UPDATE servers ls
    LEFT OUTER JOIN printservers.servers gs ON (gs.serverName = ls.serverName)
    SET local = CASE
        WHEN gs.serverName IS NULL THEN 1
        ELSE 0
    END;

    CREATE TEMPORARY TABLE del_printers (
        serverName varchar(32) NOT NULL,
        printerName varchar(16) NOT NULL,
        PRIMARY KEY(serverName, printerName)
    );
	INSERT INTO del_printers(serverName, printerName)
    SELECT lp.serverName, lp.printerName FROM printers lp
    JOIN printservers.servers gs ON (gs.serverName = lp.serverName)
    LEFT OUTER JOIN printservers.printers gp ON (lp.serverName = gp.serverName AND lp.printerName = gp.printerName)
    WHERE gp.serverName is null;

    DELETE p FROM printers p JOIN del_printers d
    WHERE p.serverName = d.serverName and p.printerName = d.printerName;

    DROP TEMPORARY TABLE del_printers;
    
    INSERT INTO servers(serverName, address, location, active, local)
    SELECT P.serverName, P.address, '', 0, 0
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
