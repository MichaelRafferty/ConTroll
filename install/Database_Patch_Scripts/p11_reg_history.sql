/* p11_reg_history */
/* rename atcon_history to reg_history and add new potential enum values */


ALTER TABLE atcon_history RENAME reg_history;
ALTER TABLE reg_history MODIFY COLUMN action enum('attach','print','notes','transfer','rollover','overpayment','refund') 
	CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;

DROP PROCEDURE IF EXISTS mergePerid;

DELIMITER $$
CREATE PROCEDURE mergePerid (IN userid INT, IN to_mergePID INT, IN to_survivePID INT, OUT statusmsg TEXT, OUT rollback_log TEXT)
BEGIN
    /* updates the database to change records with to_mergePID to to_survivePID to preserver referential integrity as it merges two perinfo records together
    /* tables with perinfo refs:
        artist
        artsales
        artshow
        atcon_user
        badgeList
        club
        couponkeys
        newperson
        payments
        reg
        reg_history
        transaction

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

        /* artist */
        SET stmt = (SELECT CONCAT('UPDATE artist SET artist = ', to_mergePID, ' WHERE ID IN (', group_concat(id SEPARATOR ','), ');')
                    FROM artist
                    WHERE artist = to_mergePID);

        IF stmt is not null THEN
            UPDATE artist SET artist = to_survivePID where artist = to_mergePID;
            SET msg = CONCAT(msg, 'artist:  ', CONVERT(ROW_COUNT(), char), CHAR(10));

            SET rollback_stmts = CONCAT(rollback_stmts, stmt, CHAR(10));
        END IF;

        /* artsales */
        SET stmt = (SELECT CONCAT('UPDATE artsales SET perid = ', to_mergePID, ' WHERE ID IN (', group_concat(id SEPARATOR ','), ');')
                    FROM artsales
                    WHERE perid = to_mergePID);

        IF stmt is not null THEN
            UPDATE artsales SET perid = to_survivePID where perid = to_mergePID;
            SET msg = CONCAT(msg, 'artsales:  ', CONVERT(ROW_COUNT(), char), CHAR(10));

            SET rollback_stmts = CONCAT(rollback_stmts, stmt, CHAR(10));
        END IF;

        /* artshow */
        SET stmt = (SELECT CONCAT('UPDATE artshow SET perid = ', to_mergePID, ' WHERE ID IN (', group_concat(id SEPARATOR ','), ');')
                    FROM artshow
                    WHERE perid = to_mergePID);

        IF stmt is not null THEN
            UPDATE artshow SET perid = to_survivePID where perid = to_mergePID;
            SET msg = CONCAT(msg, 'artshow:  ', CONVERT(ROW_COUNT(), char), CHAR(10));

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

        /* newperson */
        SET stmt = (SELECT CONCAT('UPDATE newperson SET perid = ', to_mergePID, ' WHERE ID IN (', group_concat(id SEPARATOR ','), ');')
                    FROM newperson
                    WHERE perid = to_mergePID);

        IF stmt is not null THEN
            UPDATE newperson SET perid = to_survivePID where perid = to_mergePID;
            SET msg = CONCAT(msg, 'newperson:  ', CONVERT(ROW_COUNT(), char), CHAR(10));

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

        /* reg */
        SET stmt = (SELECT CONCAT('UPDATE reg SET perid = ', to_mergePID, ' WHERE ID IN (', group_concat(id SEPARATOR ','), ');')
                    FROM reg
                    WHERE perid = to_mergePID);

        IF stmt is not null THEN
            UPDATE reg SET perid = to_survivePID where perid = to_mergePID;
            SET msg = CONCAT(msg, 'reg:  ', CONVERT(ROW_COUNT(), char), CHAR(10));

            SET rollback_stmts = CONCAT(rollback_stmts, stmt, CHAR(10));
        END IF;

        /* reg_history */
        SET stmt = (SELECT CONCAT('UPDATE reg_history SET userid = ', to_mergePID, ' WHERE ID IN (', group_concat(id SEPARATOR ','), ');')
                    FROM reg_history
                    WHERE userid = to_mergePID);

        IF stmt is not null THEN
            UPDATE reg_history SET userid = to_survivePID where userid = to_mergePID;
            SET msg = CONCAT(msg, 'reg_history:  ', CONVERT(ROW_COUNT(), char), CHAR(10));

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

        /* perinfo */

        SET stmt = (SELECT CONCAT('UPDATE perinfo SET first_name = ''', REPLACE(IFNULL(first_name,''), '''', ''''''),
                                  ''', middle_name = ''', REPLACE(IFNULL(middle_name,''), '''', ''''''),
                                  ''', last_name = ''', REPLACE(IFNULL(last_name,''), '''', ''''''),
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
            first_name = 'Merged', middle_name = 'into', last_name = to_survivePID
        WHERE id = to_mergePID;
        SET msg = CONCAT(msg, 'perinfo: ', to_mergePID, ': ', CONVERT(ROW_COUNT(), char), CHAR(10));

        COMMIT;

    END procBlock;

/* SET statusmsg =  msg; */
    SET statusmsg = msg;
    SET rollback_log = CONCAT(trans_time, ':  User ', userid, ' merged ', to_mergePID, ' into ', to_survivePID, CHAR(10), rollback_stmts);

END$$
DELIMITER ;

INSERT INTO patchLog(id, name) values(11, 'reg_history');
