<?php
// library AJAX Processor: reg_findRecord.php
// Balticon Registration System
// Author: Syd Weinstein
// Retrieve perinfo and reg records for the Find and Add tabs

require_once '../lib/base.php';

$check_auth = google_init('ajax');
$perm = 'registration';

$response = array('post' => $_POST, 'get' => $_GET, 'perm' => $perm);

if ($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    RenderErrorAjax('Authentication Failed');
    exit();
}

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$con = get_conf('con');
$conid = $con['id'];
$ajax_request_action = '';
if ($_POST && $_POST['ajax_request_action']) {
    $ajax_request_action = $_POST['ajax_request_action'];
}
if ($ajax_request_action != 'findRecord') {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}

// findRecord:
// load all perinfo/reg records matching the search string or unpaid if that flag is passed
$find_type = $_POST['find_type'];
$name_search = $_POST['name_search'];

$response['find_type'] = $find_type;
$response['name_search'] = $name_search;
$perinfo = [];
$membership = [];-

$limit = 99999999;
if ($find_type == 'unpaid') {
//
// Find Unpaid on latest transaction ID for those records
//
    $withClauseUnpaid = <<<EOS
WITH unpaids AS (
/* first the unpaid transactions from regs with their create_trans */
SELECT r.id, create_trans as tid
FROM reg r
JOIN memList m ON (m.id = r.memId)
WHERE r.price != (r.paid + r.couponDiscount) AND (r.conid = ? OR (r.conid = ? AND m.memCategory in ('yearahead', 'rollover')))
), tids AS (
/* add in unpaids from transactions in attach records in reg_history */
SELECT u.id AS regid, CASE WHEN u.tid > IFNULL(h.tid, -999) THEN u.tid ELSE h.tid END AS tid
FROM unpaids u
LEFT OUTER JOIN reg_history h ON (h.regid = u.id AND h.action = 'attach')
), maxtids AS (
/* find the most recent transaction (highest number) across each reg and the selected list of transactions */
SELECT regid, MAX(tid) AS tid
FROM tids
GROUP BY regid
), tidlist AS (
/* and get each tid only once */
SELECT DISTINCT tid 
FROM maxtids
), perids AS (
/* now get all the perinfo ids that are mentioned in each of those tid records, from both reg, and from reg_history */
SELECT perid 
FROM reg r
JOIN tidlist t ON (t.tid = r.create_trans)
UNION SELECT perid 
FROM reg r
JOIN reg_history h on (h.regid = r.id)
JOIN tidlist t ON (t.tid = h.tid)
), uniqueperids AS (
SELECT DISTINCT perid
FROM perids
)
EOS;
    $unpaidSQLP = <<<EOS
$withClauseUnpaid
SELECT DISTINCT u.perid, IFNULL(p.first_name, '') as first_name, IFNULL(p.middle_name, '') as middle_name, IFNULL(p.last_name, '') as last_name,
    IFNULL(p.suffix, '') as suffix, IFNULL(p.legalName, '') as legalName,
    p.badge_name, IFNULL(p.address, '') as address_1, IFNULL(p.addr_2, '') as address_2, IFNULL(p.city, '') AS city,
    IFNULL(p.state, '') AS state, IFNULL(p.zip, '') as postal_code, IFNULL(p.country, '') as country, IFNULL(p.email_addr, '') as email_addr,
    IFNULL(p.phone, '') as phone, p.share_reg_ok, p.contact_ok, p.active, p.banned,
    CASE 
        WHEN IFNULL(p.last_name, '') != '' THEN
            TRIM(REGEXP_REPLACE(CONCAT(IFNULL(p.last_name, ''), ', ', IFNULL(p.first_name, ''),' ', IFNULL(p.middle_name, ''), ' ', IFNULL(p.suffix, '')), ' *', ' '))
        ELSE
            TRIM(REGEXP_REPLACE(CONCAT(IFNULL(p.first_name, ''),' ', IFNULL(p.middle_name, ''), ' ', IFNULL(p.suffix, '')), '  *', ' '))
        END AS fullname,
    p.open_notes
FROM uniqueperids u
JOIN perinfo p ON (u.perid = p.id)
ORDER BY last_name, first_name;
EOS;
    $unpaidSQLM = <<<EOS
$withClauseUnpaid
, ridtid AS (
SELECT r.id as regid, create_trans as tid
FROM uniqueperids p
JOIN reg r ON (r.perid = p.perid)
UNION
SELECT h.regid, h.tid
FROM uniqueperids p
JOIN reg r ON (r.perid = p.perid)
JOIN reg_history h ON (r.id = h.regid AND h.action = 'attach')
), uniqrids AS (
SELECT regid, MAX(tid) AS tid
FROM ridtid
GROUP BY regid
), notes AS (
SELECT h.regid, GROUP_CONCAT(CONCAT(h.userid, '@', h.logdate, ': ', h.notes) SEPARATOR '\n') AS reg_notes, COUNT(*) AS reg_notes_count
FROM unpaids m
JOIN reg_history h ON (m.id = h.regid)
WHERE h.action = 'notes'
GROUP BY h.regid
), printcount AS (
SELECT h.regid, COUNT(*) printcount
FROM unpaids m
JOIN reg_history h ON (m.id = h.regid)
WHERE h.action = 'print'
GROUP BY h.regid
)
SELECT DISTINCT r.perid, r.id as regid, m.conid, r.price, r.couponDiscount, r.paid, r.paid AS priorPaid, r.create_date, u.tid, r.memId, IFNULL(pc.printcount, 0) AS printcount,
                n.reg_notes, n.reg_notes_count, m.memCategory, m.memType, m.memAge, m.shortname,
                CASE WHEN m.conid = ? THEN m.label ELSE concat(m.conid, ' ', m.label) END AS label, r.coupon
FROM uniqrids u
JOIN reg r ON (r.id = u.regid)
JOIN memLabel m ON (r.memId = m.id)
LEFT OUTER JOIN printcount pc ON (r.id = pc.regid)
LEFT OUTER JOIN notes n ON (r.id = n.regid)
WHERE (r.conid = ? OR (r.conid = ? AND m.memCategory in ('yearahead', 'rollover')))
ORDER BY create_date;
EOS;
    //web_error_log($unpaidSQLM);
    $rp = dbSafeQuery($unpaidSQLP, 'ii', array($conid, $conid + 1));
    $rm = dbSafeQuery($unpaidSQLM, 'iiiii', array($conid, $conid + 1, $conid, $conid, $conid + 1));
} else if (is_numeric($name_search)) {
//
// this is perid, or transid
//
    // first can we tell if it's a perid or a tid?
    $overlapQ = <<<EOS
SELECT 'p' AS which, id
FROM perinfo p
WHERE p.id = ?
UNION SELECT 't' AS which, id
FROM transaction t 
WHERE t.id = ? AND t.conid in (?, ?);
EOS;
    $overlapR = dbSafeQuery($overlapQ, 'iiii', array($name_search, $name_search, $conid, $conid + 1));
    $found_perid = false;
    $found_tid = false;
    while ($overlapL = $overlapR->fetch_assoc()) {
        if ($overlapL['which'] == 'p')
            $found_perid = true;
        if ($overlapL['which'] == 't')
            $found_tid = true;
    }
    $overlapR->free();

    if ($found_perid == false && $found_tid == false) {
        // nothing to search for, return zero records found (early exit)
        $response['message'] = '0 memberships found';
        $response['perinfo'] = $perinfo;
        $response['membership'] = $membership;
        ajaxSuccess($response);
        exit();
    }
    // tid has higher precidence than perid in the matching
    if ($found_tid) {
        // pull all the matching regs for this transid for this period
        $withClause = <<<EOS
WITH regids AS (
    /* first reg ids for this create transaction as specified as a number */
    SELECT r.id AS regid, create_trans as tid
    FROM reg r
    JOIN memLabel m ON (r.memId = m.id)
    WHERE create_trans = ? AND (r.conid = ? OR (r.conid = ? AND m.memCategory in ('yearahead', 'rollover')))
    /* then add in reg ids for this attach transaction */
    UNION SELECT regid, tid
    FROM reg_history h
    JOIN reg r ON (r.id = h.regid)
    JOIN memLabel m ON (r.memId = m.id)
    WHERE tid = ? AND h.action = 'attach' AND (r.conid = ? OR (r.conid = ? AND m.memCategory in ('yearahead', 'rollover')))
)
EOS;
        $searchSQLP = <<<EOS
$withClause
SELECT DISTINCT p.id AS perid, IFNULL(p.first_name, '') as first_name, IFNULL(p.middle_name, '') as middle_name, IFNULL(p.last_name, '') as last_name,
    IFNULL(p.suffix, '') as suffix, IFNULL(p.legalName, '') as legalName,
    p.badge_name, IFNULL(p.address, '') as address_1, IFNULL(p.addr_2, '') as address_2, IFNULL(p.city, '') AS city,
    IFNULL(p.state, '') AS state, IFNULL(p.zip, '') as postal_code, IFNULL(p.country, '') as country, IFNULL(p.email_addr, '') as email_addr,
    IFNULL(p.phone, '') as phone, p.share_reg_ok, p.contact_ok, p.active, p.banned,
    CASE 
        WHEN IFNULL(p.last_name, '') != '' THEN
            TRIM(REGEXP_REPLACE(CONCAT(IFNULL(p.last_name, ''), ', ', IFNULL(p.first_name, ''),' ', IFNULL(p.middle_name, ''), ' ', IFNULL(p.suffix, '')), ' *', ' '))
        ELSE
            TRIM(REGEXP_REPLACE(CONCAT(IFNULL(p.first_name, ''),' ', IFNULL(p.middle_name, ''), ' ', IFNULL(p.suffix, '')), '  *', ' '))
        END AS fullname,
    p.open_notes
FROM regids r
JOIN reg r1 ON (r1.id = r.regid)
JOIN perinfo p ON (p.id = r1.perid)
ORDER BY last_name, first_name;
EOS;
        //web_error_log($searchSQLP);
        $searchSQLM = <<<EOS
$withClause
, notes AS (
SELECT h.regid, GROUP_CONCAT(CONCAT(h.userid, '@', h.logdate, ': ', h.notes) SEPARATOR '\n') AS reg_notes, COUNT(*) AS reg_notes_count
FROM regids m
JOIN reg_history h ON (m.regid = h.regid)
WHERE h.action = 'notes'
GROUP BY h.regid
), printcount AS (
SELECT h.regid, COUNT(*) printcount
FROM regids m
JOIN reg_history h ON (m.regid = h.regid)
WHERE h.action = 'print'
GROUP BY h.regid
), attachcount AS (
SELECT h.regid, COUNT(*) attachcount
FROM regids m
JOIN reg_history h ON (m.regid = h.regid)
WHERE h.action = 'attach'
GROUP BY h.regid
)
SELECT DISTINCT r1.perid, r1.id as regid, m.conid, r1.price, r1.paid, r1.paid AS priorPaid, r1.create_date, IFNULL(r1.create_trans, -1) as tid, r1.memId, IFNULL(pc.printcount, 0) AS printcount,
                IFNULL(ac.attachcount, 0) AS attachcount, n.reg_notes, n.reg_notes_count, m.memCategory, m.memType, m.memAge, m.shortname, rs.tid as rstid,
                CASE WHEN m.conid = ? THEN m.label ELSE concat(m.conid, ' ', m.label) END AS label
FROM regids rs
JOIN reg r ON (rs.regid = r.id)
JOIN perinfo p ON (p.id = r.perid)
JOIN reg r1 ON (r1.perid = r.perid)
JOIN memLabel m ON (r1.memId = m.id)
LEFT OUTER JOIN printcount pc ON (r1.id = pc.regid)
LEFT OUTER JOIN attachcount ac ON (r1.id = ac.regid)
LEFT OUTER JOIN notes n ON (r1.id = n.regid)
WHERE (r1.conid = ? OR (r1.conid = ? AND m.memCategory in ('yearahead', 'rollover')))
ORDER BY create_date;
EOS;
        //web_error_log($searchSQLM);
        $rp = dbSafeQuery($searchSQLP, 'iiiiii', array($name_search, $conid, $conid + 1, $name_search, $conid, $conid + 1));
        $rm = dbSafeQuery($searchSQLM, 'iiiiiiiii', array($name_search, $conid, $conid + 1, $name_search, $conid, $conid + 1, $conid, $conid, $conid + 1));
    } else if ($found_perid) {
        // pull all the matching regs for this perid for this period
        $searchSQLP = <<<EOS
SELECT DISTINCT p.id AS perid, IFNULL(p.first_name, '') as first_name, IFNULL(p.middle_name, '') as middle_name, IFNULL(p.last_name, '') as last_name,
    IFNULL(p.suffix, '') as suffix, IFNULL(p.legalName, '') as legalName, p.badge_name,
    IFNULL(p.address, '') as address_1, IFNULL(p.addr_2, '') as address_2, IFNULL(p.city, '') AS city,
    IFNULL(p.state, '') AS state, IFNULL(p.zip, '') as postal_code, IFNULL(p.country, '') as country, IFNULL(p.email_addr, '') as email_addr,
    IFNULL(p.phone, '') as phone, p.share_reg_ok, p.contact_ok, p.active, p.banned,
    CASE 
        WHEN IFNULL(p.last_name, '') != '' THEN
            TRIM(REGEXP_REPLACE(CONCAT(IFNULL(p.last_name, ''), ', ', IFNULL(p.first_name, ''),' ', IFNULL(p.middle_name, ''), ' ', IFNULL(p.suffix, '')), ' *', ' '))
        ELSE
            TRIM(REGEXP_REPLACE(CONCAT(IFNULL(p.first_name, ''),' ', IFNULL(p.middle_name, ''), ' ', IFNULL(p.suffix, '')), '  *', ' '))
        END AS fullname,
    p.open_notes
FROM perinfo p
WHERE id = ?;
EOS;
        $searchSQLM = <<<EOS
WITH regids AS (
    SELECT r.id AS regid
    FROM reg r
    JOIN memList m ON (r.memId = m.id)
    WHERE (r.conid = ? OR (r.conid = ? AND m.memCategory in ('yearahead', 'rollover'))) AND r.perid = ?
), notes AS (
SELECT h.regid, GROUP_CONCAT(CONCAT(h.userid, '@', h.logdate, ': ', h.notes) SEPARATOR '\n') AS reg_notes, COUNT(*) AS reg_notes_count
FROM regids m
JOIN reg_history h ON (m.regid = h.regid)
WHERE h.action = 'notes'
GROUP BY h.regid
), printcount AS (
SELECT h.regid, COUNT(*) printcount
FROM regids m
JOIN reg_history h ON (m.regid = h.regid)
WHERE h.action = 'print'
GROUP BY h.regid
), attachcount AS (
SELECT h.regid, COUNT(*) attachcount
FROM regids m
JOIN reg_history h ON (m.regid = h.regid)
WHERE h.action = 'attach'
GROUP BY h.regid
)
SELECT DISTINCT r.perid, r.id as regid, m.conid, r.price, r.paid, r.paid AS priorPaid, r.create_date, IFNULL(r.create_trans, -1) as tid, r.memId, IFNULL(pc.printcount, 0) AS printcount,
                IFNULL(ac.attachcount, 0) AS attachcount, n.reg_notes, n.reg_notes_count, m.memCategory, m.memType, m.memAge, m.shortname, r.create_trans as rstid,
                CASE WHEN m.conid = ? THEN m.label ELSE concat(m.conid, ' ', m.label) END AS label
FROM regids rs
JOIN reg r ON (rs.regid = r.id)
JOIN perinfo p ON (p.id = r.perid)
JOIN memLabel m ON (r.memId = m.id)
LEFT OUTER JOIN printcount pc ON (r.id = pc.regid)
LEFT OUTER JOIN attachcount ac ON (r.id = ac.regid)
LEFT OUTER JOIN notes n ON (r.id = n.regid)
ORDER BY create_date;
EOS;
        $rp = dbSafeQuery($searchSQLP, 'i', array($name_search));
        $rm = dbSafeQuery($searchSQLM, 'iiii', array($conid, $conid + 1, $name_search, $conid));
    }
} else {
//
// this is the string search portion as the field is alphanumeric
//
    // name match
    $limit = 50; // only return 50 people's memberships
    $name_search = mb_strtolower('%' . preg_replace('/ +/', '%', $name_search) . '%');
    //web_error_log("match string: $name_search");
    $searchSQLP = <<<EOS
SELECT DISTINCT p.id AS perid, IFNULL(p.first_name, '') as first_name, IFNULL(p.middle_name, '') as middle_name, IFNULL(p.last_name, '') as last_name,
    IFNULL(p.suffix, '') as suffix, IFNULL(p.legalName, '') as legalName, 
    p.badge_name, IFNULL(p.address, '') as address_1, IFNULL(p.addr_2, '') as address_2, IFNULL(p.city, '') AS city,
    IFNULL(p.state, '') AS state, IFNULL(p.zip, '') as postal_code, IFNULL(p.country, '') as country, IFNULL(p.email_addr, '') as email_addr, IFNULL(p.phone, '') as phone,
    p.share_reg_ok, p.contact_ok, p.active, p.banned,
    CASE 
        WHEN IFNULL(p.last_name, '') != '' THEN
            TRIM(REGEXP_REPLACE(CONCAT(IFNULL(p.last_name, ''), ', ', IFNULL(p.first_name, ''),' ', IFNULL(p.middle_name, ''), ' ', IFNULL(p.suffix, '')), ' *', ' '))
        ELSE
            TRIM(REGEXP_REPLACE(CONCAT(IFNULL(p.first_name, ''),' ', IFNULL(p.middle_name, ''), ' ', IFNULL(p.suffix, '')), '  *', ' '))
        END AS fullname,
    p.open_notes
FROM perinfo p
WHERE (LOWER(TRIM(CONCAT_WS(' ', TRIM(CONCAT_WS(' ', IFNULL(first_name, ''), IFNULL(middle_name, ''))), IFNULL(last_name, '')))) LIKE ? OR LOWER(badge_name) LIKE ? OR LOWER(legalName) LIKE ? OR
       LOWER(email_addr) LIKE ? OR LOWER(address) LIKE ? OR LOWER(addr_2) LIKE ?)
ORDER BY last_name, first_name LIMIT $limit;
EOS;
    $searchSQLM = <<<EOS
WITH limitedp AS (
/* first get the perid's for this name search */
    SELECT DISTINCT p.id, IFNULL(p.first_name, '') as first_name, IFNULL(p.last_name, '') as last_name
    FROM perinfo p
    WHERE (LOWER(TRIM(CONCAT_WS(' ', TRIM(CONCAT_WS(' ', IFNULL(first_name, ''), IFNULL(middle_name, ''))), IFNULL(last_name, '')))) LIKE ? OR LOWER(badge_name) LIKE ? OR LOWER(legalName) LIKE ? OR
       LOWER(email_addr) LIKE ? OR LOWER(address) LIKE ? OR LOWER(addr_2) LIKE ?)
    ORDER BY last_name, first_name LIMIT $limit
), regids AS (
SELECT r.id AS regid
FROM limitedp p
JOIN reg r ON (r.perid = p.id)
JOIN memList m ON (r.memId = m.id)
WHERE (r.conid = ? OR (r.conid = ? AND m.memCategory in ('yearahead', 'rollover')))
), regtid AS (
SELECT r.id as regid, IFNULL(create_trans, -1) as tid
FROM regids rs
JOIN reg r ON (r.id = rs.regid)
UNION
SELECT h.regid, IFNULL(h.tid, -1) AS tid
FROM regids rs
JOIN reg_history h ON (h.regid = rs.regid)
), maxtids AS (
SELECT regid, MAX(tid) as tid
FROM regtid
GROUP BY regid
), notes AS (
SELECT h.regid, GROUP_CONCAT(CONCAT(h.userid, '@', h.logdate, ': ', h.notes) SEPARATOR '\n') AS reg_notes, COUNT(*) AS reg_notes_count
FROM maxtids m
JOIN reg_history h ON (m.regid = h.regid)
WHERE h.action = 'notes'
GROUP BY h.regid
), printcount AS (
SELECT h.regid, COUNT(*) printcount
FROM maxtids m
JOIN reg_history h ON (m.regid = h.regid)
WHERE h.action = 'print'
GROUP BY h.regid
)
SELECT DISTINCT r.perid, t.regid, m.conid, r.price, r.couponDiscount, r.paid, r.paid AS priorPaid,
                r.create_date, t.tid, r.memId, IFNULL(pc.printcount, 0) AS printcount,
                n.reg_notes, n.reg_notes_count, m.memCategory, m.memType, m.memAge, m.shortname,
                CASE WHEN m.conid = ? THEN m.label ELSE concat(m.conid, ' ', m.label) END AS label, r.coupon      
FROM maxtids t
JOIN reg r ON (r.id = t.regid)
JOIN limitedp p ON (p.id = r.perid)
JOIN memLabel m ON (r.memId = m.id)
LEFT OUTER JOIN notes n ON (r.id = n.regid)
LEFT OUTER JOIN printcount pc ON (r.id = pc.regid)
ORDER BY create_date;
EOS;
    $rp = dbSafeQuery($searchSQLP, 'ssssss', array($name_search, $name_search, $name_search, $name_search, $name_search, $name_search));
    $rm = dbSafeQuery($searchSQLM, 'ssssssiii', array($name_search, $name_search, $name_search, $name_search, $name_search, $name_search, $conid, $conid + 1, $conid));
}

$perinfo = [];
$index = 0;
$perids = [];
$num_rows = $rp->num_rows;
while ($l = $rp->fetch_assoc()) {
    $l['index'] = $index;
    $perinfo[] = $l;
    $perids[$l['perid']] = $index;
    $index++;
}
$response['perinfo'] = $perinfo;
if ($num_rows >= $limit) {
    $response['warn'] = "$num_rows memberships found, limited to $limit, use different search criteria to refine your search.";
} else {
    $response['message'] = "$num_rows memberships found";
}
$rp->free();

$membership = [];
$index = 0;
while ($l = $rm->fetch_assoc()) {
    $l['pindex'] = $perids[$l['perid']];
    $l['index'] = $index;
    $membership[] = $l;
    $index++;
}
$response['membership'] = $membership;
$rm->free();
ajaxSuccess($response);
