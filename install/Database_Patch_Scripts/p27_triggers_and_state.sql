/*
 * changes needed to add perinfo and artItems logging and to add missing state from artItems
 *   changes to artItems and artSales to correct float, and replace artSales table
 *
 */

 /* disabler old reg for now */
UPDATE auth SET page = 'N' WHERE id = 999;

ALTER TABLE artItems MODIFY COLUMN status enum('Entered','Not In Show','Checked In','Removed from Show',
    'BID','Quicksale/Sold','To Auction','Sold Bid Sheet','Sold at Auction','Checked Out','Purchased/Released') COLLATE utf8mb4_general_ci DEFAULT 'Entered';

// NOTE: if you have the table artsales, you'll need to drop that as well
//DROP TABLE IF EXISTS artsales;
DROP TABLE IF EXISTS artSales;
CREATE TABLE artSales (
    id int NOT NULL AUTO_INCREMENT,
    transid int DEFAULT NULL,
    artid int DEFAULT NULL,
    unit int DEFAULT NULL,
    status enum('Entered','Not In Show','Checked In','Removed from Show','BID','Quicksale/Sold','To Auction','Sold Bid Sheet','Sold at Auction','Checked Out','Purchased/Released') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    perid int DEFAULT NULL,
    amount decimal(8,2) NOT NULL,
    paid decimal(8,2) NOT NULL DEFAULT '0.00',
    quantity int NOT NULL,
    PRIMARY KEY (id),
    KEY artSales_transid_fk (transid),
    KEY artSales_artitem_fk (artid),
    KEY artSales_perinfo_fk (perid),
    CONSTRAINT artSales_artitem_fk FOREIGN KEY (artid) REFERENCES artItems (id) ON UPDATE CASCADE,
    CONSTRAINT artSales_perinfo_fk FOREIGN KEY (perid) REFERENCES perinfo (id) ON UPDATE CASCADE,
    CONSTRAINT artSales_transid_fk FOREIGN KEY (transid) REFERENCES transaction (id) ON UPDATE CASCADE
);

/*
 * create artItems trigger for logging
 */
ALTER TABLE artItems ADD COLUMN updatedBy int DEFAULT NULL after time_updated;
INSERT INTO perinfo(id, first_name, last_name, email_addr, banned, active, open_notes, contact_ok, share_reg_ok) VALUES (3, 'Exhibitor', 'Internal', NULL, 'N', 'N', 'INTERNAL NOT FOR REGISTRATION USE', 'N', 'N');
UPDATE artItems SET updatedBy=3;
ALTER TABLE artItems MODIFY COLUMN updatedBy int NOT NULL;
ALTER TABLE artItems ADD CONSTRAINT `artItems_updatedBy_fk` FOREIGN KEY (`updatedBy`) REFERENCES `perinfo` (`id`) ON UPDATE CASCADE;

