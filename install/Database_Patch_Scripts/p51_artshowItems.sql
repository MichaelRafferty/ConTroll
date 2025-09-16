/* P51
 * Upgrades/changes to art show items
 */

/*
 * WARNING: These alter table statements for notes may fail, some databases seem to be missing this field
 */
ALTER TABLE exhibitors ADD COLUMN IF NOT EXISTS `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE exhibitorYears ADD COLUMN IF NOT EXISTS `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

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

/*
 *  Clean up exhibitors and exhibitorYears for text fields to be not null default blank
 */
UPDATE exhibitors SET artistName = '' WHERE  artistName IS NULL;
UPDATE exhibitors SET exhibitorName = '' WHERE  exhibitorName IS NULL;
UPDATE exhibitors SET exhibitorPhone = '' WHERE  exhibitorPhone IS NULL;
UPDATE exhibitors SET salesTaxId = '' WHERE  salesTaxId IS NULL;
UPDATE exhibitors SET website = '' WHERE  website IS NULL;
UPDATE exhibitors SET description = '' WHERE  description IS NULL;
UPDATE exhibitors SET password = '' WHERE  password IS NULL;
UPDATE exhibitors SET addr = '' WHERE  addr IS NULL;
UPDATE exhibitors SET addr2 = '' WHERE  addr2 IS NULL;
UPDATE exhibitors SET city = '' WHERE  city IS NULL;
UPDATE exhibitors SET state = '' WHERE  state IS NULL;
UPDATE exhibitors SET zip = '' WHERE  zip IS NULL;
UPDATE exhibitors SET country = '' WHERE  country IS NULL;
UPDATE exhibitors SET shipCompany = '' WHERE  shipCompany IS NULL;
UPDATE exhibitors SET shipAddr = '' WHERE  shipAddr IS NULL;
UPDATE exhibitors SET shipAddr2 = '' WHERE  shipAddr2 IS NULL;
UPDATE exhibitors SET shipCity = '' WHERE  shipCity IS NULL;
UPDATE exhibitors SET shipState = '' WHERE  shipState IS NULL;
UPDATE exhibitors SET shipZip = '' WHERE  shipZip IS NULL;
UPDATE exhibitors SET shipCountry = '' WHERE  shipCountry IS NULL;
UPDATE exhibitors SET notes = '' WHERE  notes IS NULL;

UPDATE exhibitorYears SET contactName = '' WHERE  contactName IS NULL;
UPDATE exhibitorYears SET contactEmail = '' WHERE  contactEmail IS NULL;
UPDATE exhibitorYears SET contactPhone = '' WHERE  contactPhone IS NULL;
UPDATE exhibitorYears SET contactPassword = '' WHERE  contactPassword IS NULL;
UPDATE exhibitorYears SET notes = '' WHERE  notes IS NULL;

ALTER TABLE exhibitors MODIFY COLUMN artistName varchar(128) NOT NULL DEFAULT '';
ALTER TABLE exhibitors MODIFY COLUMN exhibitorName varchar(64) NOT NULL DEFAULT '';
ALTER TABLE exhibitors MODIFY COLUMN exhibitorPhone varchar(32) NOT NULL DEFAULT '';
ALTER TABLE exhibitors MODIFY COLUMN salesTaxId varchar(32) NOT NULL DEFAULT '';
ALTER TABLE exhibitors MODIFY COLUMN website varchar(256) NOT NULL DEFAULT '';
ALTER TABLE exhibitors MODIFY COLUMN description text NOT NULL;
ALTER TABLE exhibitors MODIFY COLUMN password varchar(64) NOT NULL DEFAULT '';
ALTER TABLE exhibitors MODIFY COLUMN addr varchar(64) NOT NULL DEFAULT '';
ALTER TABLE exhibitors MODIFY COLUMN addr2 varchar(64) NOT NULL DEFAULT '';
ALTER TABLE exhibitors MODIFY COLUMN city varchar(32) NOT NULL DEFAULT '';
ALTER TABLE exhibitors MODIFY COLUMN state varchar(16) NOT NULL DEFAULT '';
ALTER TABLE exhibitors MODIFY COLUMN zip varchar(10) NOT NULL DEFAULT '';
ALTER TABLE exhibitors MODIFY COLUMN country varchar(3) NOT NULL DEFAULT '';
ALTER TABLE exhibitors MODIFY COLUMN shipCompany varchar(64) NOT NULL DEFAULT '';
ALTER TABLE exhibitors MODIFY COLUMN shipAddr varchar(64) NOT NULL DEFAULT '';
ALTER TABLE exhibitors MODIFY COLUMN shipAddr2 varchar(64) NOT NULL DEFAULT '';
ALTER TABLE exhibitors MODIFY COLUMN shipCity varchar(64) NOT NULL DEFAULT '';
ALTER TABLE exhibitors MODIFY COLUMN shipState varchar(16) NOT NULL DEFAULT '';
ALTER TABLE exhibitors MODIFY COLUMN shipZip varchar(10) NOT NULL DEFAULT '';
ALTER TABLE exhibitors MODIFY COLUMN shipCountry varchar(3) NOT NULL DEFAULT '';
ALTER TABLE exhibitors MODIFY COLUMN notes text NOT NULL;

ALTER TABLE exhibitorYears MODIFY COLUMN contactName varchar(64) NOT NULL DEFAULT '';
ALTER TABLE exhibitorYears MODIFY COLUMN contactEmail varchar(254) NOT NULL DEFAULT '';
ALTER TABLE exhibitorYears MODIFY COLUMN contactPhone varchar(32) NOT NULL DEFAULT '';
ALTER TABLE exhibitorYears MODIFY COLUMN contactPassword varchar(64) NOT NULL DEFAULT '';
ALTER TABLE exhibitorYears MODIFY COLUMN notes text NOT NULL;

INSERT INTO patchLog(id, name) VALUES(51, 'artshowItems');
