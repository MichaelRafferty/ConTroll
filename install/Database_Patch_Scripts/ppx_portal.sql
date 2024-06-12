/*
 * changes needed to make sales work and track the things we want to track
 */
CREATE TABLE portalTokenLinks (
    id int NOT NULL AUTO_INCREMENT,
    email varchar(254) NOT NULL,
    source_ip varchar(16) NOT NULL,
    createdTS timestamp NOT NULL default NOW(),
    useCnt int NOT NULL DEFAULT 0,
    useIP varchar(16) DEFAULT NULL,
    useTS timestamp DEFAULT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
CREATE INDEX ptlEmail_idx ON portalTokenLinks (email ASC, createdTS DESC);

/*
 * perinfoIdentities: list of valid verifiers for this perinfo, and the email address it will return.
 *  NOTE: if provider='allow', this is a user added email address for validation using any provider,
 *      and when the provider returnds valid, an entry is added to this table with their provider name and subscriber id.
 *  This this table lists altername email addresses for this person that could be used by validators.
 */
CREATE TABLE perinfoIdentities (
    perid int NOT NULL,
    provider varchar(16) NOT NULL,
    email_addr varchar(254) NOT NULL,
    subscriberID varchar(254) DEFAULT NULL,
    creationTS TIMESTAMP DEFAULT NOW(),
    lastUseTS TIMESTAMP DEFAULT NULL,
    useCount int default NULL,
    PRIMARY KEY (perid, provider, email_addr)
);
ALTER TABLE perinfoIdentities ADD FOREIGN KEY pi_perinfo_fk (perid) REFERENCES perinfo(id) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE perinfo ADD COLUMN managedBy int DEFAULT NULL;
ALTER TABLE perinfo ADD COLUMN lastVerified datetime DEFAULT NULL;
ALTER TABLE perinfoHistory ADD COLUMN managedBy int DEFAULT NULL;
ALTER TABLE perinfoHistory ADD COLUMN lastVerified datetime DEFAULT NULL;
ALTER TABLE perinfo ADD COLUMN updatedBy int DEFAULT NULL;
ALTER TABLE perinfo ADD FOREIGN KEY pi_managedBy_fk (managedBy) REFERENCES perinfo(id) ON UPDATE CASCADE;
ALTER TABLE perinfo ADD FOREIGN KEY pi_updatedBy_fk (updatedBy) REFERENCES perinfo(id) ON UPDATE CASCADE;

ALTER TABLE newperson ADD COLUMN managedBy int DEFAULT NULL;
ALTER TABLE newperson ADD COLUMN managedByNew int DEFAULT NULL;
ALTER TABLE newperson ADD COLUMN lastVerified datetime DEFAULT NULL;
ALTER TABLE newperson ADD COLUMN updatedBy int DEFAULT NULL;

ALTER TABLE newperson ADD FOREIGN KEY np_managedBy_fk (managedBy) REFERENCES perinfo(id) ON UPDATE CASCADE;
ALTER TABLE newperson ADD FOREIGN KEY np_managedByNew_fk (managedByNew) REFERENCES newperson(id) ON UPDATE CASCADE;

DROP TRIGGER IF EXISTS perinfo_update;
DELIMITER ;;
CREATE DEFINER=CURRENT_USER  TRIGGER `perinfo_update` BEFORE UPDATE ON `perinfo` FOR EACH ROW BEGIN
    IF (OLD.id != NEW.id OR OLD.last_name != NEW.last_name OR OLD.first_name != NEW.first_name OR OLD.middle_name != NEW.middle_name OR OLD.suffix != NEW.suffix
        OR OLD.email_addr != NEW.email_addr OR OLD.phone != NEW.phone OR OLD.badge_name != NEW.badge_name OR OLD.legalName != NEW.legalName
        OR OLD.address != NEW.address OR OLD.addr_2 != NEW.addr_2 OR OLD.city != NEW.city OR OLD.state != NEW.state OR OLD.zip != NEW.zip
        OR OLD.country != NEW.country OR OLD.banned != NEW.banned OR OLD.creation_date != NEW.creation_date OR OLD.update_date != NEW.update_date
        OR OLD.change_notes != NEW.change_notes OR OLD.active != NEW.active OR OLD.open_notes != NEW.open_notes OR OLD.admin_notes != NEW.admin_notes
        OR OLD.old_perid != NEW.old_perid OR OLD.contact_ok != NEW.contact_ok OR OLD.share_reg_ok != NEW.share_reg_ok OR OLD.managedBy != NEW.managedBy
        OR OLD.lastVerified != NEW.lastVerified)
    THEN

        INSERT INTO perinfoHistory(id, last_name, first_name, middle_name, suffix, email_addr, phone, badge_name, legalName,
                                   address, addr_2, city, state, zip, country, banned, creation_date, update_date, change_notes, active,
                                   open_notes, admin_notes, old_perid, contact_ok, share_reg_ok, managedBy, lastVerified)
        VALUES (OLD.id, OLD.last_name, OLD.first_name, OLD.middle_name, OLD.suffix, OLD.email_addr, OLD.phone, OLD.badge_name, OLD.legalName,
                OLD.address, OLD.addr_2, OLD.city, OLD.state, OLD.zip, OLD.country, OLD.banned, OLD.creation_date, OLD.update_date, OLD.change_notes,
                OLD.active, OLD.open_notes, OLD.admin_notes, OLD.old_perid, OLD.contact_ok, OLD.share_reg_ok, OLD.managedBy, OLD.lastVerified);
    END IF;
END;;
DELIMITER ;


ALTER TABLE reg ADD COLUMN printable ENUM('N','Y') NOT NULL DEFAULT 'N';
ALTER TABLE reg ADD COLUMN status ENUM('unpaid', 'plan', 'paid', 'cancelled', 'refunded', 'transfered', 'upgraded', 'rolled-over') DEFAULT 'unpaid';

UPDATE reg SET status = 'paid' WHERE price = (paid + couponDiscount);

/* would like a reg chain
ALTER TABLE reg ADD COLUMN

 */

/*
 * Membership rules
 *   memCategory Items for default rules
 *
 *  memRules table sets
 */

CREATE TABLE memRules (
    name varchar(16) NOT NULL,
    optionName varchar(64) NOT NULL,
    description text DEFAULT NULL,
    typeList varchar(1024) DEFAULT NULL,
    catList varchar(1024) DEFAULT NULL,
    ageList varchar(1024) DEFAULT NULL,
    memList varchar(1024) DEFAULT NULL,
    PRIMARY KEY (name)
);

CREATE TABLE memRuleItems
(
    name     varchar(16) NOT NULL,
    step     int NOT NULL,
    ruleType enum ('needAny', 'needAll', 'notAny', 'notAll', 'limitAge') NOT NULL,
    applyTo  enum ('person','all') NOT NULL DEFAULT 'person',
    typeList varchar(1024) DEFAULT NULL,
    catList  varchar(1024) DEFAULT NULL,
    ageList  varchar(1024) DEFAULT NULL,
    memList  varchar(1024) DEFAULT NULL,
    PRIMARY KEY (name, step)
);


ALTER TABLE memRuleItems ADD CONSTRAINT mri_rule_fk FOREIGN KEY (name) REFERENCES memRules(name) ON UPDATE CASCADE ON DELETE CASCADE;


ALTER TABLE memCategories ADD COLUMN onlyOne enum('Y', 'N') NOT NULL DEFAULT 'Y' AFTER memCategory;
ALTER TABLE memCategories ADD COLUMN standAlone enum('Y', 'N') NOT NULL DEFAULT 'N' AFTER onlyOne;
ALTER TABLE memCategories ADD COLUMN variablePrice enum('Y', 'N') NOT NULL DEFAULT 'N' AFTER standAlone;

CREATE TABLE interests
(
    interest    varchar(16)     NOT NULL,
    description varchar(4096)            DEFAULT NULL,
    notifyList  varchar(512)             DEFAULT NULL,
    sortOrder   int                      DEFAULT 0,
    createDate  timestamp       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updateDate  timestamp       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updateBy    int                      DEFAULT NULL,
    active      enum ('Y', 'N') NOT NULL DEFAULT 'Y',
    csv         enum ('Y', 'N') NOT NULL DEFAULT 'N',
    PRIMARY KEY (interest)
);

ALTER TABLE interests ADD FOREIGN KEY interests_updatdBy_fk (updateBy) REFERENCES perinfo(id) ON UPDATE CASCADE;


CREATE TABLE memberInterests
(
    id int NOT NULL AUTO_INCREMENT,
    perid int DEFAULT NULL,
    newperid int DEFAULT NULL,
    interest varchar(16) NOT NULL,
    interested enum ('Y', 'N') NOT NULL DEFAULT 'N',
    notifyDate datetime DEFAULT NULL,
    csvDate datetime DEFAULT NULL,
    createDate  timestamp       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updateDate  timestamp       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updateBy    int DEFAULT NULL,
    PRIMARY KEY (id)
);

ALTER TABLE memberInterests ADD CONSTRAINT FOREIGN KEY (interest) REFERENCES interests(interest) ON UPDATE CASCADE;

CREATE TABLE paymentPlans (
    id int NOT NULL AUTO_INCREMENT,
    name varchar(16) NOT NULL,
    ddescription varchar(1024) DEFAULT NULL,
    catList varchar(1024) DEFAULT NULL,
    memList varchar(1024) DEFAULT NULL,
    excludeList varchar(1024) DEFAULT NULL,
    portalList varchar(1024) DEFAULT NULL,
    downPercent decimal(8,2) DEFAULT NULL,
    downAmt decimal(8,2) DEFAULT NULL,
    numPaymentMax int DEFAULT NULL,
    payByDate datetime DEFAULT NULL,
    payType enum('manual','auto') DEFAULT 'manual',
    modify enum ('Y', 'N') NOT NULL DEFAULT 'N',
    reminders enum ('Y', 'N') NOT NULL DEFAULT 'N',
    active enum ('Y', 'N') NOT NULL DEFAULT 'Y',
    sortorder int NOT NULL DEFAULT 0,
    createDate  timestamp       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updateDate  timestamp       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updateBy    int DEFAULT NULL,
    PRIMARY KEY (id)
);

ALTER TABLE paymentPlans ADD FOREIGN KEY pp_updateBy_fk (updateBy) REFERENCES perinfo(id) ON UPDATE CASCADE;


CREATE TABLE payorPlans (
    id int NOT NULL AUTO_INCREMENT,
    planId int NOT NULL,
    perid int DEFAULT NULL,
    newperid int default NULL,
    initialAmt decimal(8,2) NOT NULL,
    nonPlanAmt decimal(8,2) NOT NULL DEFAULT 0,
    downPayment decimal(8,2) NOT NULL DEFAULT 0,
    openingBalance decimal(8,2) NOT NULL DEFAULT 0,
    numPayments int NOT NULL,
    payByDate datetime NOT NULL,
    payType enum('manual','auto') DEFAULT 'manual',
    reminders enum ('Y', 'N') NOT NULL DEFAULT 'N',
    status enum('active','paid','refunded','cancelled') DEFAULT 'active',
    createTransaction int default NULL,
    balanceDue decimal(8,2) NOT NULL DEFAULT 0,
    createDate  timestamp       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updateDate  timestamp       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updateBy    int DEFAULT NULL,
    PRIMARY KEY (id)
);

CREATE TABLE payorPlanPayments (
    id int NOT NULL AUTO_INCREMENT,
    planId int NOT NULL,
    paymentNbr int NOT NULL DEFAULT 0,
    dueDate datetime DEFAULT NULL,
    payDate datetime DEFAULT NULL,
    amount decimal(8,2) NOT NULL DEFAULT 0,
    paymentId int DEFAULT NULL,
    transactionId int DEFAULT NULL,
    PRIMARY KEY (id)
);


INSERT INTO patchLog(id, name) values(ppx, 'Portal Changes');
