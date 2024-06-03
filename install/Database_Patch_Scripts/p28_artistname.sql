/*
 * changes needed to make sales work and track the things we want to track
 */
ALTER TABLE exhibitors ADD COLUMN artistName varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL AFTER newperid;
UPDATE exhibitors SET artistName = exhibitorName WHERE artistName IS NULL;

INSERT INTO patchLog(id, name) values(28, 'artist name');
