/*
 * P56 - starts with quick sale changes
 */

ALTER TABLE exhibitsRegionTypes ADD COLUMN allowQuickSale enum('Y', 'N') NOT NULL DEFAULT 'Y';



INSERT INTO patchLog(id, name) VALUES(xx, 'art, et al');

