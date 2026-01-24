/*
 * changes needed to make sales work and track the things we want to track
 */
ALTER TABLE exhibitors MODIFY COLUMN shipState varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL;

INSERT INTO patchLog(id, name) values(25, 'exhibitor shipState');
