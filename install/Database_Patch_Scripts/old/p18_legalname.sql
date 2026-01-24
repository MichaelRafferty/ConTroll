/* p18 - legalname - Add restricted field legalName to the people tables perinfo and newperson
 * This field is restricted to viewing only in Reg by atcon for checking and by reg_control
 * It is never to be used in reports or printed on badges or receipts
 */

ALTER TABLE perinfo ADD COLUMN legalName varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL AFTER badge_name;
ALTER TABLE newperson ADD COLUMN legalName varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL AFTER badge_name;


INSERT INTO patchLog(id, name) values(18, 'legalname');
