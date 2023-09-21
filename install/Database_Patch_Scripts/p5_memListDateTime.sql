/* P5-memList change to datetime from datr */

ALTER TABLE memList MODIFY COLUMN startdate datetime DEFAULT NULL;
ALTER TABLE memList MODIFY COLUMN enddate datetime DEFAULT NULL;

INSERT INTO patchLog(id, name) values(5, 'memList  to DateTime');

