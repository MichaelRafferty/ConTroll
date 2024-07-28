/*
 * changes needed to make sales work and track the things we want to track
 */
CREATE TABLE portalTokenLinks (
    id int NOT NULL AUTO_INCREMENT,
    email varchar(254) NOT NULL,
    action enum('login','attach','identity','other') NOT NULL DEFAULT 'other',
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
    useCount int NOT NULL DEFAULT 0,
    PRIMARY KEY (perid, provider, email_addr)
);
ALTER TABLE perinfoIdentities ADD FOREIGN KEY pi_perinfo_fk (perid) REFERENCES perinfo(id) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE perinfo ADD COLUMN managedBy int DEFAULT NULL;
ALTER TABLE perinfo ADD COLUMN managedByNew int DEFAULT NULL after managedBy;
ALTER TABLE perinfo ADD COLUMN managedReason varchar(16) DEFAULT NULL;
ALTER TABLE perinfo ADD COLUMN lastVerified datetime DEFAULT NULL;
ALTER TABLE perinfo ADD COLUMN pronouns varchar(64) DEFAULT NULL AFTER legalName;

ALTER TABLE perinfoHistory ADD COLUMN managedBy int DEFAULT NULL;
ALTER TABLE perinfoHistory ADD COLUMN managedByNew int DEFAULT NULL after managedBy;

ALTER TABLE perinfoHistory ADD COLUMN managedReason varchar(16) DEFAULT NULL;
ALTER TABLE perinfoHistory ADD COLUMN lastVerified datetime DEFAULT NULL;
ALTER TABLE perinfoHistory ADD COLUMN pronouns varchar(64) DEFAULT NULL AFTER legalName;

ALTER TABLE perinfo ADD COLUMN updatedBy int DEFAULT NULL;
ALTER TABLE perinfo ADD FOREIGN KEY pi_managedBy_fk (managedBy) REFERENCES perinfo(id) ON UPDATE CASCADE;
ALTER TABLE perinfo ADD FOREIGN KEY pi_managedByNew_fk (managedByNew) REFERENCES newperson(id) ON UPDATE CASCADE;
ALTER TABLE perinfo ADD FOREIGN KEY pi_updatedBy_fk (updatedBy) REFERENCES perinfo(id) ON UPDATE CASCADE;

ALTER TABLE newperson ADD COLUMN managedBy int DEFAULT NULL;
ALTER TABLE newperson ADD COLUMN managedByNew int DEFAULT NULL;
ALTER TABLE newperson ADD COLUMN managedReason varchar(16) DEFAULT NULL;
ALTER TABLE newperson ADD COLUMN lastVerified datetime DEFAULT NULL;
ALTER TABLE newperson ADD COLUMN updatedBy int DEFAULT NULL;
ALTER TABLE newperson ADD COLUMN pronouns varchar(64) DEFAULT NULL AFTER legalName;

ALTER TABLE newperson ADD FOREIGN KEY np_managedBy_fk (managedBy) REFERENCES perinfo(id) ON UPDATE CASCADE;
ALTER TABLE newperson ADD FOREIGN KEY np_managedByNew_fk (managedByNew) REFERENCES newperson(id) ON UPDATE CASCADE;

DROP TRIGGER IF EXISTS perinfo_update;
DELIMITER ;;
CREATE DEFINER=CURRENT_USER  TRIGGER `perinfo_update` BEFORE UPDATE ON `perinfo` FOR EACH ROW BEGIN
    IF (OLD.id != NEW.id OR OLD.last_name != NEW.last_name OR OLD.first_name != NEW.first_name OR OLD.middle_name != NEW.middle_name
        OR OLD.suffix != NEW.suffix OR OLD.legalName != NEW.legalName OR OLD.pronouns != NEW.pronouns
        OR OLD.email_addr != NEW.email_addr OR OLD.phone != NEW.phone OR OLD.badge_name != NEW.badge_name
        OR OLD.address != NEW.address OR OLD.addr_2 != NEW.addr_2 OR OLD.city != NEW.city OR OLD.state != NEW.state OR OLD.zip != NEW.zip
        OR OLD.country != NEW.country OR OLD.banned != NEW.banned OR OLD.creation_date != NEW.creation_date OR OLD.update_date != NEW.update_date
        OR OLD.change_notes != NEW.change_notes OR OLD.active != NEW.active OR OLD.open_notes != NEW.open_notes OR OLD.admin_notes != NEW.admin_notes
        OR OLD.old_perid != NEW.old_perid OR OLD.contact_ok != NEW.contact_ok OR OLD.share_reg_ok != NEW.share_reg_ok
        OR OLD.managedBy != NEW.managedBy OR OLD.managedByNew != NEW.managedByNew
        OR OLD.managedReason != NEW.managedReason OR OLD.lastVerified != NEW.lastVerified)
    THEN
        INSERT INTO perinfoHistory(id, last_name, first_name, middle_name, suffix, email_addr, phone, badge_name, legalName, pronouns,
                                   address, addr_2, city, state, zip, country, banned, creation_date, update_date, change_notes, active,
                                   open_notes, admin_notes, old_perid, contact_ok, share_reg_ok, managedBy, managedByNew,
                                   managedReason, lastVerified)
        VALUES (OLD.id, OLD.last_name, OLD.first_name, OLD.middle_name, OLD.suffix, OLD.email_addr, OLD.phone, OLD.badge_name, OLD.legalName, OLD.pronouns,
                OLD.address, OLD.addr_2, OLD.city, OLD.state, OLD.zip, OLD.country, OLD.banned, OLD.creation_date, OLD.update_date, OLD.change_notes,
                OLD.active, OLD.open_notes, OLD.admin_notes, OLD.old_perid, OLD.contact_ok, OLD.share_reg_ok, OLD.managedBy, OLD.managedByNew,
                OLD.managedReason, OLD.lastVerified);
    END IF;
