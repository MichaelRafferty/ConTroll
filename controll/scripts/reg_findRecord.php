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
$membership = [];
$policies = [];

$limit = 99999999;
$fieldListP = <<<EOS
SELECT DISTINCT p.id AS perid, TRIM(IFNULL(p.first_name, '')) AS first_name, TRIM(IFNULL(p.middle_name, '')) AS middle_name, 
    TRIM(IFNULL(p.last_name, '')) AS last_name, TRIM(IFNULL(p.suffix, '')) AS suffix, 
    TRIM(IFNULL(p.legalName, '')) AS legalName, TRIM(IFNULL(p.pronouns, '')) AS pronouns,
    p.badge_name, TRIM(IFNULL(p.address, '')) AS address_1, TRIM(IFNULL(p.addr_2, '')) AS address_2, 
    TRIM(IFNULL(p.city, '')) AS city, TRIM(IFNULL(p.state, '')) AS state, TRIM(IFNULL(p.zip, '')) AS postal_code, 
    IFNULL(p.country, '') as country, TRIM(IFNULL(p.email_addr, '')) AS email_addr,
    TRIM(IFNULL(p.phone, '')) as phone, p.active, p.banned,
    TRIM(REGEXP_REPLACE(CONCAT(IFNULL(p.first_name, ''),' ', IFNULL(p.middle_name, ''), ' ', IFNULL(p.last_name, ''), ' ',  
        IFNULL(p.suffix, '')), '  *', ' ')) AS fullName,
    p.open_notes
EOS;
$fieldListM = <<<EOS
, notes AS (
SELECT h.regid, GROUP_CONCAT(CONCAT(h.userid, '@', h.logdate, ': ', h.notes) SEPARATOR '\n') AS reg_notes, COUNT(*) AS reg_notes_count
FROM regids m
JOIN regActions h ON (m.regid = h.regid)
WHERE h.action = 'notes'
GROUP BY h.regid
), printcount AS (
SELECT h.regid, COUNT(*) printcount
FROM regids m
JOIN regActions h ON (m.regid = h.regid)
WHERE h.action = 'print'
GROUP BY h.regid
), attachcount AS (
SELECT h.regid, COUNT(*) attachcount
FROM regids m
JOIN regActions h ON (m.regid = h.regid)
WHERE h.action = 'attach'
GROUP BY h.regid
)
SELECT DISTINCT r1.perid, r1.id as regid, m.conid, r1.price, r1.paid, r1.paid AS priorPaid, r1.couponDiscount,
    r1.create_date, IFNULL(r1.create_trans, -1) as tid, r1.memId, IFNULL(pc.printcount, 0) AS printcount,
    IFNULL(ac.attachcount, 0) AS attachcount, n.reg_notes, n.reg_notes_count, m.memCategory, m.memType, m.memAge, m.shortname, rs.tid as rstid,
    CASE WHEN m.conid = ? THEN m.label ELSE concat(m.conid, ' ', m.label) END AS label
EOS;
$fieldListL = <<<EOS
SELECT DISTINCT p.id AS perid, mp.policy, mp.response
EOS;

$managerWith = <<<EOS
WITH manager AS (
    SELECT managedBy AS manager
    FROM perinfo
    WHERE id = ?
), pids AS (
SELECT DISTINCT id
FROM perinfo
WHERE id = ? OR managedBy = ?
UNION SELECT DISTINCT p.id
FROM manager p1
JOIN perinfo p ON p1.manager = p.managedBy OR p1.manager = p.id
)
EOS;

if (is_numeric($name_search)) {
    //
    // this is perid, or transid
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
    if ($overlapR === false) {
        ajaxsuccess(array('error' => 'SQL Error in overlap query'));
        return;
    }
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
        $response['policies'] = $policies;
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
    FROM regActions h
    JOIN reg r ON (r.id = h.regid)
    JOIN memLabel m ON (r.memId = m.id)
    WHERE tid = ? AND h.action = 'attach' AND (r.conid = ? OR (r.conid = ? AND m.memCategory in ('yearahead', 'rollover')))
)
EOS;
        // now the with clause has the regid's and the transactions we want
        $searchSQLP = <<<EOS
$withClause
$fieldListP
FROM regids r
JOIN reg r1 ON (r1.id = r.regid)
JOIN perinfo p ON (p.id = r1.perid)
ORDER BY last_name, first_name;
EOS;
        //web_error_log($searchSQLP);
        // now get the registrations for those same regids
        $searchSQLM = <<<EOS
