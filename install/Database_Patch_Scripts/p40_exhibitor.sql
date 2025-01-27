/*
 * add add sales tax id to exhibitor Profile Info
 */

ALTER TABLE exhibitors ADD COLUMN salesTaxId varchar(32) DEFAULT NULL AFTER exhibitorPhone;
ALTER TABLE exhibitorRegionYears ADD COLUMN specialRequests text DEFAULT NULL AFTER approval;
ALTER TABLE controllAppItems RENAME COLUMN appsection TO appSection;

INSERT INTO controllAppPages(appName, appPage, pageDescription) VALUES
('exhibitor','index','Exhibitor Portal Main Page - artist/vendor/fan/exhibits');

INSERT INTO controllAppSections(appName, appPage, appSection, sectionDescription) VALUES
('exhibitor','index','login','main body of the exhibitor portal'),
('exhibitor','index','main','main body of the exhibitor portal'),
('exhibitor','index','profile','profile modal popup of the exhibitor portal'),
('exhibitor','index','signup','signup modal popup of the exhibitor portal'),
('exhibitor','index','request','space request modal popup of the exhibitor portal'),
('exhibitor','index','invoice','space invoice modal popup of the exhibitor portal'),
('exhibitor','index','receipt','space payment receipt modal popup of the exhibitor portal'),
('exhibitor','index','items','art inventory modal popup of the exhibitor portal'),
('exhibitor','index','email','exhibitor emails');

INSERT INTO controllAppItems(appName, appPage, appSection, txtItem, txtItemDescription) VALUES
('exhibitor','index','login','topArtist','Custom Text for top of login page of the Artist portal'),
('exhibitor','index','login','topVendor','Custom Text for top of login page of the Vendor portal'),
('exhibitor','index','login','topFan','Custom Text for top of login page of the Fan portal'),
('exhibitor','index','login','topExhibitor','Custom Text for top of login page of the Exhibitor portal'),
('exhibitor','index','login','bottomArtist','Custom Text for bottom of login page of the Artist portal'),
('exhibitor','index','login','bottomVendor','Custom Text for bottom of login page of the Vendor portal'),
('exhibitor','index','login','bottomFan','Custom Text for bottom of login page of the Fan portal'),
('exhibitor','index','login','bottomExhibitor','Custom Text for bottom of login page of the Exhibitor portal'),

('exhibitor','index','main','topArtist','Custom Text for just below Welcome to the Artist portal'),
('exhibitor','index','main','topVendor','Custom Text for just below Welcome to the Vendor portal'),
('exhibitor','index','main','topFan','Custom Text for just below Welcome to the Fan portal'),
('exhibitor','index','main','topExhibitor','Custom Text for just below Welcome to the Exhibitor portal'),
('exhibitor','index','main','beforeSpaces','Custom Text for just the space list of the all of the exhibitor portals'),
('exhibitor','index','main','spacesVendor','Custom Text for just above the vendor spaces and below beforeSpaces'),
('exhibitor','index','main','spacesFan','Custom Text for above the fan spaces and below beforeSpaces'),
('exhibitor','index','main','spacesExhibitor','Custom Text for above the exhibitor spaces and below beforeSpaces'),
('exhibitor','index','main','spacesArtist','Custom Text for above the artist spaces and below beforeSpaces'),
('exhibitor','index','main','bottomVendor','Custom Text for bottom of the main page of the Vendor portal'),
('exhibitor','index','main','bottomFan','Custom Text for bottom of the main page of the Fan portal'),
('exhibitor','index','main','bottomExhibitor','Custom Text for bottom of the main page of the Exhibitor portal'),
('exhibitor','index','main','bottomArtist','Custom Text for bottom of the main page of the Artist portal'),
('exhibitor','index','main','bottom','Custom Text for the bottom of the page just before the status block/credits'),

('exhibitor','index','profile','top','Custom Text for the top of the modal profile popup-all portals'),
('exhibitor','index','profile','topArtist','Custom Text for the top custom text of the modal profile popup-artist portal'),
('exhibitor','index','profile','topVendor','Custom Text for the top custom text of the modal profile popup-vendor portals'),
('exhibitor','index','profile','topFan','Custom Text for the top custom text of the modal profile popup-fan portals'),
('exhibitor','index','profile','topExhibitor','Custom Text for the top custom text of the modal profile popup-exhibitor portals'),
('exhibitor','index','profile','busArtist','Custom Text for after the Business Information header of the modal profile popup-artist portal'),
('exhibitor','index','profile','busVendor','Custom Text for after the Business Information header of the modal profile popup-vendor portals'),
('exhibitor','index','profile','busFan','Custom Text for after the Business Information header of the modal profile popup-fan portals'),
('exhibitor','index','profile','busExhibitor','Custom Text for after the Business Information header of the modal profile popup-exhibitor portals'),
('exhibitor','index','profile','addArtist','Custom Text for after the Address header of the modal profile popup-artist portal'),
('exhibitor','index','profile','addVendor','Custom Text for after the Address header of the modal profile popup-vendor portals'),
('exhibitor','index','profile','addFan','Custom Text for after the Address header of the modal profile popup-fan portals'),
('exhibitor','index','profile','addExhibitor','Custom Text for after the Address header of the modal profile popup-exhibitor portals'),
('exhibitor','index','profile','contact','Custom Text for after the Primary Contact of the modal profile popup-all portals'),
('exhibitor','index','profile','shipping','Custom Text for after the Shipping Address of the modal profile popup-artist portals'),

