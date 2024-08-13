/*
 * Make a true regHistory trigger table and have it track reg
 * Add priorRegId entry to reg to track rollovers and transfers
 * Rename reg_history to regActions
 */

ALTER TABLE reg ADD COLUMN priorRegId int DEFAULT NULL AFTER oldperid;
ALTER TABLE reg ADD COLUMN updatedBy int DEFAULT NULL AFTER create_user;
ALTER TABLE reg ADD CONSTRAINT FOREIGN KEY reg_priorRegId_fk(priorRegId) REFERENCES reg(id);

CREATE TABLE regActions (
    id int NOT NULL AUTO_INCREMENT,
    logdate timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    userid int NOT NULL,
    tid int NULL,
    regid int NOT NULL,
    action enum('attach','print','notes','transfer','rollover','overpayment','refund') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    notes varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    PRIMARY KEY (id),
    KEY regActions_tid_fk (tid),
    KEY regActions_regid_fk (regid),
    KEY regActions_userid_fk (userid),
    CONSTRAINT regActions_regid_fk FOREIGN KEY (regid) REFERENCES reg (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT regActions_tid_fk FOREIGN KEY (tid) REFERENCES transaction (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT regActions_userid_fk FOREIGN KEY (userid) REFERENCES perinfo (id) ON DELETE CASCADE ON UPDATE CASCADE
);

INSERT INTO regActions(id, logdate, userid, tid, regid, action, notes)
SELECT id, logdate, userid, tid, regid, action, notes
FROM reg_history;

DROP TABLE IF EXISTS regHistory;
CREATE TABLE regHistory (
    historyId      int                                                                                                                   NOT NULL AUTO_INCREMENT,
    id             int                                                                                                                   NOT NULL,
    conid          int DEFAULT NULL,
    perid          int DEFAULT NULL,
    newperid       int DEFAULT NULL,
    oldperid       int DEFAULT NULL,
    priorRegId     int DEFAULT NULL,
    create_date    datetime                                                                                                              NULL,
    change_date    timestamp                                                                                                             NULL,
    pickup_date    datetime                                                                                                              NULL,
    price          decimal(8, 2)                                                                                                         NULL,
    couponDiscount decimal(8, 2)                                                                                                         NULL,
    paid           decimal(8, 2)                                                                                                         NULL,
    create_trans   int                                                                                                                   NULL,
    complete_trans int                                                                                                                   NULL,
    locked         enum ('N','Y') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci                                                       NULL,
    create_user    int DEFAULT NULL,
    updatedBy      int DEFAULT NULL,
    memId          int                                                                                                                   NULL,
    coupon         int                                                                                                                   NULL,
    planId         int                                                                                                                   NULL,
    printable      enum ('N','Y') COLLATE utf8mb4_general_ci                                                                             NULL,
    status         enum ('unpaid','plan','paid','cancelled','refunded','transfered','upgraded','rolled-over') COLLATE utf8mb4_general_ci NULL,
    PRIMARY KEY (historyId)
);

DROP TRIGGER IF EXISTS reg_update;
DELIMITER ;;
CREATE DEFINER=CURRENT_USER TRIGGER `reg_update` BEFORE UPDATE ON `reg` FOR EACH ROW BEGIN
    IF (OLD.id != NEW.id OR OLD.conid != NEW.conid OR OLD.perid != NEW.perid OR OLD.newperid != NEW.newperid
        OR OLD.oldperid != NEW.oldperid OR OLD.priorRegId != NEW.priorRegId OR OLD.create_date != NEW.create_date
        OR OLD.change_date != NEW.change_date OR OLD.pickup_date != NEW.pickup_date OR OLD.price != NEW.price
        OR OLD.couponDiscount != NEW.couponDiscount OR OLD.paid != NEW.paid OR OLD.create_trans != NEW.create_trans
        OR OLD.complete_trans != NEW.complete_trans OR OLD.locked != NEW.locked OR OLD.create_user != NEW.create_user
        OR OLD.updatedBy != NEW.updatedBy OR OLD.memId != NEW.memId OR OLD.coupon != NEW.coupon
        OR OLD.planId != NEW.planId OR OLD.printable != NEW.printable OR OLD.status != NEW.status)
    THEN
        INSERT INTO regHistory(id, conid, perid, newperid, oldperid, create_date, change_date, pickup_date, price, couponDiscount,
                               paid, create_trans, complete_trans, locked, create_user, updatedBy, memId, coupon, planId, printable, status)
            VALUES (OLD.id, OLD.conid, OLD.perid, OLD.newperid, OLD.oldperid, OLD.create_date, OLD.change_date, OLD.pickup_date,
                    OLD.price, OLD.couponDiscount, OLD.paid, OLD.create_trans, OLD.complete_trans, OLD.locked, OLD.create_user,
                    OLD.updatedBy, OLD.memId, OLD.coupon, OLD.planId, OLD.printable, OLD.status);
    END IF;
END;;
DELIMITER ;

INSERT INTO patchLog(id, name) values(32, 'regHistory');
