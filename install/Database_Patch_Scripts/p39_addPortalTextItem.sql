/*
 * add more portal text entries - fixed patch for missing one for inserting the data
 */

INSERT INTO `controllAppPages` VALUES
    ('portal','accountSettings','Sets up management associations and identities for the Registation Portal'),
    ('portal','addUpgrade','Adds / Updates members including profile, interests and memberships for the Registration Portal'),
    ('portal','index','Login page for the Registration Portal'),
    ('portal','membershipHistory','Displays past memberships for the Registation Portal'),
    ('portal','portal','Home page for the Registration Portal');

INSERT INTO `controllAppSections` VALUES
    ('portal','accountSettings','main','main body of the account settings page'),
    ('portal','addUpgrade','interests','data entry forms related to interests'),
    ('portal','addUpgrade','main','main body of the addUpgrade page'),
    ('portal','addUpgrade','portalForms','data entry forms shared with the portal page'),
    ('portal','index','loginItems','data entry for the login page'),
    ('portal','index','main','main body of the login page'),
    ('portal','index','portalForms','data entry forms shared with the portal page'),
    ('portal','membershipHistory','main','main body of the membership history page'),
    ('portal','portal','interests','data entry forms related to interests'),
    ('portal','portal','main','main body of the portal home page'),
    ('portal','portal','paymentPlamns','data entry forms related to payment plans'),
    ('portal','portal','portalForm','data entry forms used by the portal page');

INSERT INTO `controllAppItems` VALUES
    ('portal','accountSettings','main','bottom','The bottom of the page/section'),
    ('portal','accountSettings','main','identities','Just after the identities header'),
    ('portal','accountSettings','main','managed','Just after the managed header'),
    ('portal','accountSettings','main','top','The top of the page/section'),
    ('portal','addUpgrade','main','bottom','The bottom of the page/section'),
    ('portal','addUpgrade','main','step0','The email address (Step 0)'),
    ('portal','addUpgrade','main','step1','Just after the Step 1 header'),
    ('portal','addUpgrade','main','step2','Just after the Step 2 header'),
    ('portal','addUpgrade','main','step3','Just after the Step 3 header'),
    ('portal','addUpgrade','main','step4','Just after the Step 4 header'),
    ('portal','addUpgrade','main','step4bottom','Just brelow step 4 (cart) and ahead of the HR (rule line)'),
    ('portal','addUpgrade','main','top','The top of the page/section'),
    ('portal','index','main','bottom','The bottom of the page/section'),
    ('portal','index','main','multiple','Juat after the this email has multiple membership accounts'),
    ('portal','index','main','notloggedin','Text to show if not logged in and not returned from auth link for no account'),
    ('portal','index','main','top','The top of the page/section'),
    ('portal','membershipHistory','main','bottom','The bottom of the page/section'),
    ('portal','membershipHistory','main','top','The top of the page/section'),
    ('portal','portal','main','bottom','The bottom of the page/section'),
    ('portal','portal','main','changeEmail','Bottom of change email address portal'),
    ('portal','portal','main','people','Just after the people managed header'),
    ('portal','portal','main','plan','Just after the plan header'),
    ('portal','portal','main','purchased','Just aqfter the purchased header'),
    ('portal','portal','main','top','The top of the page/section');

UPDATE controllAppItems
    SET txtItemDescription = 'Just after the Step 2 header'
    WHERE appName = 'portal' AND appPage = 'addUpgrade' AND appsection = 'main' AND txtItem = 'step2';
UPDATE controllAppItems
    SET txtItemDescription = 'Just after the Step 3 header'
    WHERE appName = 'portal' AND appPage = 'addUpgrade' AND appsection = 'main' AND txtItem = 'step3';
UPDATE controllAppItems
    SET txtItemDescription = 'Just after the Step 4 header'
    WHERE appName = 'portal' AND appPage = 'addUpgrade' AND appsection = 'main' AND txtItem = 'step4';

-- add initialize default controllTxtItems elements
INSERT INTO `controllTxtItems` VALUES
('portal','accountSettings','main','identities','<p>If you have multiple email addresses, and do not want to have to remember which one you used to create your account, you may add the others here to link them. You may also delete any accounts you no longer use.<br><br>There are two ways to login to the registration portal.<br>If you wish to login using the \"Email with Authentication\" method, leave the provider space blank. You will receive an email with a link to login and confirm.<br>If you wish to login using Google, type \"google\" in the provider space. You will receive an email now to login and confirm, but in the future can use your Google account to login</p>'),
('portal','addUpgrade','main','step1','<p>The purchase price of a membership is determined in part by the age of the member.  That is why we need this information.</p>\n<p>Children under the age of 13 must be associated with an adult or young adult member of the convention in order to create an attending membership.\nAn attending membership for them cannot be created until there is a membership for a person of guardianship age either purchased or in the cart.</p>'),
('portal','addUpgrade','main','step4','<p>This is where you may purchase memberships,&nbsp; upgrade your current membership and make donations and and any other special Membership offerings.</p>\n<p>Not to buy a Child or Kid In Tow membership you must have an over 18 membership already in your account.</p>'),
('portal','index','main','notloggedin','<p>\nIf you don’t already have an account, the next screen will say: \n“The email (your email address) does not have an account” and we will take you through the steps of creating an account.\n</p>\n<p>This is our way of asking “Are you Sure this is the correct email?”  If you’re sure then go ahead and create your account.</p>\n<p>You will still need to create a minimal account for yourself even if you are just buying a membership for someone else if you want to receive a valid receipt for your payment.</p>'),
('portal','portal','main','changeEmail','<p>You can only change your accounts email address to an email address in your identities in Account Settings.  Please use the \"Add New\" button to add any new email addresses to your account. Identities is only available in Account Settings once your account has been assigned an ID and is no longer pending.</p>\n<p>You can only change the email address for an account you manage to one of your own (as above) or to one of the email addresses of people you manage.</p>\n<p>If you need to make any other changes, please contact registration and ask for assistance</p>'),
('portal','portal','main','purchased','<p>Please review our payment plan policy. <<PaymentPlanPolicy>></p>\n<p>The minimum payment amount for any payment is <<MinPayment>>.</p>');

DELETE FROM controllTxtItems WHERE contents LIKE '%Controll-Default: %';
INSERT INTO controllTxtItems(appName, appPage, appSection, txtItem, contents)
SELECT a.appName, a.appPage, a.appSection, a.txtItem, CONCAT('Controll-Default: This is ', a.appName, '-', a.appPage, '-', a.appSection, '-', a.txtItem,
    '<br/>Custom HTML that can replaced with a custom value in the Controll Admin App under Edit Custom Text.<br/>',
    ' Default text can be suppressed in the configuration file.')
FROM controllAppItems a
LEFT OUTER JOIN controllTxtItems t on (a.appName = t.appName AND a.appPage = t.appPage AND a.appSection = t.appSection and a.txtItem = t.txtItem)
WHERE t.contents is NULL;

INSERT INTO patchLog(id, name) VALUES(39, 'add portal text item');