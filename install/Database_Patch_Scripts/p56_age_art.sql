/*
 * P56 - starts with quick sale changes and continues through portal rewrite
 */

/*
 * flag for disabling quick sale
 */
ALTER TABLE exhibitsRegionTypes ADD COLUMN allowQuickSale enum('Y', 'N') NOT NULL DEFAULT 'Y';

/*
 * use portalTokenLinks for exhibitor portal password resets
 */
ALTER TABLE portalTokenLinks MODIFY COLUMN action enum('login','attach','identity','password','other') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'other';

/*
 * flag for confirm age on new year
 */
ALTER TABLE ageList ADD COLUMN verify enum('Y', 'N') NOT NULL DEFAULT 'Y';

/*
 * ArtItems -> Not In Show => Withdrawn
 */

ALTER TABLE `artItems` MODIFY COLUMN `status`
    ENUM('Entered','Not In Show','Withdrawn','Checked In','Removed from Show','BID','Quicksale/Sold','To Auction',
        'Sold Bid Sheet','Sold at Auction','Checked Out','Purchased/Released')
    CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'Entered';

UPDATE artItems SET status='Withdrawn' WHERE status='Not In Show';

ALTER TABLE `artItems` MODIFY COLUMN `status`
    ENUM('Entered','Withdrawn','Checked In','Removed from Show','BID','Quicksale/Sold','To Auction',
        'Sold Bid Sheet','Sold at Auction','Checked Out','Purchased/Released')
        CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'Entered';

ALTER TABLE `artItemsHistory` MODIFY COLUMN  `status`
    ENUM('Entered','Withdrawn','Not In Show','Checked In','Removed from Show','BID','Quicksale/Sold','To Auction',
        'Sold Bid Sheet','Sold at Auction','Checked Out','Purchased/Released')
        CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;

UPDATE artItemsHistory SET status='Withdrawn' WHERE status='Not In Show';

ALTER TABLE `artItemsHistory` MODIFY COLUMN `status`
    ENUM('Entered','Withdrawn','Checked In','Removed from Show','BID','Quicksale/Sold','To Auction',
        'Sold Bid Sheet','Sold at Auction','Checked Out','Purchased/Released')
        CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;

/*
 * portal custom text changes for rewrite
 */
INSERT INTO controllAppPages(appName,appPage,pageDescription)
VALUES ('portal', 'add', 'Add New Member to a Portal Account'),
VALUES ('portal', 'cart', 'Purchase Memberships/Items for a Portal Account');

INSERT INTO controllAppSections(appName,appPage,appSection,sectionDescription) VALUES
('portal', 'index', 'profile', 'Profile section of add new account on login'),
('portal', 'add', 'email', 'Profile section of add new account email');

INSERT INTO controllAppItems(appName,appPage,AppSection,txtItem, txtItemDescription)
VALUES ('portal', 'index', 'profile', 'email', 'Create new account profile before email address entry');

INSERT INTO controllAppItems(appName,appPage,AppSection,txtItem, txtItemDescription)
VALUES ('portal', 'index', 'profile', 'top', 'Create new account profile before profile data');

INSERT INTO controllAppItems(appName,appPage,AppSection,txtItem, txtItemDescription)
VALUES ('portal', 'index', 'profile', 'bottom', 'Create new account profile at bottom before buttons');

INSERT INTO controllAppSections(appName,appPage,appSection,sectionDescription)
VALUES ('portal', 'add', 'profile', 'Profile section of add new account on login');

INSERT INTO controllAppItems(appName,appPage,AppSection,txtItem, txtItemDescription)
VALUES ('portal', 'add', 'profile', 'top', 'Create new account profile before profile data');

INSERT INTO controllAppItems(appName,appPage,AppSection,txtItem, txtItemDescription)
VALUES ('portal', 'add', 'profile', 'bottom', 'Create new account profile at bottom before buttons');

INSERT INTO controllTxtItems(appName, appPage, appSection, txtItem, contents)
SELECT a.appName, a.appPage, a.appSection, a.txtItem,
       CONCAT('Controll-Default: This is ', a.appName, '-', a.appPage, '-', a.appSection, '-', a.txtItem,
              '<br/>Custom HTML that can replaced with a custom value in the ConTroll Admin App under RegAdmin/Edit Custom Text.<br/>',
              'Default text display can be suppressed in the configuration file.')
FROM controllAppItems a
LEFT OUTER JOIN controllTxtItems t ON (a.appName = t.appName AND a.appPage = t.appPage AND a.appSection = t.appSection AND a.txtItem = t.txtItem)
WHERE t.contents is NULL;

UPDATE controllTxtItems td
JOIN controllTxtItems ts ON (ts.appName = td.appName AND ts.appPage = 'addUpgrade' AND ts.appSection = 'main' AND ts.txtItem = 'step0')
SET td.contents = ts.contents
WHERE td.appName = 'portal' AND td.appPage = 'index' AND td.appSection = 'profile' AND td.txtItem = 'email' AND ts.contents NOT LIKE 'ConTroll-Default: %';

DELETE FROM controllTxtItems WHERE appName = 'portal' AND appPage = 'addUpgrade' AND appSection = 'main' AND txtItem = 'step0';
DELETE FROM controllAppItems WHERE appName = 'portal' AND appPage = 'addUpgrade' AND appSection = 'main' AND txtItem = 'step0';

UPDATE controllAppSections SET appPage = 'cart' where appPage = 'addUpgrade';

DELETE FROM controllTxtItems where appPage = 'cart' and txtItem in ('step1', 'step2', 'step3');
DELETE FROM controllAppItems where appPage = 'cart' and txtItem in ('step1', 'step2', 'step3');

INSERT INTO patchLog(id, name) VALUES(xx, 'art, portal, et al');

