<?php
require_once "../lib/base.php";

$check_auth = google_init("ajax");
$perm = "overview";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);

if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

if($_SERVER['REQUEST_METHOD'] != "GET" ||
  !isset($_GET['method'])) {
    $response['error'] = "invalid input";
    ajaxSuccess($response);
    exit();
}

$conConf = get_conf('con');
$minCon = $conConf['minComp'];
$maxLen = $conConf['compLen'];
$conLen = $conConf['conLen'];

$debug_stats = getConfValue('debug', 'controll_stats', 0);
if(isset($_GET['conid'])) {
    $conid=$_GET['conid'];
    $con = dbSafeQuery('SELECT id, name, label, startdate, DATE_ADD(enddate, INTERVAL 1 DAY) as enddate FROM conlist WHERE id=?;', 'i', array($conid))->fetch_assoc();
} else {
    $con = get_con();
    $conid= $con['id'];
    $con = dbSafeQuery('SELECT id, name, label, startdate, DATE_ADD(enddate, INTERVAL 1 DAY) as enddate FROM conlist WHERE id=?;', 'i', array($conid))->fetch_assoc();
}

$addlwhere = '';
#$addlwhere = "AND (B.action = 'create' OR B.action = 'upgrade' OR B.action = 'pickup')";

$dayRegQ = <<<EOF
SELECT datediff(startdate, current_timestamp())
FROM conlist
WHERE id=?;
EOF;

$dayRegA = dbSafeQuery($dayRegQ, 'i', array($conid));
$dayReg = $dayRegA->fetch_array();
if ($dayReg > 0) $response['today'] = $dayReg[0];

