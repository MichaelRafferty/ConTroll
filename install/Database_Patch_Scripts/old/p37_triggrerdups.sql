/*
 * update clean up trigger adding rows when nothing really changed
 *      (update statement says 0 rows updated, but the trigger created one based on change_date)
 *      regHistory, perinfo, artItems
 */

/*
 *  reg trigger
 */

DROP TRIGGER IF EXISTS reg_update;
DELIMITER ;;
CREATE DEFINER=CURRENT_USER  TRIGGER `reg_update` BEFORE UPDATE ON `reg` FOR EACH ROW BEGIN
    IF (OLD.id != NEW.id OR OLD.conid != NEW.conid OR OLD.perid != NEW.perid OR OLD.newperid != NEW.newperid
        OR OLD.oldperid != NEW.oldperid OR OLD.priorRegId != NEW.priorRegId OR OLD.create_date != NEW.create_date
        OR OLD.pickup_date != NEW.pickup_date OR OLD.price != NEW.price
        OR OLD.couponDiscount != NEW.couponDiscount OR OLD.paid != NEW.paid OR OLD.create_trans != NEW.create_trans
        OR OLD.complete_trans != NEW.complete_trans OR OLD.locked != NEW.locked OR OLD.create_user != NEW.create_user
        OR OLD.updatedBy != NEW.updatedBy OR OLD.memId != NEW.memId OR OLD.coupon != NEW.coupon
        OR OLD.planId != NEW.planId OR OLD.printable != NEW.printable OR OLD.status != NEW.status)
    THEN
        INSERT INTO regHistory(id, conid, perid, newperid, oldperid, create_date, change_date, pickup_date, price, couponDiscount,
                               paid, create_trans, complete_trans, locked, create_user, updatedBy, memId, coupon, planId, printable, status)
        VALUES (OLD.id, OLD.conid, OLD.perid, OLD.newperid, OLD.oldperid, OLD.create_date, OLD.change_date, OLD.pickup_date,
                OLD.price, OLD.couponDiscount, OLD.paid, OLD.create_trans, OLD.complete_trans, OLD.locked, OLD.create_user,
                OLD.updatedBy, OLD.memId, OLD.coupon, OLD.planId, OLD.printable, OLD.status);
    END IF;
END;;
DELIMITER ;
ALTER TABLE reg DROP CONSTRAINT IF EXISTS `reg_ibfk_1`;

/*
 * now clean up the regHistory Table
 */

DROP TABLE IF EXISTS dupsToDelete;

CREATE TEMPORARY TABLE dupsToDelete AS (
    with dups AS (
        select change_date, id, count(*)
        FROM regHistory
        group by change_date, id
        having count(*) > 1
    ), odups AS (
        select rh.historyId, rh.id, rh.change_date,
               ROW_NUMBER() OVER (PARTITION BY rh.id, rh.change_date ORDER BY rh.historyId, rh.id, rh.change_date) AS rownum
        FROM regHistory rh
                 JOIN dups on (rh.change_date = dups.change_date and rh.id = dups.id)
    )
    select historyId from odups
    where rownum > 1
);

DELETE regHistory
FROM regHistory
JOIN dupsToDelete ON (dupsToDelete.historyId = regHistory.historyId);

DROP TABLE IF EXISTS dupsToDelete;
DROP TABLE IF EXISTS reg_history;

/*
 *  perinfo trigger
 */

DROP TRIGGER IF EXISTS perinfo_update;
DELIMITER ;;
CREATE DEFINER=CURRENT_USER  TRIGGER `perinfo_update` BEFORE UPDATE ON `perinfo` FOR EACH ROW BEGIN
    IF (OLD.id != NEW.id OR OLD.last_name != NEW.last_name OR OLD.first_name != NEW.first_name OR OLD.middle_name != NEW.middle_name
        OR OLD.suffix != NEW.suffix OR OLD.legalName != NEW.legalName OR OLD.pronouns != NEW.pronouns
        OR OLD.email_addr != NEW.email_addr OR OLD.phone != NEW.phone OR OLD.badge_name != NEW.badge_name
        OR OLD.address != NEW.address OR OLD.addr_2 != NEW.addr_2 OR OLD.city != NEW.city OR OLD.state != NEW.state OR OLD.zip != NEW.zip
        OR OLD.country != NEW.country OR OLD.banned != NEW.banned OR OLD.creation_date != NEW.creation_date
        OR OLD.change_notes != NEW.change_notes OR OLD.active != NEW.active OR OLD.open_notes != NEW.open_notes OR OLD.admin_notes != NEW.admin_notes
        OR OLD.old_perid != NEW.old_perid OR OLD.contact_ok != NEW.contact_ok OR OLD.share_reg_ok != NEW.share_reg_ok
        OR OLD.managedBy != NEW.managedBy OR OLD.managedByNew != NEW.managedByNew
        OR OLD.managedReason != NEW.managedReason OR OLD.lastVerified != NEW.lastVerified)
    THEN
        INSERT INTO perinfoHistory(id, last_name, first_name, middle_name, suffix, email_addr, phone, badge_name, legalName, pronouns,
                                   address, addr_2, city, state, zip, country, banned, creation_date, update_date, change_notes, active,
                                   open_notes, admin_notes, old_perid, contact_ok, share_reg_ok, managedBy, managedByNew,
                                   managedReason, lastVerified)
        VALUES (OLD.id, OLD.last_name, OLD.first_name, OLD.middle_name, OLD.suffix, OLD.email_addr, OLD.phone, OLD.badge_name, OLD.legalName, OLD.pronouns,
                OLD.address, OLD.addr_2, OLD.city, OLD.state, OLD.zip, OLD.country, OLD.banned, OLD.creation_date, OLD.update_date, OLD.change_notes,
                OLD.active, OLD.open_notes, OLD.admin_notes, OLD.old_perid, OLD.contact_ok, OLD.share_reg_ok, OLD.managedBy, OLD.managedByNew,
                OLD.managedReason, OLD.lastVerified);
    END IF;
