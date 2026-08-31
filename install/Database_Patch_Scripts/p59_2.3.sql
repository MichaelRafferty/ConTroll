/*
 * P59 - Stripe, finance data entry changes, art show additions
 *
 */

ALTER TABLE payments MODIFY COLUMN description varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL;

/*
 * exhibitor attendance reminder custom text items
 */

INSERT INTO `controllAppSections` VALUES
    ('exhibitor','emails','artistAttReminder','In exhibitor, attendance reminder emails'),
    ('exhibitor','emails','exhibitorAttReminder','In exhibitor, attendance reminder emails'),
    ('exhibitor','emails','fanAttReminder','In exhibitor, attendance reminder emails'),
    ('exhibitor','emails','vendorAttReminder','In exhibitor, attendance reminder emails');


INSERT INTO `controllAppItems` VALUES
('portal','portal','main','beforePayment','Custom Text just above the make payment button'),
('portal','portal','main','afterPayment','Custom Text just below the make payment button'),
('portal','index','main','oauthTop','Custom Text just above the login with buttons'),
('portal','index','main','oauthBottom','Custom Text just below the login with buttons'),
('exhibitor','emails','artistAttReminder','html','Custom Text for the html artist attendance reminder email'),
('exhibitor','emails','fanAttReminder','html','Custom Text for the html fan table attendance reminder email'),
('exhibitor','emails','exhibitorAttReminder','html','Custom Text for the html exhibitor attendance reminder email'),
('exhibitor','emails','vendorAttReminder','html','Custom Text for the html vendor (dealer) attendance reminder email'),
('exhibitor','emails','artistAttReminder','text','Custom Text for the text artist attendance reminder email'),
('exhibitor','emails','fanAttReminder','text','Custom Text for the text fan table attendance reminder email'),
('exhibitor','emails','exhibitorAttReminder','text','Custom Text for text html exhibitor attendance reminder email'),
('exhibitor','emails','vendorAttReminder','text','Custom Text for the text vendor (dealer) attendance reminder email');

INSERT INTO `controllTxtItems` VALUES
    ('portal','index','main','oauthTop',
     '<p>&nbsp;<br/>You can login immediately without waiting for an email to arrive with your login link by using our "Login With" providers.<br/>
We only access your email address, name, and subscriber id from the providers and do not share any information nor perform any other actions with them.</p>');

INSERT INTO `controllTxtItems` VALUES
    ('portal','index','main','oauthBottom',
     '<p>&nbsp;<br/>Using the "Login with Email Authentication" will send you an email with a link to click to login.<br/>
If you do not see the email, check your spam folder.</p>');

INSERT INTO `controllTxtItems` VALUES
('exhibitor','emails','artistAttReminder','html',
'<p>Dear [[FirstName]],</p>
<p>#label# is almost upon us!  You are receiving this email because your email address is associated with a valid registration as an artist in this year''s [[regionName]].</p>
<p>For further information about checking in for the [[regionName]], please see their page on our website at #website#.</p>
<p>All attendees need a membership in the convention to attend. If you are not sure if you have a membership, or if you need to
check the status of your, or the rest of your family''s, registration you can always visit the registration portal at #server#.</p>
<p>This year we are at the same hotel, which is now the #hotelname#, at #hoteladdr#.</p>
<p>Badges can be picked up or purchased at #conname# Registration.</p>
<p>See you at the convention!</p>\n'),
('exhibitor','emails','artistAttReminder','text',
'Dear [[FirstName]],\n\n#label# is almost upon us!  You are receiving this email because your email address is associated with a valid registration as an artist in this year''s [[regionName]].\n\nFor further information about checking in for the [[regionName]], please see their page on our website at #website#.\n\nAll attendees need a membership in the convention to attend.  If you are not sure if you have a membership, or if you need to check the status of your, or the rest of your family''s, registration you can always visit the registration portal at #server#.\n\nThis year we are at the same hotel, which is now the #hotelname#, at #hoteladdr#.\n\nBadges can be picked up or purchased at #conname# Registration. #addlpickuptext#\n\nSee you at the convention!\n');

INSERT INTO controllTxtItems(appName, appPage, appSection, txtItem, contents)
SELECT a.appName, a.appPage, a.appSection, a.txtItem, CONCAT('Controll-Default: This is ', a.appName, '-', a.appPage, '-', a.appSection, '-', a.txtItem,
     '<br/>Custom HTML that can replaced with a custom value in the Controll Admin App under Edit Custom Text.<br/>',
     ' Default text can be suppressed in the configuration file.')
FROM controllAppItems a
LEFT OUTER JOIN controllTxtItems t on (a.appName = t.appName AND a.appPage = t.appPage AND a.appSection = t.appSection and a.txtItem = t.txtItem)
WHERE t.contents is NULL;

UPDATE controllAppItems SET txtItemDescription = 'Custom Text for the html enter your item registration reminder email'
WHERE appName = 'exhibitor' AND appPage = 'emails' AND appSection = 'invReminder' and txtItem = 'html';

/*
 * Add new field to memList for rptGrouping and copy in the glLabel data to this field for initial starting values.
 *      For switching the reporting over
 */
ALTER TABLE memList ADD COLUMN rptGrouping varchar(128) DEFAULT '' AFTER notes;
UPDATE memList SET rptGrouping = glLabel;

DROP VIEW IF EXISTS memLabel;
CREATE ALGORITHM=UNDEFINED
    SQL SECURITY INVOKER
    VIEW memLabel AS SELECT
         m.id, m.conid, m.sort_order, m.memCategory, m.memType, m.memAge, a.shortname AS ageShortName, m.label AS shortname,
         concat(m.label,' [',a.label,']') AS label, m.cartDesc, m.notes, m.rptGrouping, m.price, m.badgeLabel, m.startdate, m.enddate,
         m.atcon, m.online, m.glNum, m.glLabel, c.taxable, c.badgeLabel AS catBadgeLabel
     FROM memList m
     JOIN ageList a ON m.memAge = a.ageType and m.conid = a.conid
     JOIN memCategories c ON m.memCategory = c.memCategory;


INSERT INTO patchLog(id, name) VALUES(59, 'Release 2.3 Stripe, Finance, Exhibitor and other changes');
