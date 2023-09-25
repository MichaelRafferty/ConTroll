/* p11_reg_history */
/* rename atcon_history to reg_history and add new potential enum values */


ALTER TABLE atcon_history RENAME reg_history;
ALTER TABLE reg_history MODIFY COLUMN action enum('attach','print','notes','transfer','rollover','overpayment','refund') 
	CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;

INSERT INTO patchLog(id, name) values(11, 'reg_history');
