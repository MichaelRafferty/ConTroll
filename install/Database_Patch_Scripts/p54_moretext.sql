/*
 * Addition of more custom text fields
 */

INSERT INTO controllAppItems(appName, appPage, appSection, txtItem, txtItemDescription) VALUES
('exhibitor', 'index','invoice','termsArtistMailin','Custom Text for the plain text email for mailin artist invoice terms'),
('exhibitor', 'index','invoice','termsArtistOnsite','Custom Text for the plain text email for onsite artist invoice terms'),
('exhibitor', 'index','invoice','termsExhibitor','Custom Text for the plain text email for exhibitor invoice terms'),
('exhibitor', 'index','invoice','termsFan','Custom Text for the plain text email for fan invoice terms'),
('exhibitor', 'index','invoice','termsVendor','Custom Text for the plain text email for vendor invoice terms'),
('exhibitor', 'index','profile','descArtist','Before the description field insert into the artist profile'),
('exhibitor', 'index','profile','descExhibitor','Before the description field insert into the exhibitor profile'),
('exhibitor', 'index','profile','descFan','Before the description field insert into the fan profile'),
('exhibitor', 'index','profile','descVendor','Before the description field insert into the vendor profile');


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

INSERT INTO patchLog(id, name) VALUES(54, 'More Custom Text Fields');

