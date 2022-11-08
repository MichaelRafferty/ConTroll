<?php
global $db_ini;

function getSameDayNextYear($date)
{
    $current = DateTime::createFromFormat("Y-m-d", $date);
    $next = new DateTime();
    $year = (int)$current->format('o') + 1;
    $week = (int)$current->format('W');
    $dow = (int)$current->format('N');
    $next->setISODate((int)$current->format('o') + 1, (int)$current->format('W'), (int)$current->format('N'));
    return date_format($next, "Y-m-d");
}

require_once "../lib/base.php";
$check_auth = google_init("ajax");
$perm = "admin";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);


if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

if(!isset($_GET) || !isset($_GET['year'])) {
    $response['error'] = "Invalid Query";
    ajaxSuccess($response);
    exit();
}

$con=get_con();
$conid= $con['id'];

$id = 0;
$year = $_GET['year'];
$type= $_GET['type'];
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
$priorcondata = array();
$currentcondata = array();
$twopriorcondata = array();
$conlistSQL = <<<EOS
SELECT id, name, label, CAST(startdate AS DATE) AS startdate, CAST(enddate as DATE) AS enddate
FROM conlist
WHERE id = ?;
EOS;

$result = dbSafeQuery($conlistSQL, 'i', array($id - 2));
if($result->num_rows == 1) {
    $twopriorcondata = fetch_safe_assoc($result);
}
$result = dbSafeQuery($conlistSQL, 'i', array($id - 1));
if($result->num_rows == 1) {
    $priorcondata = fetch_safe_assoc($result);
}
if (!array_key_exists('enddate', $twopriorcondata)) {
    if (array_key_exists('startdate', $priorcondata)) {
        $datestr = $priorcondata['startdate'];
        $year = mb_substr($datestr, 0, 4)  + 1;
        $twopriorcondata['enddate'] = $year . mb_substr($datestr, 4);
    }
}
$result = dbSafeQuery($conlistSQL, 'i', array($id));
if($result->num_rows == 1) {
    $currentcondata = fetch_safe_assoc($result);
    if ($type == 'all' || $type = 'conlist') {
    $response['conlist'] = $currentcondata;
    $response['conlist-type'] = 'actual';
    }
} else {
    $sql = <<<EOS
SELECT
	id + 1 as id,
    CASE
		WHEN id > 900 THEN REPLACE(name, MOD(id, 100), MOD(id + 1, 100))
        ELSE REPLACE(name, id, id + 1)
	END AS name,
    REPLACE(label, id, id + 1) as label,
    CAST (CASE
		WHEN WEEK(startdate) = WEEK(date_add(startdate, INTERVAL 52 WEEK)) then DATE_ADD(startdate, INTERVAL 52 WEEK)
        ELSE DATE_ADD(startdate, INTERVAL 53 WEEK)
	END AS DATE) AS startdate,
    CAST (CASE
		WHEN WEEK(enddate) = WEEK(DATE_ADD(enddate, INTERVAL 52 WEEK)) THEN DATE_ADD(enddate, INTERVAL 52 WEEK)
        ELSE DATE_ADD(enddate, INTERVAL 53 WEEK)
	END AS DATE) AS enddate,
    NOW() AS create_date
FROM conlist
WHERE id = ?;
EOS;

    $result = dbSafeQuery($sql, 'i', array($conid));
    if($result->num_rows == 1) {
        $currentcondata = fetch_safe_assoc($result);
        if ($type == 'all' || $type = 'conlist') {
            $response['conlist'] = fetch_safe_assoc($result);;
            $response['conlist-type'] = 'proposed';
        }
    }
}

