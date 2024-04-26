/*
 * changes needed to att perinfo and artItems logging and to add missing state from artItems
 *
 */
ALTER TABLE artItems MODIFY COLUMN status enum('Entered','Not In Show','Checked In','NFS','Removed from Show',
    'BID','Quicksale/Sold','To Auction','Sold Bid Sheet','Sold at Auction','Checked Out','purchased/released') COLLATE utf8mb4_general_ci DEFAULT 'Entered';


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
     status enum('Entered','Not In Show','Checked In','NFS','Removed from Show',
         'BID','Quicksale/Sold','To Auction','Sold Bid Sheet','Sold at Auction','Checked Out','purchased/released') COLLATE utf8mb4_general_ci NOT NULL,
    location varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    quantity int NOT NULL,
    original_qty int NOT NULL,
    min_price float NOT NULL,
    sale_price float DEFAULT NULL,
    final_price float DEFAULT NULL,
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
    INSERT INTO artItemsHistory(id, item_key, title, type, status, location, quantity, original_qty, min_price, sale_price,
                                final_price, bidder, conid, artshow, time_updated, updatedBy, material, exhibitorRegionYearId)
    VALUES (OLD.id, OLD.item_key, OLD.title, OLD.type, OLD.status, OLD.location, OLD.quantity, OLD.original_qty, OLD.min_price, OLD.sale_price,
            OLD.final_price, OLD.bidder, OLD.conid, OLD.artshow, OLD.time_updated, OLD.updatedBy, OLD.material, OLD.exhibitorRegionYearId);
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
    INSERT INTO perinfoHistory(id, last_name, first_name, middle_name, suffix, email_addr, phone, badge_name, legalName,
       address, addr_2, city, state, zip, country, banned, creation_date, update_date, change_notes, active,
       open_notes, admin_notes, old_perid, contact_ok, share_reg_ok)
    VALUES (OLD.id, OLD.last_name, OLD.first_name, OLD.middle_name, OLD.suffix, OLD.email_addr, OLD.phone, OLD.badge_name, OLD.legalName,
        OLD.address, OLD.addr_2, OLD.city, OLD.state, OLD.zip, OLD.country, OLD.banned, OLD.creation_date, OLD.update_date, OLD.change_notes,
        OLD.active, OLD.open_notes, OLD.admin_notes, OLD.old_perid, OLD.contact_ok, OLD.share_reg_ok);
END;//
DELIMITER ;

INSERT INTO patchLog(id, name) values(27, 'logging triggers');
