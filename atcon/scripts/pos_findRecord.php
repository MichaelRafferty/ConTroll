<?php
// library AJAX Processor: pos_findRecord.php
// ConTroll Registration System
// Author: Syd Weinstein
// Retrieve perinfo and reg records for the Find and Add tabs

require_once '../lib/base.php';

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$response = array('post' => $_POST, 'get' => $_GET);

$con = get_conf('con');
$controll = get_conf('controll');
$usePortal = $controll['useportal'];
$conid = intval($con['id']);
$ajax_request_action = '';

if ($_POST && $_POST['ajax_request_action']) {
    $ajax_request_action = $_POST['ajax_request_action'];
}
if ($ajax_request_action != 'findRecord') {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}

if (!(check_atcon('cashier', $conid) || check_atcon('data_entry', $conid))) {
    $message_error = 'No permission.';
    RenderErrorAjax($message_error);
    exit();
}

// findRecord:
// load all perinfo/reg records matching the search string or unpaid if that flag is passed
$find_type = $_POST['find_type'];
$name_search = $_POST['name_search'];

$response['find_type'] = $find_type;
$response['name_search'] = $name_search;
$perinfo = [];

$limit = 99999999;
$fieldListP = <<<EOS
SELECT DISTINCT p.id AS perid, TRIM(p.first_name) AS first_name, TRIM(p.middle_name) AS middle_name, TRIM(p.last_name) AS last_name,
    TRIM(p.suffix) AS suffix, TRIM(p.legalName) AS legalName, TRIM(p.pronouns) AS pronouns, TRIM(p.badge_name) AS badge_name, 
    TRIM(p.address) AS address_1, TRIM(p.addr_2) AS address_2, TRIM(p.city) AS city, TRIM(p.state) AS state, TRIM(p.zip) AS postal_code, 
    TRIM(p.country) as country, TRIM(p.email_addr) AS email_addr, TRIM(p.phone) as phone, p.active, p.banned,
    TRIM(REGEXP_REPLACE(CONCAT_WS(' ', p.first_name, p.middle_name, p.last_name, p.suffix), '  *', ' ')) AS fullName,
    p.open_notes, p.managedBy, cnt.cntManages,
    TRIM(REGEXP_REPLACE(CONCAT_WS(' ', mgr.first_name, mgr.middle_name, mgr.last_name, mgr.suffix), '  *', ' ')) AS mgrFullName
EOS;
$withClauseMgr = <<<EOS
, manages AS (
SELECT p.id, COUNT(m.id) AS cntManages
FROM perinfo p
LEFT OUTER JOIN perinfo m ON m.managedBy = p.id
GROUP BY p.id
)
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
SELECT DISTINCT r1.perid, r1.id as regid, m.conid, r1.price, r1.paid, r1.paid AS priorPaid, r1.couponDiscount, r1.coupon,
    r1.create_date, IFNULL(r1.create_trans, -1) as tid,IFNULL(r1.complete_trans, -1) as tid2,r1.memId, r1.planId, r1.status, IFNULL(pc.printcount, 0) AS 
    printcount,
    IFNULL(ac.attachcount, 0) AS attachcount, n.reg_notes, n.reg_notes_count, m.memCategory, m.memType, m.memAge, m.shortname, rs.tid as rstid,
    CASE WHEN m.conid = ? THEN m.label ELSE concat(m.conid, ' ', m.label) END AS label, m.glNum, m.taxable, m.ageShortName
EOS;
$fieldListL = <<<EOS
SELECT DISTINCT p.id AS perid, mp.policy, mp.response, mp.id AS policyId
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
UNION SELECT DISTINCT manager AS id
FROM manager p1
JOIN perinfo p ON p1.manager = p.managedBy OR p1.manager = p.id
)
EOS;
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
WHERE r.status IN ('plan', 'unpaid') AND (r.conid = ? OR (r.conid = ? AND m.memCategory in ('yearahead', 'rollover')))
), tids AS (
/* add in unpaids from transactions in attach records in regActions */
SELECT u.id AS regid, CASE WHEN u.tid > IFNULL(h.tid, -999) THEN u.tid ELSE h.tid END AS tid
FROM unpaids u
LEFT OUTER JOIN regActions h ON (h.regid = u.id AND h.action = 'attach')
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
/* now get all the perinfo ids that are mentioned in each of those tid records, from both reg, and from regActions */
SELECT perid 
FROM reg r
JOIN tidlist t ON (t.tid = r.create_trans)
WHERE r.status IN ('unpaid', 'paid', 'plan')
UNION SELECT perid 
FROM reg r
JOIN regActions h on (h.regid = r.id)
JOIN tidlist t ON (t.tid = h.tid)
WHERE r.status IN ('unpaid', 'paid', 'plan')
), uniqueperids AS (
SELECT DISTINCT perid
FROM perids
)
EOS;
    $searchSQLP = <<<EOS
