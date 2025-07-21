/* P51
 * Upgrades/changes to art show items
 */

/* delete the auth artshow, as obsolete replaced by artsales and artinventory */
DELETE FROM atcon_auth WHERE auth = 'artshow';
ALTER TABLE atcon_auth MODIFY COLUMN  auth enum('data_entry','cashier','manager','artinventory','artsales','vol_roll');


INSERT INTO patchLog(id, name) VALUES(xx, 'artshowItems');
