/*
 * P56 - starts with quick sale changes
 */

/*
 * flag for disabling quick sale
 */
ALTER TABLE exhibitsRegionTypes ADD COLUMN allowQuickSale enum('Y', 'N') NOT NULL DEFAULT 'Y';

/*
 * use portalTokenLinks for exhibitor portal password resets
 */
ALTER TABLE portalTokenLinks MODIFY COLUMN action enum('login','attach','identity','password','other') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'other';

/*
 * flag for confirm age on new year
 */
ALTER TABLE ageList ADD COLUMN verify enum('Y', 'N') NOT NULL DEFAULT 'Y';

INSERT INTO patchLog(id, name) VALUES(xx, 'art, et al');