$withClause
$fieldListM
FROM regids rs
JOIN reg r ON (rs.regid = r.id)
JOIN perinfo p ON (p.id = r.perid)
JOIN reg r1 ON (r1.perid = r.perid)
JOIN memLabel m ON (r1.memId = m.id)
LEFT OUTER JOIN printcount pc ON (r1.id = pc.regid)
LEFT OUTER JOIN attachcount ac ON (r1.id = ac.regid)
LEFT OUTER JOIN notes n ON (r1.id = n.regid)
WHERE (r1.conid = ? OR (r1.conid = ? AND m.memCategory in ('yearahead', 'rollover')))
ORDER BY r1.perid, r1.create_date;
EOS;
        // now get the policies for all of these perids
        $searchSQLL = <<<EOS
$withClause
$fieldListL
FROM regids r
JOIN reg r1 ON (r1.id = r.regid)
JOIN perinfo p ON (p.id = r1.perid)
JOIN memberPolicies mp ON (p.id = mp.perid AND r1.conid = mp.conid)
WHERE (r1.conid = ? OR (r1.conid = ? AND m.memCategory in ('yearahead', 'rollover')))
ORDER BY perid, policy;
EOS;

        //web_error_log($searchSQLM);
        $rp = dbSafeQuery($searchSQLP, 'iiiiii', array($name_search, $conid, $conid + 1, $name_search, $conid, $conid + 1));
        $rm = dbSafeQuery($searchSQLM, 'iiiiiiiii', array($name_search, $conid, $conid + 1, $name_search, $conid, $conid + 1, $conid, $conid + 1, $conid));
        $rl = dbSafeQuery($searchSQLL, 'iiiiiiii', array($name_search, $conid, $conid + 1, $name_search, $conid, $conid + 1, $conid + 1, $conid));
    } else if ($found_perid) {
        // pull all the matching regs for this perid for this period, plus anyone managed by this perid, UNION by this perid's manager
        $searchSQLP = <<<EOS
$managerWith
$fieldListP
FROM pids p1
JOIN perinfo p ON p.id = p1.id
ORDER BY last_name, first_name;
EOS;
        // noe the registration entries for these perids
        $searchSQLM = <<<EOS
$managerWith, regids AS (
    SELECT r.id AS regid, create_trans as tid
    FROM reg r
    JOIN pids p ON (p.id = r.perid)
    JOIN memList m ON (r.memId = m.id)
    WHERE (r.conid = ? OR (r.conid = ? AND m.memCategory in ('yearahead', 'rollover'))) AND r.perid = p.id
) $fieldListM
FROM regids rs
JOIN reg r1 ON (rs.regid = r1.id)
JOIN perinfo p ON (p.id = r1.perid)
JOIN memLabel m ON (r1.memId = m.id)
LEFT OUTER JOIN printcount pc ON (r1.id = pc.regid)
LEFT OUTER JOIN attachcount ac ON (r1.id = ac.regid)
LEFT OUTER JOIN notes n ON (r1.id = n.regid)
ORDER BY r1.perid, r1.create_date;
EOS;
        //  now the policies for these perids
        $searchSQLL = <<<EOS
$managerWith
$fieldListL
FROM pids p1
JOIN perinfo p ON (p.id = p1.id)
JOIN memberPolicies mp ON (p.id = mp.perid)
WHERE mp.conid = ?
ORDER BY perid, policy;
EOS;

        $rp = dbSafeQuery($searchSQLP, 'iii', array($name_search, $name_search, $name_search));
        if ($rp === false) {
            ajaxSuccess(array('error' => "Error in numeric person query for $name_search"));
            return;
        }
        $rm = dbSafeQuery($searchSQLM, 'iiiiii', array($name_search, $name_search, $name_search, $conid, $conid + 1, $conid));
        if ($rm === false) {
            ajaxSuccess(array('error' => "Error in numeric membership query for $name_search ($conid)"));
            return;
        }
        $rl = dbSafeQuery($searchSQLL, 'iiii', array($name_search, $name_search, $name_search, $conid));
        if ($rl === false) {
            ajaxSuccess(array('error' => "Error in numeric policy query for $name_search ($conid)"));
            return;
        }
    }
} else {
//
// this is the string search portion as the field is alphanumeric
//
    $limit = 50; // only return 50 people's memberships
    $findPattern = '%' . strtolower(str_replace(' ', '%', $name_search)) . '%';
    // name match (same as in people lookup, to get a list of perids that match, then we can use that list for the managed by/manager set
    $nameMatchWith = <<<EOS
WITh p1 AS (
    SELECT id
    FROM perinfo p
    WHERE
        (
            LOWER(p.legalname) LIKE ?
            OR LOWER(p.badge_name) LIKE ?
            OR LOWER(p.address) LIKE ?
            OR LOWER(p.addr_2) LIKE ?
            OR LOWER(p.email_addr) LIKE ?
            OR LOWER(CONCAT(p.first_name, ' ', p.last_name)) LIKE ?
            OR LOWER(CONCAT(p.last_name, ' ', p.first_name)) LIKE ?
            OR LOWER(CONCAT(p.first_name, ' ', p.middle_name, ' ', p.last_name, ' ', p.suffix)) LIKE ?
        )
        AND (NOT (p.first_name = 'Merged' AND p.middle_name = 'into'))
), manager AS
    SELECT managedBy AS manager
    FROM p1
    JOIN perinfo p ON (p1.id = p.id)
    WHERE id = ?;
), p1 AS (
SELECT DISTINCT id
FROM perinfo
WHERE id = ? OR managedBy = ?
UNION SELECT DISTINCT p.id
FROM manager p1
JOIN perinfo p ON p1.manager = p.managedBy OR p1.manager = p.id
), pids AS (
    SELECT DISTINCT id
    FROM p1
    LIMIT $limit
)
EOS;
    //web_error_log("match string: $name_search");
    $searchSQLP = <<<EOS