('exhibitor','index','signup','top','Custom Text for the top of the modal signup popup-all portals, shows on all pages'),
('exhibitor','index','signup','pg1Artist','Custom Text for the top custom text of page 1 of the modal signup popup-artist portal'),
('exhibitor','index','signup','pg1Vendor','Custom Text for the top custom text of page 1 of the modal signup popup-vendor portals'),
('exhibitor','index','signup','pg1Fan','Custom Text for the top custom text of page 1 of the modal signup popup-fan portals'),
('exhibitor','index','signup','pg1Exhibitor','Custom Text for the top custom text of page 1 of the modal signup popup-exhibitor portals'),
('exhibitor','index','signup','pg2Artist','Custom Text for the top of page 2 of the modal signup popup-artist portal'),
('exhibitor','index','signup','pg2Vendor','Custom Text for the top of page 2 of the modal signup popup-vendor portals'),
('exhibitor','index','signup','pg2Fan','Custom Text for the top of page 2 of the modal signup popup-fan portals'),
('exhibitor','index','signup','pg2Exhibitor','Custom Text for the top of page 2 of the modal signup popup-exhibitor portals'),
('exhibitor','index','signup','pg3Artist','Custom Text for the top of page 3 of the modal signup popup-artist portal'),
('exhibitor','index','signup','pg3Vendor','Custom Text for the top of page 3 of the modal signup popup-vendor portals'),
('exhibitor','index','signup','pg3Fan','Custom Text for the top of page 3 of the modal signup popup-fan portals'),
('exhibitor','index','signup','pg3Exhibitor','Custom Text for the top of page 3 of the modal signup popup-exhibitor portals'),
('exhibitor','index','signup','pg4Artist','Custom Text for the top of page 4 of the modal signup popup-artist portal'),
('exhibitor','index','signup','pg4Vendor','Custom Text for the top of page 4 of the modal signup popup-vendor portals'),
('exhibitor','index','signup','pg4Fan','Custom Text for the top of page 4 of the modal signup popup-fan portals'),
('exhibitor','index','signup','pg4Exhibitor','Custom Text for the top of page 4 of the modal signup popup-exhibitor portals'),
('exhibitor','index','signup','bottom','Custom Text for the bottom of the modal signup popup-all portals, shows on all pages'),

('exhibitor','index','request','top','Custom Text for the top of the modal space request popup-all portals'),
('exhibitor','index','request','topArtist','Custom Text for the top custom text of the modal space request popup-artist portal'),
('exhibitor','index','request','topVendor','Custom Text for the top custom text of the modal space request popup-vendor portals'),
('exhibitor','index','request','topFan','Custom Text for the top custom text of the modal space request popup-fan portals'),
('exhibitor','index','request','topExhibitor','Custom Text for the top custom text of the modal space request popup-exhibitor portals'),
('exhibitor','index','request','bottom','Custom Text for the bottom of the modal space request popup-all portals'),
('exhibitor','index','request','bottomArtist','Custom Text for the bottom custom text of the modal space request popup-artist portal'),
('exhibitor','index','request','bottomVendor','Custom Text for the t custom text of the modal space request popup-vendor portals'),
('exhibitor','index','request','bottomFan','Custom Text for the bottom custom text of the modal space request popup-fan portals'),
('exhibitor','index','request','bottomExhibitor','Custom Text for bottom custom text of the modal space request popup-exhibitor portals'),
('exhibitor','index','request','disclaimer','Custom Text for the registration disclaimer popup-all portals'),
('exhibitor','index','request','disclaimerArtist','Custom Text for the registration disclaimer popup-artist portal'),
('exhibitor','index','request','disclaimerVendor','Custom Text for the registration disclaimer popup-vendor portal'),
('exhibitor','index','request','disclaimerExhibitor','Custom Text for the registrationd disclaimer popup-exhibitor portal'),
('exhibitor','index','request','disclaimerFan','Custom Text for the registration disclaimer popup-fan portal'),

