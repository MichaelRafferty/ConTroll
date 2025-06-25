/* P49
 * tracking of exceptions via inline inventory changes
 */

ALTER TABLE artItems ADD COLUMN notes text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL;
ALTER TABLE artItemsHistory ADD COLUMN notes text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL AFTER exhibitorRegionYearId;

DROP TRIGGER IF EXISTS artItems_update;
DELIMITER ;;
ALTER TABLE artItems DROP TRIGGER artItems_update;
CREATE DEFINER=CURRENT_USER  TRIGGER `artItems_update` BEFORE UPDATE ON `artItems` FOR EACH ROW BEGIN
IF (OLD.id != NEW.id OR OLD.item_key != NEW.item_key OR OLD.title != NEW.title OR OLD.type != NEW.type OR OLD.status != NEW.status
OR OLD.location != NEW.location OR OLD.quantity != NEW.quantity OR OLD.original_qty != NEW.original_qty
OR OLD.min_price != NEW.min_price OR OLD.sale_price != NEW.sale_price OR OLD.final_price != NEW.final_price
OR OLD.bidder != NEW.bidder OR OLD.conid != NEW.conid OR OLD.artshow != NEW.artshow
OR OLD.updatedBy != NEW.updatedBy OR OLD.material != NEW.material OR OLD.exhibitorRegionYearId != NEW.exhibitorRegionYearId
OR OLD.notes != NEW.notes)
THEN
INSERT INTO artItemsHistory(id, item_key, title, type, status, location, quantity, original_qty, min_price, sale_price,
final_price, bidder, conid, artshow, time_updated, updatedBy, material, exhibitorRegionYearId, notes)
VALUES (OLD.id, OLD.item_key, OLD.title, OLD.type, OLD.status, OLD.location, OLD.quantity, OLD.original_qty, OLD.min_price, OLD.sale_price,
OLD.final_price, OLD.bidder, OLD.conid, OLD.artshow, OLD.time_updated, OLD.updatedBy, OLD.material, OLD.exhibitorRegionYearId, OLD.notes);
END IF;
END;;
DELIMITER ;

INSERT INTO patchLog(id, name) VALUES(49, 'artinventory');