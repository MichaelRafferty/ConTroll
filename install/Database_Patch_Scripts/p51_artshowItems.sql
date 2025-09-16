/* P51
 * Upgrades/changes to art show items
 */

/* delete the auth artshow, as obsolete replaced by artsales and artinventory */
DELETE FROM atcon_auth WHERE auth = 'artshow';
ALTER TABLE atcon_auth MODIFY COLUMN  auth enum('data_entry','cashier','manager','artinventory','artsales','vol_roll');

UPDATE exhibitorRegionYears SET locations = '' WHERE locations IS NULL;
ALTER TABLE exhibitorRegionYears MODIFY COLUMN locations  varchar(512) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '';

UPDATE artItems SET location = '' WHERE location IS NULL;
ALTER TABLE artItems MODIFY COLUMN location varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '';

/*
 * Changes to create new expiring unpaid membership reminder email
 */

INSERT INTO controllAppSections (appName, appPage, appSection, sectionDescription) VALUES
    ('controll', 'emails', 'expire', 'Expiring Unpaid Registration Email');

INSERT INTO controllAppItems(appName, appPage, appSection, txtItem, txtItemDescription) VALUES
    ('controll', 'emails','expire','text','Custom Text for the plain text expiring unpaid reg email'),
    ('controll', 'emails','expire','html','Custom Text for the html expiring unpaid reg email');

INSERT INTO controllTxtItems(appName, appPage, appSection, txtItem, contents)
SELECT a.appName, a.appPage, a.appSection, a.txtItem,
       CONCAT('Controll-Default: This is ', a.appName, '-', a.appPage, '-', a.appSection, '-', a.txtItem,
              '<br/>Custom HTML that can replaced with a custom value in the ConTroll Admin App under RegAdmin/Edit Custom Text.<br/>',
              'Default text display can be suppressed in the configuration file.')
FROM controllAppItems a
         LEFT OUTER JOIN controllTxtItems t ON (a.appName = t.appName AND a.appPage = t.appPage AND a.appSection = t.appSection AND a.txtItem = t.txtItem)
WHERE t.contents is NULL;

/*
 * default emails for the distribution
 */
update controllTxtItems set contents = '<p><strong>Hello!</strong></p>
<p>Dear [[FirstName]],</p>
<p>You are receiving this email because you created a [[label]] membership for #conname# at a discounted rate on [[createdate]], but never paid for it.
That discounted rate will expire on [[enddate]].  If the membership is not paid before that date, we will cancel that unpaid membership
and you will need to purchase one at the then current rate to attend.</p>
<p>Please sign in to our registration portal at <a href="#server#">#server#</a> and click on the "Pay Total Amount Due" button to pay for your membership.</p>
<p>If you no longer desire this membership, either sign on  and use the "Add To/Edit Cart" button to remove the membership from your
account or just let it expire and it will be removed automatically.</p>
<p>We look forward to seeing you at #conname#.</p>
<p>If you have any issues in paying for your membership or removing it from your account, please reach out to us at
<a href="mailto:#regadminemail#">#regadminemail#</a>.
<p>Thank you,<br/>
#conname# Registration
</p>'
where appName = 'controll' and appPage = 'emails' and appSection = 'expire' and txtItem = 'html';

update controllTxtItems set contents = 'Dear [[FirstName]],

You are receiving this email because you created a [[label]] membership for #conname# at a discounted rate on [[createdate]], but never paid for it.
That discounted rate will expire on [[enddate]].  If the membership is not paid before that date, we will cancel that unpaid membership
and you will need to purchase one at the then current rate to attend.

Please sign in to our registration portal at #server# and click on the "Pay Total Amount Due" button to pay for your membership.

If you no longer desire this membership, either sign on  and use the "Add To/Edit Cart" button to remove the membership from your account or just let it
expire and it will be removed automatically.

We look forward to seeing you at #conname#.

If you have any issues in paying for your membership or removing it from your account, please reach out to us at #regadminemail#.

Thank you,
#conname# Registration
'
where appName = 'controll' and appPage = 'emails' and appSection = 'expire' and txtItem = 'text';

INSERT INTO patchLog(id, name) VALUES(xx, 'artshowItems');
