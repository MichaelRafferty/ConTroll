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
UPDATE reg SET complete_trans = create_trans WHERE price = 0 AND status = 'paid' AND complete_trans is null;

/*
 * new date range table - and the correspond changes to memList and memListDates
 */
DROP TABLE IF EXISTS dateRanges;
CREATE TABLE dateRanges (
    id int NOT NULL AUTO_INCREMENT PRIMARY KEY COMMENT "Primary key for access, update, and deletes",
    conid int NOT NULL COMMENT "applicable convention year for this date range",
    nickName varchar(16) NOT NULL
        COMMENT "identifier for the time period, should be unique to the conid, used for select list for applying dates from the list",
    startdate datetime NOT NULL COMMENT "range starts on this datetime inclusive of the datetime).",
    enddate datetime NOT NULL COMMENT "range ends before this datetime (exclusive of the datetime).",
    sortorder int NOT NULL COMMENT "Display order for select lists using this table."
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS memListDates;
CREATE TABLE memListDates (
    `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY COMMENT "Internal id, unique key for this date item",
    `memId` int NOT NULL COMMENT "Which memList id does this date belong to",
    `glNum` varchar(16)  DEFAULT NULL COMMENT "For accounting, which General Ledger account gets registations of this type and time",
    `price` decimal(8,2) NOT NULL COMMENT "Price for this registration item",
    `startdate` datetime DEFAULT NULL COMMENT "Valid starting on or after startdate",
    `enddate` datetime DEFAULT NULL COMMENT "Valid before enddate",
    `nickName`  varchar(16) DEFAULT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
ALTER TABLE memListDates ADD CONSTRAINT FOREIGN KEY mldate_memList(memId) REFERENCES memList(id) ON UPDATE CASCADE;
ALTER TABLE memListDates ADD CONSTRAINT FOREIGN KEY mldate_glt(glNum) REFERENCES gl(glNum) ON UPDATE CASCADE;

-- now select what goes into it
INSERT INTO memListDates(memid, glNum, price, startdate, enddate)
WITH lowestdates AS (
    SELECT conid, memCategory, memType, memAge, label, `online`, atcon, badgeLabel, min(startdate) as startdate
    FROM memList
    GROUP by conid, memCategory, memType, memAge, label, `online`, atcon, badgeLabel
), mastermids AS (
    SELECT m.id, m.conid, m.memCategory, m.memType, m.memAge, m.label, m.online, m.atcon, m.badgeLabel, m.startdate
    FROM memList m
    JOIN lowestdates md ON md.conid = m.conid AND md.memCategory = m.memCategory AND m.memType = md.memType AND
        m.memAge = md.memAge AND m.label = md.label AND m.online = md.online AND
        m.atcon = md.atcon AND m.badgeLabel = md.badgeLabel AND m.startdate = md.startdate
)
SELECT md.id, m.glNum, m.price, m.startdate, m.enddate
FROM memList m
JOIN mastermids md ON md.conid = m.conid AND md.memCategory = m.memCategory AND m.memType = md.memType AND
    m.memAge = md.memAge AND m.label = md.label AND m.online = md.online AND
    m.atcon = md.atcon AND m.badgeLabel = md.badgeLabel;

UPDATE memListDates m
JOIN memList ml ON ml.id = m.memId
JOIN dateRanges d ON m.startdate = d.startdate and m.enddate = d.enddate and ml.conid = d.conid
SET m.nickName = d.nickName
WHERE d.nickName IS NOT NULL AND d.nickName != '';

DROP VIEW IF EXISTS memLabel;
CREATE ALGORITHM=UNDEFINED
    SQL SECURITY INVOKER
    VIEW memLabel AS SELECT
         m.id, md.id AS mdId, m.conid, m.sort_order, m.memCategory, m.memType, m.memAge,
         a.shortname AS ageShortName, m.label AS shortname, concat(m.label,' [',a.label,']') AS label,
         m.cartDesc, m.notes, m.rptGrouping, m.badgeLabel, m.atcon, m.online,
         md.price, md.startdate, md.enddate, md.nickName, md.glNum,
         c.taxable, c.badgeLabel AS catBadgeLabel
    FROM memList m
    JOIN memListDates md ON m.id = md.memId
    JOIN ageList a ON m.memAge = a.ageType AND m.conid = a.conid
    JOIN memCategories c ON m.memCategory = c.memCategory;

DROP VIEW IF EXISTS memListFull;
CREATE ALGORITHM=UNDEFINED
    SQL SECURITY INVOKER
    VIEW memListFull AS SELECT
         m.id, md.id AS mdId, m.conid, m.sort_order, m.memCategory, m.memType, m.memAge,	m.label,
         m.cartDesc, m.notes, m.rptGrouping, m.badgeLabel, m.atcon, m.online,
         md.price, md.startdate, md.enddate, md.nickName, md.glNum
    FROM memList m
    JOIN memListDates md ON m.id = md.memId;

-- now update the existing regs and old rows
DROP TABLE IF EXISTS memListMap;
CREATE TABLE memListMap (
    oldMemId int PRIMARY KEY,
    newMemId int NOT NULL,
    newMdId int NOT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO memListMap(oldMemId, newMemId, newMdId)
WITH lowestdates AS (
    SELECT conid, memCategory, memType, memAge, label, `online`, atcon, badgeLabel, min(startdate) as startdate
    FROM memList
    GROUP by conid, memCategory, memType, memAge, label, `online`, atcon, badgeLabel
), mastermids AS (
    SELECT m.id, m.conid, m.memCategory, m.memType, m.memAge, m.label, m.online, m.atcon, m.badgeLabel, m.startdate, m.enddate
    FROM memList m
    JOIN lowestdates md ON md.conid = m.conid AND md.memCategory = m.memCategory AND m.memType = md.memType AND
        m.memAge = md.memAge AND m.label = md.label AND m.online = md.online AND
        m.atcon = md.atcon AND m.badgeLabel = md.badgeLabel AND m.startdate = md.startdate
)
SELECT m.id, md.id, mld.id
FROM memList m
JOIN mastermids md ON md.conid = m.conid AND md.memCategory = m.memCategory AND m.memType = md.memType AND
    m.memAge = md.memAge AND m.label = md.label AND m.online = md.online AND
    m.atcon = md.atcon AND m.badgeLabel = md.badgeLabel
JOIN memListDates mld WHERE md.id = mld.memId AND mld.startDate = md.startDate AND mld.enddate = md.enddate;

-- now rebuild reg and exhibitsRegionYears
DROP TRIGGER IF EXISTS reg_update;
ALTER TABLE reg ADD COLUMN mldId int DEFAULT NULL COMMENT "registration date/price detail for a memList entry" AFTER memId;
ALTER TABLE reg ADD CONSTRAINT FOREIGN KEY reg_mldates(mldId) REFERENCES memListDates(id) ON UPDATE CASCADE;
ALTER TABLE regHistory ADD COLUMN mldId int DEFAULT NULL AFTER memId;

UPDATE reg
JOIN memListMap mlm ON reg.memId = mlm.oldMemId
SET memId = mlm.newMemId, mldId = mlm.newMdId;
UPDATE coupon
JOIN memListMap mlm ON coupon.memId = mlm.oldMemId
SET memId = mlm.newMemId;
UPDATE regHistory
JOIN memListMap mlm ON regHistory.memId = mlm.oldMemId
SET memId = mlm.newMemId, mldId = mlm.newMdId;

DELIMITER ;;
CREATE DEFINER=CURRENT_USER  TRIGGER `reg_update` BEFORE UPDATE ON `reg` FOR EACH ROW BEGIN
    IF (OLD.id != NEW.id OR OLD.conid != NEW.conid OR OLD.perid != NEW.perid OR OLD.newperid != NEW.newperid
        OR OLD.oldperid != NEW.oldperid OR OLD.priorRegId != NEW.priorRegId OR OLD.create_date != NEW.create_date
        OR OLD.pickup_date != NEW.pickup_date OR OLD.price != NEW.price
        OR OLD.couponDiscount != NEW.couponDiscount OR OLD.paid != NEW.paid OR OLD.create_trans != NEW.create_trans
        OR OLD.complete_trans != NEW.complete_trans OR OLD.locked != NEW.locked OR OLD.create_user != NEW.create_user
        OR OLD.memId != NEW.memId OR OLD.mldId != NEW.mldId OR OLD.coupon != NEW.coupon OR OLD.planId != NEW.planId
        OR OLD.printable != NEW.printable OR OLD.status != NEW.status)
    THEN
        INSERT INTO regHistory(id, conid, perid, newperid, oldperid, create_date, change_date, pickup_date, price, couponDiscount,
                               paid, create_trans, complete_trans, locked, create_user, updatedBy, memId, mldId, coupon, planId, printable, status)
        VALUES (OLD.id, OLD.conid, OLD.perid, OLD.newperid, OLD.oldperid, OLD.create_date, OLD.change_date, OLD.pickup_date,
                OLD.price, OLD.couponDiscount, OLD.paid, OLD.create_trans, OLD.complete_trans, OLD.locked, OLD.create_user,
                OLD.updatedBy, OLD.memId, OLD.mldId, OLD.coupon, OLD.planId, OLD.printable, OLD.status);
    END IF;
END;;
DELIMITER ;

DROP TABLE IF EXISTS memListMap;

-- now clean up old fields and memList rows with no memListDates
DELETE memList
FROM memList
LEFT OUTER JOIN memListDates ON memList.id = memListDates.memId
WHERE memListDates.memId is null;

ALTER TABLE memList DROP CONSTRAINT memList_gl;
DROP INDEX memList_dups ON memList;
ALTER TABLE memList DROP startdate, DROP enddate, DROP glNum, DROP price;

/*
 * new custom text items
 */

INSERT INTO `controllAppPages` VALUES
    ('profile', 'profile', 'Profile modal / section on any page'),
    ('portal', 'email', 'Custom Emails sent by Portal');

INSERT INTO `controllAppSections` VALUES
    ('profile', 'profile', 'profile', 'Information Block for Profile Fields'),
    ('portal', 'email', 'donation', 'Donation Thank you Email');

INSERT INTO `controllAppItems` VALUES
    ('profile', 'profile', 'profile', 'emergencyContact', 'Info block for emergency Contact'),
    ('portal', 'email', 'donation', 'html', 'HTML portion of Donation acknowledgement Email'),
    ('portal', 'email', 'donation', 'text', 'Text portion of Donation acknowledgement Email');

INSERT INTO `controllTxtItems` VALUES
    ('portal','email','donation','html', '<p>Dear [[FirstName]] [[LastName]],</p>
<p>#org#, a 501(c)3 organization, would like to thank you for making a donation of [[amount]] to #label#.</p>
<p>This is to certify that you have received nothing of value from #org# for your donation and the full amount will go to running #label#.
As such, to the extent permitted by code, for US tax filers, the full amount of this contribution is tax deductible on your federal tax return.
Please see your tax advisor for deductibility of this contribution on a state tax return.</p>
<p>Again, thank you for your contribution,</p>
<p>#org#</p>'),
    ('portal','email','donation','text', 'Dear [[FirstName]] [[LastName]],

#org#, a 501(c)3 organization, would like to thank you for making a donation of [[amount]] to #label#.

This is to certify that you have received nothing of value from #org# for your donation and the full amount will go to running #label#.
As such, to the extent permitted by code, for US tax filers, the full amount of this contribution is tax deductible on your federal tax return.
Please see your tax advisor for deductibility of this contribution on a state tax return.

Again, thank you for your contribution,
#org#');

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
