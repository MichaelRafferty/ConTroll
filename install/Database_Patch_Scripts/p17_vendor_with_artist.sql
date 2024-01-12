/* p17 - vendor_with_artist - Add parent/child relationship, add permissions for artist style, indicate type of space as vendor or artshow
   Note: vendor = no pre-approval needed, artshow - pre-approval required to allow asking for space
 */

/* it is recommended that if you have not used vendor before and there is no data you wish to migrate you uncomment out the drop table statements and recreate the tables */
/*

DROP TABLE IF EXISTS vendor_space;
DROP TABLE IF EXISTS vendors;
DROP TABLE IF EXISTS vendorSpacePrices;
DROP TABLE IF EXISTS vendorSpaces;
DROP TABLE IF EXISTS vendorRegionYears;
DROP TABLE IF EXISTS vendorRegions;
DROP TABLE IF EXISTS vendorRegionTypes;

*/

/*  vendorRegionTypes table - Rules for different types of vendor regions */
CREATE TABLE vendorRegionTypes (
    regionType varchar(16) NOT NULL,
    requestApprovalRequired enum('None','Once','Annual') NOT NULL  DEFAULT 'Once',
    purchaseApprovalRequired enum('Y','N') NOT NULL  DEFAULT 'Y',
    purchaseAreaTotals enum('unique', 'combined') DEFAULT 'combined',
    mailinAllowed enum('Y','N') NOT NULL DEFAULT 'Y',
    sortorder int NOT NULL DEFAULT 0,
    active enum('N','Y') NOT NULL DEFAULT 'Y',
    PRIMARY KEY(regionType)
);

/* vendor regions - the name and description of the various regions (locations) of vendor space */
CREATE TABLE vendorRegions (
    id int NOT NULL AUTO_INCREMENT,
    regionType varchar(16) COLLATE utf8mb4_general_ci NOT NULL,
    shortname varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
    name varchar(128) COLLATE utf8mb4_general_ci NOT NULL,
    description text COLLATE utf8mb4_general_ci,
    sortorder int NOT NULL DEFAULT 0,
    PRIMARY KEY(id)
);

ALTER TABLE vendorRegions ADD CONSTRAINT vr_regiontype_fk FOREIGN KEY(regionType) REFERENCES vendorRegionTypes(regionType) ON DELETE CASCADE ON UPDATE CASCADE;

CREATE TABLE vendorRegionYears (
    id int NOT NULL AUTO_INCREMENT,
    conid int NOT NULL,
    vendorRegion int NOT NULL,
    ownerName varchar(64) COLLATE utf8mb4_general_ci NOT NULL,
    ownerEmail varchar(64) COLLATE utf8mb4_general_ci NOT NULL,
    includedMemId int DEFAULT NULL,
    additionalMemId int DEFAULT NULL,
    totalUnitsAvailable int NOT NULL DEFAULT 0,
    sortorder int NOT NULL DEFAULT 0,
    PRIMARY KEY(id)
);

ALTER TABLE vendorRegionYears ADD CONSTRAINT vendorRegion_memList_a FOREIGN KEY(additionalMemId) REFERENCES memList(id) ON UPDATE CASCADE;
ALTER TABLE vendorRegionYears ADD CONSTRAINT vendorRegion_memList_i FOREIGN KEY(includedMemId) REFERENCES memList(id) ON UPDATE CASCADE;
ALTER TABLE vendorRegionYears ADD CONSTRAINT vsy_conlist_fk FOREIGN KEY(conid) REFERENCES conlist(id) ON UPDATE CASCADE;
ALTER TABLE vendorRegionYears ADD CONSTRAINT vsy_vendorRegion_fk FOREIGN KEY(vendorRegion) REFERENCES vendorRegions(id) ON UPDATE CASCADE;

/* vendor spaces (spaces within a region sold individually) */
CREATE TABLE vendorSpaces (
    id int NOT NULL AUTO_INCREMENT,
    vendorRegionYear int NOT NULL,
    shortname varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
    name varchar(128) COLLATE utf8mb4_general_ci NOT NULL,
    description text COLLATE utf8mb4_general_ci,
    unitsAvailable int NOT NULL DEFAULT 0,
    unitsAvailableMailin int NOT NULL DEFAULT 0,
    sortorder int NOT NULL DEFAULT 0,
    PRIMARY KEY(id)
);

ALTER TABLE vendorSpaces ADD CONSTRAINT vs_vendorRegionYears_fk FOREIGN KEY(vendorRegionYear) REFERENCES vendorRegionYears(id) ON DELETE CASCADE ON UPDATE CASCADE;

CREATE TABLE vendorSpacePrices (
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
);

ALTER TABLE vendorSpacePrices ADD CONSTRAINT vsp_vendorspaceid_fk FOREIGN KEY(spaceId) REFERENCES vendorSpaces(id) ON DELETE CASCADE ON UPDATE CASCADE;

CREATE TABLE vendors (
    id int NOT NULL AUTO_INCREMENT,
    perid int DEFAULT NULL,
    name varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
    website varchar(256) COLLATE utf8mb4_general_ci DEFAULT NULL,
    description text COLLATE utf8mb4_general_ci,
    email varchar(64) COLLATE utf8mb4_general_ci NOT NULL,
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
    ship_addr varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    ship_addr2 varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    ship_city varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    ship_state varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    ship_zip varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    ship_country varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    archived enum('N','Y') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'N',
    PRIMARY KEY (id)
);

ALTER TABLE vendors ADD CONSTRAINT vendor_perid_fk FOREIGN KEY (perid) REFERENCES perinfo (id) ON UPDATE CASCADE;
);

/* this has number xxx in it (needs to be 17) to prevent insert from working, update to 17 or final number when integrated */
INSERT INTO patchLog(id, name) values(xxx, vendor_with_artist);
