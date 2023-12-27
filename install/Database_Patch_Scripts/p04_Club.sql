/* P4-Rename BSFS to Club */

ALTER TABLE bsfs RENAME club;
UPDATE auth SET name = 'club' where name = 'bsfs';
UPDATE auth SET display = 'Club' where display = 'BSFS';

INSERT INTO patchLog(id, name) values(4, 'Rename BSFS to Club');
