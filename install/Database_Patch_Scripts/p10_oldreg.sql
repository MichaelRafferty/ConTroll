/* p10_oldreg */
/* temporary change to add the old reg code as it's own menu until all features are verified as available in the new reg code in reg_control */


insert into auth(id, name, page, display)
values('999', 'registration-old', 'Y', 'Old Reg');

INSERT INTO patchLog(id, name) values(10, 'oldreg');
