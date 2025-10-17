/*
 * Addition multiple tax fields to where tax is noe
 */

ALTER TABLE payments MODIFY COLUMN tax decimal(8,2) DEFAULT '0.00' COMMENT 'Sum of the tax fields, or the total tax if they are all null';
ALTER TABLE payments ADD COLUMN tax1 decimal(8,2) DEFAULT NULL COMMENT 'Primary Sales Tax field, defined in config file' AFTER amount;
ALTER TABLE payments ADD COLUMN tax2 decimal(8,2) DEFAULT NULL COMMENT 'Additional Tax field, defined in config file' AFTER tax1;
ALTER TABLE payments ADD COLUMN tax3 decimal(8,2) DEFAULT NULL COMMENT 'Additional Tax field, defined in config file' AFTER tax2;
ALTER TABLE payments ADD COLUMN tax4 decimal(8,2) DEFAULT NULL COMMENT 'Additional Tax field, defined in config file' AFTER tax3;
ALTER TABLE payments ADD COLUMN tax5 decimal(8,2) DEFAULT NULL COMMENT 'Additional Tax field, defined in config file' AFTER tax4;

ALTER TABLE transaction MODIFY COLUMN tax decimal(8,2) DEFAULT '0.00' COMMENT 'Sum of the tax fields, or the total tax if they are all null';
ALTER TABLE transaction ADD COLUMN tax1 decimal(8,2) DEFAULT NULL COMMENT 'Primary Sales Tax field, defined in config file' AFTER tax;
ALTER TABLE transaction ADD COLUMN tax2 decimal(8,2) DEFAULT NULL COMMENT 'Additional Tax field, defined in config file' AFTER tax1;
ALTER TABLE transaction ADD COLUMN tax3 decimal(8,2) DEFAULT NULL COMMENT 'Additional Tax field, defined in config file' AFTER tax2;
ALTER TABLE transaction ADD COLUMN tax4 decimal(8,2) DEFAULT NULL COMMENT 'Additional Tax field, defined in config file' AFTER tax3;
ALTER TABLE transaction ADD COLUMN tax5 decimal(8,2) DEFAULT NULL COMMENT 'Additional Tax field, defined in config file' AFTER tax4;

DROP TABLE IF EXISTS taxList;
CREATE TABLE  taxList (
    conid int NOT NULL,
    taxField enum('tax1','tax2','tax3','tax4','tax5') NOT NULL,
    description varchar(64) DEFAULT '',
    rate decimal(8,6) NOT NULL DEFAULT 0,
    active enum('N', 'Y') NOT NULL DEFAULT 'N',
    lastUpdate timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updatedBy int DEFAULT NULL,
    PRIMARY KEY (conid, taxField)
);

ALTER TABLE taxList ADD CONSTRAINT taxC_conid FOREIGN KEY(conid) REFERENCES conlist(id) ON UPDATE CASCADE;
ALTER TABLE taxList ADD CONSTRAINT taxC_perinfo FOREIGN KEY(updatedBy) REFERENCES perinfo(id) ON UPDATE CASCADE;

INSERT INTO patchLog(id, name) VALUES(xx, 'taxes et al');

