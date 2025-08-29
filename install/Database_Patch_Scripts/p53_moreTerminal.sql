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

INSERT INTO patchLog(id, name) VALUES(xx, 'moreTerminal');
