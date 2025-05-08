/*
 * New Changes for Square Terminals
 */

/*
 * Credentials
 */
CREATE TABLE terminals (
    name varchar(32) NOT NULL,
    productType varchar(32) NOT NULL,
    locationId varchar(16) NOT NULL,
    squareId varchar(32) NULL,
    deviceId varchar(32) NULL,
    squareCode varchar(16) NULL,
    pairBy datetime NULL,
    pairedAt datetime NULL,
    createDate datetime NOT NULL,
    status varchar(32) NOT NULL,
    statusChanged datetime NOT NULL,
    currentOrder varchar(64) NULL,
    currentPayment varchar(64) NULL,
    currentOperator int NULL,
    controllStatus varchar(32) NULL,
    controllStatusChanged datetime NULL,
    PRIMARY KEY (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/* change to deal with taxable items in the 'membership category */
ALTER TABLE memCategories ADD COLUMN taxable enum('Y','N') NOT NULL DEFAULT 'N' AFTER variablePrice;

INSERT INTO memCategories(memCategory, notes, onlyOne, standAlone, variablePrice, taxable, badgeLabel, active, sortorder)
VALUES ('addonTaxable', 'Req: Taxable add-on'' to memberships', 'N', 'Y', 'N', 'Y', 'X', 'Y', 75 );

/* changes for splitting orders from payments for square, leading to terminal usage */
ALTER TABLE transaction ADD COLUMN orderId varchar(64) DEFAULT NULL AFTER coupon;
ALTER TABLE transaction ADD COLUMN orderDate datetime DEFAULT NULL AFTER orderId;

/* changes for mail in fee receipt ability */
ALTER TABLE exhibitorYears ADD COLUMN mailinFeePaidAmount decimal(8, 2) DEFAULT NULL AFTER mailin;
ALTER TABLE exhibitorYears ADD COLUMN mailinFeeTransaction int DEFAULT NULL AFTER mailinFeePaidAmount;
ALTER TABLE exhibitorYears ADD FOREIGN KEY ey_mailintrans(mailinFeeTransaction) REFERENCES transaction(id) ON UPDATE CASCADE;

UPDATE controllTxtItems SET contents = REPLACE(contents, "Not to buy", "Note to buy")
WHERE appName = 'portal' AND appPage = 'addUpgrade' AND appSection = 'main' AND txtItem = 'step4';

/* finally drop memGroup */
DROP VIEW IF EXISTS memLabel;
CREATE ALGORITHM=UNDEFINED SQL SECURITY INVOKER VIEW memLabel AS
SELECT m.id AS id,m.conid AS conid,m.sort_order AS sort_order,m.memCategory AS memCategory,m.memType AS memType,
       m.memAge AS memAge,m.label AS shortname,concat(m.label,' [',a.label,']') AS label,m.notes AS notes,
       m.price AS price,m.startdate AS startdate,m.enddate AS enddate,
       m.atcon AS atcon,m.online AS `online`,m.glNum AS glNum, m.glLabel AS glLabel, c.taxable AS taxable
FROM memList m
JOIN ageList a ON (m.memAge = a.ageType and m.conid = a.conid)
JOIN memCategories c ON (m.memCategory = c.memCategory);

/* add payment Id to payments */
ALTER TABLE payments ADD COLUMN paymentId varchar(64) DEFAULT NULL;
ALTER TABLE payments MODIFY COLUMN  type enum('credit','terminal','card','cash','check','discount','refund','other','coupon') DEFAULT NULL;



INSERT INTO patchLog(id, name) VALUES(45, 'Square Terminals');