/*
 * P57 - continued refinement of Registration Portal and extensions of cross year back end
 */

ALTER TABLE memList ADD COLUMN cartDesc text DEFAULT null AFTER label;

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

/* add badge label override to memList (overrides memCategories badgeLabel if not empty) */
ALTER TABLE memList ADD COLUMN badgeLabel varchar(16) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' AFTER enddate;

DROP VIEW IF EXISTS `memLabel`;
CREATE ALGORITHM=UNDEFINED
    SQL SECURITY INVOKER
    VIEW memLabel AS
SELECT m.id AS id,m.conid AS conid,m.sort_order AS sort_order,m.memCategory AS memCategory,m.memType AS memType,m.memAge AS memAge,
       a.shortname AS ageShortName,m.label AS shortname,concat(m.label,' [',a.label,']') AS label,m.cartDesc AS cartDesc,
       m.notes AS notes,m.price AS price, m.badgeLabel AS badgeLabel,
       m.startdate AS startdate,m.enddate AS enddate,m.atcon AS atcon,m.online AS online,m.glNum AS glNum,m.glLabel AS glLabel,
       c.taxable AS taxable,c.badgeLabel AS catBadgeLabel
FROM memList m
         JOIN ageList a ON (m.memAge = a.ageType) AND (m.conid = a.conid)
         JOIN memCategories c ON m.memCategory = c.memCategory;


/* email custom text */
/* fix typo */
UPDATE controllAppItems SET txtItemDescription = replace(txtItemDescription, 'Np membership created', 'No membership created')
    WHERE appName = 'controll' AND appPage = 'emails' AND txtItemDescription LIKE '%Np membership created%';

/* survey emails */
INSERT INTO controllAppSections (appName, appPage, appSection, sectionDescription) VALUES
    ('controll', 'emails', 'survey', 'Post Convention Survey Request Email'),
    ('controll', 'emails', 'invReminder', 'Artist Item Registration Reminder Email');

INSERT INTO controllAppItems(appName, appPage, appSection, txtItem, txtItemDescription) VALUES
    ('controll', 'emails','survey','text','Custom Text for the plain text post con survey email'),
    ('controll', 'emails','survey','html','Custom Text for the html post con survey email'),
    ('controll', 'emails','invReminder','text','Custom Text for the plain text enter your item registration reminder email'),
    ('controll', 'emails','invReminder','html','Custom Text for the html post con enter your item registration reminder email');

INSERT INTO controllTxtItems(appName, appPage, appSection, txtItem, contents)
SELECT a.appName, a.appPage, a.appSection, a.txtItem,
       CONCAT('Controll-Default: This is ', a.appName, '-', a.appPage, '-', a.appSection, '-', a.txtItem,
              '<br/>Custom HTML that can replaced with a custom value in the ConTroll Admin App under RegAdmin/Edit Custom Text.<br/>',
              'Default text display can be suppressed in the configuration file.')
FROM controllAppItems a
         LEFT OUTER JOIN controllTxtItems t ON (a.appName = t.appName AND a.appPage = t.appPage AND a.appSection = t.appSection AND a.txtItem = t.txtItem)
WHERE t.contents is NULL;

UPDATE controllTxtItems
SET contents = 'Dear [[FirstName]],
<p>Thank you for attending #label#. You are receiving this email because your email address is associated with a registration that attended this year. We have a short survey we would like you to complete that will help is improve #conname#.</p>
<p><a href="#survey_url#">Take the #label# Post Convention Feedback Survey</a></p>
<p>We look forward to reviewing your comments to help us improve #conname#.</p>
<br>
<p>If you have any issues please reach out to us at <a href="mailto:#regadminemail#">#regadminemail#</a>.</p>
<p>Thank you,<br>#conname# Registration</p>'
WHERE appName = 'controll' AND appPage = 'emails' AND appSection = 'survey' AND txtItem = 'html';

UPDATE controllTxtItems
SET contents = 'Dear [[FirstName]],

Thank you for attending #label#. You are receiving this email because your email address is associated with a registration that attended this year. We have a short survey we would like you to complete that will help is improve #conname#.

Take the #label# Post Convention Feedback Survey: #survey_url#

We look forward to reviewing your comments to help us improve #conname#.

If you have any issues please reach out to us at #regadminemail#.

Thank you,
#conname# Registration
'
WHERE appName = 'controll' AND appPage = 'emails' AND appSection = 'survey' AND txtItem = 'text';

UPDATE controllTxtItems
SET contents = '<p>Dear [[FirstName]],</p>
<p>This is a reminder that have not yet registered the items you are bringing to #label#.</p>
<p>Please sign into the portal at #vendor.artistsite# and click the "Open Item Registration" button.</p>
<p>Thank you,<br/>#vendor.artist#</p>'
WHERE appName = 'controll' AND appPage = 'emails' AND appSection = 'invReminder' AND txtItem = 'html';

UPDATE controllTxtItems
SET contents = 'Dear [[FirstName]],

This is a reminder that have not yet registered the items you are bringing to #label#.

Please sign into the portal at #vendor.artistsite# and click the "Open Item Registration" button.

Thank you,
#vendor.artist#
'
WHERE appName = 'controll' AND appPage = 'emails' AND appSection = 'invReminder' AND txtItem = 'text';

INSERT INTO patchLog(id, name) VALUES(x57, 'Release 2.1 Portal and other changes');
