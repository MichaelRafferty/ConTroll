/* p11_reg_history */
/* rename atcon_history to reg_history and add new potential enum values */


ALTER TABLE atcon_history RENAME reg_history;
ALTER TABLE reg_history MODIFY COLUMN action enum('attach','print','notes','transfer','rollover','overpayment','refund') 
	CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;

DROP PROCEDURE IF EXISTS mergePerid;

DELIMITER $$
CREATE PROCEDURE "mergePerid"(IN to_mergePID INT, IN to_survivePID INT, OUT statusmsg TEXT)
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
    DECLARE cnt int;

    procBlock: BEGIN
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

        SET msg = CONCAT('rows updated', CHAR(10 using utf8));

        UPDATE artist SET artist = to_survivePID where artist = to_mergePID;
        SET msg = CONCAT(msg, 'artist:  ', CONVERT(ROW_COUNT(), char), CHAR(10));

        UPDATE artsales SET perid = to_survivePID where perid = to_mergePID;
        SET msg = CONCAT(msg, 'newperson:  ', CONVERT(ROW_COUNT(), char), CHAR(10));

        UPDATE artshow SET perid = to_survivePID where perid = to_mergePID;
        SET msg = CONCAT(msg, 'newperson:  ', CONVERT(ROW_COUNT(), char), CHAR(10));

        UPDATE atcon_user SET perid = to_survivePID where perid = to_mergePID;
        SET msg = CONCAT(msg, 'atcon_user:  ', CONVERT(ROW_COUNT(), char), CHAR(10));

        UPDATE badgeList SET perid = to_survivePID where perid = to_mergePID;
        SET msg = CONCAT(msg, 'badgeList:  ', CONVERT(ROW_COUNT(), char), CHAR(10));

        UPDATE club SET perid = to_survivePID where perid = to_mergePID;
        SET msg = CONCAT(msg, 'club:  ', CONVERT(ROW_COUNT(), char), CHAR(10));

        UPDATE couponKeys SET perid = to_survivePID where perid = to_mergePID;
        SET msg = CONCAT(msg, 'couponKeys:  ', CONVERT(ROW_COUNT(), char), CHAR(10));

        UPDATE newperson SET perid = to_survivePID where perid = to_mergePID;
        SET msg = CONCAT(msg, 'newperson:  ', CONVERT(ROW_COUNT(), char), CHAR(10));

        UPDATE payments SET cashier = to_survivePID where cashier = to_mergePID;
        SET msg = CONCAT(msg, 'club:  ', CONVERT(ROW_COUNT(), char), CHAR(10));

        UPDATE reg SET perid = to_survivePID where perid = to_mergePID;
        SET msg = CONCAT(msg, 'reg:  ', CONVERT(ROW_COUNT(), char), CHAR(10));

        UPDATE reg_history SET userid = to_survivePID where userid = to_mergePID;
        SET msg = CONCAT(msg, 'reg_history:  ', CONVERT(ROW_COUNT(), char), CHAR(10));

        UPDATE transaction SET perid = to_survivePID where perid = to_mergePID;
        SET msg = CONCAT(msg, 'transaction:  ', CONVERT(ROW_COUNT(), char), CHAR(10));

        DELETE FROM perinfo WHERE id = to_mergePID;
        SET msg = CONCAT(msg, 'Deletion of to_mergePID: ', CONVERT(ROW_COUNT(), char), CHAR(10));

    END procBlock;

    SET statusmsg =  msg;
END$$
DELIMITER ;

INSERT INTO patchLog(id, name) values(11, 'reg_history');