if ($type == 'all' || $type = 'memlist') {
    $memSQL = <<<EOS
SELECT m.id,
    m.conid,
    m.sort_order,
    m.memCategory,
    m.memType,
    m.memAge,
    m.shortname,
    m.label,
    m.memGroup,
    m.price,
    m.startdate,
    m.enddate,
    m.atcon,
    m.online,
    count(r.id) as uses
FROM memLabel m
LEFT OUTER JOIN reg r ON (r.memId = m.id)
WHERE ((m.conid = ? and m.memCategory != 'yearahead') OR (m.conid = ? AND m.memCategory in ('rollover', 'yearahead')))
GROUP BY m.id, m.conid,m.sort_order,m.memCategory,m.memType,m.memAge,m.shortname,m.label,m.memGroup,m.price,m.startdate,m.enddate,m.atcon,m.online
ORDER BY m.conid, m.sort_order, m.memCategory, m.memType, m.memAge, m.startdate;
EOS;
    $thisyearcount = 0;
    $result = dbSafeQuery($memSQL, 'ii', array($id, $id+1));
    $memlist = array();
    if($result->num_rows >= 1) {
        while($memtype = $result->fetch_assoc()) {
            if ($memtype['conid'] == $id && $memtype['memCategory'] != 'yearahead' and $memtype['memCategory'] != 'rollover')
                $thisyearcount++;
            array_push($memlist, $memtype);
        }
        $response['memlist'] = $memlist;
    } else {
        $response['memlist'] = null;
    }

    if ($thisyearcount < 5) {
        $breaksql = <<<EOS
SELECT DISTINCT CAST(startdate as DATE) AS startdate
FROM memList
WHERE conid = ?;
EOS;
        $result = dbSafeQuery($breaksql, 'i', array($id - 1));
        $breaklist = array();
        if($result->num_rows >= 1) {
            while($breakrow = $result->fetch_assoc()) {
                // test date to see where it is
                $break = $breakrow['startdate'];
                $breaktime = strtotime($break);
                $diff = $breaktime - strtotime($priorcondata['startdate']);
                if ($breaktime >= strtotime($priorcondata['startdate']) && $breaktime <= strtotime($priorcondata['enddate'])) {
                    // during the prior con, add the offset to the start of the current con
                    $diff = $breaktime - strtotime($priorcondata['startdate']);
                    $break = date('Y-m-d', strtotime($currentcondata['startdate']) + $diff);
                } else if (str_ends_with($break, '-01')) {
                    // -01 (start of month) - same month, this year;
                    $year = date('Y', $breaktime) + 1;
                    $break = $year . mb_substr($break, 4);
                } else if ($breaktime <= strtotime($twopriorcondata['enddate'])) {
                    $break = $priorcondata['startdate'];
                } else {
                    // remaining, use same day of the same week of the year.
                    $break = getSameDayNextYear($break);
                }
                 array_push($breaklist, array ('old' => $breakrow['startdate'], 'new' => $break));
            }
            $response['breaklist']  = $breaklist;
        } else {
            $response['breaklist']  = null;
        }

    }

    $result = dbQuery("SELECT memType FROM memTypes WHERE active = 'Y' ORDER BY sortorder;");
    $memTypes = array();
    if($result->num_rows >= 1) {
        while($memtype = $result->fetch_assoc()) {
            array_push($memTypes, $memtype['memType']);
        }
        $response['memTypes'] = $memTypes;
    } else {
        $response['memTypes'] = null;
    }

    $result = dbQuery("SELECT memCategory FROM memCategories WHERE active = 'Y' ORDER BY sortorder;");
    $memCats = array();
    if($result->num_rows >= 1) {
        while($memcat = $result->fetch_assoc()) {
            array_push($memCats, $memcat['memCategory']);
        }
        $response['memCats'] = $memCats;
    } else {
        $response['memCats'] = null;
    }

    $result = dbSafeQuery("SELECT ageType FROM ageList WHERE conid = ? ORDER BY sortorder;", 'i', array($id));
    $ageTypes = array();
    if($result->num_rows >= 1) {
        while($agetype = $result->fetch_assoc()) {
            array_push($ageTypes, $agetype['ageType']);
        }
        $response['ageTypes'] = $ageTypes;
    } else {
        $response['ageTypes'] = null;
    }
}
ajaxSuccess($response);
?>
