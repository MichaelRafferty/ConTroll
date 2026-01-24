/* P3-Atcon History table */

ALTER TABLE atcon_auth ADD CONSTRAINT atcon_authuser_fk 
FOREIGN KEY (authuser) references atcon_user(id) ON UPDATE CASCADE ON DELETE CASCADE;

DROP TABLE IF EXISTS atcon_history;
CREATE TABLE atcon_history (
    id int NOT NULL AUTO_INCREMENT,
    logdate timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          userid int NOT NULL,
    tid int NOT NULL,
    regid int NOT NULL,
    action enum('attach', 'print', 'notes') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    notes varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
    PRIMARY KEY(id)
    );

ALTER TABLE atcon_history ADD CONSTRAINT atcon_history_tid_fk
FOREIGN KEY (tid) REFERENCES transaction(id) ON UPDATE CASCADE ON DELETE CASCADE;
ALTER TABLE atcon_history ADD CONSTRAINT atcon_history_regid_fk
FOREIGN KEY (regid) REFERENCES reg(id) ON UPDATE CASCADE ON DELETE CASCADE;
	ALTER TABLE atcon_history ADD CONSTRAINT atcon_history_userid_fk FOREIGN KEY (userid) REFERENCES perinfo(id) ON UPDATE CASCADE ON DELETE CASCADE;

INSERT INTO atcon_history(id, logdate, tid, userid, regid, action, notes)
SELECT b.id, b.date, t.id, IFNULL(t.userid, 2), r.id, action, comment
FROM atcon_badge b
JOIN atcon a ON (b.atconId = a.id)
JOIN reg r ON (r.id = b.badgeId)
JOIN transaction t ON (t.id = a.transid)
WHERE action in ('attach', 'notes');

INSERT INTO atcon_history(id, logdate, tid, userid, regid, action, notes)
SELECT b.id, b.date, t.id, IFNULL(t.userid, 2), r.id, 'print', comment
FROM atcon_badge b
JOIN atcon a ON (b.atconId = a.id)
JOIN reg r ON (r.id = b.badgeId)
JOIN transaction t ON (t.id = a.transid)
WHERE action in ('pickup');

DROP TABLE atcon_badge;
DROP TABLE atcon;

INSERT INTO patchLog(id, name) values(3, 'Atcon History');