$nameMatchWith
$fieldListP
FROM pids p1
JOIN perinfo p ON p1.id = p.id
ORDER BY last_name, first_name LIMIT $limit;
EOS;
    $searchSQLM = <<<EOS
$nameMatchWith $fieldListM     
FROM regids rs
JOIN reg r ON (rs.regid = r.id)
JOIN perinfo p ON (p.id = r.perid)
JOIN memLabel m ON (r.memId = m.id)
LEFT OUTER JOIN printcount pc ON (r.id = pc.regid)
LEFT OUTER JOIN attachcount ac ON (r.id = ac.regid)
LEFT OUTER JOIN notes n ON (r.id = n.regid)
ORDER BY r1.perid, r1.create_date;
EOS;
    //  now the policies for these perids
    $searchSQLL = <<<EOS
$nameMatchWith,
$fieldListL
FROM pids p1
JOIN perinfo p ON (p.id = p1.id)
JOIN memberPolicies mp ON (p.id = mp.perid AND r1.conid = mp.conid)
WHERE (r1.conid = ? OR (r1.conid = ? AND m.memCategory in ('yearahead', 'rollover')))
ORDER BY perid, policy;
EOS;

    $rp = dbSafeQuery($searchSQLP, 'ssssssss',
          array ($findPattern, $findPattern, $findPattern, $findPattern, $findPattern, $findPattern, $findPattern, $findPattern));

    if ($rp === false) {
        ajaxSuccess(array('error' => "Error in string person query for $findPattern"));
        return;
    }

    $rm = dbSafeQuery($searchSQLM, 'ssssssssiii',
          array ($findPattern, $findPattern, $findPattern, $findPattern, $findPattern, $findPattern, $findPattern, $findPattern,
                 $conid, $conid + 1, $conid));
    if ($rm === false) {
        ajaxSuccess(array('error' => "Error in string membership query for $findPattern ($conid)"));
        return;
    }

    $rl = dbSafeQuery($searchSQLL, 'ssssssssii',
          array ($findPattern, $findPattern, $findPattern, $findPattern, $findPattern, $findPattern, $findPattern, $findPattern,
                 $conid, $conid + 1));
    if ($rl === false) {
        ajaxSuccess(array('error' => "Error in string policy query for $findPattern ($conid)"));
        return;
    }
}

// Tabulator needs the data as a plain array, cart wants it as an indexed array, so build the array of all the perid records in order
//      and the index by perid with the array position
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
$response['perids'] = $perids;
if ($num_rows >= $limit) {
    $response['warn'] = "$num_rows memberships found, limited to $limit, use different search criteria to refine your search.";
} else {
    $response['message'] = "$num_rows memberships found";
}
$rp->free();

// Now get memberships stored as an array of arrays by perid
$membership = [];
$lastPID = -1;
$memberships = [];

while ($l = $rm->fetch_assoc()) {
    if ($l['perid'] != $lastPID) {
        if ($lastPID >= 0) {
            $membership[$lastPID] = $memberships;
            $lastPID = $l['perid'];
        }
        $memberships = [];
    }

    $l['pindex'] = $perids[$l['perid']];
    $memberships[] = $l;
}
$membership[$lastPID] = $memberships;
$response['membership'] = $membership;
$rm->free();

// now get the policies the same way
$policies = [];
$lastPID = -1;
$policy = [];
while ($l = $rl->fetch_assoc()) {
    if ($l['perid'] != $lastPID) {
        if ($lastPID >= 0) {
            $policies[$lastPID] = $policy;
            $lastPID = $l['perid'];
        }
        $policy = [];
    }

    $l['pindex'] = $perids[$l['perid']];
    $policy[$l['policy']] = $l;
}
$policies[$lastPID] = $policy;
$response['policies'] = $policies;
$rl->free();

ajaxSuccess($response);
