/*
 * add conid to rules table entries (Note you must fix this to use your current conid for the XXX
 */


INSERT INTO `controllAppItems` VALUES
('portal','addUpgrade','main','step4bottom','Custom Text for just below step 4 (cart) and ahead of the HR (rule line)');
UPDATE controllAppItems
    SET txtItemDescription = 'Custom Text for just after the Step 2 header'
    WHERE appName = 'portal' AND appPage = 'addUpgrade' AND appsection = 'main' AND txtItem = 'step2';
UPDATE controllAppItems
    SET txtItemDescription = 'Custom Text for just after the Step 3 header'
    WHERE appName = 'portal' AND appPage = 'addUpgrade' AND appsection = 'main' AND txtItem = 'step3';
UPDATE controllAppItems
    SET txtItemDescription = 'Custom Text for just after the Step 4 header'
    WHERE appName = 'portal' AND appPage = 'addUpgrade' AND appsection = 'main' AND txtItem = 'step4';

INSERT INTO patchLog(id, name) VALUES(39, 'add portal text item');