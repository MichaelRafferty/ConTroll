/* P48
 * add limits and notes to exhibitors
 */

/*
 * add limits and exhibitors notes to tables
 * start on status mode for checkout updates
 */

ALTER TABLE exhibitors ADD notes text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL AFTER archived;
ALTER TABLE exhibitorYears ADD notes text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL AFTER needReview;
ALTER TABLE exhibitsRegionTypes ADD maxInventory int DEFAULT NULL AFTER usesInventory;
ALTER TABLE exhibitsRegionYears ADD mailinGLNum varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL AFTER mailinFee;
ALTER TABLE exhibitsRegionYears ADD mailinGLLabel varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL AFTER mailinGLNum;
ALTER TABLE exhibitsRegionYears ADD roomStatus enum('precon', 'bid', 'checkout', 'closed', 'all') DEFAULT 'precon' AFTER exhibitsRegion;

INSERT INTO patchLog(id, name) VALUES(xx, 'artshow');
