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

$debug = get_conf('debug');

if(isset($_GET['conid'])) {
    $conid=$_GET['conid'];
    $con = dbSafeQuery('SELECT * FROM conlist WHERE id=?;', 'i', array($conid))->fetch_assoc();
} else {
    $con = get_con();
    $conid= $con['id'];
}

$addlwhere = '';
#$addlwhere = "AND (B.action = 'create' OR B.action = 'upgrade' OR B.action = 'pickup')";

$dayRegQ = <<<EOF
SELECT datediff(enddate, current_timestamp())
FROM conlist
WHERE id=?;
EOF;

$dayRegA = dbSafeQuery($dayRegQ, 'i', array($conid));
$dayReg = $dayRegA->fetch_array();
if ($dayReg > 0) $response['today'] = $dayReg[0] - $conLen;

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

            $response['overview'][$status][$cat][$label]= $count;
        }


        break;
    case 'attendance':
      #  $con = get_con();
        $badgeQ = <<<EOF
SELECT Distinct R.perid, M.shortname as label, R.conid, M.memType
    , FROM_UNIXTIME(FLOOR(UNIX_TIMESTAMP(min(T.complete_date))/900)*900) AS time
    , DATEDIFF(CURRENT_TIMESTAMP(), MIN(T.complete_date)) as diff
FROM regActions H
JOIN reg R ON (R.id=H.regid)
JOIN transaction T ON (T.id=H.tid)
JOIN memLabel M ON (M.id=R.memId)
WHERE R.conid>=? AND H.action = 'print'
    $addlwhere
GROUP BY R.perid, M.shortname, R.conid, M.memType ORDER BY time, M.memType;
EOF;

        $badgeR = dbSafeQuery($badgeQ, 'i', array($conid));
        $badgeList = array(
            'expired'=>array(),
            'onsite'=>array(),
            'prereg'=>array(),
            'yearahead'=>array()
        );
        while($badge = $badgeR->fetch_assoc()) {
            if($badge['conid'] > $conid) {
                if(isset($badgeList['yearahead'][$badge['label']])) {
                    $badgeList['yearahead'][$badge['label']] += 1;
                } else {
                    $badgeList['yearahead'][$badge['label']] = 1;
                }
            } else if($badge['memType']=='oneday' && $badge['diff'] > 0) {
                if(isset($badgeList['expired'][$badge['label']])) {
                    $badgeList['expired'][$badge['label']] += 1;
                } else {
                    $badgeList['expired'][$badge['label']] = 1;
                }
            } else {
                if(isset($badgeList['onsite'][$badge['label']])) {
                    $badgeList['onsite'][$badge['label']] += 1;
                } else {
                    $badgeList['onsite'][$badge['label']] = 1;
                }
            }
        }
        $preregQ = <<<EOS
SELECT M.id, M.shortname as label, COUNT(distinct R.perid) AS c
FROM reg R
JOIN memLabel M ON (M.id=R.memId)
JOIN conlist C ON (C.id=R.conid)
LEFT OUTER JOIN regActions H ON (H.regid = R.id AND H.action!='attach')
WHERE R.create_date < C.startdate and R.conid=? AND H.action is NULL
GROUP BY M.shortname, M.id
ORDER BY M.id;
EOS;
        $preregR = dbSafeQuery($preregQ, 'i', array($conid));
        while ($prereg = $preregR->fetch_assoc()) {
            if(isset($badgeList['prereg'][$prereg['label']])) {
                $badgeList['prereg'][$prereg['label']] += $prereg['c'];
            } else {
                $badgeList['prereg'][$prereg['label']] = $prereg['c'];
            }
        }


# OLD WHERE CLAUSE: AND (B.action='attach')
        $histoQ = <<<EOS
SELECT COUNT(distinct T.id) AS trans, COUNT(distinct R.id) AS badge
    , IF(T.complete_date is not null
    ,FROM_UNIXTIME(FLOOR(UNIX_TIMESTAMP(T.complete_date)/900)*900)
    ,FROM_UNIXTIME(FLOOR(UNIX_TIMESTAMP(T.create_date)/900)*900)) AS time
    , DATEDIFF(CURRENT_TIMESTAMP(), T.complete_date) AS diff
    , M.memType
