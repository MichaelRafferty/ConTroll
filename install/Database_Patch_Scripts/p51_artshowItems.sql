/* P51
 * Upgrades/changes to art show items
 */

/* delete the auth artshow, as obsolete replaced by artsales and artinventory */
DELETE FROM atcon_auth WHERE auth = 'artshow';
ALTER TABLE atcon_auth MODIFY COLUMN  auth enum('data_entry','cashier','manager','artinventory','artsales','vol_roll');

UPDATE exhibitorRegionYears SET locations = '' WHERE locations IS NULL;
ALTER TABLE exhibitorRegionYears MODIFY COLUMN locations  varchar(512) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '';

UPDATE artItems SET location = '' WHERE location IS NULL;
ALTER TABLE artItems MODIFY COLUMN location varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '';


INSERT INTO patchLog(id, name) VALUES(xx, 'artshowItems');
