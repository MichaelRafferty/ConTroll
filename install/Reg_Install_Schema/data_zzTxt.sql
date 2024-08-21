-- add initialize default controllTxtItems elements
DELETE FROM controllTxtItems WHERE contents LIKE 'Controll-Default: %';
INSERT INTO controllTxtItems(appName, appPage, appSection, txtItem, contents)
SELECT a.appName, a.appPage, a.appSection, a.txtItem, CONCAT('Controll-Default: This is ', a.appName, '-', a.appPage, '-', a.appSection, '-', a.txtItem,
                                                             '<br/>Custom HTML that can replaced with a custom value in the Controll Admin App under Edit Custom Text.<br/>',
                                                             ' Default text can be suppressed in the configuration file.')
FROM controllAppItems a
         LEFT OUTER JOIN controllTxtItems t on (a.appName = t.appName AND a.appPage = t.appPage AND a.appSection = t.appSection and a.txtItem = t.txtItem)
WHERE t.contents is NULL;