FROM conlist C
JOIN transaction T ON (T.conid=C.id)
JOIN regActions H ON (H.tid=T.id)
JOIN reg R ON (R.id=H.regid)
JOIN memList M ON (M.id=R.memId)
WHERE C.id=?
    AND H.action='print'
    AND T.create_date >= C.startdate - INTERVAL 1 Day
GROUP BY time, diff, memType ORDER BY time;
EOS;

        $histoR = dbSafeQuery($histoQ, 'i', array($conid));
        $histogram = array(); //sub arrays 'expired', 'oneday', 'full', 'trans'
        $acc = array('expired'=>0, 'oneday'=>0, 'full'=>0);
        $lastdiff = 0;
        while($histo = $histoR->fetch_assoc()) {
            if(!isset($histogram[$histo['time']])) {
                $histogram[$histo['time']] = array(
                    'expired'=>$acc['expired'],
                    'oneday'=>$acc['oneday'],
                    'full'=>$acc['full'],
                    'trans'=>$histo['trans'],
                    'badge'=>$histo['badge']
                );
            } else {
                $histogram[$histo['time']]['trans']+=$histo['trans'];
                $histogram[$histo['time']]['badge']+=$histo['badge'];
            }

            if($histo['memType'] == 'full') {
                $histogram[$histo['time']]['full'] += $histo['badge'];
                $acc['full'] += $histo['badge'];
            } else if($histo['diff'] == 0) {
                $histogram[$histo['time']]['oneday'] += $histo['badge'];
                $acc['oneday'] += $histo['badge'];
            } else {
                $histogram[$histo['time']]['expired'] += $histo['badge'];
                $acc['expired'] += $histo['badge'];
            }
        }

        $staffQ = <<<EOF
SELECT COUNT(distinct P.cashier) AS reg
    , COUNT(distinct T.userid) AS de
    , FROM_UNIXTIME(FLOOR(UNIX_TIMESTAMP(P.time)/900)*900) AS t
FROM transaction T
LEFT OUTER JOIN payments P ON (P.transid=T.id and P.cashier IS NOT NULL)
JOIN regActions H ON (H.tid=T.id)
WHERE T.conid=?
GROUP BY t;
EOF;
        $staffR = dbSafeQuery($staffQ, 'i', array($conid));
        $staffing = array();
        while($staff = $staffR->fetch_assoc()) {
            array_push($staffing, $staff);
        }

        $histo = array();
        foreach($histogram as $key => $value) {
            $value['time']=$key;
            array_push($histo, $value);
        }

        $response['badgeList']=$badgeList;
        $response['histogram']=$histo;
        $response['staffing']=$staffing;
        $response['con']=$con;
        break;
    case "totalMembership": //updated 2025-01-09
        $maxRegQ = <<<EOQ
SELECT R.conid, COUNT(R.id) cnt_all, COUNT(CASE WHEN paid>0 THEN R.id ELSE null END) as cnt_paid
FROM reg R
	JOIN conlist C on R.conid=C.id
WHERE R.conid>=? and status='paid'
GROUP BY R.conid
ORDER BY R.conid;
EOQ;
        $maxRegR = dbSafeQuery($maxRegQ, 'i', array($minCon));
        $response['maxReg'] = array();
        if ($debug['controll_stats'] & 1) {  // make up 10 years of history data so there's something there.
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
SELECT R.conid, datediff(C.enddate, R.create_date) as diff, COUNT(R.id) cnt_all, COUNT(CASE WHEN status='plan' and paid>0 THEN R.id ELSE null END) as cnt_plan, COUNT(CASE WHEN status='paid' and paid>0 THEN R.id ELSE null END) as cnt_paid
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
            if ($preconResponse[$preconR['conid']] === null) {$preconResponse[$preconR['conid']]=array();}
            if($preconR['conid'] == $minCon && ($debug['controll_stats'] & 1)) {
                for($i = 10 ; $i > 0; $i--) {
                    $rand = random_int(0,$preconR['cnt_all']*2);
                    if ($preconResponse[$minCon - $i*5] === null) {$preconResponse[$minCon - $i*5]=array();}
                    array_push($preconResponse[$minCon - $i*5], array('x' => $preconR['diff'], 'y' => $rand));
                }
            }
            array_push($preconResponse[$preconR['conid']], array('x' => $preconR['diff'], 'y' => $preconR['cnt_all']));
        }
        $response['dailyHistory'] = $preconResponse;
        break;
     default:
        $response['error']="Invalid Request";
}

ajaxSuccess($response);
?>
