/*
 * changes needed to make sales work and track the things we want to track
 *
 * payments:
 */
ALTER TABLE payments ADD COLUMN userPerid int DEFAULT NULL AFTER userid;

/*
 *  create the new perinfo foreign key value to move the fk from user to perinfo
 */
UPDATE payments
    JOIN user U ON (payments.userid = U.id)
    JOIN perinfo I ON (I.id = U.perid)
SET cashier = I.id
WHERE I.id IS NOT NULL AND cashier IS NULL;

ALTER TABLE payments DROP CONSTRAINT `payments_userid_fk`;
ALTER TABLE payments DROP COLUMN userid;

/*
 * Coupon Tables
 */
ALTER TABLE coupon DROP CONSTRAINT coupon_createby_fk;
ALTER TABLE coupon DROP CONSTRAINT coupon_updateby_fk;
ALTER TABLE couponKeys DROP CONSTRAINT couponkeys_createby_fk;

UPDATE coupon
JOIN user U
SET createBy = U.perid
WHERE createBy = U.id;

UPDATE coupon
JOIN user U
SET updateBy = U.perid
WHERE updateBy = U.id;

UPDATE couponKeys
JOIN user U
SET createBy = U.perid
WHERE createBy = U.id;

ALTER TABLE coupon ADD CONSTRAINT `coupon_createby_fk` FOREIGN KEY (`createBy`) REFERENCES `perinfo` (`id`) ON UPDATE CASCADE;
ALTER TABLE coupon ADD CONSTRAINT `coupon_updateby_fk` FOREIGN KEY (`updateBy`) REFERENCES `perinfo` (`id`) ON UPDATE CASCADE;
ALTER TABLE couponKeys ADD CONSTRAINT `couponkeys_createby_fk` FOREIGN KEY (`createBy`) REFERENCES `perinfo` (`id`) ON UPDATE CASCADE;

/*
 * transaction?
 */

UPDATE transaction
    JOIN user U
SET userid = U.perid
WHERE userid = U.id;

UPDATE transaction SET userid = NULL WHERE userid = 0;

ALTER TABLE transaction ADD CONSTRAINT `transaction_userid_fk` FOREIGN KEY (`userid`) REFERENCES `perinfo` (`id`) ON UPDATE CASCADE;

INSERT INTO patchLog(id, name) values(26, 'userid fk to perid fk');
