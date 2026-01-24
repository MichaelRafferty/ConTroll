/* p12_reg_complete_trans.sql
/* add field complete_trans to reg table to aid in producing receipts */

ALTER TABLE reg ADD COLUMN complete_trans int AFTER create_trans;
ALTER TABLE reg ADD CONSTRAINT reg_complete_fk FOREIGN KEY (complete_trans) REFERENCES transaction (id)  ON UPDATE CASCADE;

/* try change master to populate it from existing data */
/* first: exact matches */
UPDATE reg r
JOIN transaction t ON (
    (t.complete_date = r.change_date OR t.complete_date = r.create_date OR t.create_date = r.create_date)
    AND t.price > 0 AND t.price + t.couponDiscount = t.paid
    AND r.price > 0 AND r.price + r.couponDiscount = r.paid
    AND (t.perid = r.perid OR t.newperid = r.newperid)
    AND t.conid = r.conid
    )
SET r.complete_trans = t.id
WHERE r.complete_trans IS NULL AND t.complete_date IS NOT NULL;

/* now the free ones, who don't have paid transactions */
UPDATE reg r
JOIN transaction t ON (r.conid = t.conid AND t.price = 0 AND r.price = 0 AND (t.perid = r.perid OR t.newperid = r.newperid))
SET r.complete_trans = t.id
WHERE r.complete_trans IS NULL;

/* now the ones that are part of a multi-reg transaction */
UPDATE reg r
    JOIN (SELECT r.id, count(*) occurs
          FROM reg r
                   JOIN transaction t ON (r.create_trans = t.id)
                   JOIN transaction t1 ON (t1.conid = r.conid and t1.perid = r.perid and t1.paid > 0 )
          WHERE r.complete_trans IS NULL AND (r.paid + r.couponDiscount) = r.price AND r.price > 0
          GROUP BY r.id
          HAVING count(*) = 1) ra ON (ra.id =r.id)
    JOIN transaction t ON (r.create_trans = t.id)
    JOIN transaction t1 ON (t1.conid = r.conid and t1.perid = r.perid and t1.paid > 0 )
SET r.complete_trans = t1.id
WHERE r.complete_trans IS NULL AND (r.paid + r.couponDiscount) = r.price AND r.price > 0;

/* now the other items in the transaction for a particular paid transaction */
UPDATE reg r1
JOIN reg r2 ON (r1.create_trans = r2.create_trans)
SET  r1.complete_trans = r2.complete_trans
WHERE r1.complete_trans IS NULL AND r2.complete_trans IS NOT NULL;

/* and for the prior ones where the date stamp just doesn't match */
UPDATE reg r
    JOIN transaction t ON (r.create_trans = t.id)
    JOIN payments p ON (t.id = p.transid)
SET r.complete_trans = t.id
WHERE r.complete_trans IS NULL AND r.price > 0 AND r.price = (r.paid + r.couponDiscount) AND p.amount >= r.paid;

INSERT INTO patchLog(id, name) values(12, 'reg_complete_trans');
