/* P48
 * add limits and notes to exhibitors
 * improve performace
 * add site selection items for worldcons
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

/*
 * some performance improvement indicies
 */
CREATE INDEX perinfo_idx_email ON perinfo(email_addr);
CREATE INDEX perinfoIdent_idx_email ON perinfoIdentities(email_addr);
CREATE INDEX exhibitors_idx_email ON exhibitors(exhibitorEmail);
CREATE INDEX exhibitorYears_idx_email ON exhibitorYears(contactEmail);

/*
 * site selection table
 */
CREATE TABLE siteSelectionTokens (
    id int NOT NULL AUTO_INCREMENT,
    encTokenKey varbinary(256) NOT NULL,
    perid int DEFAULT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
ALTER TABLE siteSelectionTokens ADD CONSTRAINT `sst_perinfo_fk` FOREIGN KEY (perid) REFERENCES perinfo(id) ON UPDATE CASCADE;

INSERT INTO patchLog(id, name) VALUES(48, 'artshow-siteselection');
