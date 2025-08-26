/* P53
 * Updates for more items in tracking and recovering terminal transactions.
 * Updates for additional terminal function.
 */

/*
 * transaction fields for terminal payment tracking
 */
ALTER TABLE transaction ADD COLUMN  paymentInfo varchar(4096) DEFAULT NULL;

INSERT INTO patchLog(id, name) VALUES(xx, 'moreTerminal');
