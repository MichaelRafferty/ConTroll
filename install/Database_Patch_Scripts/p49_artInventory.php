/* P49
 * tracking of exceptions via inline inventory changes
 */

ALTER TABLE artItems ADD COLUMN notes text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL;

INSERT INTO patchLog(id, name) VALUES(49 'artinventory');
