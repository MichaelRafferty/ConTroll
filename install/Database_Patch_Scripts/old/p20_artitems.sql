/* p20 - artitems - update to tie to exhibitor using new region year id table
 */

alter table artItems add column exhibitorRegionYearId INT;
alter table artItems add constraint `artItems_exhibitorRegionYear_fk` FOREIGN KEY (`exhibitorRegionYearId`) REFERENCES `exhibitorRegionYears` (`id`) ON UPDATE CASCADE;


INSERT INTO patchLog(id, name) values(20, 'artitems');