$withClauseUnpaid
$withClauseMgr
$fieldListP
FROM perids p1
JOIN perinfo p ON (p.id = p1.perid)
JOIN manages cnt ON (cnt.id = p.id)
LEFT OUTER JOIN perinfo mgr ON (mgr.id = p.managedBy)
ORDER BY last_name, first_name;
EOS;

    $searchSQLM = <<<EOS
$withClauseUnpaid
, regids AS (
    /* first reg ids for this create transaction as specified as a number */
    SELECT r.id AS regid, create_trans as tid
    FROM perids p1
    JOIN reg r ON r.perid = p1.perid
    JOIN memLabel m ON (r.memId = m.id)
    WHERE (r.conid = ? OR (r.conid = ? AND m.memCategory in ('yearahead', 'rollover'))) AND r.status IN ('unpaid', 'paid', 'plan')
)
$fieldListM
FROM perids p1
JOIN perinfo p ON (p.id = p1.perid)
JOIN reg r1 ON (r1.perid = p.id)
JOIN regids rs ON (r1.id = rs.regid)
JOIN memLabel m ON (r1.memId = m.id)
LEFT OUTER JOIN printcount pc ON (r1.id = pc.regid)
LEFT OUTER JOIN attachcount ac ON (r1.id = ac.regid)
LEFT OUTER JOIN notes n ON (r1.id = n.regid)
WHERE (r1.conid = ? OR (r1.conid = ? AND m.memCategory in ('yearahead', 'rollover'))) AND r1.status IN ('unpaid', 'paid', 'plan')
ORDER BY r1.perid, r1.create_date;
EOS;
    // now get the policies for all of these perids
    $searchSQLL = <<<EOS
$withClauseUnpaid
$fieldListL
FROM perids p1
JOIN perinfo p ON (p.id = p1.perid)
JOIN memberPolicies mp ON (p.id = mp.perid)
WHERE mp.conid = ?
ORDER BY perid, policy;
EOS;

    $rp = dbSafeQuery($searchSQLP, 'ii', array($conid, $conid + 1));
    if ($rp === false) {
        ajaxSuccess(array('error' => "Error in person query for unpaid"));
        return;
    }
    $rm = dbSafeQuery($searchSQLM, 'iiiiiii', array($conid, $conid + 1, $conid, $conid + 1, $conid, $conid, $conid + 1));
    if ($rm === false) {
        ajaxSuccess(array('error' => "Error in membership query for unpaid"));
        return;
    }
    $rl = dbSafeQuery($searchSQLL, 'iii', array($conid, $conid = 1, $conid));
    if ($rl === false) {
        ajaxSuccess(array('error' => "Error in policy query for unpaid"));
        return;
    }
} else if (is_numeric($name_search)) {
    //
    // this is perid, or transid
    // first can we tell if it's a perid or a tid?
    // if [controll].useportal is 1, then its a perid
    // if [controll].useprotal is 0, then it could be a perid or a tid
    $name_search = intval($name_search); // sql is requiring we change this to a number
    if ($usePortal == 1 && getSessionVar("POSMode") == 'checkin') {
        $overlapQ = <<<EOS
SELECT 'p' AS which, id
FROM perinfo p
WHERE p.id = ?
UNION SELECT 'p' AS which, id
FROM perinfo t 
WHERE t.managedBy = ?;
EOS;
        $typestr = 'ii';
        $values = array($name_search, $name_search);
    } else {
        $overlapQ = <<<EOS
SELECT 'p' AS which, id
FROM perinfo p
WHERE p.id = ?
UNION SELECT 't' AS which, id
FROM transaction t 
WHERE t.id = ? AND t.conid IN (?, ?);
EOS;
        $typestr = 'iiii';
        $values = array($name_search, $name_search, $conid, $conid + 1);
    }
    $overlapR = dbSafeQuery($overlapQ, $typestr, $values);
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
        $response['message'] = '0 members found';
        $response['perinfo'] = $perinfo;
        //$response['membership'] = $membership;
        //$response['policies'] = $policies;
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
    WHERE create_trans = ? AND (r.conid = ? OR (r.conid = ? AND m.memCategory in ('yearahead', 'rollover'))) AND r.status IN ('unpaid', 'paid', 'plan')
    /* then add in reg ids for this attach transaction */
    UNION SELECT regid, tid
    FROM regActions h
    JOIN reg r ON (r.id = h.regid)
    JOIN memLabel m ON (r.memId = m.id)
    WHERE tid = ? AND h.action = 'attach' AND (r.conid = ? OR (r.conid = ? AND m.memCategory in ('yearahead', 'rollover'))) 
        AND r.status IN ('unpaid', 'paid', 'plan')
)
EOS;
        // now the with clause has the regid's and the transactions we want
        $searchSQLP = <<<EOS
