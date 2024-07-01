/*
 * changes needed to make sales take taxes
 */
ALTER TABLE payments ADD COLUMN pretax decimal(8,2) DEFAULT NULL AFTER source;
ALTER TABLE payments ADD COLUMN tax decimal(8,2) DEFAULT 0.00 AFTER pretax;

UPDATE payments SET tax = 0, pretax = amount;

INSERT INTO patchLog(id, name) values(29, 'artsales_payments');
