/*
 * P58 - art show additions, PHP tabulator reports, additional email custom text conversions,
 *
 */

/* comeback email custom text */
update controllTxtItems set contents = 'Hello [[FirstName]] [[LastName]],

#label# is almost upon us! You are receiving this email because your email address is associated with a valid registration to a prior convention, but you haven''t registered in the past few years and we don''t have you registered for this year''s convention.

We would like to encourage you to come back this year by letting you know that our early discount period is ending soon. You can see the complete
registration price list on our website at: #regpage#. You can register on-site of course, but if you register now you can save up to 20% on each membership.

This year, we are again at the #hotelname# at #hoteladdr#. Please register for rooms as soon as possible as the block will be closing soon.

Our programming team is putting together a great schedule for us this year, and you will soon be able to take a look at it at #schedulepage#. Information about other activities, as well as our Guests of Honor, can be found on our website at #website#.

The #org# (#orgabv#) is dedicated to providing a comfortable and harassment-free environment for everyone at #conname# and other #orgabv#-sponsored events. For specific information, including our full Anti-Harassment Policy, #policy#.

If you have any further questions, please feel free to contact us at #feedbackemail#. or visit our website for information on how to contact individual departments.'
where appName = 'controll' and appPage = 'emails' and appSection = 'comeback' and txtItem = 'text';

update controllTxtItems set contents = '<p>Hello [[FirstName]] [[LastName]],</p>
<p>#label# is almost upon us! You are receiving this email because your email address is associated with a valid registration to a prior convention, but you haven''t registered in the past few years and we don''t have you registered for this year''s convention.</p>
<p>We would like to encourage you to come back this year by letting you know that our early discount period is ending soon. You can see the complete registration price list on our website at: <a href="#regpage#" target="_blank" rel="noopener">#regpage#</a>. You can register on-site of course, but if you
register now you can save up to 20% on each membership.</p>
<p>This year, we are again at the <a href="#hotelpage#" target="_blank" rel="noopener">#hotelname#</a>, at #hoteladdr#.  Please register for rooms as soon as possible as the block will be closing soon.</p>
<p>Our programming team is putting together a great schedule for us this year, and you will soon be able to take a look at it at <a href="#schedulepage#" target="_blank" rel="noopener">#schedulepage#</a>. Information about other activities, as well as our Guests of Honor, can be found on our website at <a href="#website#" target="_blank" rel="noopener">#website#</a>.</p>
<p>The #org# (#orgabv#) is dedicated to providing a comfortable and harassment-free environment for everyone at #conname# and other #orgabv#-sponsored events.
For specific information, including our full Anti-Harassment Policy, see <a href="#policy#" target="_blank" rel="noopener">#policy#</a>.</p>
<p>If you have any further questions, please feel free to contact us at <a href="maito:#feedbackemail#" target="_blank" rel="noopener">#feedbackemail#</a>, or visit our website for information on how to contact individual departments.</p>'
where appName = 'controll' and appPage = 'emails' and appSection = 'comeback' and txtItem = 'html';

/* add deceased and former goh to trigger for perinfo */
DROP TRIGGER IF EXISTS perinfo_update;
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
        OR OLD.deceased != NEW.deceased OR OLD.formerGoh != NEW.formerGoh
        OR OLD.managedBy != NEW.managedBy OR OLD.managedByNew != NEW.managedByNew OR OLD.updatedBy != NEW.updatedby
        OR OLD.managedReason != NEW.managedReason)
    THEN
        INSERT INTO perinfoHistory(id, currentAgeConId, currentAgeType,
                                   last_name, first_name, middle_name, suffix, email_addr, phone,
                                   badge_name, badgeNameL2, legalName, pronouns,
                                   address, addr_2, city, state, zip, country, banned, creation_date, update_date,
                                   change_notes, active, open_notes, admin_notes, old_perid, contact_ok, share_reg_ok,
                                   deceased, formerGoh,  managedBy, managedByNew, managedReason, lastVerified, updatedBy)
        VALUES (OLD.id, OLD.currentAgeConId, OLD.currentAgeType,
                OLD.last_name, OLD.first_name, OLD.middle_name, OLD.suffix, OLD.email_addr, OLD.phone, OLD.badge_name,
                OLD.badgeNameL2, OLD.legalName, OLD.pronouns,
                OLD.address, OLD.addr_2, OLD.city, OLD.state, OLD.zip, OLD.country, OLD.banned, OLD.creation_date, OLD.update_date,
                OLD.change_notes, OLD.active, OLD.open_notes, OLD.admin_notes, OLD.old_perid, OLD.contact_ok, OLD.share_reg_ok,
                OLD.deceased, OLD.formerGoH, OLD.managedBy, OLD.managedByNew, OLD.managedReason, OLD.lastVerified, OLD.updatedBy);
    END IF;
END;;
DELIMITER ;

/* add former goh category */
INSERT INTO memCategories (memCategory, notes, onlyOne, standAlone, variablePrice, taxable, sortorder, active, badgeLabel)
VALUES ('formerGoH', 'Req: Only available to Former GoH', 'Y', 'Y', 'N', 'N', 100, 'Y','Former GoH');

/* interest table changes - add end date for interests as days before start of con,  add notes prompt field (if not blank enable notes for this interest),
 *      add notes field to memberInterests
 */
