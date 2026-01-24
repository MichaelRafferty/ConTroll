/* P7-Volunteer rollover auth permission */

ALTER TABLE atcon_auth MODIFY COLUMN auth enum('data_entry','cashier','manager','artinventory','artshow','artsales',
'vol_roll') NOT NULL;

INSERT INTO patchLog(id, name) values(7, 'volrollover auth');

