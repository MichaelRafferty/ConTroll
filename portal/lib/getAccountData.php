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
WITH pn AS (
    SELECT id AS memberId, managedBy, NULL AS managedByNew,
    CASE 
        WHEN badge_name IS NULL OR badge_name = '' THEN TRIM(REGEXP_REPLACE(CONCAT(IFNULL(first_name, ''),' ', IFNULL(last_name, '')) , '  *', ' ')) 
        ELSE badge_name 
    END AS badge_name,
    TRIM(REGEXP_REPLACE(CONCAT(IFNULL(first_name, ''),' ', IFNULL(middle_name, ''), ' ', IFNULL(last_name, ''), ' ', IFNULL(suffix, '')), '  *', ' ')) AS fullname
    FROM perinfo
), nn AS (
    SELECT id AS memberId, managedBy, managedByNew,
    CASE 
        WHEN badge_name IS NULL OR badge_name = '' THEN TRIM(REGEXP_REPLACE(CONCAT(IFNULL(first_name, ''),' ', IFNULL(last_name, '')) , '  *', ' ')) 
        ELSE badge_name 
    END AS badge_name,
    TRIM(REGEXP_REPLACE(CONCAT(IFNULL(first_name, ''),' ', IFNULL(middle_name, ''), ' ', IFNULL(last_name, ''), ' ', IFNULL(suffix, '')), '  *', ' ')) AS fullname
    FROM newperson
), mems AS (
    SELECT t.id, r.create_date, r.id as regId, r.memId, r.conid, r.status, r.price, r.paid, r.complete_trans, r.couponDiscount, m.label, m.memAge, m.memType, m.memCategory,
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
        END AS memberId
    FROM transaction t
    JOIN reg r ON t.id = r.create_trans
    JOIN memLabel m ON m.id = r.memId
    LEFT OUTER JOIN pn ON pn.memberId = r.perid AND (pn.managedBy = ? OR pn.memberId = ?)
    LEFT OUTER JOIN nn ON nn.memberId = r.newperid
    WHERE status $statusCheck AND t.perid = ? AND t.conid = ?
    UNION
    SELECT t.id, r.create_date, r.id AS regId, r.memId, r.conid, r.status, r.price, r.paid, r.complete_trans, r.couponDiscount, m.label, m.memAge, m.memType, m.memCategory, nn.managedBy, nn.managedByNew, nn.badge_name, nn.fullname, nn.memberId    
    FROM transaction t
    JOIN reg r ON t.id = r.create_trans
    JOIN memLabel m ON m.id = r.memId
    JOIN nn ON nn.memberId = r.newperid
    WHERE status $statusCheck AND t.perid = ? AND t.conid = ?
)
SELECT DISTINCT *
FROM mems
ORDER BY memberId, create_date
EOS;
        $membershipsR = dbSafeQuery($membershipsQ, 'iiiiii', array($personId, $personId, $personId, $conid,$personId, $conid));
    } else {
        $membershipsQ = <<<EOS
SELECT t.id, r.create_date, r.id AS regId, r.memId, r.conid, r.status, r.price, r.paid, r.complete_trans, r.couponDiscount, m.label, m.memAge, m.memType, m.memCategory, p.managedBy, p.managedByNew,
    CASE 
        WHEN p.badge_name IS NULL OR p.badge_name = '' THEN TRIM(REGEXP_REPLACE(CONCAT(IFNULL(p.first_name, ''),' ', IFNULL(p.last_name, '')) , '  *', ' ')) 
        ELSE p.badge_name
    END AS badge_name, p.id AS memberId,
    TRIM(REGEXP_REPLACE(CONCAT(IFNULL(p.first_name, ''),' ', IFNULL(p.middle_name, ''), ' ', IFNULL(p.last_name, ''), ' ', IFNULL(p.suffix, '')), '  *', ' ')) AS fullname
FROM transaction t
JOIN reg r ON t.id = r.create_trans
JOIN memLabel m ON m.id = r.memId
JOIN newperson p ON p.id = r.newperid
WHERE status $statusCheck AND t.newperid = ? AND t.conid = ?
ORDER BY memberId ASC, create_date
EOS;
        $membershipsR = dbSafeQuery($membershipsQ, 'ii', array($personId, $conid));
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