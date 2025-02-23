/*
 * New Permissions and changes for general reports
 */

INSERT INTO auth(id, name, page, display, sortOrder)
VALUES (20,'gen_rpts', 'N', 'N', 135);

DELETE FROM user_auth WHERE auth_id IN (17,18);
DELETE FROM auth WHERE id IN (17,18);


INSERT INTO patchLog(id, name) VALUES(43, 'General Reports');