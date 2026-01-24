/*
 * changes post portal to make rules easier to work with (notes fields on memList support tables to explain things) and
 * work to suppress the (All Ages) for All age group to make things easier to read.
 */
ALTER TABLE memTypes ADD COLUMN notes varchar(1024) DEFAULT NULL AFTER memType;
ALTER TABLE memCategories ADD COLUMN notes varchar(1024) DEFAULT NULL AFTER memCategory;
ALTER TABLE memList ADD COLUMN notes varchar(1024) DEFAULT NULL AFTER label;

DROP VIEW IF EXISTS memLabel;
CREATE ALGORITHM=UNDEFINED SQL SECURITY INVOKER VIEW memLabel AS
SELECT m.id AS id,m.conid AS conid,m.sort_order AS sort_order,
       m.memCategory AS memCategory,m.memType AS memType,m.memAge AS memAge,m.label AS shortname,
       CASE
           WHEN m.memAge = 'all' THEN m.label
           ELSE concat(m.label,' [',a.label,']')
       END AS label,
       m.price AS price,m.startdate AS startdate,m.enddate AS enddate,m.atcon AS atcon,m.online AS online
FROM memList m
JOIN ageList a ON m.memAge = a.ageType AND m.conid = a.conid;

INSERT INTO patchLog(id, name) values(31, 'post_portal');
