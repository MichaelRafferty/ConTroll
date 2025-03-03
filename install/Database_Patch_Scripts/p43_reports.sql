/*
 * New Permissions and changes for general reports
 */

INSERT INTO auth(id, name, page, display, sortOrder)
VALUES (20,'gen_rpts', 'N', 'N', 135);

DELETE FROM user_auth WHERE auth_id IN (17,18);
DELETE FROM auth WHERE id IN (17,18);

/*
 * items for paid by others payments
 */

INSERT INTO controllAppItems(appName, appPage, appSection, txtItem, txtItemDescription) VALUES
('portal', 'portal','main','purchOthers',
 'Custom Text for You have unpaid purchases for you by others section');

UPDATE controllAppItems SET appSection = 'paymentPlans' WHERE appSection = 'paymentPlamns';

/*
 * Items for GL Codes for reporting
 */

ALTER TABLE memList ADD COLUMN glNum varchar(16) DEFAULT NULL AFTER notes;
ALTER TABLE memList ADD COLUMN glLabel varchar(64) DEFAULT NULL AFTER glNum;

ALTER TABLE exhibitsRegions ADD COLUMN glNum varchar(16) DEFAULT NULL AFTER description;
ALTER TABLE exhibitsRegions ADD COLUMN glLabel varchar(64) DEFAULT NULL AFTER glNum;

ALTER TABLE exhibitsRegionYears ADD COLUMN glNum varchar(16) DEFAULT NULL AFTER ownerEmail;
ALTER TABLE exhibitsRegionYears ADD COLUMN glLabel varchar(64) DEFAULT NULL AFTER glNum;

ALTER TABLE exhibitsSpaces ADD COLUMN glNum varchar(16) DEFAULT NULL AFTER description;
ALTER TABLE exhibitsSpaces ADD COLUMN glLabel varchar(64) DEFAULT NULL AFTER glNum;

ALTER TABLE exhibitsSpacePrices ADD COLUMN glNum varchar(16) DEFAULT NULL AFTER description;
ALTER TABLE exhibitsSpacePrices ADD COLUMN glLabel varchar(64) DEFAULT NULL AFTER glNum;

DROP VIEW IF EXISTS memLabel;
CREATE
    ALGORITHM=UNDEFINED
    SQL SECURITY INVOKER
    VIEW `memLabel` AS
    SELECT `m`.`id` AS `id`,`m`.`conid` AS `conid`,`m`.`sort_order` AS `sort_order`,`m`.`memCategory` AS `memCategory`,
       `m`.`memType` AS `memType`,`m`.`memAge` AS `memAge`,`m`.`label` AS `shortname`,
       concat(`m`.`label`,' [',`a`.`label`,']') AS `label`,`m`.`notes` AS `notes`,
       concat(`m`.`memCategory`,'_',`m`.`memType`,'_',`m`.`memAge`) AS `memGroup`,`m`.`price` AS `price`,
       `m`.`startdate` AS `startdate`,`m`.`enddate` AS `enddate`,`m`.`atcon` AS `atcon`,
       `m`.`online` AS `online`, m.glNum, m.glLabel
    FROM `memList` `m`
    JOIN `ageList` `a` ON `m`.`memAge` = `a`.`ageType` AND `m`.`conid` = `a`.`conid`;


INSERT INTO patchLog(id, name) VALUES(43, 'General Reports');