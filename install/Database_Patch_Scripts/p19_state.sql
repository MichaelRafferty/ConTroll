/* p19 - state - convert remaining State columns to varchar16 in prep for international use and for USPS APIs
 */

ALTER TABLE perinfo MODIFY COLUMN state varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL;
ALTER TABLE newperson MODIFY COLUMN state varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL;


INSERT INTO patchLog(id, name) values(19, 'state');
