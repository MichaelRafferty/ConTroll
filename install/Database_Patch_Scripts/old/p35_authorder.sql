/*
 * update to allow sort order in auth entries for menu orderin
 */

ALTER TABLE auth  ADD COLUMN sortOrder int DEFAULT 0;
UPDATE auth SET sortOrder = id * 100;

DELETE FROM auth where id = 7 AND name = 'artist'; /* obsolete */
DELETE FROM auth where id = 12 AND name = 'art_sales'; /* obsolete */
UPDATE auth set sortOrder = 300 WHERE id = 6;
UPDATE auth set sortOrder = 400 WHERE id = 3;
UPDATE auth set sortOrder = 1050 WHERE id = 32;
UPDATE auth set sortOrder = 550 WHERE id = 19;
UPDATE auth set sortOrder = 520 WHERE id = 9;

INSERT INTO patchLog(id, name) VALUES(35, 'auth order');
