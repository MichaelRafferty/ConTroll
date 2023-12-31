/* P2-Local Print Servers */

CREATE TABLE servers (
	serverName varchar(32) not null,
	address varchar(64) not null,
	location varchar(64) null,
	active int not null default 0,
local int not null default 1,
PRIMARY KEY(serverName)
);

CREATE TABLE printers (
	serverName varchar(32) not null,
	printerName varchar(16) not null,
	printerType enum('generic', 'receipt', 'badge') not null DEFAULT 'generic',
active int not null default 0,
codePage enum('PS','HPCL','Dymo4xxPS','Dymo3xxPS','DymoSEL',
'Windows-1252','ASCII','7bit','8bit','UTF-8','UTF-16')
COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Windows-1252',
	PRIMARY KEY(serverName, printerName)
);

ALTER TABLE printers ADD CONSTRAINT printers_server
      FOREIGN KEY (serverName)
	REFERENCES servers (serverName)
	ON DELETE CASCADE
	ON UPDATE CASCADE;

DROP PROCEDURE IF EXISTS syncServerPrinters;
DELIMITER $$
CREATE PROCEDURE syncServerPrinters()
BEGIN

    UPDATE servers ls LEFT OUTER JOIN printservers.servers gs ON (gs.serverName = ls.serverName)
    SET local = CASE
            WHEN gs.serverName IS NULL THEN 1
            ELSE 0
        END;

    CREATE TEMPORARY TABLE del_printers (
            serverName varchar(32) NOT NULL,
            printerName varchar(16) NOT NULL,
            PRIMARY KEY(serverName, printerName));
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

END$$
DELIMITER ;

INSERT INTO patchLog(id, name) values(2, 'Local Print Servers');

