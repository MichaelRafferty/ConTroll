/*
 * Changes to fix issues discovered at Balticon 59
 */

/*
 * due to how perinfo is used, all the name/address field need to default to blank, not nul
 */

UPDATE perinfo SET last_name = '' WHERE last_name IS NULL;
UPDATE perinfo SET first_name = '' WHERE first_name IS NULL;
UPDATE perinfo SET middle_name = '' WHERE middle_name IS NULL;
UPDATE perinfo SET suffix = '' WHERE suffix IS NULL;
UPDATE perinfo SET email_addr = '' WHERE email_addr IS NULL;
UPDATE perinfo SET phone = '' WHERE phone IS NULL;
UPDATE perinfo SET badge_name = '' WHERE badge_name IS NULL;
UPDATE perinfo SET legalName = '' WHERE legalName IS NULL;
UPDATE perinfo SET pronouns = '' WHERE pronouns IS NULL;
UPDATE perinfo SET address = '' WHERE address IS NULL;
UPDATE perinfo SET addr_2 = '' WHERE addr_2 IS NULL;
UPDATE perinfo SET city = '' WHERE city IS NULL;
UPDATE perinfo SET state = '' WHERE state IS NULL;
UPDATE perinfo SET zip = '' WHERE zip IS NULL;
UPDATE perinfo SET country = '' WHERE country IS NULL;
UPDATE perinfo SET managedReason = '' WHERE managedReason IS NULL;
ALTER TABLE perinfo MODIFY COLUMN last_name varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci  NOT NULL DEFAULT '';
ALTER TABLE perinfo MODIFY COLUMN first_name varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci  NOT NULL DEFAULT '';
ALTER TABLE perinfo MODIFY COLUMN middle_name varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci  NOT NULL DEFAULT '';
ALTER TABLE perinfo MODIFY COLUMN suffix varchar(4) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci  NOT NULL DEFAULT '';
ALTER TABLE perinfo MODIFY COLUMN email_addr varchar(254) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci  NOT NULL DEFAULT '';
ALTER TABLE perinfo MODIFY COLUMN phone varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci  NOT NULL DEFAULT '';
ALTER TABLE perinfo MODIFY COLUMN badge_name varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci  NOT NULL DEFAULT '';
ALTER TABLE perinfo MODIFY COLUMN legalName varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci  NOT NULL DEFAULT '';
ALTER TABLE perinfo MODIFY COLUMN pronouns varchar(64) COLLATE utf8mb4_general_ci  NOT NULL DEFAULT '';
ALTER TABLE perinfo MODIFY COLUMN address varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci  NOT NULL DEFAULT '';
ALTER TABLE perinfo MODIFY COLUMN addr_2 varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci  NOT NULL DEFAULT '';
ALTER TABLE perinfo MODIFY COLUMN city varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci  NOT NULL DEFAULT '';
ALTER TABLE perinfo MODIFY COLUMN state varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci  NOT NULL DEFAULT '';
ALTER TABLE perinfo MODIFY COLUMN zip varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci  NOT NULL DEFAULT '';
ALTER TABLE perinfo MODIFY COLUMN country varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci  NOT NULL DEFAULT '';
ALTER TABLE perinfo MODIFY COLUMN managedReason varchar(16) COLLATE utf8mb4_general_ci  NOT NULL DEFAULT '';

/*
 * same for newperson
 */
UPDATE newperson SET last_name = '' WHERE last_name IS NULL;
UPDATE newperson SET first_name = '' WHERE first_name IS NULL;
UPDATE newperson SET middle_name = '' WHERE middle_name IS NULL;
UPDATE newperson SET suffix = '' WHERE suffix IS NULL;
UPDATE newperson SET email_addr = '' WHERE email_addr IS NULL;
UPDATE newperson SET phone = '' WHERE phone IS NULL;
UPDATE newperson SET badge_name = '' WHERE badge_name IS NULL;
UPDATE newperson SET legalName = '' WHERE legalName IS NULL;
UPDATE newperson SET pronouns = '' WHERE pronouns IS NULL;
UPDATE newperson SET address = '' WHERE address IS NULL;
UPDATE newperson SET addr_2 = '' WHERE addr_2 IS NULL;
UPDATE newperson SET city = '' WHERE city IS NULL;
UPDATE newperson SET state = '' WHERE state IS NULL;
UPDATE newperson SET zip = '' WHERE zip IS NULL;
UPDATE newperson SET country = '' WHERE country IS NULL;
UPDATE newperson SET managedReason = '' WHERE managedReason IS NULL;
ALTER TABLE newperson MODIFY COLUMN last_name varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci  NOT NULL DEFAULT '';
ALTER TABLE newperson MODIFY COLUMN first_name varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci  NOT NULL DEFAULT '';
ALTER TABLE newperson MODIFY COLUMN middle_name varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci  NOT NULL DEFAULT '';
ALTER TABLE newperson MODIFY COLUMN suffix varchar(4) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci  NOT NULL DEFAULT '';
ALTER TABLE newperson MODIFY COLUMN email_addr varchar(254) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci  NOT NULL DEFAULT '';
ALTER TABLE newperson MODIFY COLUMN phone varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci  NOT NULL DEFAULT '';
ALTER TABLE newperson MODIFY COLUMN badge_name varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci  NOT NULL DEFAULT '';
ALTER TABLE newperson MODIFY COLUMN legalName varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci  NOT NULL DEFAULT '';
ALTER TABLE newperson MODIFY COLUMN pronouns varchar(64) COLLATE utf8mb4_general_ci  NOT NULL DEFAULT '';
ALTER TABLE newperson MODIFY COLUMN address varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci  NOT NULL DEFAULT '';
ALTER TABLE newperson MODIFY COLUMN addr_2 varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci  NOT NULL DEFAULT '';
ALTER TABLE newperson MODIFY COLUMN city varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci  NOT NULL DEFAULT '';
ALTER TABLE newperson MODIFY COLUMN state varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci  NOT NULL DEFAULT '';
ALTER TABLE newperson MODIFY COLUMN zip varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci  NOT NULL DEFAULT '';
ALTER TABLE newperson MODIFY COLUMN country varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci  NOT NULL DEFAULT '';
ALTER TABLE newperson MODIFY COLUMN managedReason varchar(16) COLLATE utf8mb4_general_ci  NOT NULL DEFAULT '';

/*
 * regActions needs to record which program recorded the action
 */
ALTER TABLE regActions ADD COLUMN source varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL AFTER userid;


INSERT INTO patchLog(id, name) VALUES(46, 'Post Balticon Cleanup');
