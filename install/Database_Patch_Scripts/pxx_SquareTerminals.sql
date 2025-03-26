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

ALTER TABLE memCategories ADD COLUMN taxable enum('Y','N') NOT NULL DEFAULT 'N' AFTER variablePrice;

INSERT INTO memCategories(memCategory, notes, onlyOne, standAlone, variablePrice, taxable, badgeLabel, active, sortorder)
VALUES ('addonTaxable', 'Req: Taxable add-on'' to memberships', 'N', 'Y', 'N', 'Y', 'X', 'Y', 75 );

ALTER TABLE transaction ADD COLUMN orderId varchar(64) DEFAULT NULL AFTER coupon;
ALTER TABLE transaction ADD COLUMN orderDate datetime DEFAULT NULL AFTER orderId;

INSERT INTO patchLog(id, name) VALUES(xx, 'Square Terminals');