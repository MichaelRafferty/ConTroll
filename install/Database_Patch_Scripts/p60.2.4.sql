/*
 * P60 - 2.4
 *
 */

/*
 * periodic cleanup of interests and policies
 */
CALL deleteDupsIntPol();

/*
 * Add rounding to transaction for cash rounding
 */
ALTER TABLE transaction ADD COLUMN rounding decimal(8,2) AFTER withtax;

/*
 * move gl code and label definition to a dedicated gl table
 */
DROP TABLE IF EXISTS gl;
CREATE TABLE gl (
    glNum varchar(16) COLLATE utf8mb4_general_ci NOT NULL COMMENT "General Ledger Number in Accounting System",
    glLabel varchar(64) COLLATE utf8mb4_general_ci NOT NULL COMMENT "Label for the GL Number",
    description varchar(4096) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT "Useful instructions/details for this GL Number",
    sortOrder int DEFAULT '0' COMMENT "Sort order for select pulldown where GL is used, and optionally the GL Edit screen",
    createDate timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT "Auto field to mark when the record was inserted",
    updateDate timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT "Auto tracking field for last update",
    updateBy int DEFAULT NULL COMMENT "Tracking field of perid of who modifed the record last",
    active enum('Y','N') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Y' COMMENT "Is this gl line active for this years convention",
    PRIMARY KEY (`glNum`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE gl ADD CONSTRAINT gl_updatedby FOREIGN KEY(updateBy) REFERENCES perinfo(id) ON UPDATE CASCADE;
/*
 * preload the gl table
 */
UPDATE exhibitsRegions SET glNum = null, glLabel = null WHERE trim(glNum) = '';
UPDATE exhibitsRegionYears SET glNum = null, glLabel = null WHERE trim(glNum) = '';
UPDATE exhibitsRegionYears SET revenueGlLabel = null, revenueGlNum = null WHERE trim(revenueGlNum) = '';
UPDATE exhibitsRegionYears SET mailinGLNum = null, mailinGLLabel = null WHERE trim(mailinGLNum) = '';
UPDATE exhibitsSpacePrices SET glNum = null, glLabel = null WHERE trim(glNum) = '';
UPDATE exhibitsSpaces SET glNum = null, glLabel = null WHERE trim(glNum) = '';
UPDATE memList SET glNum = null, glLabel = null WHERE trim(glNum) = '';
UPDATE taxList SET glNum = null, glLabel = null WHERE trim(glNum) = '';

DROP TABLE IF EXISTS temp_glnum;
CREATE TABLE temp_glnum(
    pri int,
    glNum varchar(16) COLLATE utf8mb4_general_ci,
    glLabel varchar(64) COLLATE utf8mb4_general_ci
);


INSERT INTO temp_glnum(pri, glNum, glLabel)
SELECT pri, glNum, glLabel
FROM (
         SELECT DISTINCT 5 as pri, glNum, glLabel FROM exhibitsRegionYears
         UNION
         SELECT DISTINCT 6 as pri, revenueGlNum, revenueGlLabel FROM exhibitsRegionYears
         UNION
         SELECT DISTINCT 7 as pri, mailinGLNum, mailinGLLabel FROM exhibitsRegionYears
         UNION
         SELECT DISTINCT 8 as pri, glNum, glLabel FROM exhibitsSpacePrices
         UNION
         SELECT DISTINCT 9 as pri, glNum, glLabel FROM exhibitsSpaces
         UNION
         SELECT DISTINCT 1 as pri, glNum, glLabel FROM memList
         UNION
         SELECT DISTINCT 2 as pri, glNum, glLabel FROM taxList
     ) a
WHERE glNum IS NOT NULL;

INSERT INTO gl(glNum, glLabel)
SELECT glNum, glLabel
FROM (
         SELECT t.glNum, MAX(t.glLabel)AS glLabel
         FROM (
                  SELECT min(pri) as pri, glNum, glLabel
                  FROM temp_glnum
                  GROUP BY glNum) s
                  JOIN temp_glnum t ON t.pri = s.pri and t.glNum = s.glNum
         GROUP BY t.glnum
         ORDER BY t.glnum
     ) a
ORDER BY glNum;

DROP TABLE IF EXISTS temp_glnum;

/*
 * Now modify all the tables that have glNum and glLabel to use just glNum as a ref to the gl table.
 */
ALTER TABLE exhibitsRegions DROP COLUMN glLabel;
ALTER TABLE exhibitsRegions DROP CONSTRAINT exhibitsRegions_ibfk_1; -- your constraint name may be different
ALTER TABLE exhibitsRegions DROP COLUMN glNum;
ALTER TABLE exhibitsRegionYears DROP COLUMN glLabel;
ALTER TABLE exhibitsRegionYears DROP COLUMN revenueGlLabel;
ALTER TABLE exhibitsRegionYears DROP COLUMN mailinGLLabel;
ALTER TABLE exhibitsSpacePrices DROP COLUMN glLabel;
ALTER TABLE exhibitsSpaces DROP COLUMN glLabel;
ALTER TABLE memList DROP COLUMN glLabel;
ALTER TABLE taxList DROP COLUMN glLabel;
ALTER TABLE exhibitsRegionYears ADD CONSTRAINT FOREIGN KEY ery_gl(glNum) REFERENCES gl(glNum) ON UPDATE CASCADE;
ALTER TABLE exhibitsSpacePrices ADD CONSTRAINT FOREIGN KEY esp_gl(glNum) REFERENCES gl(glNum) ON UPDATE CASCADE;
ALTER TABLE exhibitsSpaces ADD CONSTRAINT FOREIGN KEY es_gl(glNum) REFERENCES gl(glNum) ON UPDATE CASCADE;
ALTER TABLE memList ADD CONSTRAINT FOREIGN KEY memList_gl(glNum) REFERENCES gl(glNum) ON UPDATE CASCADE;
ALTER TABLE taxList ADD CONSTRAINT FOREIGN KEY taxList_gl(glNum) REFERENCES gl(glNum) ON UPDATE CASCADE;

/*
 * now fix the memLabel view
 */
DROP VIEW IF EXISTS `memLabel`;
CREATE ALGORITHM=UNDEFINED
SQL SECURITY INVOKER
VIEW memLabel AS SELECT m.id AS id,m.conid AS conid,m.sort_order AS sort_order,m.memCategory AS memCategory,m.memType AS memType,
    m.memAge AS memAge,a.shortname AS ageShortName,m.label AS shortname,concat(m.label,' [',a.label,']') AS label,
    m.cartDesc AS cartDesc,m.notes AS notes,m.rptGrouping AS rptGrouping,m.price AS price,m.badgeLabel AS badgeLabel,
    m.startdate AS startdate,m.enddate AS enddate,m.atcon AS atcon,m.online AS `online`,
    m.glNum AS glNum,g.glLabel AS glLabel,
    c.taxable AS taxable,c.badgeLabel AS catBadgeLabel
FROM memList m
JOIN ageList a ON m.memAge = a.ageType AND m.conid = a.conid
JOIN memCategories c ON m.memCategory = c.memCategory
LEFT OUTER JOIN gl g ON g.glNum = m.glNum;

/*
 * add optional textedit field emergencyContact to perinfo, newperson
 */

ALTER TABLE perinfo ADD column emergencyContact varchar(2048) DEFAULT '' NOT NULL AFTER pronouns;
ALTER TABLE perinfoHistory ADD column emergencyContact varchar(2048) AFTER pronouns;
ALTER TABLE newperson ADD column emergencyContact varchar(2048) DEFAULT '' NOT NULL AFTER pronouns;

DROP TRIGGER perinfo_update;
DELIMITER ;;
CREATE DEFINER=CURRENT_USER  TRIGGER `perinfo_update` BEFORE UPDATE ON `perinfo` FOR EACH ROW BEGIN
    IF (OLD.id != NEW.id OR OLD.currentAgeConId != NEW.currentAgeConId OR OLD.currentAgeType != NEW.currentAgeType
        OR OLD.last_name != NEW.last_name OR OLD.first_name != NEW.first_name OR OLD.middle_name != NEW.middle_name
        OR OLD.suffix != NEW.suffix OR OLD.legalName != NEW.legalName OR OLD.pronouns != NEW.pronouns
        OR OLD.email_addr != NEW.email_addr OR OLD.phone != NEW.phone OR OLD.badge_name != NEW.badge_name OR OLD.badgeNameL2 != NEW.badgeNameL2
        OR OLD.address != NEW.address OR OLD.addr_2 != NEW.addr_2 OR OLD.city != NEW.city OR OLD.state != NEW.state OR OLD.zip != NEW.zip
        OR OLD.country != NEW.country OR OLD.banned != NEW.banned OR OLD.creation_date != NEW.creation_date
        OR OLD.change_notes != NEW.change_notes OR OLD.active != NEW.active OR OLD.open_notes != NEW.open_notes OR OLD.admin_notes != NEW.admin_notes
        OR OLD.old_perid != NEW.old_perid OR OLD.contact_ok != NEW.contact_ok OR OLD.share_reg_ok != NEW.share_reg_ok
        OR OLD.deceased != NEW.deceased OR OLD.formerGoh != NEW.formerGoh OR OLD.emergencyContact != NEW.emergencyContact
        OR OLD.managedBy != NEW.managedBy OR OLD.managedByNew != NEW.managedByNew OR OLD.updatedBy != NEW.updatedby
        OR OLD.managedReason != NEW.managedReason)
    THEN
        INSERT INTO perinfoHistory(id, currentAgeConId, currentAgeType,
                                   last_name, first_name, middle_name, suffix, email_addr, phone,
                                   badge_name, badgeNameL2, legalName, pronouns, emergencyContact,
                                   address, addr_2, city, state, zip, country, banned, creation_date, update_date,
                                   change_notes, active, open_notes, admin_notes, old_perid, contact_ok, share_reg_ok,
                                   deceased, formerGoh,  managedBy, managedByNew, managedReason, lastVerified, updatedBy)
        VALUES (OLD.id, OLD.currentAgeConId, OLD.currentAgeType,
                OLD.last_name, OLD.first_name, OLD.middle_name, OLD.suffix, OLD.email_addr, OLD.phone, OLD.badge_name,
                OLD.badgeNameL2, OLD.legalName, OLD.pronouns, OLD.emergencyContact,
                OLD.address, OLD.addr_2, OLD.city, OLD.state, OLD.zip, OLD.country, OLD.banned, OLD.creation_date, OLD.update_date,
                OLD.change_notes, OLD.active, OLD.open_notes, OLD.admin_notes, OLD.old_perid, OLD.contact_ok, OLD.share_reg_ok,
                OLD.deceased, OLD.formerGoH, OLD.managedBy, OLD.managedByNew, OLD.managedReason, OLD.lastVerified, OLD.updatedBy);
    END IF;
END;;
DELIMITER ;

ALTER TABLE ageList ADD COLUMN emergencyContactReq enum('N', 'Y', 'S') NOT NULL DEFAULT 'N'
    COMMENT 'Require an emergency contact in the profile for this age type';

/*
 * fix some old bad data from free badges and rollover volunteer
 */
update reg set complete_trans = create_trans where price = 0 and status = 'paid' and complete_trans is null;

/*
 * new custom text items
 */

INSERT INTO `controllAppPages` VALUES
    ('profile', 'profile', 'Profile modal / section on any page');

INSERT INTO `controllAppSections` VALUES
    ('profile', 'profile', 'profile', 'Information Block for Profile Fields');


INSERT INTO `controllAppItems` VALUES
    ('profile', 'profile', 'profile', 'emergencyContact', 'Info block for emergency Contact');

INSERT INTO `controllTxtItems` VALUES
    ();

/* better wording */
UPDATE controllTxtItems SET contents =
    CONCAT('<p>You can only change your accounts email address to an email address in your identities in Account Settings.</p>',
      '<p>Please use the "Add New" button in the "Identities" section of "Account Setings" to add any new email addresses to your account.</p>',
      '<p>Identities is only available in Account Settings once your account has been assigned an ID and is no longer has a temporary id.</p>',
      '<p>You can only change the email address for an account you manage to one of your own (as created above) ',
       'or to one of the email addresses of people you manage.</p>',
      '<p>If you need to make any other changes, please contact registration at #regadminemail# and ask for assistance.</p>')
where appName = 'portal' and appPage = 'portal' and appSection = 'main' and txtItem = 'changeEmail';
/*
 * make new no show defaults for ones without a default value
 */
INSERT INTO controllTxtItems(appName, appPage, appSection, txtItem, contents)
SELECT a.appName, a.appPage, a.appSection, a.txtItem, CONCAT('Controll-Default: This is ', a.appName, '-', a.appPage, '-', a.appSection, '-', a.txtItem,
     '<br/>Custom HTML that can replaced with a custom value in the Controll Admin App under Edit Custom Text.<br/>',
     ' Default text can be suppressed in the configuration file.')
FROM controllAppItems a
LEFT OUTER JOIN controllTxtItems t on (a.appName = t.appName AND a.appPage = t.appPage AND a.appSection = t.appSection and a.txtItem = t.txtItem)
WHERE t.contents is NULL;

UPDATE controllAppItems SET txtItemDescription = 'Custom Text for the html enter your item registration reminder email'
WHERE appName = 'exhibitor' AND appPage = 'emails' AND appSection = 'invReminder' and txtItem = 'html';


INSERT INTO patchLog(id, name) VALUES(x60, 'Release 2.4');
