/* P53
 * Updates for more items in tracking and recovering terminal transactions.
 * Updates for additional terminal function.
 */

/*
 * transaction fields for terminal payment tracking
 */
ALTER TABLE transaction ADD COLUMN  paymentInfo varchar(4096) DEFAULT NULL;

ALTER TABLE printers MODIFY COLUMN
    codePage enum('PS','HPCL','Dymo4xx','Dymo3xx','Dymo4xxPS','Dymo3xxPS','DymoSEL','Windows-1252','ASCII','7bit','8bit','UTF-8','UTF-16')
    COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Windows-1252';
UPDATE printers SET codePage = 'Dymo4xx' WHERE codePage = 'Dymo4xxPS';
UPDATE printers SET codePage = 'Dymo3xx' WHERE codePage = 'Dymo3xxPS';
UPDATE printers SET codePage = 'Dymo3xx' WHERE codePage = 'DymoSEL';
ALTER TABLE printers MODIFY COLUMN
    codePage enum('PS','HPCL','Dymo4xx','Dymo3xx','Windows-1252','ASCII','7bit','8bit','UTF-8','UTF-16')
    COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Windows-1252';

ALTER TABLE payments RENAME COLUMN  paymentId TO ccPaymentId;
ALTER TABLE transaction RENAME COLUMN paymentId TO ccPaymentId;

/*
 * Add former GoH and Deceased to perinfo, for use by admins only
 */
ALTER TABLE perinfo ADD COLUMN deceased enum('N','Y') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'N' AFTER banned;
ALTER TABLE perinfo ADD COLUMN formerGoH enum('N','Y') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'N' AFTER deceased;
ALTER TABLE perinfoHistory ADD COLUMN deceased enum('N','Y') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'N' AFTER banned;
ALTER TABLE perinfoHistory ADD COLUMN formerGoH enum('N','Y') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'N' AFTER deceased;

DROP TRIGGER IF EXISTS perinfo_update;
DELIMITER ;;
CREATE DEFINER=CURRENT_USER  TRIGGER `perinfo_update` BEFORE UPDATE ON `perinfo` FOR EACH ROW BEGIN
    IF (OLD.id != NEW.id OR OLD.last_name != NEW.last_name OR OLD.first_name != NEW.first_name OR OLD.middle_name != NEW.middle_name
        OR OLD.suffix != NEW.suffix OR OLD.legalName != NEW.legalName OR OLD.pronouns != NEW.pronouns
        OR OLD.email_addr != NEW.email_addr OR OLD.phone != NEW.phone OR OLD.badge_name != NEW.badge_name
        OR OLD.address != NEW.address OR OLD.addr_2 != NEW.addr_2 OR OLD.city != NEW.city OR OLD.state != NEW.state OR OLD.zip != NEW.zip
        OR OLD.country != NEW.country OR OLD.banned != NEW.banned OR OLD.deceased != NEW.deceased OR OLD.formerGoH != NEW.formerGoH
        OR OLD.creation_date != NEW.creation_date OR OLD.change_notes != NEW.change_notes OR OLD.active != NEW.active
        OR OLD.open_notes != NEW.open_notes OR OLD.admin_notes != NEW.admin_notes
        OR OLD.old_perid != NEW.old_perid OR OLD.contact_ok != NEW.contact_ok OR OLD.share_reg_ok != NEW.share_reg_ok
        OR OLD.managedBy != NEW.managedBy OR OLD.managedByNew != NEW.managedByNew OR OLD.updatedBy != NEW.updatedby
        OR OLD.managedReason != NEW.managedReason OR OLD.lastVerified != NEW.lastVerified)
    THEN
        INSERT INTO perinfoHistory(id, last_name, first_name, middle_name, suffix, email_addr, phone, badge_name, legalName, pronouns,
                                   address, addr_2, city, state, zip, country, banned, deceased, formerGoH,
                                   creation_date, update_date, change_notes, active,
                                   open_notes, admin_notes, old_perid, contact_ok, share_reg_ok, managedBy, managedByNew,
                                   managedReason, lastVerified, updatedBy)
        VALUES (OLD.id, OLD.last_name, OLD.first_name, OLD.middle_name, OLD.suffix, OLD.email_addr, OLD.phone, OLD.badge_name, OLD.legalName, OLD.pronouns,
                OLD.address, OLD.addr_2, OLD.city, OLD.state, OLD.zip, OLD.country, OLD.banned, OLD.deceased, OLD.formerGoH,
                OLD.creation_date, OLD.update_date, OLD.change_notes,
                OLD.active, OLD.open_notes, OLD.admin_notes, OLD.old_perid, OLD.contact_ok, OLD.share_reg_ok, OLD.managedBy, OLD.managedByNew,
                OLD.managedReason, OLD.lastVerified, OLD.updatedBy);
    END IF;
END;;
DELIMITER ;

/*
 * Items related to creating control table to sync with membership ribbons to allow purchasing membership types based on con roles
 */

CREATE TABLE `conRoles` (
    `conRole` varchar(16) COLLATE utf8mb4_general_ci NOT NULL,
    `description` varchar(4096) COLLATE utf8mb4_general_ci DEFAULT NULL,
    `memLabel` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
    `sortOrder` int DEFAULT '0',
    `createDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updateDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `updateBy` int DEFAULT NULL,
    `active` enum('Y','N') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Y',
    PRIMARY KEY (`conRole`),
    KEY `conFole_updatBy_fk` (`updateBy`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `memberConRoles` (
    `id` int NOT NULL AUTO_INCREMENT,
    `perid` int DEFAULT NULL,
    `conid` int DEFAULT NULL,
    `conRole` varchar(16) COLLATE utf8mb4_general_ci NOT NULL,
    `assigned` enum('Y','N') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'N',
    `createDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updateDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updateBy` int DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `memberConRolesConRole_fk` (`conRole`),
    KEY `memberConRolesPerinfo_fk` (`perid`),
    CONSTRAINT `memberConRolesConRole_fk` FOREIGN KEY (`conRole`) REFERENCES `conRoles` (`conRole`) ON UPDATE CASCADE,
    CONSTRAINT `memberConRolesPerinfo_fk` FOREIGN KEY (`perid`) REFERENCES `perinfo` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=100 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*
 *  new merge person proc for conRoles addition
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
            first_name = 'Merged', middle_name = 'into', last_name = to_survivePID, email_addr = CONCAT('merged into ', to_survivePID),
            contact_ok = 'N', active='N'
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

/*
 *  new dedup proc to add conRoles addition
 */

DROP PROCEDURE IF EXISTS `deleteDupsIntPol` ;
DELIMITER ;;
CREATE PROCEDURE `deleteDupsIntPol`()
    SQL SECURITY INVOKER
BEGIN
    DROP TABLE IF exists remainPolicy;

    CREATE TEMPORARY TABLE remainPolicy AS
    SELECT perid, conid, policy, MAX(IFNULL(updateDate, createDate)) AS matchDate, COUNT(*) dups
    FROM memberPolicies
    WHERE perid IS NOT NULL
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
    WHERE perid IS NOT NULL
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
END;;
DELIMITER ;

/* update artItemsHistory for prices to 11,2 to match artItems, missed in Patch 50
 */
ALTER TABLE artItemsHistory MODIFY COLUMN min_price decimal(11,2) NOT NULL DEFAULT 0.00;
ALTER TABLE artItemsHistory MODIFY COLUMN sale_price decimal(11,2)  NULL;
ALTER TABLE artItemsHistory MODIFY COLUMN final_price decimal(11,2) NULL;

INSERT INTO patchLog(id, name) VALUES(53, 'moreTerminal');
