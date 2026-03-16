/*
 * P57 - continued refinement of Registration Portal and extensions of cross year back end
 */

ALTER TABLE memList ADD COLUMN cartDesc text DEFAULT null AFTER label;

DROP VIEW IF EXISTS `memLabel`;
CREATE ALGORITHM=UNDEFINED
    SQL SECURITY INVOKER
    VIEW memLabel AS
SELECT m.id AS id,m.conid AS conid,m.sort_order AS sort_order,m.memCategory AS memCategory,m.memType AS memType,m.memAge AS memAge,
       a.shortname AS ageShortName,m.label AS shortname,concat(m.label,' [',a.label,']') AS label,m.cartDesc AS cartDesc,
       m.notes AS notes,m.price AS price, m.startdate AS startdate,m.enddate AS enddate,
       m.atcon AS atcon,m.online AS online,m.glNum AS glNum,m.glLabel AS glLabel, c.taxable AS taxable
FROM memList m
JOIN ageList a ON (m.memAge = a.ageType) AND (m.conid = a.conid)
JOIN memCategories c ON m.memCategory = c.memCategory;

ALTER TABLE exhibitsRegionYears ADD COLUMN revenueGlNum varchar(16) COLLATE utf8mb4_general_ci DEFAULT NULL AFTER ownerEmail;
ALTER TABLE exhibitsRegionYears ADD COLUMN revenueGlLabel varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL AFTER revenueGlNum;

ALTER TABLE exhibitors ADD COLUMN artistPayee varchar(128) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' AFTER artistName;

/* make new backup history table for exhibitors with it's trigger */
DROP TABLE IF EXISTS exhibitorsHistory;
CREATE TABLE exhibitorsHistory (
    historyId int NOT NULL AUTO_INCREMENT,
    historyDate timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id int NOT NULL,
    perid int DEFAULT NULL,
    newperid int DEFAULT NULL,
    artistName varchar(128) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
    artistPayee varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
    exhibitorName varchar(64) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
    exhibitorEmail varchar(254) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    exhibitorPhone varchar(32) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
    salesTaxId varchar(32) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
    website varchar(256) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
    description text COLLATE utf8mb4_general_ci NOT NULL,
    password varchar(64) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
    need_new tinyint(1) DEFAULT '1',
    publicity tinyint(1) DEFAULT '0',
    addr varchar(64) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
    addr2 varchar(64) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
    city varchar(32) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
    state varchar(16) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
    zip varchar(10) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
    country varchar(3) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
    shipCompany varchar(64) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
    shipAddr varchar(64) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
    shipAddr2 varchar(64) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
    shipCity varchar(64) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
    shipState varchar(16) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
    shipZip varchar(10) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
    shipCountry varchar(3) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
    archived enum('N','Y') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'N',
    notes text COLLATE utf8mb4_general_ci NOT NULL,
    PRIMARY KEY (historyId)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TRIGGER IF EXISTS exhibitors_update;
DELIMITER ;;
CREATE DEFINER=CURRENT_USER  TRIGGER `exhibitors_update` BEFORE UPDATE ON exhibitors FOR EACH ROW BEGIN
    IF (OLD.id != NEW.id OR OLD.perid != NEW.perid OR OLD.newperid != NEW.newperid OR OLD.artistName != NEW.artistName
            OR OLD.artistPayee != NEW.artistPayee OR OLD.exhibitorName != NEW.exhibitorName OR OLD.exhibitorEmail != NEW.exhibitorEmail
            OR OLD.exhibitorPhone != NEW.exhibitorPhone OR OLD.salesTaxId != NEW.salesTaxId OR OLD.website != NEW.website
            OR OLD.description != NEW.description OR OLD.password != NEW.password OR OLD.need_new != NEW.need_new OR OLD.publicity != NEW.publicity
            OR OLD.addr != NEW.addr OR OLD.addr2 != NEW.addr2 OR OLD.city != NEW.city OR OLD.state != NEW.state OR OLD.zip != NEW.zip
            OR OLD.country != NEW.country OR OLD.shipCompany != NEW.shipCompany OR OLD.shipAddr != NEW.shipAddr OR OLD.shipAddr2 != NEW.shipAddr2
            OR OLD.shipCity != NEW.shipCity OR OLD.shipState != NEW.shipState OR OLD.shipZip != NEW.shipZip OR OLD.shipCountry != NEW.shipCountry
            OR OLD.archived != NEW.archived OR OLD.notes != NEW.notes)
    THEN
        INSERT INTO exhibitorsHistory (id, perid, newperid, artistName, artistPayee, exhibitorName, exhibitorEmail, exhibitorPhone,
           salesTaxId, website, description, password, need_new, publicity, addr, addr2, city, state,
           zip, country, shipCompany, shipAddr, shipAddr2, shipCity, shipState, shipZip, shipCountry,
           archived, notes)
        VALUES (OLD.id, OLD.perid, OLD.newperid, OLD.artistName, OLD.artistPayee, OLD.exhibitorName, OLD.exhibitorEmail, OLD.exhibitorPhone,
            OLD.salesTaxId, OLD.website, OLD.description, OLD.password, OLD.need_new, OLD.publicity, OLD.addr, OLD.addr2, OLD.city, OLD.state,
            OLD.zip, OLD.country, OLD.shipCompany, OLD.shipAddr, OLD.shipAddr2, OLD.shipCity, OLD.shipState, OLD.shipZip, OLD.shipCountry,
            OLD.archived, OLD.notes);
    END IF;
END;;
DELIMITER ;

INSERT INTO patchLog(id, name) VALUES(x57, 'Release 2.1 Portal and other changes');
