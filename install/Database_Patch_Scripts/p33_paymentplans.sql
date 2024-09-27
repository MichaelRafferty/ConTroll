/*
 * updates to payment plans to assist with management
 */

CREATE TABLE payorPlanReminders (
    id int NOT NULL AUTO_INCREMENT,
    sentDate timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    perid int NOT NULL,
    payorPlanId int NOT NULL,
    conid int NOT NULL,
    emailAddr varchar(256) NOT NULL,
    dueDate datetime NOT NULL,
    minAmt decimal(8,2) NOT NULL,
    PRIMARY KEY (id)
);

ALTER TABLE payorPlanReminders ADD CONSTRAINT FOREIGN KEY fk_ppr_perid(perid) REFERENCES perinfo(id) ON UPDATE CASCADE;
ALTER TABLE payorPlanReminders ADD CONSTRAINT FOREIGN KEY fk_ppr_payorPlan(payorPlanId) REFERENCES payorPlans(id) ON UPDATE CASCADE;
ALTER TABLE payorPlanReminders ADD CONSTRAINT FOREIGN KEY fk_ppr_conid(conid) REFERENCES conlist(id) ON UPDATE CASCADE;

ALTER TABLE payorPlans ADD COLUMN conid int DEFAULT NULL AFTER planId;
UPDATE payorPlans SET conid = FILL-IN-YOUR-CURRENT-CONID-HERE-BEFORE-RUNNING-THIS-SCRIPT;

INSERT INTO patchLog(id, name) values(33, 'paymentplans');