ALTER TABLE interests ADD column endDate int comment 'Number of days before start of convention that this interest becomes static'
    NOT NULL DEFAULT 0 AFTER notifyList;
ALTER TABLE interests ADD column notesPrompt varchar(256) comment 'label for notes input field'
    NOT NULL DEFAULT '' AFTER description;
ALTER TABLE memberInterests ADD column notes varchar(512) comment 'Notes entered by member for this interest'
    NOT NULL DEFAULT '' AFTER interested;

/* fix philcon reference in default value, this fix might be temporary pending a change to how this page works, or a better wording from BSFS team */
UPDATE controllTxtItems SET contents = CONCAT('<p>You can only change your accounts email address to an email address in your identities in Account Settings. ',
    'Please use the "Add New" button to add any new email addresses to your account. Identities is only available in Account Settings once your account has ',
    'been assigned an ID and is no longer pending.</p>
',
    '<p>You can only change the email address for an account you manage to one of your own (as above) or to one of the email addresses of people you manage.</p>
',
    '<p>If you need to make any other changes, please contact registration at #regadminemail# and ask for assistance.</p>')
where appName = 'portal' and appPage = 'portal' and appSection = 'main' and txtItem = 'changeEmail';

/* new table for alternate pickup perids for artshow
 */

DROP TABLE IF EXISTS artshowAltPickupAuth;
CREATE TABLE artshowAltPickupAuth (
    conid int NOT NULL COMMENT 'valid year for this authorization',
    bidderPerid int NOT NULL COMMENT 'perid (badgeId) of the art show bidder',
    pickupPerid int NOT NULL COMMENT 'perid (badgeId) of someone who can pick up bidderPerids purchased art items',
    createdBy int NOT NULL COMMENT 'perid of the art show cashier who created the relationship',
    createDate datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'date/time the relationship was created',
    active enum('N','Y') NOT NULL DEFAULT 'Y' COMMENT 'Y=active, N=inactive - for tracking when/who inactivated a relationship',
    deactivateDate datetime DEFAULT NULL COMMENT 'date/time the relationship was deactivated',
    deactivatedBy int DEFAULT NULL COMMENT 'perid of the user who deactivated the relationship',
    PRIMARY KEY (conid,bidderPerid, pickupPerid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
ALTER TABLE artshowAltPickupAuth ADD CONSTRAINT `conid`  FOREIGN KEY (conid) REFERENCES conlist(id);
ALTER TABLE artshowAltPickupAuth ADD CONSTRAINT `app_bidder`  FOREIGN KEY (bidderPerid) REFERENCES perinfo(id) ON UPDATE CASCADE;
ALTER TABLE artshowAltPickupAuth ADD CONSTRAINT `app_pickup`  FOREIGN KEY (pickupPerid) REFERENCES perinfo(id) ON UPDATE CASCADE;
ALTER TABLE artshowAltPickupAuth ADD CONSTRAINT `app_user`  FOREIGN KEY (createdBy) REFERENCES perinfo(id) ON UPDATE CASCADE;
ALTER TABLE artshowAltPickupAuth ADD CONSTRAINT `app_deactuser`  FOREIGN KEY (deactivatedBy) REFERENCES perinfo(id) ON UPDATE CASCADE;

/*
 * Taxable items split out by type of items sold for auto tax computes
 */
DROP TABLE IF EXISTS taxable;
CREATE TABLE taxable (
    item varchar(16) NOT NULL PRIMARY KEY COMMENT 'type of item being sold',
    label varchar(64) NOT NULL COMMENT 'longer name for item, more descriptive',
    defaultValue enum('N', 'Y') NOT NULL DEFAULT 'N' COMMENT 'default value for is this item taxable',
    sortOrder int NOT NULL DEFAULT 0
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO taxable (item, label, defaultValue, sortOrder) VALUES
    ('taxableMem', 'Taxable Memberships', 'Y', 10),
    ('nontaxMem', 'Non Taxable Memberships', 'N', 20),
    ('artSales', 'Art Sales', 'Y', 30),
    ('artSpace', 'Art Space', 'N', 40),
    ('artShipping', 'Art Shipping Fees', 'N', 50),
    ('vendorSpace', 'Vendor Space', 'N', 60),
    ('exhibitSpace', 'Exhibits Space', 'N', 70),
    ('fanSpace', 'Fan Table Space', 'N', 80),
    ('otherFees', 'Other Fees', 'N', 10000);

DROP TABLE IF EXISTS taxItems;
CREATE TABLE taxItems (
    conid int NOT NULL COMMENT 'applicable convention year',
    item varchar(16) NOT NULL COMMENT 'type of item being sold',
    taxable enum('N', 'Y') NOT NULL DEFAULT 'N' COMMENT 'default value for is this item taxable',
    `lastUpdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `updatedBy` int DEFAULT NULL COMMENT 'perid of signed in user that made change, null if done directly in SQL',
    sortOrder int NOT NULL DEFAULT 0 COMMENT 'Copied from taxable table',
    PRIMARY KEY (conid, item)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE taxItems ADD CONSTRAINT ti_item_taxable FOREIGN KEY (item) REFERENCES taxable(item);
ALTER TABLE taxItems ADD CONSTRAINT ti_conid_conlist FOREIGN KEY (conid) REFERENCES conlist(id);







INSERT INTO patchLog(id, name) VALUES(p58, 'Release 2.2 Artshow and other changes');
