<?php
require_once "../lib/base.php";
require_once '../lib/sessionAuth.php';

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$perm = 'reg_staff';
$response = array ('post' => $_POST, 'get' => $_GET, 'perm' => $perm);
$authToken = new authToken('script');
$response['tokenStatus'] = $authToken->checkToken();
if (!$authToken->isLoggedIn() || !$authToken->checkAuth($perm)) {
    $response['error'] = 'Authentication Failed';
    ajaxSuccess($response);
    exit();
}

if(!isset($_POST) || !isset($_POST['year'])) {
    $response['error'] = "Invalid Query";
    ajaxSuccess($response);
    exit();
}

$con=get_con();
$conid= $con['id'];

$id = 0;
$year = $_POST['year'];
$type= $_POST['type'];
if ($year == 'current') {
    $id = $conid;
} else if ($year == 'next') {
    $id = $conid + 1;
} else {
    $response['error'] = "Invalid year";
    ajaxSuccess($response);
    exit();
}

$response['conlist'] = null;
$response['year'] = $year;
$response['conid'] = $id;

$conlistSQL = <<<EOS
SELECT id, name, label, startdate, enddate
FROM conlist
WHERE id = ?;
EOS;

$result = dbSafeQuery($conlistSQL, 'i', array($id));
if($result->num_rows == 1) {
    $currentcondata = $result->fetch_assoc();
    if ($type == 'all' || $type == 'conlist') {
    $response['conlist'] = $currentcondata;
    }
} else {
    $response['conlist'] = null;
}

if ($type == 'all' || $type == 'datelist') {
    $dateSQL = <<<EOS
SELECT *, id AS dateRangeKey, 0 AS uses
FROM dateRanges
WHERE conid = ?;
EOS;
    $result = dbSafeQuery($dateSQL, 'i', array($id));
    $dateRanges = array();
    if ($result !== false) {
        while($row = $result->fetch_assoc()) {
            $dateRanges[] = $row;
        }
    }
    $response['dateRanges'] = $dateRanges;
}


if ($type == 'all' || $type == 'memlist') {
    // first the memlist proper
    $memSQL = <<<EOS
WITH priceGroups AS (
    SELECT memId, startdate, CONCAT(price, ': ', startdate, ' / ', id) AS period
    FROM memListDates
), mems AS (  
SELECT m.id, m.id AS memlistkey,
    m.conid,
    m.sort_order,
    m.memCategory,
    m.memType,
    m.memAge,
    m.label as shortname,
    concat(m.label,' [',a.label,']') AS label,
    m.cartDesc,
    m.notes,
    m.atcon,
    m.online,
    m.rptGrouping,
    m.badgeLabel,
    c.badgeLabel AS catBadgeLabel,
    count(r.id) + count (cp.id) as uses
FROM memList m
JOIN ageList a ON m.memAge = a.ageType AND m.conid = a.conid
JOIN memCategories c ON m.memCategory = c.memCategory
LEFT OUTER JOIN reg r ON (r.memId = m.id)
LEFT OUTER JOIN coupon cp ON (cp.memId = m.id)
WHERE ((m.conid = ? and m.memCategory != 'yearahead') OR (m.conid = ? AND m.memCategory in ('rollover', 'yearahead')))
GROUP BY m.id, m.conid,m.sort_order,m.memCategory,m.memType,m.memAge,shortname,label,m.cartDesc,
m.notes,m.atcon,m.online,m.rptGrouping,m.badgeLabel,catBadgeLabel
)
SELECT m.*,GROUP_CONCAT(pg.period ORDER BY pg.startdate ASC SEPARATOR '<br/>') AS priceRanges
FROM mems m
LEFT OUTER JOIN priceGroups pg ON (pg.memId = m.id)
GROUP BY m.id, m.conid,m.sort_order,m.memCategory,m.memType,m.memAge,shortname,label,m.cartDesc,
m.notes,m.atcon,m.online,m.rptGrouping,m.badgeLabel,m.catBadgeLabel
ORDER BY m.conid, m.sort_order, m.memCategory, m.memType, m.memAge
EOS;
    $result = dbSafeQuery($memSQL, 'ii', array($id, $id+1));
    $memlist = array();
    if($result->num_rows >= 1) {
        while($memtype = $result->fetch_assoc()) {
            $memlist[] = $memtype;
        }
        $response['memlist'] = $memlist;
    } else {
        $response['memlist'] = null;
    }

    // now we need to also add the subtable memListDates
    $memSQL = <<<EOS
SELECT mld.id, mld.id AS idKey, mld.memId, mld.glNum, mld.price, mld.startdate, mld.enddate, mld.nickName, count(r.mldId) as uses, 0 AS to_delete
FROM memList m
JOIN memCategories c ON m.memCategory = c.memCategory
JOIN memListDates mld ON (mld.memId = m.id)
LEFT OUTER JOIN reg r ON (r.memId = mld.memId AND r.mldId = mld.id)
WHERE ((m.conid = ? and m.memCategory != 'yearahead') OR (m.conid = ? AND m.memCategory in ('rollover', 'yearahead')))
GROUP BY mld.id, mld.memId, mld.glNum, mld.price, mld.startdate, mld.enddate, mld.nickName
ORDER BY mld.memId, mld.startdate, mld.enddate
EOS;
    $result = dbSafeQuery($memSQL, 'ii', array($id, $id+1));
    $memlist = array();
    while($memtype = $result->fetch_assoc()) {
        if (!array_key_exists($memtype['memId'], $memlist))
            $memlist[$memtype['memId']] = array();
        $memlist[$memtype['memId']][] = $memtype;
    }
    $response['memListDates'] = $memlist;

    // now all the filter tables
    $result = dbQuery("SELECT memType FROM memTypes WHERE active = 'Y' ORDER BY sortorder;");
    $memTypes = array();
    if($result->num_rows >= 1) {
        while($memtype = $result->fetch_assoc()) {
            $memTypes[] = $memtype['memType'];
        }
        $response['memTypes'] = $memTypes;
    } else {
        $response['memTypes'] = null;
    }

    $result = dbQuery("SELECT memCategory FROM memCategories WHERE active = 'Y' ORDER BY sortorder;");
    $memCats = array();
    if($result->num_rows >= 1) {
        while($memcat = $result->fetch_assoc()) {
            $memCats[] = $memcat['memCategory'];
        }
        $response['memCats'] = $memCats;
    } else {
        $response['memCats'] = null;
    }

    $result = dbSafeQuery("SELECT ageType FROM ageList WHERE conid = ? ORDER BY sortorder;", 'i', array($id));
    $ageTypes = array();
    if($result->num_rows >= 1) {
        while($agetype = $result->fetch_assoc()) {
            $ageTypes[] = $agetype['ageType'];
        }
        $response['ageTypes'] = $ageTypes;
    } else {
        $response['ageTypes'] = null;
    }

    $yaAgeTypes = array ();
    $response['yaAgeTypes'] = null;
    if (getConfValue('con', 'oneoff', 0) == 0) {
        $result = dbSafeQuery('SELECT ageType FROM ageList WHERE conid = ? ORDER BY sortorder;', 'i', array ($id + 1));

        if ($result->num_rows >= 1) {
            while ($yaAgeType = $result->fetch_assoc()) {
                $yaAgeTypes[] = $yaAgeType['ageType'];
            }
            $response['yaAgeTypes'] = $yaAgeTypes;
        } else {
            $response['yaAgeTypes'] = null;
        }
    }
}
ajaxSuccess($response);
