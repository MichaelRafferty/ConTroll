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
INSERT INTO controllAppPages(appName,appPage,pageDescription) VALUES
('portal', 'add', 'Add New Member to a Portal Account'),
('portal', 'cart', 'Purchase Memberships/Items for a Portal Account');

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

UPDATE controllAppItems SET txtItem = 'memberships' WHERE appName = 'portal' AND appPage = 'cart' AND appSection = 'main' AND txtItem = 'step4';
UPDATE controllAppItems SET txtItem = 'cart' WHERE appName = 'portal' AND appPage = 'cart' AND appSection = 'main' AND txtItem = 'step4bottom';

ALTER TABLE user DROP CONSTRAINT `fk_user_perid`;
ALTER TABLE user ADD CONSTRAINT `fk_user_perid` FOREIGN KEY (`perid`) REFERENCES `perinfo` (`id`) ON UPDATE CASCADE;

/* for passkeys userid, extend google_sub to 64 chars to hold passkey userid */
ALTER TABLE user MODIFY COLUMN google_sub varchar(64) NOT NULL;

/* fix typo in custom text */
UPDATE controllTxtItems SET contents = REPLACE(contents, 'Numbrer', 'Number') WHERE contents like '%Numbrer%';
UPDATE controllTxtItems SET contents = REPLACE(contents, '<span class="s1">Dear [</span><span class="s1">[',
    '<span class="s1">Dear [[') WHERE contents LIKE '%[</span><span class="s1">%';

/*
 *  fix mergepid to set manager fields to null
 */

DROP PROCEDURE IF EXISTS `mergePerid` ;
DELIMITER ;;
CREATE PROCEDURE `mergePerid`(IN userid INT, IN to_mergePID INT, IN to_survivePID INT, OUT statusmsg TEXT, OUT rollback_log TEXT)
    SQL SECURITY INVOKER
