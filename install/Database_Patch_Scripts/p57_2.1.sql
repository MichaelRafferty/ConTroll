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

INSERT INTO patchLog(id, name) VALUES(x57, 'Release 2.1 Portal and other changes');
