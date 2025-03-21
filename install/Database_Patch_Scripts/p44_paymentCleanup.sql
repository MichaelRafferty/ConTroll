/*
 * New Changes for Payment Cleanup and new Registration Coordinatior Role
 */

/* owner for unknown person mail in registrations */
INSERT INTO perinfo(id, last_name, first_name, banned, creation_date, update_date, active, open_notes, contact_ok, share_reg_ok)
VALUES (5, 'Registration', 'Mail In', 'N', now(), now(), 'N', 'INTERNAL NOT FOR REGISTRATION USE', 'N', 'N');
UPDATE perinfo SET first_name = 'At Con', last_name = 'Registration' WHERE id = 2;
// for those sites with id 5 already:
// UPDATE perinfo SET first_name = 'Mail In', last_name = 'Registration' WHERE id = 5;

/* less powerful reg admin, does not get everything */
INSERT INTO auth(id, name, page, display, sortOrder) VALUES
(21,'reg_admin', 'N', 'N', 140),
(22,'reg_ad_menu', 'N', 'N', 145);

UPDATE auth SET name = 'reg_staff' WHERE id = 6;
UPDATE auth SET name = 'reg_admin' WHERE id = 21;
update auth set page = 'N', display = 'N' where id = 19;

INSERT INTO patchLog(id, name) VALUES(44, 'Payment Cleanup');