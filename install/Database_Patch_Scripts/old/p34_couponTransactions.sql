/*
 * updates to handle transaction changes for coupon split
 */

ALTER TABLE transaction RENAME COLUMN couponDiscount TO couponDiscountCart;
ALTER TABLE transaction ADD COLUMN couponDiscountReg decimal(8,2) DEFAULT 0 AFTER couponDiscountCart;

ALTER TABLE payments MODIFY COLUMN type enum('credit','cash','check','discount','refund','other','coupon')
    CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL;

DROP VIEW IF EXISTS couponUsage;

CREATE VIEW couponUsage AS
SELECT
    t.conid AS conid,
    t.id AS transId,
    c.id AS CouponId,
    t.perid AS perid,
    t.price AS price,
    t.couponDiscountReg AS couponDiscountReg,
    t.couponDiscountCart AS couponDiscountCart,
    t.couponDiscountReg + t.couponDiscountCart AS couponDiscount,
    t.paid AS paid,
    t.type AS type,
    t.complete_date ,
    c.code AS code,
    c.name AS name,
    c.couponType AS couponType,
    c.discount AS discount,
    c.oneUse AS oneUse,
    k.guid AS guid,
    k.useTS AS useTS
FROM transaction t
JOIN coupon c ON c.id = t.coupon
LEFT OUTER JOIN couponKeys k ON k.usedBy = t.id;

INSERT INTO patchLog(id, name) values(34, 'couponTransactions');
