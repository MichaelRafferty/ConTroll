/* p17 - exhibits- Rename to exhibits and exhibitor, add permissions, and contact/ship to fields
   Add multi-level hierarchy for exhibit
 */

/* artists an artshow move to exhibits/exhibitors, so remove those pages from the menu */
UPDATE auth SET page = 'N' WHERE NAME IN ('artist', 'artshow');
UPDATE auth SET display='Exhibitors' WHERE NAME = 'vendor';

DROP TABLE IF EXISTS `exhibitorApprovals`;
CREATE TABLE `exhibitorApprovals` (
                                      `id` int NOT NULL AUTO_INCREMENT,
                                      `exhibitorId` int NOT NULL,
                                      `exhibitsRegionYearId` int NOT NULL,
                                      `approval` enum('none','requested','approved','denied','hide') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'none',
                                      `updateDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                      `updateBy` int NOT NULL,
                                      PRIMARY KEY (`id`),
                                      KEY `ea_exhibitor_fk` (`exhibitorId`),
                                      KEY `ea_regionYear_fk` (`exhibitsRegionYearId`),
                                      KEY `ea_updateby_fk` (`updateBy`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `exhibitorSpaces`;
CREATE TABLE `exhibitorSpaces` (
                                   `id` int NOT NULL AUTO_INCREMENT,
                                   `exhibitorYearId` int NOT NULL,
                                   `spaceId` int NOT NULL,
                                   `item_requested` int DEFAULT NULL,
                                   `time_requested` timestamp NULL DEFAULT NULL,
                                   `item_approved` int DEFAULT NULL,
                                   `time_approved` timestamp NULL DEFAULT NULL,
                                   `item_purchased` int DEFAULT NULL,
                                   `time_purchased` timestamp NULL DEFAULT NULL,
                                   `price` decimal(8,2) DEFAULT NULL,
                                   `paid` decimal(8,2) DEFAULT NULL,
                                   `transid` int DEFAULT NULL,
                                   `membershipCredits` int DEFAULT '0',
                                   PRIMARY KEY (`id`),
                                   KEY `es_exhibitorYears_fk` (`exhibitorYearId`),
                                   KEY `es_transaction_fk` (`transid`),
                                   KEY `es_space_req_fk` (`item_requested`),
                                   KEY `es_space_app_fk` (`item_approved`),
                                   KEY `es_space_pur_fk` (`item_purchased`),
                                   KEY `es_spaceid_fk` (`spaceId`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `exhibitorYears`;
CREATE TABLE `exhibitorYears` (
                                  `id` int NOT NULL AUTO_INCREMENT,
                                  `conid` int NOT NULL,
                                  `exhibitorId` int NOT NULL,
                                  `contactName` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
                                  `contactEmail` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
                                  `contactPhone` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
                                  `contactPassword` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
                                  `mailin` enum('N','Y') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'N',
                                  `artistId` int DEFAULT NULL,
                                  `need_new` tinyint(1) DEFAULT '1',
                                  `confirm` tinyint(1) DEFAULT '0',
                                  `needReview` tinyint(1) NOT NULL DEFAULT '1',
                                  PRIMARY KEY (`id`),
                                  KEY `ey_exhibitors_fk` (`exhibitorId`),
                                  KEY `ey_conlist_fk` (`conid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `exhibitors`;
CREATE TABLE `exhibitors` (
                              `id` int NOT NULL AUTO_INCREMENT,
                              `perid` int DEFAULT NULL,
                              `exhibitorName` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
                              `exhibitorEmail` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
                              `exhibitorPhone` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
                              `website` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
                              `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
                              `password` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
                              `need_new` tinyint(1) DEFAULT '1',
                              `confirm` tinyint(1) DEFAULT '0',
                              `publicity` tinyint(1) DEFAULT '0',
                              `addr` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
                              `addr2` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
                              `city` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
                              `state` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
                              `zip` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
                              `country` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
                              `shipCompany` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
                              `shipAddr` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
                              `shipAddr2` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
                              `shipCity` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
                              `shipState` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
                              `shipZip` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
                              `shipCountry` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
                              `archived` enum('N','Y') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'N',
                              PRIMARY KEY (`id`),
                              KEY `exhibitor_perid_fk` (`perid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `exhibitsRegionTypes`;
CREATE TABLE `exhibitsRegionTypes` (
                                       `regionType` varchar(16) COLLATE utf8mb4_general_ci NOT NULL,
                                       `portalType` enum('vendor','artist') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'vendor',
                                       `requestApprovalRequired` enum('None','Once','Annual') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Once',
                                       `purchaseApprovalRequired` enum('Y','N') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Y',
                                       `purchaseAreaTotals` enum('unique','combined') COLLATE utf8mb4_general_ci DEFAULT 'combined',
                                       `inPersonMaxUnits` int NOT NULL DEFAULT '0',
                                       `mailinAllowed` enum('Y','N') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Y',
                                       `mailinMaxUnits` int NOT NULL DEFAULT '0',
                                       `sortorder` int NOT NULL DEFAULT '0',
                                       `active` enum('N','Y') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Y',
                                       PRIMARY KEY (`regionType`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `exhibitsRegionYears`;
CREATE TABLE `exhibitsRegionYears` (
                                       `id` int NOT NULL AUTO_INCREMENT,
                                       `conid` int NOT NULL,
                                       `exhibitsRegion` int NOT NULL,
                                       `ownerName` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
                                       `ownerEmail` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
                                       `includedMemId` int DEFAULT NULL,
                                       `additionalMemId` int DEFAULT NULL,
                                       `totalUnitsAvailable` int NOT NULL DEFAULT '0',
                                       `sortorder` int NOT NULL DEFAULT '0',
                                       PRIMARY KEY (`id`),
                                       KEY `ery_memList_a` (`additionalMemId`),
                                       KEY `ery_memList_i` (`includedMemId`),
                                       KEY `ery_conlist_fk` (`conid`),
                                       KEY `ery_exhibitsRegion_fk` (`exhibitsRegion`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `exhibitsRegions`;
CREATE TABLE `exhibitsRegions` (
                                   `id` int NOT NULL AUTO_INCREMENT,
                                   `regionType` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
                                   `shortname` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
                                   `name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
                                   `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
                                   `sortorder` int NOT NULL DEFAULT '0',
                                   PRIMARY KEY (`id`),
                                   KEY `er_regiontype_fk` (`regionType`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `exhibitsSpacePrices`;
CREATE TABLE `exhibitsSpacePrices` (
                                       `id` int NOT NULL AUTO_INCREMENT,
                                       `spaceId` int NOT NULL,
                                       `code` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
                                       `description` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
                                       `units` decimal(4,2) DEFAULT '1.00',
                                       `price` decimal(8,2) NOT NULL,
                                       `includedMemberships` int NOT NULL DEFAULT '0',
                                       `additionalMemberships` int NOT NULL DEFAULT '0',
                                       `requestable` tinyint DEFAULT '1',
                                       `sortorder` int NOT NULL DEFAULT '0',
                                       PRIMARY KEY (`id`),
                                       KEY `esp_exhibitsspaceid_fk` (`spaceId`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `exhibitsSpaces`;
CREATE TABLE `exhibitsSpaces` (
                                  `id` int NOT NULL AUTO_INCREMENT,
                                  `exhibitsRegionYear` int NOT NULL,
                                  `shortname` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
                                  `name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
                                  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
                                  `unitsAvailable` int NOT NULL DEFAULT '0',
                                  `unitsAvailableMailin` int NOT NULL DEFAULT '0',
                                  `sortorder` int NOT NULL DEFAULT '0',
                                  PRIMARY KEY (`id`),
                                  KEY `es_exhibitsRegionYears_fk` (`exhibitsRegionYear`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE exhibitorSpaces ADD CONSTRAINT `es_exhibitorYears_fk` FOREIGN KEY (`exhibitorYearId`) REFERENCES `exhibitorYears` (`id`) ON UPDATE CASCADE;
ALTER TABLE exhibitorSpaces ADD CONSTRAINT `es_space_app_fk` FOREIGN KEY (`item_approved`) REFERENCES `exhibitsSpacePrices` (`id`) ON UPDATE CASCADE;
ALTER TABLE exhibitorSpaces ADD CONSTRAINT `es_space_pur_fk` FOREIGN KEY (`item_purchased`) REFERENCES `exhibitsSpacePrices` (`id`) ON UPDATE CASCADE;
ALTER TABLE exhibitorSpaces ADD CONSTRAINT `es_space_req_fk` FOREIGN KEY (`item_requested`) REFERENCES `exhibitsSpacePrices` (`id`) ON UPDATE CASCADE;
ALTER TABLE exhibitorSpaces ADD CONSTRAINT `es_spaceid_fk` FOREIGN KEY (`spaceId`) REFERENCES `exhibitsSpaces` (`id`) ON UPDATE CASCADE;
ALTER TABLE exhibitorSpaces ADD CONSTRAINT `es_transaction_fk` FOREIGN KEY (`transid`) REFERENCES `transaction` (`id`) ON UPDATE CASCADE;
ALTER TABLE exhibitorApprovals ADD CONSTRAINT `ea_exhibitor_fk` FOREIGN KEY (`exhibitorId`) REFERENCES `exhibitors` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE exhibitorApprovals ADD CONSTRAINT `ea_regionYear_fk` FOREIGN KEY (`exhibitsRegionYearId`) REFERENCES `exhibitsRegionYears` (`id`) ON UPDATE CASCADE;
ALTER TABLE exhibitorApprovals ADD CONSTRAINT `ea_updateby_fk` FOREIGN KEY (`updateBy`) REFERENCES `perinfo` (`id`) ON UPDATE CASCADE;
ALTER TABLE exhibitsRegions ADD CONSTRAINT `er_regiontype_fk` FOREIGN KEY (`regionType`) REFERENCES `exhibitsRegionTypes` (`regionType`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE exhibitors ADD CONSTRAINT `exhibitor_perid_fk` FOREIGN KEY (`perid`) REFERENCES `perinfo` (`id`) ON UPDATE CASCADE;
ALTER TABLE exhibitsSpaces ADD CONSTRAINT `es_exhibitsRegionYears_fk` FOREIGN KEY (`exhibitsRegionYear`) REFERENCES `exhibitsRegionYears` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE exhibitsSpacePrices ADD CONSTRAINT `esp_exhibitsspaceid_fk` FOREIGN KEY (`spaceId`) REFERENCES `exhibitsSpaces` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE exhibitorYears ADD CONSTRAINT `ey_conlist_fk` FOREIGN KEY (`conid`) REFERENCES `conlist` (`id`) ON UPDATE CASCADE;
ALTER TABLE exhibitorYears ADD CONSTRAINT `ey_exhibitors_fk` FOREIGN KEY (`exhibitorId`) REFERENCES `exhibitors` (`id`) ON UPDATE CASCADE;
ALTER TABLE exhibitsRegionYears ADD CONSTRAINT `ery_conlist_fk` FOREIGN KEY (`conid`) REFERENCES `conlist` (`id`) ON UPDATE CASCADE;
ALTER TABLE exhibitsRegionYears ADD CONSTRAINT `ery_exhibitsRegion_fk` FOREIGN KEY (`exhibitsRegion`) REFERENCES `exhibitsRegions` (`id`) ON UPDATE CASCADE;
ALTER TABLE exhibitsRegionYears ADD CONSTRAINT `ery_memList_a` FOREIGN KEY (`additionalMemId`) REFERENCES `memList` (`id`) ON UPDATE CASCADE;
ALTER TABLE exhibitsRegionYears ADD CONSTRAINT `ery_memList_i` FOREIGN KEY (`includedMemId`) REFERENCES `memList` (`id`) ON UPDATE CASCADE;

DROP VIEW IF EXISTS `vw_ExhibitorSpace`;
CREATE ALGORITHM=UNDEFINED
    SQL SECURITY INVOKER
    VIEW `vw_ExhibitorSpace` AS
    select `ert`.`portalType` AS `portalType`,`ert`.`requestApprovalRequired` AS `requestApprovalRequired`,`ert`.`purchaseApprovalRequired` AS `purchaseApprovalRequired`,
           `ert`.`purchaseAreaTotals` AS `purchaseAreaTotals`,`ert`.`mailinAllowed` AS `mailInAllowed`,`er`.`name` AS `regionName`,`er`.`shortname` AS `regionShortName`,
           `er`.`description` AS `regionDesc`,`er`.`sortorder` AS `regionSortOrder`,`ery`.`ownerName` AS `ownerName`,`ery`.`ownerEmail` AS `ownerEmail`,`ery`.`id` AS `regionYearId`,
           `ery`.`includedMemId` AS `includedMemId`,`ery`.`additionalMemId` AS `additionalMemId`,`ery`.`totalUnitsAvailable` AS `totalUnitsAvailable`,`ery`.`conid` AS `yearId`,
           `s`.`id` AS `id`,`Ey`.`conid` AS `conid`,`e`.`id` AS `exhibitorId`,`s`.`spaceId` AS `spaceId`,`es`.`shortname` AS `shortname`,`es`.`name` AS `name`,
           `s`.`item_requested` AS `item_requested`,`s`.`time_requested` AS `time_requested`,`req`.`code` AS `requested_code`,`req`.`description` AS `requested_description`,
           `req`.`units` AS `requested_units`,`req`.`price` AS `requested_price`,`req`.`sortorder` AS `requested_sort`,`s`.`item_approved` AS `item_approved`,
           `s`.`time_approved` AS `time_approved`,`app`.`code` AS `approved_code`,`app`.`description` AS `approved_description`,`app`.`units` AS `approved_units`,
           `app`.`price` AS `approved_price`,`app`.`sortorder` AS `approved_sort`,`s`.`item_purchased` AS `item_purchased`,`s`.`time_purchased` AS `time_purchased`,
           `pur`.`code` AS `purchased_code`,`pur`.`description` AS `purchased_description`,`pur`.`units` AS `purchased_units`,`pur`.`price` AS `purchased_price`,
           `pur`.`sortorder` AS `purchased_sort`,`s`.`price` AS `price`,`s`.`paid` AS `paid`,`s`.`transid` AS `transid`,`s`.`membershipCredits` AS `membershipCredits`
    from (((((((((`exhibitors` `e` join `exhibitorYears` `Ey` on((`e`.`id` = `Ey`.`exhibitorId`))) left join `exhibitorSpaces` `s` on((`Ey`.`id` = `s`.`exhibitorYearId`)))
        left join `exhibitsSpacePrices` `req` on((`s`.`item_requested` = `req`.`id`))) left join `exhibitsSpacePrices` `app` on((`s`.`item_approved` = `app`.`id`)))
        left join `exhibitsSpacePrices` `pur` on((`s`.`item_purchased` = `pur`.`id`))) left join `exhibitsSpaces` `es` on((`s`.`spaceId` = `es`.`id`)))
        join `exhibitsRegionYears` `ery` on((`es`.`exhibitsRegionYear` = `ery`.`id`))) join `exhibitsRegions` `er` on((`er`.`id` = `ery`.`exhibitsRegion`)))
        join `exhibitsRegionTypes` `ert` on((`ert`.`regionType` = `er`.`regionType`))) ;

/* NOTE: these tables totally replace the old vendor* and vendor_* tables, you can migrate data to save it, if desired. */
/*  Eventually drop all vendor tables as obsolete

    to migrate old data:


INSERT INTO exhibitsRegionTypes(regionType,portalType,requestApprovalRequired,purchaseApprovalRequired,purchaseAreaTotals,inPersonMaxUnits,
mailinAllowed,mailinMaxUnits,sortorder,`active`)
SELECT spaceType, 'vendor', 'None', 'Y', 'unique', 0,
'N', 0, 10, 'Y'
FROM vendorSpaces;

INSERT INTO exhibitsRegions(id, regionType, shortname, name, description, sortorder)
SELECT id, spaceType, shortname, name, description, 10
FROM vendorSpaces;

INSERT INTO exhibitsRegionYears(id, conid, exhibitsRegion, ownerName, ownerEmail, includedMemId, additionalMemId, totalUnitsAvailable, sortorder)
SELECT id * 10000 + conid, conid, id, 'need-owner', 'need-email', includedMemId, additionalMemId, unitsAvailable, 10
FROM vendorSpaces;

INSERT INTO exhibitsSpaces(id, exhibitsRegionYear, shortname, name, description, unitsAvailable, unitsAvailableMailin, sortorder)
SELECT id, 10000 * id + conid, shortname, name, description, unitsAvailable, unitsAvailable, 10
FROM vendorSpaces;

INSERT INTO exhibitsSpacePrices(id, spaceId, code, description, units, price, includedMemberships, additionalMemberships, requestable, sortorder)
SELECT vsp.id, vs.id, vsp.code, vsp.description, vsp.units, vsp.price, vsp.includedMemberships, vsp.additionalMemberships, vsp.requestable, vsp.sortOrder
FROM vendorSpacePrices vsp
JOIN vendorSpaces vs ON (vsp.spaceId = vs.id);

INSERT INTO exhibitors (id, exhibitorName, exhibitorEmail, website, description, password, need_new,
	confirm, publicity, addr, addr2, city, state, zip, country, archived)
SELECT id, name, email, website, description, password, need_new, confirm, publicity, addr, addr2, city, state, zip, 'US', 'N'
FROM vendors;

INSERT INTO exhibitorYears(id, conid, exhibitorId, contactName, contactEmail, contactPassword, mailin, need_new, confirm)
SELECT v.id * 10000 + vs.conid, vs.conid, v.id, v.name, v.email, v.password, 'N', v.need_new, v.confirm
FROM vendors v
JOIN vendor_space vs ON (vs.vendorid = v.id);

INSERT INTO exhibitorSpaces(id, exhibitorYearId, spaceId, item_requested, time_requested, item_approved, time_approved, item_purchased, time_purchased,
	price, paid, transid, membershipCredits)
SELECT vs.id, ey.id, vs.spaceId, item_requested, time_requested, item_approved, time_approved, item_purchased, time_purchased,
	price, paid, transid, membershipCredits
FROM vendor_space vs
JOIN exhibitorYears ey ON (ey.exhibitorId = vs.vendorId and ey.conid = vs.conid);

    then delete the old tables

DROP TABLE IF EXISTS vendor_space;
DROP TABLE IF EXISTS vendors;
DROP TABLE IF EXISTS vendorSpacePrices;
DROP TABLE IF EXISTS vendorSpaces;

    NOTE2: the table artist references vendor for artist_vendor_fk.  This is not handled by this patch, a separate patch to artist table,
    if it survives this change, will be needed

*/

INSERT INTO patchLog(id, name) values(17, 'exhibits');