DROP TABLE IF EXISTS artItemsHistory;
CREATE TABLE artItemsHistory (
    historyId int NOT NULL AUTO_INCREMENT,
    id int NOT NULL,
    item_key int NOT NULL,
    title varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    type enum('art','nfs','print') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
     status enum('Entered','Not In Show','Checked In','Removed from Show',
         'BID','Quicksale/Sold','To Auction','Sold Bid Sheet','Sold at Auction','Checked Out','Purchased/Released') COLLATE utf8mb4_general_ci NOT NULL,
    location varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    quantity int NOT NULL,
    original_qty int NOT NULL,
    min_price decimal(8,2) NOT NULL,
    sale_price decimal(8,2) DEFAULT NULL,
    final_price decimal(8,2) DEFAULT NULL,
    bidder int DEFAULT NULL,
    conid int DEFAULT NULL,
    artshow int DEFAULT NULL,
    time_updated timestamp NULL,
    updatedBy int NOT NULL,
    material varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    exhibitorRegionYearId int DEFAULT NULL,
    historyDate timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (historyId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE artItemsHistory ADD CONSTRAINT `artItemsHistory_conid_fk` FOREIGN KEY (`conid`) REFERENCES `conlist` (`id`) ON UPDATE CASCADE;
ALTER TABLE artItemsHistory ADD CONSTRAINT `aIH_exhibitorRegionYear_fk` FOREIGN KEY (`exhibitorRegionYearId`) REFERENCES `exhibitorRegionYears` (`id`) ON UPDATE CASCADE;
ALTER TABLE artItemsHistory ADD CONSTRAINT `artItemsHistory_updatedBy_fk` FOREIGN KEY (`updatedBy`) REFERENCES `perinfo` (`id`) ON UPDATE CASCADE;
ALTER TABLE artItemsHistory ADD CONSTRAINT `artItemsHistory_id_fk` FOREIGN KEY (`id`) REFERENCES `artItems` (`id`) ON UPDATE CASCADE;

DROP TRIGGER IF EXISTS artItems_update;
DELIMITER //
CREATE
    DEFINER = CURRENT_USER
    TRIGGER artItems_update
    BEFORE UPDATE ON artItems FOR EACH ROW
BEGIN
    IF (OLD.id != NEW.id OR OLD.item_key != NEW.item_key OR OLD.title != NEW.title OR OLD.type != NEW.type OR OLD.status != NEW.status
        OR OLD.location != NEW.location OR OLD.quantity != NEW.quantity OR OLD.original_qty != NEW.original_qty
        OR OLD.min_price != NEW.min_price OR OLD.sale_price != NEW.sale_price OR OLD.final_price != NEW.final_price
        OR OLD.bidder != NEW.bidder OR OLD.conid != NEW.conid OR OLD.artshow != NEW.artshow OR OLD.time_updated != NEW.time_updated
        OR OLD.updatedBy != NEW.updatedBy OR OLD.material != NEW.material OR OLD.exhibitorRegionYearId != NEW.exhibitorRegionYearId)
        THEN
            INSERT INTO artItemsHistory(id, item_key, title, type, status, location, quantity, original_qty, min_price, sale_price,
                final_price, bidder, conid, artshow, time_updated, updatedBy, material, exhibitorRegionYearId)
            VALUES (OLD.id, OLD.item_key, OLD.title, OLD.type, OLD.status, OLD.location, OLD.quantity, OLD.original_qty, OLD.min_price, OLD.sale_price,
                OLD.final_price, OLD.bidder, OLD.conid, OLD.artshow, OLD.time_updated, OLD.updatedBy, OLD.material, OLD.exhibitorRegionYearId);
        END IF;
END;//
DELIMITER ;

DROP TABLE IF EXISTS perinfoHistory;
CREATE TABLE perinfoHistory (
    historyId int NOT NULL AUTO_INCREMENT,
    id int NOT NULL,
    last_name varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    first_name varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    middle_name varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    suffix varchar(4) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    email_addr varchar(254) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    phone varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    badge_name varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    legalName varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    address varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    addr_2 varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    city varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    state varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    zip varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    country varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    banned enum('N','Y') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    creation_date datetime NOT NULL,
    update_date timestamp DEFAULT NULL,
    change_notes text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    active enum('N','Y') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    open_notes text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    admin_notes text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    old_perid int  DEFAULT NULL,
    contact_ok enum('N','Y') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci  DEFAULT NULL,
    share_reg_ok enum('N','Y') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci  DEFAULT NULL,
    historyDate timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (historyId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE perinfoHistory ADD CONSTRAINT `perinfoHistory_id_fk` FOREIGN KEY (`id`) REFERENCES `perinfo` (`id`) ON UPDATE CASCADE;

DROP TRIGGER IF EXISTS perinfo_update;
DELIMITER //
CREATE
    DEFINER = CURRENT_USER
    TRIGGER perinfo_update
    BEFORE UPDATE ON perinfo FOR EACH ROW
BEGIN
    IF (OLD.id != NEW.id OR OLD.last_name != NEW.last_name OR OLD.first_name != NEW.first_name OR OLD.middle_name != NEW.middle_name OR OLD.suffix != NEW.suffix
        OR OLD.email_addr != NEW.email_addr OR OLD.phone != NEW.phone OR OLD.badge_name != NEW.badge_name OR OLD.legalName != NEW.legalName
        OR OLD.address != NEW.address OR OLD.addr_2 != NEW.addr_2 OR OLD.city != NEW.city OR OLD.state != NEW.state OR OLD.zip != NEW.zip
        OR OLD.country != NEW.country OR OLD.banned != NEW.banned OR OLD.creation_date != NEW.creation_date OR OLD.update_date != NEW.update_date
        OR OLD.change_notes != NEW.change_notes OR OLD.active != NEW.active OR OLD.open_notes != NEW.open_notes OR OLD.admin_notes != NEW.admin_notes
        OR OLD.old_perid != NEW.old_perid OR OLD.contact_ok != NEW.contact_ok OR OLD.share_reg_ok != NEW.share_reg_ok)
    THEN

        INSERT INTO perinfoHistory(id, last_name, first_name, middle_name, suffix, email_addr, phone, badge_name, legalName,
                                   address, addr_2, city, state, zip, country, banned, creation_date, update_date, change_notes, active,
                                   open_notes, admin_notes, old_perid, contact_ok, share_reg_ok)
        VALUES (OLD.id, OLD.last_name, OLD.first_name, OLD.middle_name, OLD.suffix, OLD.email_addr, OLD.phone, OLD.badge_name, OLD.legalName,
                OLD.address, OLD.addr_2, OLD.city, OLD.state, OLD.zip, OLD.country, OLD.banned, OLD.creation_date, OLD.update_date, OLD.change_notes,
                OLD.active, OLD.open_notes, OLD.admin_notes, OLD.old_perid, OLD.contact_ok, OLD.share_reg_ok);
    END IF;
END;//
DELIMITER ;

INSERT INTO patchLog(id, name) values(27, 'logging triggers');