END;;
DELIMITER ;


ALTER TABLE reg ADD COLUMN printable ENUM('N','Y') NOT NULL DEFAULT 'N';
ALTER TABLE reg ADD COLUMN status ENUM('unpaid', 'plan', 'paid', 'cancelled', 'refunded', 'transfered', 'upgraded', 'rolled-over') DEFAULT 'unpaid';

UPDATE reg SET status = 'paid' WHERE price = (paid + couponDiscount);

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
    conid int DEFAULT NULL,
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
ALTER TABLE memberInterests ADD CONSTRAINT FOREIGN KEY (newperid) REFERENCES newperson(id) ON UPDATE CASCADE;
ALTER TABLE memberInterests ADD CONSTRAINT FOREIGN KEY (perid) REFERENCES perinfo(id) ON UPDATE CASCADE;

CREATE TABLE paymentPlans (
    id int NOT NULL AUTO_INCREMENT,
    name varchar(16) NOT NULL,
    description varchar(1024) DEFAULT NULL,
    catList varchar(1024) DEFAULT NULL,
    memList varchar(1024) DEFAULT NULL,
    excludeList varchar(1024) DEFAULT NULL,
    portalList varchar(1024) DEFAULT NULL,
    downPercent decimal(8,2) DEFAULT 0,
    downAmt decimal(8,2) DEFAULT 25.00,
    minPayment decimal(8,2) DEFAULT 10.00,
    numPaymentMax int DEFAULT 4,
    payByDate date DEFAULT NULL,
    payType enum('manual','auto') DEFAULT 'manual',
    modify enum ('Y', 'N') NOT NULL DEFAULT 'N',
    reminders enum ('Y', 'N') NOT NULL DEFAULT 'N',
    downIncludeNonPlan enum('Y','N') NOT NULL DEFAULT 'N',
    lastPaymentPartial enum('Y','N') NOT NULL DEFAULT 'N',
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
    minPayment decimal(8,2) DEFAULT 10.00,
    finalPayment decimal(8,2) DEFAULT 10.00,
    openingBalance decimal(8,2) NOT NULL DEFAULT 0,
    numPayments int NOT NULL,
    daysBetween int NOT NULL DEFAULT 30,
    payByDate date NOT NULL,
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

ALTER TABLE payorPlans ADD CONSTRAINT pp_planid_fk FOREIGN KEY (planId) REFERENCES paymentPlans(id);
ALTER TABLE payorPlans ADD CONSTRAINT pp_newperid_fk FOREIGN KEY (newperid) REFERENCES newperson(id) ON UPDATE CASCADE;
ALTER TABLE payorPlans ADD CONSTRAINT pp_perid_fk FOREIGN KEY (perid) REFERENCES perinfo(id) ON UPDATE CASCADE;

CREATE TABLE payorPlanPayments (
    payorPlanId int NOT NULL,
    paymentNbr int NOT NULL DEFAULT 0,
    dueDate datetime DEFAULT NULL,
    payDate datetime DEFAULT NULL,
    planPaymentAmount decimal(8,2) NOT NULL DEFAULT 0,
    amount decimal(8,2) NOT NULL DEFAULT 0,
    paymentId int DEFAULT NULL,
    transactionId int DEFAULT NULL,
    PRIMARY KEY (payorPlanId, paymentNbr)
);

ALTER TABLE payorPlanPayments ADD CONSTRAINT ppp_payorplanid_fk FOREIGN KEY (payorPlanId) REFERENCES payorPlans(id);

ALTER TABLE reg ADD COLUMN planId int DEFAULT NULL AFTER coupon;
ALTER TABLE reg ADD CONSTRAINT reg_planid_fk FOREIGN KEY (planId) REFERENCES payorPlans (id) ON UPDATE CASCADE;

ALTER TABLE transaction DROP COLUMN ticket_num;


INSERT INTO perinfo(id, last_name, first_name, banned, active, contact_ok, share_reg_ok, open_notes)
VALUES(4, 'Internal', 'Portal', 'N', 'N', 'N', 'N', 'INTERNAL NOT FOR REGISTRAITON USE');

CREATE TABLE controllAppPages (
    appName  varchar(16) NOT NULL,
    appPage varchar(32) NOT NULL,
    pageDescription varchar(4096) DEFAULT '',
    PRIMARY KEY (appName, appPage)
);

CREATE TABLE controllAppSections (
    appName  varchar(16) NOT NULL,
    appPage varchar(32) NOT NULL,
    appSection varchar(32) NOT NULL,
    sectionDescription varchar(4096) DEFAULT '',
    PRIMARY KEY (appName, appPage, appSection)
);

ALTER TABLE controllAppSections ADD FOREIGN KEY (appName, appPage) REFERENCES controllAppPages(appName, appPage) ON UPDATE CASCADE;

CREATE TABLE controllAppItems (
    appName  varchar(16) NOT NULL,
    appPage varchar(32) NOT NULL,
    appsection varchar(32) NOT NULL,
    txtItem varchar(32) NOT NULL,
    txtItemDescription varchar(4096) DEFAULT '',
    PRIMARY KEY (appName, appPage, appSection, txtItem)
);

ALTER TABLE controllAppItems ADD FOREIGN KEY (appName, appPage, appSection)
    REFERENCES controllAppSections(appName, appPage, appSection) ON UPDATE CASCADE;

CREATE TABLE controllTxtItems (
    appName  varchar(16) NOT NULL,
    appPage varchar(32) NOT NULL,
    appSection varchar(32) NOT NULL,
    txtItem varchar(32) NOT NULL,
    contents text DEFAULT NULL,
    PRIMARY KEY (appName, appPage, appSection, txtItem)
);

ALTER TABLE controllTxtItems ADD FOREIGN KEY (appName, appPage, appSection, txtItem)
    REFERENCES controllAppItems(appName, appPage, appSection, txtItem) ON UPDATE CASCADE;

CREATE TABLE policies (
    policy varchar(16) COLLATE utf8mb4_general_ci NOT NULL,
    prompt varchar(256) COLLATE utf8mb4_general_ci NOT NULL,
    description varchar(4096) COLLATE utf8mb4_general_ci DEFAULT NULL,
    sortOrder int DEFAULT '0',
    required enum('Y','N') NOT NULL DEFAULT 'N',
    defaultValue enum('Y', 'N') NOT NULL DEFAULT 'Y',
    createDate timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updateDate timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updateBy int DEFAULT NULL,
    active enum('Y','N') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Y',
    PRIMARY KEY (policy)
);

ALTER TABLE policies ADD CONSTRAINT foreign key(updateBy) references perinfo(id) ON UPDATE CASCADE;

CREATE TABLE memberPolicies (
    id int NOT NULL AUTO_INCREMENT,
    perid int DEFAULT NULL,
    conid int DEFAULT NULL,
    newperid int DEFAULT NULL,
    policy varchar(16) COLLATE utf8mb4_general_ci NOT NULL,
    response enum('Y','N') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'N',
    createDate timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updateDate timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updateBy int DEFAULT NULL,
    PRIMARY KEY (id)
);

ALTER TABLE memberPolicies ADD CONSTRAINT foreign key(updateBy) references perinfo(id) ON UPDATE CASCADE;
ALTER TABLE memberPolicies ADD CONSTRAINT foreign key(perid) references perinfo(id) ON UPDATE CASCADE;
ALTER TABLE memberPolicies ADD CONSTRAINT foreign key(newperid) references newperson(id) ON UPDATE CASCADE;
ALTER TABLE memberPolicies ADD CONSTRAINT foreign key(policy) references policies(policy) ON UPDATE CASCADE;


INSERT INTO patchLog(id, name) values(ppx, 'Portal Changes');