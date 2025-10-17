/*
 * changes needed to make sales take taxes
 */
ALTER TABLE payments ADD COLUMN pretax decimal(8,2) DEFAULT NULL AFTER source;
ALTER TABLE payments ADD COLUMN tax decimal(8,2) DEFAULT 0.00 AFTER pretax;

UPDATE payments SET tax = 0, pretax = amount WHERE IFNULL(tax, 0) = 0 OR pretax is NULL;
UPDATE transaction SET tax = 0 where tax is NULL;
update transaction SET withtax = price + tax;

INSERT INTO patchLog(id, name) values(29, 'artsales_payments');
