/*
 * P60 - 2.4
 *
 */

/*
 * periodic cleanup of interests and policies
 */
CALL deleteDupsIntPol();

/*
 * Add rounding to transaction for cash rounding
 */
ALTER TABLE transaction ADD COLUMN rounding decimal(8,2) AFTER withtax;

/*
 * new custom text items
 */

INSERT INTO `controllAppSections` VALUES
    ();

INSERT INTO `controllAppItems` VALUES
    ();

INSERT INTO `controllTxtItems` VALUES
    ();

/*
 * make new no show defaults for ones without a default value
 */
INSERT INTO controllTxtItems(appName, appPage, appSection, txtItem, contents)
SELECT a.appName, a.appPage, a.appSection, a.txtItem, CONCAT('Controll-Default: This is ', a.appName, '-', a.appPage, '-', a.appSection, '-', a.txtItem,
     '<br/>Custom HTML that can replaced with a custom value in the Controll Admin App under Edit Custom Text.<br/>',
     ' Default text can be suppressed in the configuration file.')
FROM controllAppItems a
LEFT OUTER JOIN controllTxtItems t on (a.appName = t.appName AND a.appPage = t.appPage AND a.appSection = t.appSection and a.txtItem = t.txtItem)
WHERE t.contents is NULL;

UPDATE controllAppItems SET txtItemDescription = 'Custom Text for the html enter your item registration reminder email'
WHERE appName = 'exhibitor' AND appPage = 'emails' AND appSection = 'invReminder' and txtItem = 'html';


INSERT INTO patchLog(id, name) VALUES(x60, 'Release 2.4');
