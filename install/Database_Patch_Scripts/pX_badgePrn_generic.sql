/* PX - create columns in memCategories and ageList for badge design */
ALTER TABLE `memCategories` ADD COLUMN `badgeLabel` varchar(16) NOT NULL DEFAULT 'X';

ALTER TABLE `ageList` ADD COLUMN `badgeFlag` varchar(16);

/* to make things work like Balticon 
UPDATE `memCategories` SET `badgeLabel`= UPPER(SUBSTRING(`memCategory`, 1, 1));
update memCategories set badgeLabel='M' where badgeLabel in ('D','R','S','U','Y');
update memCategories set badgeLabel='X' where badgeLabel in ('C', 'V');

ALTER TABLE `ageList` ADD COLUMN `badgeFlag` varchar(16);
*/

INSERT INTO patchLog(id, name) values(X, 'badgePrn');
