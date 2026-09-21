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
UPDATE exhibitsSpacePrices SET glNum = null, glLabel = null WHERE trim(glNum) = '';
UPDATE exhibitsSpaces SET glNum = null, glLabel = null WHERE trim(glNum) = '';
UPDATE memList SET glNum = null, glLabel = null WHERE trim(glNum) = '';
UPDATE taxList SET glNum = null, glLabel = null WHERE trim(glNum) = '';

INSERT INTO gl(glNum, glLabel)
SELECT glNum, glLabel
FROM (
         SELECT DISTINCT glNum, glLabel FROM exhibitsRegions
         UNION
         SELECT DISTINCT glNum, glLabel FROM exhibitsRegionYears
         UNION
         SELECT DISTINCT glNum, glLabel FROM exhibitsSpacePrices
         UNION
         SELECT DISTINCT glNum, glLabel FROM exhibitsSpaces
         UNION
         SELECT DISTINCT glNum, glLabel FROM memList
         UNION
         SELECT DISTINCT glNum, glLabel FROM taxList
     ) a
WHERE glNum IS NOT NULL;

/*
 * Now modify all the tables that have glNum and glLabel to use just glNum as a ref to the gl table.
 */
ALTER TABLE exhibitsRegions DROP COLUMN glLabel;
ALTER TABLE exhibitsRegionYears DROP COLUMN glLabel;
ALTER TABLE exhibitsSpacePrices DROP COLUMN glLabel;
ALTER TABLE exhibitsSpaces DROP COLUMN glLabel;
ALTER TABLE memList DROP COLUMN glLabel;
ALTER TABLE taxList DROP COLUMN glLabel;
ALTER TABLE exhibitsRegions ADD CONSTRAINT FOREIGN KEY er_gl(glNum) REFERENCES gl(glNum) ON UPDATE CASCADE;
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
JOIN gl g ON g.glNum = m.glNum;

/*
 * new custom text items
 */

INSERT INTO `controllAppSections` VALUES
    ();

INSERT INTO `controllAppItems` VALUES
    ();

INSERT INTO `controllTxtItems` VALUES
    ();

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
