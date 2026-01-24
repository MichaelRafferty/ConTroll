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
    taxField enum('tax1','tax2','tax3','tax4','tax5') NOT NULL COMMENT 'Required name of field, not editable by users',
    label varchar(64) DEFAULT '' COMMENT 'Receipt Label',
    rate decimal(8,6) NOT NULL DEFAULT 0 COMMENT 'Tax Rate in percent',
    active enum('N', 'Y') NOT NULL DEFAULT 'N' COMMENT 'Allows for tax law that disables a tax on a sunset date',
    glNum varchar(16) DEFAULT NULL COMMENT 'General Ledger Account Number for Accounting',
    glLabel varchar(64) DEFAULT NULL COMMENT 'General Ledger Account Name for Accounting (For reference, only glNum is used)',
    lastUpdate timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updatedBy int DEFAULT NULL COMMENT 'perid of signed in user that made change, null if done directly in SQL',
    PRIMARY KEY (conid, taxField)
);

ALTER TABLE taxList ADD CONSTRAINT taxC_conid FOREIGN KEY(conid) REFERENCES conlist(id) ON UPDATE CASCADE;
ALTER TABLE taxList ADD CONSTRAINT taxC_perinfo FOREIGN KEY(updatedBy) REFERENCES perinfo(id) ON UPDATE CASCADE;

/* add new managed memCategory for membership holders that must be and remain managed */
INSERT INTO memCategories (memCategory, notes, onlyOne, standAlone, variablePrice, taxable, sortorder, active, badgeLabel)
VALUES ('managed', 'Req: disble disassociate in portal and added by manager in portal',
        'Y', 'Y', 'N', 'N', 15,'Y', 'Attending');

/* fix spelling transfered to transferred */
ALTER TABLE reg MODIFY COLUMN status enum('unpaid','plan','paid','cancelled','refunded','transfered','transferred','upgraded','rolled-over') DEFAULT 'unpaid';
ALTER TABLE regHistory MODIFY COLUMN status enum('unpaid','plan','paid','cancelled','refunded','transfered','transferred','upgraded','rolled-over')
    DEFAULT 'unpaid';
UPDATE reg SET status = 'transferred' WHERE status = 'transfered';
UPDATE regHistory SET status = 'transferred' WHERE status = 'transfered';

/* add donation as a reg status type, indicating it was cancelled and the money donated, not refunded */
ALTER TABLE reg MODIFY COLUMN status enum('unpaid','plan','paid','cancelled','refunded','donated','transferred','upgraded','rolled-over') DEFAULT 'unpaid';
ALTER TABLE regHistory MODIFY COLUMN status enum('unpaid','plan','paid','cancelled','refunded','donated','transferred','upgraded','rolled-over') DEFAULT
    'unpaid';

INSERT INTO patchLog(id, name) VALUES(55, 'taxes et al');