('exhibitor','index','invoice','top','Custom Text for the top of the modal invoice popup-all portals'),
('exhibitor','index','invoice','topArtist','Custom Text for the top custom text of the modal invoice popup-artist portal'),
('exhibitor','index','invoice','topVendor','Custom Text for the top custom text of the modal invoice popup-vendor portals'),
('exhibitor','index','invoice','topFan','Custom Text for the top custom text of the modal invoice popup-fan portals'),
('exhibitor','index','invoice','topExhibitor','Custom Text for the top custom text of the modal invoice popup-exhibitor portals'),
('exhibitor','index','invoice','afterPrice','Custom Text for after the price of the modal invoice popup-all portals'),
('exhibitor','index','invoice','afterPriceArtist','Custom Text for after the price custom text of the modal invoice popup-artist portal'),
('exhibitor','index','invoice','afterPriceVendor','Custom Text for after the price custom text of the modal invoice popup-vendor portals'),
('exhibitor','index','invoice','afterPriceFan','Custom Text for after the price custom text of the modal invoice popup-fan portals'),
('exhibitor','index','invoice','afterPriceExhibitor','Custom Text after the price custom text of the modal invoice popup-exhibitor portals'),
('exhibitor','index','invoice','beforeProfile','Custom Text for before the profile of the modal invoice popup-all portals'),
('exhibitor','index','invoice','beforeProfileArtist','Custom Text for before the profile custom text of the modal invoice popup-artist portal'),
('exhibitor','index','invoice','beforeProfileVendor','Custom Text for before the profile custom text of the modal invoice popup-vendor portals'),
('exhibitor','index','invoice','beforeProfileFan','Custom Text for before the profile custom text of the modal invoice popup-fan portals'),
('exhibitor','index','invoice','beforeProfileExhibitor','Custom Text for before the profile custom text of the modal invoice popup-exhibitor portals'),
('exhibitor','index','invoice','beforeMem','Custom Text for before the memberships of the modal invoice popup-all portals'),
('exhibitor','index','invoice','beforeMemArtist','Custom Text for before the memberships of the modal invoice popup-artist portal'),
('exhibitor','index','invoice','beforeMemVendor','Custom Text for efore the memberships of the modal invoice popup-vendor portals'),
('exhibitor','index','invoice','beforeMemFan','Custom Text for before the memberships of the modal invoice popup-fan portals'),
('exhibitor','index','invoice','beforeMemExhibitor','Custom Text for before the memberships of the modal invoice popup-exhibitor portals'),
('exhibitor','index','invoice','beforeCharge','Custom Text for the before the credit card block of the modal invoice popup-all portals'),
('exhibitor','index','invoice','payDisclaimer','Custom Text for the payment disclaimer popup-all portals'),
('exhibitor','index','invoice','payDisclaimerArtist','Custom Text for the payment disclaimer popup-artist portal'),
('exhibitor','index','invoice','payDisclaimerVendor','Custom Text for the payment disclaimer popup-vendor portal'),
('exhibitor','index','invoice','payDisclaimerExhibitor','Custom Text for the payment disclaimer popup-exhibitor portal'),
('exhibitor','index','invoice','payDisclaimerFan','Custom Text for the payment disclaimer popup-fan portal'),
('exhibitor','index','invoice','bottom','Custom Text for the bottom of the modal invoice popup-all portals'),
('exhibitor','index','invoice','bottomArtist','Custom Text for the bottom custom text of the modal invoice popup-artist portal'),
('exhibitor','index','invoice','bottomVendor','Custom Text for the bottom custom text of the modal invoice popup-vendor portals'),
('exhibitor','index','invoice','bottomFan','Custom Text for the bottom custom text of the modal invoice popup-fan portals'),
('exhibitor','index','invoice','bottomExhibitor','Custom Text for bottom custom text of the modal invoice popup-exhibitor portals'),
('exhibitor','index','invoice','taxIdExtra','Custom Text for after tax id of the modal invoice popup-exhibitor portals'),

('exhibitor','index','receipt','top', 'Custom Text for the top of the receipt - all portals'),
('exhibitor','index','receipt','bottom', 'Custom Text for the bottom of the receipt - all portals'),

('exhibitor','index','items','top','Custom text for the top of the art inventory modal popup of the exhibitor portal'),
('exhibitor','index','items','bottom','Custom text for the bottom of the art inventory modal popup of the exhibitor portal'),

('exhibitor','index','email','onsiteInvHTML','On Site Artist Inventory HTML Email'),
('exhibitor','index','email','onsiteInvText','On Site Artist Inventory Test Email'),
('exhibitor','index','email','mailinInvHTML','Mail In Artist Inventory HTML Email'),
('exhibitor','index','email','mailinInvText','Mail In Artist Inventory Text Email');

INSERT INTO `controllTxtItems` VALUES
('exhibitor','index','invoice','afterPrice','<p>Please fill out this section with information on the #portalType# or store.</p>');

DELETE FROM controllTxtItems WHERE contents LIKE '%Controll-Default: %';
INSERT INTO controllTxtItems(appName, appPage, appSection, txtItem, contents)
SELECT a.appName, a.appPage, a.appSection, a.txtItem, CONCAT('Controll-Default: This is ', a.appName, '-', a.appPage, '-', a.appSection, '-', a.txtItem,
'<br/>Custom HTML that can replaced with a custom value in the Controll Admin App under Edit Custom Text.<br/>',
' Default text can be suppressed in the configuration file.')
FROM controllAppItems a
LEFT OUTER JOIN controllTxtItems t on (a.appName = t.appName AND a.appPage = t.appPage AND a.appSection = t.appSection and a.txtItem = t.txtItem)
WHERE t.contents is NULL;
INSERT INTO patchLog(id, name) VALUES(40, 'exhibitor_tax_id');