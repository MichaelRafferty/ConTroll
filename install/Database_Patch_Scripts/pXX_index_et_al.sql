/*
 * add various items including index on reg and perhinfo history
 */

CREATE INDEX regStatus_idx ON reg(status, conid, perid);

ALTER TABLE memberInterests MODIFY COLUMN updateDate timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE perinfoHistory ADD COLUMN updatedBy int DEFAULT NULL;

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
        OR OLD.managedBy != NEW.managedBy OR OLD.managedByNew != NEW.managedByNew OR OLD.updatedBy != NEW.updatedby
        OR OLD.managedReason != NEW.managedReason OR OLD.lastVerified != NEW.lastVerified)
    THEN
        INSERT INTO perinfoHistory(id, last_name, first_name, middle_name, suffix, email_addr, phone, badge_name, legalName, pronouns,
                                   address, addr_2, city, state, zip, country, banned, creation_date, update_date, change_notes, active,
                                   open_notes, admin_notes, old_perid, contact_ok, share_reg_ok, managedBy, managedByNew,
                                   managedReason, lastVerified, updatedBy)
        VALUES (OLD.id, OLD.last_name, OLD.first_name, OLD.middle_name, OLD.suffix, OLD.email_addr, OLD.phone, OLD.badge_name, OLD.legalName, OLD.pronouns,
                OLD.address, OLD.addr_2, OLD.city, OLD.state, OLD.zip, OLD.country, OLD.banned, OLD.creation_date, OLD.update_date, OLD.change_notes,
                OLD.active, OLD.open_notes, OLD.admin_notes, OLD.old_perid, OLD.contact_ok, OLD.share_reg_ok, OLD.managedBy, OLD.managedByNew,
                OLD.managedReason, OLD.lastVerified, OLD.updatedBy);
    END IF;
END;;
DELIMITER ;


INSERT INTO patchLog(id, name) VALUES(xx, 'index at al');