$withClause
$withClauseMgr
$fieldListP
FROM regids r
JOIN reg r1 ON (r1.id = r.regid)
JOIN perinfo p ON (p.id = r1.perid)
JOIN manages cnt ON (cnt.id = p.id)
LEFT OUTER JOIN perinfo mgr ON (mgr.id = p.managedBy)
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
WHERE (r1.conid = ? OR (r1.conid = ? AND m.memCategory in ('yearahead', 'rollover'))) AND r1.status IN ('unpaid', 'paid', 'plan')
AND r1.status IN ('unpaid', 'paid', 'plan')
ORDER BY r1.perid, r1.create_date;
EOS;
        // now get the policies for all of these perids
        $searchSQLL = <<<EOS
$withClause
$fieldListL
FROM regids r
JOIN reg r1 ON (r1.id = r.regid)
JOIN perinfo p ON (p.id = r1.perid)
JOIN memList m ON (r1.memId = m.id)
JOIN memberPolicies mp ON (p.id = mp.perid AND r1.conid = mp.conid)
WHERE (r1.conid = ? OR (r1.conid = ? AND m.memCategory in ('yearahead', 'rollover'))) AND r1.status IN ('unpaid', 'paid', 'plan')
ORDER BY perid, policy;
EOS;

        //web_error_log($searchSQLM);
        $rp = dbSafeQuery($searchSQLP, 'iiiiii', array($name_search, $conid, $conid + 1, $name_search, $conid, $conid + 1));
        if ($rp === false) {
            ajaxSuccess(array('error' => "Error in string person query $name_search"));
            return;
        }
        $rm = dbSafeQuery($searchSQLM, 'iiiiiiiii', array($name_search, $conid, $conid + 1, $name_search, $conid, $conid + 1, $conid, $conid, $conid + 1));
        if ($rm === false) {
            ajaxSuccess(array('error' => "Error in numeric membership query for $name_search"));
            return;
        }
        $rl = dbSafeQuery($searchSQLL, 'iiiiiiii', array($name_search, $conid, $conid + 1, $name_search, $conid, $conid + 1, $conid, $conid + 1));
        if ($rl === false) {
            ajaxSuccess(array('error' => "Error in numeric policy query for $name_search"));
            return;
        }
    } else if ($found_perid) {
        // pull all the matching regs for this perid for this period, plus anyone managed by this perid, UNION by this perid's manager
        $searchSQLP = <<<EOS
$managerWith
$withClauseMgr
$fieldListP
FROM pids p1
JOIN perinfo p ON p.id = p1.id
JOIN manages cnt ON (cnt.id = p.id)
LEFT OUTER JOIN perinfo mgr ON (mgr.id = p.managedBy)
ORDER BY last_name, first_name;
EOS;
        // now the registration entries for these perids
        $searchSQLM = <<<EOS
$managerWith, regids AS (
    SELECT r.id AS regid, create_trans as tid
    FROM reg r
    JOIN pids p ON (p.id = r.perid)
    JOIN memList m ON (r.memId = m.id)
    WHERE (r.conid = ? OR (r.conid = ? AND m.memCategory in ('yearahead', 'rollover'))) AND r.perid = p.id 
        AND r.status IN ('unpaid', 'paid', 'plan')
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
            LOWER(p.legalName) LIKE ?
            OR LOWER(p.badge_name) LIKE ?
            OR LOWER(p.address) LIKE ?
            OR LOWER(p.addr_2) LIKE ?
            OR LOWER(p.email_addr) LIKE ?
            OR LOWER(CONCAT_WS(' ', p.first_name, p.last_name)) LIKE ?
            OR LOWER(CONCAT_WS(' ', p.last_name, p.first_name)) LIKE ?
            OR LOWER(TRIM(REGEXP_REPLACE(CONCAT_WS(' ', p.first_name, p.middle_name, p.last_name, p.suffix), '  *', ' '))) LIKE ?
        )
        AND (NOT (p.first_name = 'Merged' AND p.middle_name = 'into'))
), manager AS (
    SELECT managedBy AS manager
    FROM p1
    JOIN perinfo p ON (p1.id = p.id)
), pc AS (
SELECT DISTINCT id
FROM p1
UNION SELECT DISTINCT manager AS id
FROM manager
), pids AS (
    SELECT DISTINCT p.id
    FROM pc
    JOIN perinfo p ON (p.id = pc.id OR p.managedBy = pc.id)
    LIMIT $limit
)
EOS;
    //web_error_log("match string: $name_search");
    $searchSQLP = <<<EOS
