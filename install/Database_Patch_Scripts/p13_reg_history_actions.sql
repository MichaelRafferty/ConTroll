/* p13_reg_history_actions */
/* add new actions to track */

ALTER TABLE reg_history MODIFY COLUMN `action` enum('attach','print','notes','transfer','rollover','overpayment','refund') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;

INSERT INTO patchLog(id, name) values(13, 'reg_history_actions');
