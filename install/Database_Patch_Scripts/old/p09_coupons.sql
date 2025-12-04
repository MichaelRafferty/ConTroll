/* P9 - Coupons */
CREATE TABLE coupon (
	id int NOT NULL AUTO_INCREMENT,
	conid int NOT NULL, /* ref conlist */
	oneUse int NOT NULL DEFAULT 0,
	code varchar(16) DEFAULT NULL,
	name varchar(32) DEFAULT NULL,
	startDate datetime NOT NULL DEFAULT '1900-01-01',
	endDate datetime NOT NULL DEFAULT '2100-12-31',
	couponType enum('$off', '%off', '$mem', '%mem', 'price') NOT NULL DEFAULT '$mem',
	discount decimal(8,2) NOT NULL DEFAULT 0,
	memId int DEFAUlT NULL, /* ref memList */
	minMemberships int DEFAULT NULL, /* min to buy to enable discount */
	maxMemberships int DEFAULT NULL, /* max number to discount */
	limitMemberships int DEFAULT NULL, /* max number of memberships in cart (of type memId) */
	minTransaction decimal(8,2) DEFAULT NULL, /* min size of undiscounted cart to apply discount */
	maxTransaction decimal(8,2) DEFAULT NULL, /* max size of cart discounted by % off cart type discounts */
	maxRedemption int DEFAULT NULL,
	createTS timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	createBy int DEFAULT NULL, /* ref to user table */
	updateTS timestamp DEFAULT NULL,
	updateBy int DEFAULT NULL, /* ref to user table */
	PRIMARY KEY (id),
	CONSTRAINT `coupon_conid_fk` FOREIGN KEY (conid) REFERENCES conlist(id) ON UPDATE CASCADE,
	CONSTRAINT `coupon_memid_fk` FOREIGN KEY (memId) REFERENCES memList(id) ON UPDATE CASCADE,
	CONSTRAINT `coupon_createby_fk` FOREIGN KEY (createBy) REFERENCES user(id) ON UPDATE CASCADE,
	CONSTRAINT `coupon_updateby_fk` FOREIGN KEY (updateBy) REFERENCES user(id) ON UPDATE CASCADE
);


CREATE TABLE couponKeys (
	id int NOT NULL AUTO_INCREMENT,
	couponId int NOT NULL,
	guid varchar(36)  NOT NULL,
	perid int DEFAULT NULL,
	notes varchar(256) DEFAULT NULL,
	createTS timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	createBy int DEFAULT NULL,
	useTS timestamp DEFAULT NULL,
	usedBy int DEFAULT NULL,
	PRIMARY KEY (id),
	CONSTRAINT `couponkeys_couponid_fk` FOREIGN KEY (couponId) REFERENCES coupon(id) ON UPDATE CASCADE ON DELETE CASCADE,
	CONSTRAINT `couponkeys_usedby_fk` FOREIGN KEY (usedby) REFERENCES transaction(id) ON UPDATE CASCADE,
	CONSTRAINT `couponkeys_createby_fk` FOREIGN KEY (createBy) REFERENCES user(id) ON UPDATE CASCADE,
	CONSTRAINT `couponkeys_perid_fk` FOREIGN KEY (perid) REFERENCES perinfo(id) ON UPDATE CASCADE ON DELETE CASCADE
);

ALTER TABLE reg ADD COLUMN coupon int default null;
ALTER TABLE reg ADD COLUMN couponDiscount decimal(8,2) default 0 AFTER price;
ALTER TABLE reg MODIFY COLUMN price decimal(8,2) NOT NULL;
ALTER TABLE reg MODIFY COLUMN paid decimal(8,2) default 0;
ALTER TABLE reg DROP CONSTRAINT reg_pickup_trans_fk;
ALTER TABLE reg DROP COLUMN pickup_trans;

ALTER TABLE reg ADD CONSTRAINT reg_coupon_fk FOREIGN KEY (coupon) REFERENCES coupon(id) ON UPDATE CASCADE;

ALTER TABLE transaction ADD COLUMN coupon int default null; 
ALTER TABLE transaction ADD COLUMN couponDiscount decimal(8,2) default 0 AFTER price;
ALTER TABLE transaction MODIFY COLUMN price decimal(8,2) DEFAULT NULL;
ALTER TABLE transaction MODIFY COLUMN paid decimal(8,2) DEFAULT NULL;
ALTER TABLE transaction MODIFY COLUMN tax decimal(8,2) DEFAULT NULL;
ALTER TABLE transaction MODIFY COLUMN withtax decimal(8,2) DEFAULT NULL;

ALTER TABLE transaction ADD CONSTRAINT transactions_coupon_fk FOREIGN KEY (coupon) REFERENCES coupon(id) ON UPDATE CASCADE;

AlTER TABLE memList MODIFY COLUMN price decimal(8,2) NOT NULL;

ALTER TABLE payments MODIFY COLUMN amount decimal(8,2) DEFAULT NULL;

ALTER TABLE user ADD COLUMN perid int AFTER id;
ALTER TABLE user ADD CONSTRAINT fk_user_perid FOREIGN KEY(perid) REFERENCES perinfo(id);

/* NOTE: Should price  = discount + paid for both reg and trans */

CREATE OR REPLACE VIEW couponMemberships AS
	SELECT r.id AS regId, r.conid, r.perid, r.price, r.couponDiscount, r.paid, c.id as couponId,
		c.code, c.name, c.couponType, c.discount, c.oneUse, k.guid, k.useTS
	FROM reg r
	JOIN coupon c ON (c.id = r.coupon)
	LEFT OUTER JOIN couponKeys k ON (k.usedBy = r.create_trans);
    
CREATE OR REPLACE VIEW couponUsage AS
	SELECT t.conid, t.id as transId, c.id as CouponId, t.perid, t.price, t.couponDiscount, t.paid,
		c.code, c.name, c.couponType, c.discount, c.oneUse, k.guid, k.useTS
	FROM transaction t
	JOIN coupon c ON (c.id = t.coupon)
	LEFT OUTER JOIN couponKeys k ON (k.usedBy = t.id);

DROP function IF EXISTS uuid_v4s;

DELIMITER $$
CREATE FUNCTION uuid_v4s()
    RETURNS CHAR(36)
    NOT DETERMINISTIC
    NO SQL
BEGIN
    -- 1th and 2nd block are made of 6 random bytes
    SET @h1 = HEX(RANDOM_BYTES(4));
    SET @h2 = HEX(RANDOM_BYTES(2));

    -- 3th block will start with a 4 indicating the version, remaining is random
    SET @h3 = SUBSTR(HEX(RANDOM_BYTES(2)), 2, 3);

    -- 4th block first nibble can only be 8, 9 A or B, remaining is random
    SET @h4 = CONCAT(HEX(FLOOR(ASCII(RANDOM_BYTES(1)) / 64)+8),
                SUBSTR(HEX(RANDOM_BYTES(2)), 2, 3));

    -- 5th block is made of 6 random bytes
    SET @h5 = HEX(RANDOM_BYTES(6));

    -- Build the complete UUID
    RETURN LOWER(CONCAT(
        @h1, '-', @h2, '-4', @h3, '-', @h4, '-', @h5
    ));
END$$

DELIMITER ;

INSERT INTO auth(name, page, display) values ('coupon', 'Y', 'Coupon');
INSERT INTO patchLog(id, name) values(9, 'coupons');
