/* p22 - length of email address fields
 */

ALTER TABLE user MODIFY COLUMN email varchar(254) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL;
ALTER TABLE exhibitorYears MODIFY COLUMN contactEmail varchar(254) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL;
ALTER TABLE exhibitors MODIFY COLUMN exhibitorEmail varchar(254) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;
ALTER TABLE exhibitsRegionYears MODIFY COLUMN ownerEmail varchar(254) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;
ALTER TABLE newperson MODIFY COLUMN email_addr varchar(254) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL;
ALTER TABLE perinfo MODIFY COLUMN email_addr varchar(254) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL;

INSERT INTO patchLog(id, name) values(22, 'email_length');
