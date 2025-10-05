/*
 * Addition of more custom text fields
 */

INSERT INTO controllAppItems(appName, appPage, appSection, txtItem, txtItemDescription) VALUES
('exhibitor', 'index','invoice','termsArtistMailin','Custom Text for the plain text email for mailin artist invoice terms'),
('exhibitor', 'index','invoice','termsArtistOnsite','Custom Text for the plain text email for onsite artist invoice terms'),
('exhibitor', 'index','invoice','termsExhibitor','Custom Text for the plain text email for exhibitor invoice terms'),
('exhibitor', 'index','invoice','termsFan','Custom Text for the plain text email for fan invoice terms'),
('exhibitor', 'index','invoice','termsVendor','Custom Text for the plain text email for vendor invoice terms'),
('exhibitor', 'index','profile','webExhibitor','Before the website field insert into the exhibitor profile'),
('exhibitor', 'index','profile','webFan','Before the website field insert into the fan profile'),
('exhibitor', 'index','profile','descArtist','Before the description field insert into the artist profile'),
('exhibitor', 'index','profile','descExhibitor','Before the description field insert into the exhibitor profile'),
('exhibitor', 'index','profile','descFan','Before the description field insert into the fan profile'),
('exhibitor', 'index','profile','descVendor','Before the description field insert into the vendor profile');

UPDATE controllAppItems
SET txtItemDescription = 'Before the website field insert into the artist profile'
WHERE appName = 'exhibitor' AND appPage = 'index' AND appSection = 'profile' AND txtItem = 'webArtist';

UPDATE controllAppItems
SET txtItemDescription = 'Before the website field insert into the vendor profile'
WHERE appName = 'exhibitor' AND appPage = 'index' AND appSection = 'profile' AND txtItem = 'webVendor';


INSERT INTO controllTxtItems(appName, appPage, appSection, txtItem, contents)
SELECT a.appName, a.appPage, a.appSection, a.txtItem,
       CONCAT('Controll-Default: This is ', a.appName, '-', a.appPage, '-', a.appSection, '-', a.txtItem,
              '<br/>Custom HTML that can replaced with a custom value in the ConTroll Admin App under RegAdmin/Edit Custom Text.<br/>',
              'Default text display can be suppressed in the configuration file.')
FROM controllAppItems a
LEFT OUTER JOIN controllTxtItems t ON (a.appName = t.appName AND a.appPage = t.appPage AND a.appSection = t.appSection AND a.txtItem = t.txtItem)
WHERE t.contents is NULL;

/*
 * default items for the new texts
 */
update controllTxtItems set contents = '<p>Mail-in artists do not need a membership. Included and additional discounted memberships,
however, can only be purchased while paying for your space.
If you do not purchase them now while paying your space invoice, you will have to purchase them at the current membership rates.</p>
<p>If you are unsure who will be using the registrations please use the first name of ‘Provided’ and a last name of ‘At Con’.
The on-site registration desk will update the membership to the name on their ID.</p>
<p>Program participants do not need to buy memberships; however, we will confirm that they meet the requirements to waive the membership cost.
If they do not, they will need to purchase a membership on-site at the on-site rates.</p>'
where appName = 'exhibitor' and appPage = 'index' and appSection = 'invoice' and txtItem = 'termsArtistMailin';

update controllTxtItems set contents = '<p>All non mail-in artists must have a membership.
Included and additional discounted memberships can only be purchased while paying for your space.
If you do not purchase them now while paying your space invoice, you will have to purchase them at the current membership rates.</p>
<p>If you are unsure who will be using the registrations please use the first name of ‘Provided’ and a last name of ‘At Con’.
The on-site registration desk will update the membership to the name on their ID.</p>
<p>Program participants do not need to buy memberships; however, we will confirm that they meet the requirements to waive the membership cost.
If they do not, they will need to purchase a membership on-site at the on-site rates.</p>'
where appName = 'exhibitor' and appPage = 'index' and appSection = 'invoice' and txtItem = 'termsArtistOnsite';

update controllTxtItems set contents = '<p>All vendors must have a membership.
Included and additional discounted memberships can only be purchased while paying for your space.
If you do not purchase them now while paying your space invoice, you will have to purchase them at the current membership rates.</p>
<p>If you are unsure who will be using the registrations please use the first name of ‘Provided’ and a last name of ‘At Con’.
The on-site registration desk will update the membership to the name on their ID.</p>
<p>Program participants do not need to buy memberships; however, we will confirm that they meet the requirements to waive the membership cost.
If they do not, they will need to purchase a membership on-site at the on-site rates.</p>'
where appName = 'exhibitor' and appPage = 'index' and appSection = 'invoice' and txtItem = 'termsVendor';

/* to support two lines on the badge label, add in a badge name Line 2 (badgeNameL2) field. */
ALTER TABLE perinfo ADD COLUMN badgeNameL2 varchar(32) NOT NULL DEFAULT '' AFTER badge_name;
ALTER TABLE newperson ADD COLUMN badgeNameL2 varchar(32) NOT NULL DEFAULT '' AFTER badge_name;
ALTER TABLE perinfoHistory ADD COLUMN badgeNameL2 varchar(32) DEFAULT '' AFTER badge_name;

