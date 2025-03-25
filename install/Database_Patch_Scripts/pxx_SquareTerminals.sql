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
    PRIMARY KEY (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



INSERT INTO patchLog(id, name) VALUES(xx, 'Square Terminals');