END;;
DELIMITER ;

DELIMITER ;
ALTER TABLE perinfoHistory DROP CONSTRAINT IF EXISTS `perinfoHistory_id_fk`;

/*
 * Clean up perinfoHistory
 */

CREATE TEMPORARY TABLE dupsToDelete AS (
    with dups AS (
        select update_date, id, count(*)
        FROM perinfoHistory
        group by update_date, id
        having count(*) > 1
    ), odups AS (
        select ph.historyId, ph.id, ph.update_date,
               ROW_NUMBER() OVER (PARTITION BY ph.id, ph.update_date ORDER BY ph.historyId, ph.id, ph.update_date) AS rownum
        FROM perinfoHistory ph
                 JOIN dups on (ph.update_date = dups.update_date and ph.id = dups.id)
    )
    select historyId from odups
    where rownum > 1
);

DELETE perinfoHistory
FROM perinfoHistory
JOIN dupsToDelete ON (dupsToDelete.historyId = perinfoHistory.historyId);

DROP TABLE IF EXISTS dupsToDelete;

/*
 * artItemsHistory
 */

DROP TRIGGER IF EXISTS artItems_update;
DELIMITER ;;
CREATE DEFINER=CURRENT_USER  TRIGGER `artItems_update` BEFORE UPDATE ON `artItems` FOR EACH ROW BEGIN
    IF (OLD.id != NEW.id OR OLD.item_key != NEW.item_key OR OLD.title != NEW.title OR OLD.type != NEW.type OR OLD.status != NEW.status
        OR OLD.location != NEW.location OR OLD.quantity != NEW.quantity OR OLD.original_qty != NEW.original_qty
        OR OLD.min_price != NEW.min_price OR OLD.sale_price != NEW.sale_price OR OLD.final_price != NEW.final_price
        OR OLD.bidder != NEW.bidder OR OLD.conid != NEW.conid OR OLD.artshow != NEW.artshow
        OR OLD.updatedBy != NEW.updatedBy OR OLD.material != NEW.material OR OLD.exhibitorRegionYearId != NEW.exhibitorRegionYearId)
    THEN
        INSERT INTO artItemsHistory(id, item_key, title, type, status, location, quantity, original_qty, min_price, sale_price,
                                    final_price, bidder, conid, artshow, time_updated, updatedBy, material, exhibitorRegionYearId)
        VALUES (OLD.id, OLD.item_key, OLD.title, OLD.type, OLD.status, OLD.location, OLD.quantity, OLD.original_qty, OLD.min_price, OLD.sale_price,
                OLD.final_price, OLD.bidder, OLD.conid, OLD.artshow, OLD.time_updated, OLD.updatedBy, OLD.material, OLD.exhibitorRegionYearId);
    END IF;
END;;
DELIMITER ;

ALTER TABLE artItemsHistory DROP CONSTRAINT IF EXISTS `artItemsHistory_id_fk`;

/*
 * Clean up artItemsHistory
 */

CREATE TEMPORARY TABLE dupsToDelete AS (
    with dups AS (
        select time_updated, id, count(*)
        FROM artItemsHistory
        group by time_updated, id
        having count(*) > 1
    ), odups AS (
        select ah.historyId, ah.id, ah.time_updated,
               ROW_NUMBER() OVER (PARTITION BY ah.id, ah.time_updated ORDER BY ah.historyId, ah.id, ah.time_updated) AS rownum
        FROM artItemsHistory ah
                 JOIN dups on (ah.time_updated = dups.time_updated and ah.id = dups.id)
    )
    select historyId from odups
    where rownum > 1
);

DELETE artItemsHistory
FROM artItemsHistory
JOIN dupsToDelete ON (dupsToDelete.historyId = artItemsHistory.historyId);

DROP TABLE IF EXISTS dupsToDelete;

INSERT INTO patchLog(id, name) VALUES(37, 'triggerdups');
