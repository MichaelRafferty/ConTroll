/* p11_reg_history */
/* rename the atcon_histroy table to reg_history as it now supports reg_control as well as atcon change history */


RENAME TABLE atcon_history TO reg_history;

INSERT INTO patchLog(id, name) values(11, 'reg_history');
