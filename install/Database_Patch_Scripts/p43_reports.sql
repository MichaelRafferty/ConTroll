/*
 * New Permissions and changes for general reports
 */

INSERT INTO auth(id, name, page, display, sortOrder)
VALUES (20,'gen_rpts', 'N', 'N', 135);

DELETE FROM user_auth WHERE auth_id IN (17,18);
DELETE FROM auth WHERE id IN (17,18);

/*
 * items for paid by others payments
 */

INSERT INTO controllAppItems(appName, appPage, appSection, txtItem, txtItemDescription) VALUES
('portal', 'portal','main','purchOthers',
 'Custom Text for You have unpaid purchases for you by others section');

UPDATE controllAppItems SET appSection = 'paymentPlans' WHERE appSection = 'paymentPlamns';

INSERT INTO patchLog(id, name) VALUES(43, 'General Reports');