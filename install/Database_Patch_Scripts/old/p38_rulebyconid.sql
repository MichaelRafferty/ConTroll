/*
 * add conid to rules table entries (Note you must fix this to use your current conid for the XXX
 */

ALTER TABLE memRuleItems DROP CONSTRAINT mri_rule_fk;

CREATE TABLE memRuleSteps (
    name varchar(16) COLLATE utf8mb4_general_ci NOT NULL,
    conid int NOT NULL,
    step int NOT NULL,
    ruleType enum('needAny','needAll','notAny','notAll','limitAge','currentAge') COLLATE utf8mb4_general_ci NOT NULL,
    applyTo enum('person','all') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'person',
    typeList varchar(1024) COLLATE utf8mb4_general_ci DEFAULT NULL,
    catList varchar(1024) COLLATE utf8mb4_general_ci DEFAULT NULL,
    ageList varchar(1024) COLLATE utf8mb4_general_ci DEFAULT NULL,
    memList varchar(1024) COLLATE utf8mb4_general_ci DEFAULT NULL,
    PRIMARY KEY (conid,name,step)
);

ALTER TABLE memRules ADD COLUMN conid int AFTER name;
UPDATE memRules SET conid = XXX;
ALTER TABLE memRules MODIFY COLUMN conid int NOT NULL;
ALTER TABLE memRules DROP PRIMARY KEY, ADD PRIMARY KEY (conid,name);

ALTER TABLE memRuleSteps ADD CONSTRAINT mrs_mr_fk FOREIGN KEY (conid, name) REFERENCES memRules(conid, name) ON DELETE CASCADE ON UPDATE CASCADE;

INSERT INTO memRuleSteps(name, conid, step, ruleType, applyTo, typeList, catList, ageList, memList)
SELECT name, XXX, step, ruleType, applyTo, typeList, catList, ageList, memList
FROM memRuleItems;

/* DROP TABLE memRuleItems; */
INSERT INTO patchLog(id, name) VALUES(38, 'rules conid');