$nameMatchWith
$withClauseMgr
$fieldListP
FROM pids p1
JOIN perinfo p ON p1.id = p.id
JOIN manages cnt ON (cnt.id = p.id)
LEFT OUTER JOIN perinfo mgr ON (mgr.id = p.managedBy)
ORDER BY last_name, first_name LIMIT $limit;
EOS;
    $searchSQLM = <<<EOS
$nameMatchWith, regids AS (
    SELECT r.id AS regid, create_trans as tid
    FROM reg r
    JOIN pids p ON (p.id = r.perid)
    JOIN memList m ON (r.memId = m.id)
    WHERE (r.conid = ? OR (r.conid = ? AND m.memCategory in ('yearahead', 'rollover'))) AND r.perid = p.id 
        AND r.status IN ('unpaid', 'paid', 'plan')
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
$nameMatchWith
$fieldListL
FROM pids p1
JOIN perinfo p ON (p.id = p1.id)
JOIN memberPolicies mp ON (p.id = mp.perid)
WHERE mp.conid = ?
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

    $rl = dbSafeQuery($searchSQLL, 'ssssssssi',
          array ($findPattern, $findPattern, $findPattern, $findPattern, $findPattern, $findPattern, $findPattern, $findPattern,
                 $conid));
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
    $l['memberships'] = [];
    $perinfo[] = $l;
    $perids[$l['perid']] = $index;
    $index++;
}
if ($num_rows >= $limit) {
    $response['warn'] = "$num_rows members found, limited to $limit, use different search criteria to refine your search.";
} else {
    $response['message'] = "$num_rows members found";
}
$rp->free();

// Now get memberships stored as an array of arrays by perid
//$membership = [];
$lastPID = -1;
$pindex = null;
$memberships = [];

while ($l = $rm->fetch_assoc()) {
    if ($l['perid'] != $lastPID) {
        if ($lastPID >= 0) {
            //membership[$lastPID] = $memberships;
            $perinfo[$pindex]['memberships'] = $memberships;
        }
        $memberships = [];
        $lastPID = $l['perid'];
        $pindex = $perids[$lastPID];
    }
    $l['pindex'] = $pindex;
    $memberships[] = $l;
}
if ($lastPID >= 0) {
    //$membership[$lastPID] = $memberships;
    $perinfo[$pindex]['memberships'] = $memberships;
}
$rm->free();

// now get the policies the same way
$lastPID = -1;
$policy = [];
while ($l = $rl->fetch_assoc()) {
    if ($l['perid'] != $lastPID) {
        if ($lastPID >= 0) {
            //$policies[$lastPID] = $policy;
            $perinfo[$pindex]['policies'] = $policy;
        }
        $policy = [];
        $lastPID = $l['perid'];
        $pindex = $perids[$lastPID];
    }

    $l['pindex'] = $perids[$l['perid']];
    $policy[$l['policy']] = $l;
}
if ($lastPID >= 0) {
    //$policies[$lastPID] = $policy;
    $perinfo[$pindex]['policies'] = $policy;
}
$response['perinfo'] = $perinfo;
$response['perids'] = $perids;
$rl->free();

ajaxSuccess($response);