BEGIN
    /* updates the database to change records with to_mergePID to to_survivePID to preserver referential integrity as it merges two perinfo records together
    /* tables with perinfo refs:

            artSales
            atcon_user
            badgeList
            club
            couponKeys
            exhibitors
            memberInterests
            memberPolicies
            memberRoles
            newperson
            payorPlans
            payments
            perinfo
            perinfoIdentities
            reg
            regActions
            transaction
            user

        Cannot do a simple update using ON UPDATE CASCADE as that will create a duplicate key in the perinfo table, so they need to be done separately.
        Can do in any order because the old record will still exist
        */

    DECLARE msg text;
    DECLARE rollback_stmts text;
    DECLARE stmt varchar(8192);
    DECLARE cnt int;
    DECLARE error_string TEXT;
    DECLARE trans_time VARCHAR(64);

    SET trans_time = CURRENT_TIMESTAMP;
    SET msg = '';
    SET rollback_stmts = '';

    START TRANSACTION;

    procBlock: BEGIN
        DECLARE EXIT HANDLER FOR SQLEXCEPTION
            BEGIN
                GET DIAGNOSTICS CONDITION 1
                    error_string = MESSAGE_TEXT;
                SET statusmsg = CONCAT(msg, CHAR(10), 'SQL Exception: ' , error_string, CHAR(10));
                ROLLBACK;
            END;

        IF to_mergePID = to_survivePID THEN
            SET msg = 'to_mergePID cannot be the same as to_survivePID';
            LEAVE procBlock;
        END IF;


        /* check for only one from and to perid and that they exist */
        SET cnt = (SELECT COUNT(*) FROM perinfo WHERE id = to_mergePID);
        IF cnt != 1 THEN
            SET msg = 'to_mergePID not found';
            LEAVE procBlock;
        END IF;

        SET cnt = (SELECT COUNT(*) FROM perinfo WHERE id = to_survivePID);
        IF cnt != 1 THEN
            SET msg = 'to_survivePID not found';
            LEAVE procBlock;
        END IF;

        /* check that from perid is a manager and to perid is managed, as this is a conflict */
        SET cnt = (SELECT COUNT(*) FROM perinfo WHERE managedBy = to_mergePID);
        IF cnt > 0 THEN
            SET cnt = (SELECT COUNT(*) FROM perinfo WHERE (managedBy IS NOT NULL OR managedByNew IS NOT NULL) AND id = to_survivePID);
            IF (cnt > 0) THEN
                SET msg = 'The to_survivePID is managed by someone and the to_mergePID is a manager.  This is a conflict, cannot continue the merge';
                LEAVE procBlock;
            END IF;
        END IF;

        /* artSales */
        SET stmt = (SELECT CONCAT('UPDATE artSales SET perid = ', to_mergePID, ' WHERE ID IN (', group_concat(id SEPARATOR ','), ');')
                    FROM artSales
                    WHERE perid = to_mergePID);

        IF stmt is not null THEN
            UPDATE artSales SET perid = to_survivePID where perid = to_mergePID;
            SET msg = CONCAT(msg, 'artist:  ', CONVERT(ROW_COUNT(), char), CHAR(10));

            SET rollback_stmts = CONCAT(rollback_stmts, stmt, CHAR(10));
        END IF;

        /* atcon_user */
        SET stmt = (SELECT CONCAT('UPDATE atcon_user SET perid = ', to_mergePID, ' WHERE ID IN (', group_concat(id SEPARATOR ','), ');')
                    FROM atcon_user
                    WHERE perid = to_mergePID);

        IF stmt is not null THEN
            UPDATE atcon_user SET perid = to_survivePID where perid = to_mergePID;
            SET msg = CONCAT(msg, 'atcon_user:  ', CONVERT(ROW_COUNT(), char), CHAR(10));

            SET rollback_stmts = CONCAT(rollback_stmts, stmt, CHAR(10));
        END IF;

        /* badgreList */
        SET stmt = (SELECT CONCAT('UPDATE badgeList SET perid = ', to_mergePID, ' WHERE ID IN (', group_concat(id SEPARATOR ','), ');')
                    FROM badgeList
                    WHERE perid = to_mergePID);

        IF stmt is not null THEN
            UPDATE badgeList SET perid = to_survivePID where perid = to_mergePID;
            SET msg = CONCAT(msg, 'badgeList:  ', CONVERT(ROW_COUNT(), char), CHAR(10));

            SET rollback_stmts = CONCAT(rollback_stmts, stmt, CHAR(10));
        END IF;

        /* club */
        SET stmt = (SELECT CONCAT('UPDATE club SET perid = ', to_mergePID, ' WHERE ID IN (', group_concat(id SEPARATOR ','), ');')
                    FROM club
                    WHERE perid = to_mergePID);

        IF stmt is not null THEN
            UPDATE club SET perid = to_survivePID where perid = to_mergePID;
            SET msg = CONCAT(msg, 'club:  ', CONVERT(ROW_COUNT(), char), CHAR(10));

            SET rollback_stmts = CONCAT(rollback_stmts, stmt, CHAR(10));
        END IF;

        /* couponKeys */
        SET stmt = (SELECT CONCAT('UPDATE couponKeys SET perid = ', to_mergePID, ' WHERE ID IN (', group_concat(id SEPARATOR ','), ');')
                    FROM couponKeys
                    WHERE perid = to_mergePID);

        IF stmt is not null THEN
            UPDATE couponKeys SET perid = to_survivePID where perid = to_mergePID;
            SET msg = CONCAT(msg, 'couponKeys:  ', CONVERT(ROW_COUNT(), char), CHAR(10));

            SET rollback_stmts = CONCAT(rollback_stmts, stmt, CHAR(10));
        END IF;

        /* exhibitors */
        SET stmt = (SELECT CONCAT('UPDATE exhibitors SET perid = ', to_mergePID, ' WHERE ID IN (', group_concat(id SEPARATOR ','), ');')
                    FROM exhibitors
                    WHERE perid = to_mergePID);

        IF stmt is not null THEN
            UPDATE exhibitors SET perid = to_survivePID where perid = to_mergePID;
            SET msg = CONCAT(msg, 'exhibitors:  ', CONVERT(ROW_COUNT(), char), CHAR(10));

            SET rollback_stmts = CONCAT(rollback_stmts, stmt, CHAR(10));
        END IF;

        /* memberInterests */
        SET stmt = (SELECT CONCAT('UPDATE memberInterests SET perid = ', to_mergePID, ' WHERE ID IN (', group_concat(id SEPARATOR ','), ');')
                    FROM memberInterests
                    WHERE perid = to_mergePID);

        IF stmt is not null THEN
            UPDATE memberInterests SET perid = to_survivePID where perid = to_mergePID;
            SET msg = CONCAT(msg, 'memberInterests:  ', CONVERT(ROW_COUNT(), char), CHAR(10));

            SET rollback_stmts = CONCAT(rollback_stmts, stmt, CHAR(10));
        END IF;

        /* memberPolicies */
        SET stmt = (SELECT CONCAT('UPDATE memberPolicies SET perid = ', to_mergePID, ' WHERE ID IN (', group_concat(id SEPARATOR ','), ');')
                    FROM memberPolicies
                    WHERE perid = to_mergePID);

        IF stmt is not null THEN
            UPDATE memberPolicies SET perid = to_survivePID where perid = to_mergePID;
            SET msg = CONCAT(msg, 'memberPolicies:  ', CONVERT(ROW_COUNT(), char), CHAR(10));

            SET rollback_stmts = CONCAT(rollback_stmts, stmt, CHAR(10));
        END IF;

        /* memberConRoles */
        SET stmt = (SELECT CONCAT('UPDATE memberConRoles SET perid = ', to_mergePID, ' WHERE ID IN (', group_concat(id SEPARATOR ','), ');')
                    FROM memberConRoles
                    WHERE perid = to_mergePID);

        IF stmt is not null THEN
            UPDATE memberConRoles SET perid = to_survivePID where perid = to_mergePID;
            SET msg = CONCAT(msg, 'memberConRoles:  ', CONVERT(ROW_COUNT(), char), CHAR(10));

            SET rollback_stmts = CONCAT(rollback_stmts, stmt, CHAR(10));
        END IF;


        /* newperson */
        SET stmt = (SELECT CONCAT('UPDATE newperson SET perid = ', to_mergePID, ' WHERE ID IN (', group_concat(id SEPARATOR ','), ');')
                    FROM newperson
                    WHERE perid = to_mergePID);

        IF stmt is not null THEN
            UPDATE newperson SET perid = to_survivePID where perid = to_mergePID;
            SET msg = CONCAT(msg, 'newperson:  ', CONVERT(ROW_COUNT(), char), CHAR(10));

            SET rollback_stmts = CONCAT(rollback_stmts, stmt, CHAR(10));
        END IF;

        /* payorPlans */
        SET stmt = (SELECT CONCAT('UPDATE payorPlans SET perid = ', to_mergePID, ' WHERE ID IN (', group_concat(id SEPARATOR ','), ');')
                    FROM payorPlans
                    WHERE perid = to_mergePID);

        IF stmt is not null THEN
            UPDATE payorPlans SET perid = to_survivePID where perid = to_mergePID;
            SET msg = CONCAT(msg, 'payorPlans:  ', CONVERT(ROW_COUNT(), char), CHAR(10));

            SET rollback_stmts = CONCAT(rollback_stmts, stmt, CHAR(10));
        END IF;

        /* payments */
        SET stmt = (SELECT CONCAT('UPDATE payments SET cashier = ', to_mergePID, ' WHERE ID IN (', group_concat(id SEPARATOR ','), ');')
                    FROM payments
                    WHERE cashier = to_mergePID);

        IF stmt is not null THEN
            UPDATE payments SET cashier = to_survivePID where cashier = to_mergePID;
            SET msg = CONCAT(msg, 'payments:  ', CONVERT(ROW_COUNT(), char), CHAR(10));

            SET rollback_stmts = CONCAT(rollback_stmts, stmt, CHAR(10));
        END IF;

        /* perinfoIdentities */
        SET stmt = (SELECT CONCAT('UPDATE perinfoIdentities SET perid = ', to_mergePID, ' WHERE perid IN (', group_concat(perid SEPARATOR ','), ');')
                    FROM perinfoIdentities
                    WHERE perid = to_mergePID);

        IF stmt is not null THEN
            UPDATE perinfoIdentities SET perid = to_survivePID where perid = to_mergePID;
            SET msg = CONCAT(msg, 'perinfoIdentities:  ', CONVERT(ROW_COUNT(), char), CHAR(10));

            SET rollback_stmts = CONCAT(rollback_stmts, stmt, CHAR(10));
        END IF;

        /* reg */
        SET stmt = (SELECT CONCAT('UPDATE reg SET perid = ', to_mergePID, ' WHERE ID IN (', group_concat(id SEPARATOR ','), ');')
                    FROM reg
                    WHERE perid = to_mergePID);

        IF stmt is not null THEN
            UPDATE reg SET perid = to_survivePID where perid = to_mergePID;
            SET msg = CONCAT(msg, 'reg:  ', CONVERT(ROW_COUNT(), char), CHAR(10));

            SET rollback_stmts = CONCAT(rollback_stmts, stmt, CHAR(10));
        END IF;

        /* regActions */
        SET stmt = (SELECT CONCAT('UPDATE regActions SET userid = ', to_mergePID, ' WHERE ID IN (', group_concat(id SEPARATOR ','), ');')
                    FROM regActions
                    WHERE userid = to_mergePID);

        IF stmt is not null THEN
            UPDATE regActions SET userid = to_survivePID where userid = to_mergePID;
            SET msg = CONCAT(msg, 'regActions:  ', CONVERT(ROW_COUNT(), char), CHAR(10));

            SET rollback_stmts = CONCAT(rollback_stmts, stmt, CHAR(10));
        END IF;

        /* transaction */
        SET stmt = (SELECT CONCAT('UPDATE transaction SET perid = ', to_mergePID, ' WHERE ID IN (', group_concat(id SEPARATOR ','), ');')
                    FROM transaction
                    WHERE perid = to_mergePID);

        IF stmt is not null THEN
            UPDATE transaction SET perid = to_survivePID where perid = to_mergePID;
            SET msg = CONCAT(msg, 'transaction:  ', CONVERT(ROW_COUNT(), char), CHAR(10));

            SET rollback_stmts = CONCAT(rollback_stmts, stmt, CHAR(10));
        END IF;

        /* user */
        SET stmt = (SELECT CONCAT('UPDATE user SET perid = ', to_mergePID, ' WHERE ID IN (', group_concat(id SEPARATOR ','), ');')
                    FROM user
                    WHERE perid = to_mergePID);

        IF stmt is not null THEN
            UPDATE user SET perid = to_survivePID where perid = to_mergePID;
            SET msg = CONCAT(msg, 'transaction:  ', CONVERT(ROW_COUNT(), char), CHAR(10));

            SET rollback_stmts = CONCAT(rollback_stmts, stmt, CHAR(10));
        END IF;

        /* perinfo */

        SET stmt = (SELECT CONCAT('UPDATE perinfo SET first_name = ''', REPLACE(IFNULL(first_name,''), '''', ''''''),
                                  ''', middle_name = ''', REPLACE(IFNULL(middle_name,''), '''', ''''''),
                                  ''', last_name = ''', REPLACE(IFNULL(last_name,''), '''', ''''''),
                                  ''', email_addr = ''', REPLACE(IFNULL(email_addr,''), '''', ''''''),
                                  ''', managedBy = ', managedBy,
                                  ', managedByNew = ', managedByNew,
                                  ', change_notes = CONCAT(''rollback merge'',CHAR(10),''', REPLACE(IFNULL(change_notes,''), '''', ''''''), ''')',
                                  ' where id = ', to_mergePID, ';')
                               AS cmd
                    FROM perinfo
                    WHERE id = to_mergePID);

        SET rollback_stmts = CONCAT(rollback_stmts, stmt, CHAR(10));
        UPDATE perinfo
        SET
            change_notes = CONCAT(trans_time, ':  User ', userid, ' merged into ', to_survivePID, CHAR(10),
                                  'was: first_name: "', first_name, '", middle_name: "', middle_name, '", last_name: "', last_name, '"', CHAR(10),
                                  'rollback_stmts=', CHAR(10),
                                  REPLACE(rollback_stmts, '''', ''''''), '''', char(10)
                           ),
            first_name = 'Merged', middle_name = 'into', last_name = to_survivePID, email_addr = CONCAT('merged into ', to_survivePID),
            contact_ok = 'N', active='N', managedBy = NULL, managedByNew = NULL
        WHERE id = to_mergePID;
        SET msg = CONCAT(msg, 'perinfo: ', to_mergePID, ': ', CONVERT(ROW_COUNT(), char), CHAR(10));

        /* keep only the most recent interests, policies, and conROles */
        DROP TABLE IF EXISTS remainPolicy;

        CREATE TEMPORARY TABLE remainPolicy AS
        SELECT perid, conid, policy, MAX(IFNULL(updateDate, createDate)) AS matchDate, COUNT(*) dups
        FROM memberPolicies
        WHERE perid = to_survivePID
        GROUP BY perid, conid, policy HAVING COUNT(*) > 1;

        DELETE memberPolicies
        FROM memberPolicies
                 JOIN remainPolicy r ON (memberPolicies.perid = r.perid AND memberPolicies.conid = memberPolicies.conid AND memberPolicies.policy = r.policy)
        WHERE r.perid IS NOT NULL AND IFNULL(memberPolicies.updateDate, memberPolicies.createDate) < r.matchDate;

        DROP TABLE IF EXISTS remainPolicy;

        DROP TABLE IF EXISTS remainInterest;

        CREATE TEMPORARY TABLE remainInterest AS
        SELECT perid, conid, interest, MAX(IFNULL(updateDate, createDate)) AS matchDate, COUNT(*) dups
        FROM memberInterests
        WHERE perid = to_survivePID
        GROUP BY perid, conid, interest HAVING COUNT(*) > 1;

        DELETE memberInterests
        FROM memberInterests
                 JOIN remainInterest r ON (memberInterests.perid = r.perid AND memberInterests.conid = memberInterests.conid AND memberInterests.interest = r.interest)
        WHERE r.perid IS NOT NULL AND IFNULL(memberInterests.updateDate, memberInterests.createDate) < r.matchDate;

        DROP TABLE IF EXISTS remainInterest;

        DROP TABLE IF EXISTS remainConRoles;

        CREATE TEMPORARY TABLE remainConRoles AS
        SELECT perid, conid, conRole, MAX(IFNULL(updateDate, createDate)) AS matchDate, COUNT(*) dups
        FROM memberConRoles
        WHERE perid IS NOT NULL
        GROUP BY perid, conid, conRole HAVING COUNT(*) > 1;

        DELETE memberConRoles
        FROM memberConRoles
                 JOIN remainConRoles r ON (memberConRoles.perid = r.perid AND memberConRoles.conid = memberConRoles.conid AND memberConRoles.conRole = r.conRole)
        WHERE r.perid IS NOT NULL AND IFNULL(memberConRoles.updateDate, memberConRoles.createDate) < r.matchDate;

        DROP TABLE IF EXISTS remainConRoles;

        COMMIT;

    END procBlock;

/* SET statusmsg =  msg; */
    SET statusmsg = msg;
    SET rollback_log = CONCAT(trans_time, ':  User ', userid, ' merged ', to_mergePID, ' into ', to_survivePID, CHAR(10), rollback_stmts);

END ;;
DELIMITER ;

UPDATE perinfo SET managedBy = NULL, managedByNew = NULL where first_name = 'Merged' and middle_name = 'into';

// the code added atcon as a category a while ago but the database is out of sync
ALTER TABLE payments MODIFY COLUMN category enum('reg','atcon','artshow','artist','fan','vendor','exhibits','other') DEFAULT NULL;

INSERT INTO patchLog(id, name) VALUES(56, 'art, portal, et al');

