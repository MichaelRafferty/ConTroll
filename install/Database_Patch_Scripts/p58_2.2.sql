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

INSERT INTO patchLog(id, name) VALUES(p58, 'Release 2.2 Artshow and other changes');
