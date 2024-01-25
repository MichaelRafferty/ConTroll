/* p17 - vendor_with_artist - Rename to exhibits and exhibitor, add permissions, and contact/ship to fields
   Add multi-level hierarchy for exhibit
 */

/* NOTE: these tables totally replace the old vendor* and vendor_* tables, you can migrate data to save it, if desired. */
/*  Eventually drop all vendor tables as obsolete

DROP TABLE IF EXISTS vendor_space;
DROP TABLE IF EXISTS vendors;
DROP TABLE IF EXISTS vendorSpacePrices;
DROP TABLE IF EXISTS vendorSpaces;
DROP TABLE IF EXISTS vendorRegionYears;
DROP TABLE IF EXISTS vendorRegions;
DROP TABLE IF EXISTS vendorRegionTypes;

*/

/*  exhibitsRegionTypes table - Rules for different types of exhibits regions */
CREATE TABLE exhibitsRegionTypes (
    regionType varchar(16) NOT NULL,
    portalType enum('vendor','artist') NOT NULL default 'vendor',
    requestApprovalRequired enum('None','Once','Annual') NOT NULL  DEFAULT 'Once',
    purchaseApprovalRequired enum('Y','N') NOT NULL  DEFAULT 'Y',
    purchaseAreaTotals enum('unique', 'combined') DEFAULT 'combined',
    mailinAllowed enum('Y','N') NOT NULL DEFAULT 'Y',
    sortorder int NOT NULL DEFAULT 0,
    active enum('N','Y') NOT NULL DEFAULT 'Y',
    PRIMARY KEY(regionType)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/* exhibits regions - the name and description of the various regions (locations) of exhibits spaces */
CREATE TABLE exhibitsRegions (
    id int NOT NULL AUTO_INCREMENT,
    regionType varchar(16) COLLATE utf8mb4_general_ci NOT NULL,
    shortname varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
    name varchar(128) COLLATE utf8mb4_general_ci NOT NULL,
    description text COLLATE utf8mb4_general_ci,
    sortorder int NOT NULL DEFAULT 0,
    PRIMARY KEY(id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE exhibitsRegions ADD CONSTRAINT er_regiontype_fk FOREIGN KEY(regionType) REFERENCES exhibitsRegionTypes(regionType) ON DELETE CASCADE ON UPDATE CASCADE;

/* exhibitsRegionYears - which spaces are active for a particular year.
   Allows keeping historical data, but allows for different memId's and owners per year */
CREATE TABLE exhibitsRegionYears (
    id int NOT NULL AUTO_INCREMENT,
    conid int NOT NULL,
    exhibitsRegion int NOT NULL,
    ownerName varchar(64) COLLATE utf8mb4_general_ci NOT NULL,
    ownerEmail varchar(64) COLLATE utf8mb4_general_ci NOT NULL,
    includedMemId int DEFAULT NULL,
    additionalMemId int DEFAULT NULL,
    totalUnitsAvailable int NOT NULL DEFAULT 0,
    sortorder int NOT NULL DEFAULT 0,
    PRIMARY KEY(id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE exhibitsRegionYears ADD CONSTRAINT ery_memList_a FOREIGN KEY(additionalMemId) REFERENCES memList(id) ON UPDATE CASCADE;
ALTER TABLE exhibitsRegionYears ADD CONSTRAINT ery_memList_i FOREIGN KEY(includedMemId) REFERENCES memList(id) ON UPDATE CASCADE;
ALTER TABLE exhibitsRegionYears ADD CONSTRAINT ery_conlist_fk FOREIGN KEY(conid) REFERENCES conlist(id) ON UPDATE CASCADE;
ALTER TABLE exhibitsRegionYears ADD CONSTRAINT ery_exhibitsRegion_fk FOREIGN KEY(exhibitsRegion) REFERENCES exhibitsRegions(id) ON UPDATE CASCADE;

/* exhibits spaces (spaces within a region sold individually) */
CREATE TABLE exhibitsSpaces (
    id int NOT NULL AUTO_INCREMENT,
    exhibitsRegionYear int NOT NULL,
    shortname varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
    name varchar(128) COLLATE utf8mb4_general_ci NOT NULL,
    description text COLLATE utf8mb4_general_ci,
    unitsAvailable int NOT NULL DEFAULT 0,
    unitsAvailableMailin int NOT NULL DEFAULT 0,
    sortorder int NOT NULL DEFAULT 0,
    PRIMARY KEY(id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE exhibitsSpaces ADD CONSTRAINT es_exhibitsRegionYears_fk FOREIGN KEY(exhibitsRegionYear) REFERENCES exhibitsRegionYears(id) ON DELETE CASCADE ON UPDATE CASCADE;

/* exhibitsSpacePrices - space quantities available, prices are total, not per unit
   Allows customizing number of included/additional memberships per quantity purchased.
 */
CREATE TABLE exhibitsSpacePrices (
    id int NOT NULL AUTO_INCREMENT,
    spaceId int NOT NULL,
    code varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
    description varchar(64) COLLATE utf8mb4_general_ci NOT NULL,
    units decimal(4,2) DEFAULT 1.00,
    price decimal(8,2) NOT NULL,
    includedMemberships int NOT NULL DEFAULT 0,
    additionalMemberships int NOT NULL DEFAULT 0,
    requestable tinyint DEFAULT 1,
    sortorder int NOT NULL DEFAULT 0,
    PRIMARY KEY(id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE exhibitsSpacePrices ADD CONSTRAINT esp_exhibitsspaceid_fk FOREIGN KEY(spaceId) REFERENCES exhibitsSpaces(id) ON DELETE CASCADE ON UPDATE CASCADE;

/* exhibitors - main demographic table for all exhibitors (artists, dealers, fan entities, etc.)
   Note: contact will either be a current year copy or totally move to exhibitor_years
 */
CREATE TABLE exhibitors (
    id int NOT NULL AUTO_INCREMENT,
    perid int DEFAULT NULL,
    exhibitorName varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    exhibitorEmail varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    exhibitorPhone varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    website varchar(256) COLLATE utf8mb4_general_ci DEFAULT NULL,
    description text COLLATE utf8mb4_general_ci,
    password varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
    need_new tinyint(1) DEFAULT '1',
    confirm tinyint(1) DEFAULT '0',
    publicity tinyint(1) DEFAULT '0',
    addr varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
    addr2 varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
    city varchar(32) COLLATE utf8mb4_general_ci DEFAULT NULL,
    state varchar(16) COLLATE utf8mb4_general_ci DEFAULT NULL,
    zip varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
    country varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    shipCompany varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    shipAddr varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    shipAddr2 varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    shipCity varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    shipState varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    shipZip varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    shipCountry varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    archived enum('N','Y') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'N',
    PRIMARY KEY (id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE exhibitors ADD CONSTRAINT exhibitor_perid_fk FOREIGN KEY (perid) REFERENCES perinfo (id) ON UPDATE CASCADE;

/* exhibitorYears - data per year for exhibitors
 */
CREATE TABLE exhibitorYears (
    id int NOT NULL AUTO_INCREMENT,
    conid int NOT NULL,
    exhibitorId int NOT NULL,
    contactName varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
    contactEmail varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
    contactPhone varchar(32) COLLATE utf8mb4_general_ci DEFAULT NULL,
    contactPassword varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
    need_new tinyint(1) DEFAULT '1',
    confirm tinyint(1) DEFAULT '0',
    PRIMARY KEY (id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE exhibitorYears ADD CONSTRAINT ey_exhibitors_fk FOREIGN KEY (exhibitorId) REFERENCES exhibitors (id) ON UPDATE CASCADE;
ALTER TABLE exhibitorYears ADD CONSTRAINT ey_conlist_fk FOREIGN KEY(conid) REFERENCES conlist(id) ON UPDATE CASCADE;

/* exhibitorApprovals - track permission requests by year for regions
 */
CREATE TABLE exhibitorApprovals (
    id int NOT NULL AUTO_INCREMENT,
    exhibitorId int NOT NULL,
    exhibitsRegionYearId int NOT NULL,
    approval enum('none','requested','approved','denied','hide') NOT NULL DEFAULT 'none',
    updateDate timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updateBy int NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE exhibitorApprovals ADD CONSTRAINT ea_exhibitor_fk FOREIGN KEY (exhibitorId) REFERENCES exhibitors(id) ON UPDATE CASCADE ON DELETE CASCADE;
ALTER TABLE exhibitorApprovals ADD CONSTRAINT ea_regionYear_fk FOREIGN KEY (exhibitsRegionYearId) REFERENCES exhibitsRegionYears(id) ON UPDATE CASCADE;
ALTER TABLE exhibitorApprovals ADD CONSTRAINT ea_updateby_fk FOREIGN KEY (updateBy) REFERENCES perinfo(id) ON UPDATE CASCADE;

/* exhibitorSpaces - space statuses this year for this exhibitor
 */
DROP TABLE IF EXISTS exhibitorSpaces;
CREATE TABLE exhibitorSpaces
(
    `id`                int       NOT NULL AUTO_INCREMENT,
    `exhibitorYearId`          int       NOT NULL,
    `spaceId`           int       NOT NULL,
    `item_requested`    int            DEFAULT NULL,
    `time_requested`    timestamp NULL DEFAULT NULL,
    `item_approved`     int            DEFAULT NULL,
    `time_approved`     timestamp NULL DEFAULT NULL,
    `item_purchased`    int            DEFAULT NULL,
    `time_purchased`    timestamp NULL DEFAULT NULL,
    `price`             decimal(8, 2)  DEFAULT NULL,
    `paid`              decimal(8, 2)  DEFAULT NULL,
    `transid`           int            DEFAULT NULL,
    `membershipCredits` int            DEFAULT '0',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


ALTER TABLE exhibitorSpaces ADD CONSTRAINT es_exhibitorYears_fk FOREIGN KEY (exhibitorYearId) REFERENCES exhibitorYears(id) ON UPDATE CASCADE;
ALTER TABLE exhibitorSpaces ADD CONSTRAINT es_spaceid_fk FOREIGN KEY (spaceId) REFERENCES exhibitorSpaces(id) ON UPDATE CASCADE;
ALTER TABLE exhibitorSpaces ADD CONSTRAINT es_transaction_fk FOREIGN KEY (transid) REFERENCES transaction(id) ON UPDATE CASCADE;
ALTER TABLE exhibitorSpaces ADD CONSTRAINT es_space_req_fk FOREIGN KEY (item_requested) REFERENCES exhibitsSpacePrices(id) ON UPDATE CASCADE;
ALTER TABLE exhibitorSpaces ADD CONSTRAINT es_space_req_fk FOREIGN KEY (item_approved) REFERENCES exhibitsSpacePrices(id) ON UPDATE CASCADE;
ALTER TABLE exhibitorSpaces ADD CONSTRAINT es_space_req_fk FOREIGN KEY (item_purchased) REFERENCES exhibitsSpacePrices(id) ON UPDATE CASCADE;
            

/* other tables effected */
ALTER TABLE payments MODIFY COLUMN category enum('reg','artshow','other','vendor','exhibits') COLLATE utf8mb4_general_ci DEFAULT NULL;


/* this has number xxx in it (needs to be 17) to prevent insert from working, update to 17 or final number when integrated */
INSERT INTO patchLog(id, name) values(xxx, vendor_with_artist);