switch($_GET['method']) {
    case 'overview': //updated 2025-01-09
        $membershipsQ = <<<EOF
SELECT R.status, M.memCategory, M.label, M.sort_order, COUNT(R.id) as cnt
FROM reg R    
	JOIN memList M ON (M.id=R.memId)    
WHERE R.conid=?
GROUP BY status, memCategory, sort_order, label
ORDER BY CASE `status`
	WHEN 'paid' THEN 1
    WHEN 'plan' THEN 2
    WHEN 'unpaid' THEN 3
    WHEN 'cancel' THEN 4
    ELSE 5
    END, 
	sort_order, memCategory, label;
EOF;
        $membershipR = dbSafeQuery($membershipsQ,'i',array($conid));
        while($resA = $membershipR->fetch_assoc()) {
            $status = $resA['status']; // membership category (standard, premium)
            $cat = $resA['memCategory']; // membership type (full, one-day)
            $label = $resA['label']; // membership label
            $count = $resA['cnt']; // # of matching memberships

            if(array_key_exists('overview', $response) &&
                array_key_exists($status, $response['overview']) &&
                array_key_exists($cat, $response['overview'][$status]) &&
                array_key_exists($label, $response['overview'][$status][$cat])) {
                $response['overview'][$status][$cat][$label] += $count;
            } else {
                $response['overview'][$status][$cat][$label]= $count;
            }
        }


        break;
    case 'attendance':
        $badgeList = array(
            'oneday'=>array(),
            'full'=>array(),
            'unprinted'=>array(),
            'next year'=>array()
        );
	$total = 0;

	$yearaheadQ = <<<EOF
SELECT R.conid, M.id, M.memCategory, M.shortname as label
    , count(DISTINCT R.perid) as c 
FROM reg R
JOIN memLabel M on M.id=R.memId
WHERE R.conid=? 
GROUP BY R.conid, M.id
order by M.id;
EOF;
        $yearaheadR = dbSafeQuery($yearaheadQ, 'i', array($conid+1));
        while($badgeType = $yearaheadR->fetch_assoc()) {
            $badgeList['next year'][$badgeType['label']]=$badgeType['c'];
        }

$currQ = "SELECT count(DISTINCT R.perid) as c FROM reg R WHERE R.conid=? and R.status='paid';";
$currR = dbSafeQuery($currQ, 'i', array($conid))->fetch_assoc();
$con['paid_members'] = $currR['c'];

        $preregQ = <<<EOF
SELECT R.conid, M.memCategory, M.shortname as label, count(DISTINCT R.perid) as c
FROM reg R
JOIN memLabel M on M.id=R.memId
LEFT OUTER JOIN regActions H ON H.regid=R.id and H.action='print'
WHERE R.conid=? and H.action is null
GROUP BY R.conid, M.label
order by M.label;
EOF;
        $preregR = dbSafeQuery($preregQ, 'i', array($conid));
        while($badgeType = $preregR->fetch_assoc()) {
            $badgeList['unprinted'][$badgeType['label']]=$badgeType['c'];
        }

        $atconQ = <<<EOF
SELECT R.conid, M.memCategory, LOWER(M.memType) as memType, M.shortname as label, count(DISTINCT R.perid) as c
FROM reg R
JOIN memLabel M on M.id=R.memId
JOIN regActions H ON H.regid=R.id and H.action='print'
WHERE R.conid=? 
GROUP BY R.conid, M.label
order by M.label;
EOF;

        $atconR = dbSafeQuery($atconQ, 'i', array($conid));
        while($badgeType = $atconR->fetch_assoc()) {
            $badgeList[$badgeType['memType']][$badgeType['label']]=$badgeType['c'];
        }

        $acc = array('full'=>0, 'oneday'=>0);
        $track = array();
        $graphQ = <<<EOF
SELECT COUNT(DISTINCT regid) as badge, COUNT(DISTINCT tid) as trans
    , conid, LOWER(memType) as type, time
FROM (SELECT DISTINCT H.regid, H.tid, R.conid, M.memType
, FROM_UNIXTIME(FLOOR(UNIX_TIMESTAMP(min(T.complete_date))/900)*900) AS time
FROM regActions H
JOIN reg R ON (R.id=H.regid)
JOIN transaction T ON (T.id=H.tid)
JOIN memLabel M ON (M.id=R.memId)
WHERE R.conid=? AND H.action = 'print'
GROUP BY R.perid, R.conid, M.memType
ORDER By time, M.memType) s
GROUP BY time, conid, memType
ORDER BY time, memType;
EOF;

        $graphR = dbSafeQuery($graphQ, 'i', array($conid));
        
        while($graphLine = $graphR->fetch_assoc()) {
            $acc[$graphLine['type']] += $graphLine['badge'];
            $track[$graphLine['time']][$graphLine['type']] = $acc[$graphLine['type']]; 
            $track[$graphLine['time']]['badge'] = $graphLine['badge'];
            $track[$graphLine['time']]['trans'] = $graphLine['trans'];
	    $total += $graphLine['badge'];
        }


      #  $con = get_con();

	$max_staff = 0;
        $staffQ = <<<EOF
SELECT COUNT(distinct P.cashier) AS cashier
    , COUNT(distinct T.userid) AS checkin
    , FROM_UNIXTIME(FLOOR(UNIX_TIMESTAMP(P.time)/900)*900) AS time
FROM transaction T
LEFT OUTER JOIN payments P ON (P.transid=T.id and P.cashier IS NOT NULL)
JOIN regActions H ON (H.tid=T.id)
WHERE T.conid=?
GROUP BY time;
EOF;
        $staffR = dbSafeQuery($staffQ, 'i', array($conid));
        $staffing = array();
        while($staff = $staffR->fetch_assoc()) {
            array_push($staffing, $staff);
	    if($staff['cashier'] + $staff['checkin'] > $max_staff) {
	        $max_staff = $staff['cashier'] + $staff['checkin'];
	    }
        }
	$con['max_staff'] = $max_staff;

        $histo = array();
        foreach($track as $key => $value) {
            $value['time']=$key;
            array_push($histo, $value);
        }

        $response['badgeList']=$badgeList;
        $response['histogram']=$histo;
        $response['tracking']=$track;
        $response['staffing']=$staffing;
        $response['con']=$con;
	$response['total']=$total;
        break;
    case "totalMembership": //updated 2025-01-09
        $maxRegQ = <<<EOQ
SELECT R.conid, COUNT(R.id) cnt_all, COUNT(CASE WHEN paid>0 THEN R.id ELSE null END) as cnt_paid
FROM reg R
	JOIN conlist C on R.conid=C.id
WHERE R.conid>=? and status in ('paid', 'plan')
GROUP BY R.conid
ORDER BY R.conid;
EOQ;
        $maxRegR = dbSafeQuery($maxRegQ, 'i', array($minCon));
        $response['maxReg'] = array();
        if ($debug_stats & 1) {  // make up 10 years of history data so there's something there.
            for ($i = 10; $i > 0; $i--) {
                $rand = random_int(5,50);
                $rand2 = random_int(1,$rand-1);
                array_push($response['maxReg'], ['conid' => $minCon - $i, 'cnt_all'=>$rand, 'cnt_paid' => $rand2]);
                }
        }
        while($row = $maxRegR->fetch_assoc()) {
            array_push($response['maxReg'], $row);
        }
        break;
    case "preConTrend":
        $preconQ = <<<EOQ
SELECT R.conid, datediff(C.startdate, R.create_date) as diff, COUNT(R.id) cnt_all, 
       COUNT(CASE 
           WHEN status='paid' and paid>0 THEN R.id 
           WHEN status='plan' and paid>0 THEN R.id 
           ELSE null END) as cnt_paid
FROM reg R
	JOIN conlist C on R.conid=C.id
WHERE R.conid>=?
GROUP BY conid, diff
ORDER BY conid, diff;
EOQ;
        $preconA = dbSafeQuery($preconQ,'i', array($minCon));
        $preconResponse = array();
        while($preconR = $preconA->fetch_assoc()) {
            $lastDebugValue = 0;
            if ((!array_key_exists($preconR['conid'], $preconResponse)) || ($preconResponse[$preconR['conid']] === null))
            {$preconResponse[$preconR['conid']]=array();}
            if($preconR['conid'] == $minCon && ($debug_stats & 1)) {
                for($i = 10 ; $i > 0; $i--) {
                    $rand = random_int(0,$preconR['cnt_all']*2);
                    if ($preconResponse[$minCon - $i*5] === null) {$preconResponse[$minCon - $i*5]=array();}
                    array_push($preconResponse[$minCon - $i*5], array('x' => $preconR['diff'], 'y' => $rand));
                }
            }
            array_push($preconResponse[$preconR['conid']], array('x' => $preconR['diff'], 'y' => $preconR['cnt_paid']));
        }
        $response['dailyHistory'] = $preconResponse;
        break;
     default:
        $response['error']="Invalid Request";
}

ajaxSuccess($response);
