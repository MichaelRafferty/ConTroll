/*
 * Post the 1.1 release cleanup and requested immediate features patches
 */

INSERT INTO auth(id, name, page, display, sortOrder)
VALUES (8,'lookup', 'Y', 'Reg Lookup', 65);

/*
 * Fix merge stored proc
 */

DROP PROCEDURE IF EXISTS `mergePerid` ;
DELIMITER ;;
CREATE PROCEDURE mergePerid(IN userid INT, IN to_mergePID INT, IN to_survivePID INT, OUT statusmsg TEXT, OUT rollback_log TEXT)
    SQL SECURITY INVOKER
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
                                  ''', change_notes = CONCAT(''rollback merge'',CHAR(10),''', REPLACE(IFNULL(change_notes,''), '''', ''''''), ''')',
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
            first_name = 'Merged', middle_name = 'into', last_name = to_survivePID, email_addr = CONCAT('merged into ', to_survivePID)
        WHERE id = to_mergePID;
        SET msg = CONCAT(msg, 'perinfo: ', to_mergePID, ': ', CONVERT(ROW_COUNT(), char), CHAR(10));

        COMMIT;

    END procBlock;

/* SET statusmsg =  msg; */
    SET statusmsg = msg;
    SET rollback_log = CONCAT(trans_time, ':  User ', userid, ' merged ', to_mergePID, ' into ', to_survivePID, CHAR(10), rollback_stmts);

END ;;
DELIMITER ;

INSERT INTO patchLog(id, name) VALUES(42, 'Post 1.1 Release Cleanup');
