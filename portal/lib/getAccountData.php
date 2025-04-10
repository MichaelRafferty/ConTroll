<?php
// getAccountRegistrations - get all of the registrations for this login id (account)

function getAccountRegistrations($personId, $personType, $conid, $getTypes = 'all') {
    switch ($getTypes) {
        case 'unpaid':
            $statusCheck = " = 'unpaid'";
            break;
        case 'plan':
            $statusCheck = " = 'plan'";
            break;
        default:
            $statusCheck = " IN ('unpaid', 'paid', 'plan', 'upgraded')";
    }

    if ($personType == 'p') {
        $membershipsQ = <<<EOS
WITH trans AS (
	SELECT id, create_date, complete_date, perid, newperid, conid
	FROM transaction
	WHERE perid = ?
), pn AS (
    SELECT p.id AS memberId, managedBy, NULL AS managedByNew, email_addr, phone,
        CASE 
            WHEN badge_name IS NULL OR badge_name = '' THEN TRIM(REGEXP_REPLACE(CONCAT(IFNULL(first_name, ''),' ', IFNULL(last_name, '')) , '  *', ' ')) 
            ELSE badge_name 
        END AS badge_name,
        TRIM(REGEXP_REPLACE(CONCAT(IFNULL(first_name, ''),' ', IFNULL(middle_name, ''), ' ', IFNULL(last_name, ''), ' ', IFNULL(suffix, '')), '  *', ' ')) AS fullname
    FROM trans t
    JOIN perinfo p ON p.id = t.perid
), nn AS (
    SELECT n.id AS memberId, managedBy, managedByNew, email_addr, phone,
        CASE
            WHEN badge_name IS NULL OR badge_name = '' THEN TRIM(REGEXP_REPLACE(CONCAT(IFNULL(first_name, ''),' ', IFNULL(last_name, '')) , '  *', ' ')) 
            ELSE badge_name 
        END AS badge_name,
    TRIM(REGEXP_REPLACE(CONCAT(IFNULL(first_name, ''),' ', IFNULL(middle_name, ''), ' ', IFNULL(last_name, ''), ' ', IFNULL(suffix, '')), '  *', ' ')) AS fullname
    FROM trans t
    JOIN newperson n ON n.id = t.newperid
    WHERE n.perid IS NULL
), mems AS (
    SELECT t.id, r.create_date, r.id as regId, r.memId, r.conid, r.status, r.price, r.paid, r.complete_trans, r.couponDiscount, r.perid, r.newperid,
        IFNULL(r.complete_trans, r.create_trans) AS sortTrans,
        IFNULL(tp.complete_date, t.create_date) AS transDate,
        m.label, m.memAge, m.memAge AS age, m.memType, m.memCategory, m.startdate, m.enddate, m.online, mC.taxable,
        CASE 
            WHEN pn.memberId IS NOT NULL THEN pn.managedBy
            WHEN nn.memberId IS NOT NULL THEN nn.managedBy
            ELSE NULL
        END AS managedBy,
        CASE 
            WHEN pn.memberId IS NOT NULL THEN pn.managedByNew
            WHEN nn.memberId IS NOT NULL THEN nn.managedByNew
            ELSE NULL
        END AS managedByNew,
        CASE 
            WHEN pn.memberId IS NOT NULL THEN pn.badge_name
            WHEN nn.memberid IS NOT NULL THEN nn.badge_name
            ELSE NULL
        END AS badge_name,
        CASE 
            WHEN pn.memberid IS NOT NULL THEN pn.fullname
            WHEN nn.memberId IS NOT NULL THEN nn.fullname
            ELSE NULL
        END AS fullname,
        CASE 
            WHEN pn.memberId IS NOT NULL THEN pn.memberId
            WHEN nn.memberId IS NOT NULL THEN nn.memberId
            ELSE NULL
        END AS memberId,
        CASE 
            WHEN pn.memberId IS NOT NULL THEN pn.email_addr
            WHEN nn.memberId IS NOT NULL THEN nn.email_addr
            ELSE NULL
        END AS email_addr,
        CASE 
            WHEN pn.memberId IS NOT NULL THEN pn.phone
            WHEN nn.memberId IS NOT NULL THEN nn.phone
            ELSE NULL
        END AS phone, 
        IFNULL(tp.perid, t.perid) AS transPerid,
        IFNULL(tp.newperid, t.newperid) AS transNewPerid
    FROM trans t
    JOIN reg r ON t.id = r.create_trans
    LEFT OUTER JOIN trans tp ON tp.id = r.complete_trans
    JOIN memLabel m ON m.id = r.memId
    JOIN memCategories mC ON m.memCategory = mC.memCategory
    LEFT OUTER JOIN pn ON pn.memberId = r.perid AND (pn.managedBy = ? OR pn.memberId = ?)
    LEFT OUTER JOIN nn ON nn.memberId = r.newperid
    WHERE (status $statusCheck OR (r.status = 'paid' AND r.complete_trans IS NULL)) AND (t.perid = ? OR tp.perid = ?) AND t.conid = ?
    UNION
    SELECT t.id, r.create_date, r.id AS regId, r.memId, r.conid, r.status, r.price, r.paid, r.complete_trans, r.couponDiscount, r.perid, r.newperid,
        CASE WHEN r.complete_trans IS NULL THEN r.create_trans ELSE r.complete_trans END AS sortTrans,
        CASE WHEN tp.complete_date IS NULL THEN t.create_date ELSE tp.complete_date END AS transDate,
        m.label, m.memAge, m.memAge AS age, m.memType, m.memCategory,  m.startdate, m.enddate, m.online, mC.taxable,
        nn.managedBy, nn.managedByNew, nn.badge_name, nn.fullname, nn.memberId, nn.email_addr, nn.phone,
        IFNULL(tp.perid, t.perid) AS transPerid,
        IFNULL(tp.newperid, t.newperid) AS transNewPerid
    FROM trans t
    JOIN reg r ON t.id = r.create_trans
    LEFT OUTER JOIN trans tp ON tp.id = r.complete_trans
    JOIN memLabel m ON m.id = r.memId
    JOIN memCategories mC ON m.memCategory = mC.memCategory
    JOIN nn ON nn.memberId = r.newperid
    WHERE (status $statusCheck OR (r.status = 'paid' AND r.complete_trans IS NULL)) AND (t.perid = ? OR tp.perid = ?) AND t.conid = ?
)
SELECT DISTINCT *
FROM mems
ORDER BY sortTrans, create_date, memberId
EOS;
        $membershipsR = dbSafeQuery($membershipsQ, 'iiiiiiiii', array($personId, $personId, $personId, $personId, $personId, $conid, $personId, $personId,
        $conid));
    } else {
        $membershipsQ = <<<EOS
WITH mems AS (
    SELECT t.id, r.create_date, r.id AS regId, r.memId, r.conid, r.status, r.price, r.paid, r.complete_trans, r.couponDiscount, r.perid, r.newperid,
    m.label, m.memAge, m.memAge AS age, m.memType, m.memCategory,  m.startdate, m.enddate, m.online, mC.taxable,
        p.managedBy, p.managedByNew,
        CASE WHEN r.complete_trans IS NULL THEN r.create_trans ELSE r.complete_trans END AS sortTrans,
        CASE WHEN tp.complete_date IS NULL THEN t.create_date ELSE tp.complete_date END AS transDate,
        CASE 
            WHEN p.badge_name IS NULL OR p.badge_name = '' THEN TRIM(REGEXP_REPLACE(CONCAT(IFNULL(p.first_name, ''),' ', IFNULL(p.last_name, '')) , '  *', ' ')) 
            ELSE p.badge_name
        END AS badge_name, p.id AS memberId, p.email_addr, p.phone,
        TRIM(REGEXP_REPLACE(CONCAT(IFNULL(p.first_name, ''),' ', IFNULL(p.middle_name, ''), ' ', IFNULL(p.last_name, ''), ' ',
            IFNULL(p.suffix, '')), '  *', ' ')) AS fullname,
        IFNULL(tp.perid, t.perid) AS transPerid
    FROM transaction t
    JOIN reg r ON t.id = r.create_trans
    LEFT OUTER JOIN transaction tp ON tp.id = r.complete_trans
    JOIN memLabel m ON m.id = r.memId
    JOIN memCategories mC ON m.memCategory = mC.memCategory
    JOIN newperson p ON p.id = r.newperid
    WHERE (status $statusCheck OR (r.status = 'paid' AND r.complete_trans IS NULL)) AND (t.newperid = ? OR tp.newperid = ?) AND t.conid = ?
    )
SELECT DISTINCT *
FROM mems
ORDER BY sortTrans, create_date, memberId
EOS;
        $membershipsR = dbSafeQuery($membershipsQ, 'iii', array($personId, $personId, $conid));
    }

    $memberships = [];
    if ($membershipsR !== false) {
        while ($membership = $membershipsR->fetch_assoc()) {
            if ($membership['fullname'] == null) {
                $membership['fullname'] = 'Name Redacted';
                $membership['badge_name'] = 'Name Redacted';
            }
            $memberships[] = $membership;
        }
        $membershipsR->free();
    }

    return $memberships;
}