ALTER TABLE perinfo ADD COLUMN currentAgeConId int DEFAULT NULL AFTER id;
ALTER TABLE perinfo ADD COLUMN currentAgeType varchar(16) DEFAULT NULL AFTER currentAgeConId;
ALTER TABLE newperson ADD COLUMN currentAgeConId int DEFAULT NULL AFTER id;
ALTER TABLE newperson ADD COLUMN currentAgeType varchar(16) DEFAULT NULL AFTER currentAgeConId;
ALTER TABLE perinfoHistory ADD COLUMN currentAgeConId int DEFAULT NULL AFTER id;
ALTER TABLE perinfoHistory ADD COLUMN currentAgeType varchar(16) DEFAULT NULL AFTER currentAgeConId;

ALTER TABLE perinfo ADD FOREIGN KEY perinfo_ageList(currentageConId, currentAgeType) REFERENCES ageList(conid, ageType) ON UPDATE CASCADE;
ALTER TABLE newperson ADD FOREIGN KEY nerperson_ageList(currentageConId, currentAgeType) REFERENCES ageList(conid, ageType) ON UPDATE CASCADE;

UPDATE perinfo SET badgeNameL2 = regexp_replace(badge_name, '^.*~~(.*)$','$1')
where badge_name like  '%~~%';
UPDATE perinfo SET badge_name = regexp_replace(badge_name, '^(.*)~~.*$','$1')
where badge_name like  '%~~%';

UPDATE newperson SET badgeNameL2 = regexp_replace(badge_name, '^.*~~(.*)$','$1')
where badge_name like  '%~~%';
UPDATE newperson SET badge_name = regexp_replace(badge_name, '^(.*)~~.*$','$1')
where badge_name like  '%~~%';

DROP TRIGGER `perinfo_update`;
DELIMITER ;;
CREATE DEFINER=CURRENT_USER  TRIGGER `perinfo_update` BEFORE UPDATE ON `perinfo` FOR EACH ROW BEGIN
    IF (OLD.id != NEW.id OR OLD.currentAgeConId != NEW.currentAgeConId OR OLD.currentAgeType != NEW.currentAgeType
        OR OLD.last_name != NEW.last_name OR OLD.first_name != NEW.first_name OR OLD.middle_name != NEW.middle_name
        OR OLD.suffix != NEW.suffix OR OLD.legalName != NEW.legalName OR OLD.pronouns != NEW.pronouns
        OR OLD.email_addr != NEW.email_addr OR OLD.phone != NEW.phone OR OLD.badge_name != NEW.badge_name OR OLD.badgeNameL2 != NEW.badgeNameL2
        OR OLD.address != NEW.address OR OLD.addr_2 != NEW.addr_2 OR OLD.city != NEW.city OR OLD.state != NEW.state OR OLD.zip != NEW.zip
        OR OLD.country != NEW.country OR OLD.banned != NEW.banned OR OLD.creation_date != NEW.creation_date
        OR OLD.change_notes != NEW.change_notes OR OLD.active != NEW.active OR OLD.open_notes != NEW.open_notes OR OLD.admin_notes != NEW.admin_notes
        OR OLD.old_perid != NEW.old_perid OR OLD.contact_ok != NEW.contact_ok OR OLD.share_reg_ok != NEW.share_reg_ok
        OR OLD.managedBy != NEW.managedBy OR OLD.managedByNew != NEW.managedByNew OR OLD.updatedBy != NEW.updatedby
        OR OLD.managedReason != NEW.managedReason OR OLD.lastVerified != NEW.lastVerified)
    THEN
        INSERT INTO perinfoHistory(id, currentAgeConId, currentAgeType,
            last_name, first_name, middle_name, suffix, email_addr, phone,
            badge_name, badgeNameL2, legalName, pronouns,
            address, addr_2, city, state, zip, country, banned, creation_date, update_date,
            change_notes, active, open_notes, admin_notes, old_perid, contact_ok, share_reg_ok,
            managedBy, managedByNew, managedReason, lastVerified, updatedBy)
        VALUES (OLD.id, OLD.currentAgeConId, OLD.currentAgeType,
                OLD.last_name, OLD.first_name, OLD.middle_name, OLD.suffix, OLD.email_addr, OLD.phone, OLD.badge_name,
                OLD.badgeNameL2, OLD.legalName, OLD.pronouns,
                OLD.address, OLD.addr_2, OLD.city, OLD.state, OLD.zip, OLD.country, OLD.banned, OLD.creation_date, OLD.update_date,
                OLD.change_notes, OLD.active, OLD.open_notes, OLD.admin_notes, OLD.old_perid, OLD.contact_ok, OLD.share_reg_ok,
                OLD.managedBy, OLD.managedByNew, OLD.managedReason, OLD.lastVerified, OLD.updatedBy);
    END IF;
END;;
DELIMITER ;

INSERT INTO patchLog(id, name) VALUES(54, 'More Custom Text Fields');

