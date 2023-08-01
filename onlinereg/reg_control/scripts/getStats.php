<?php
require_once "../lib/base.php";

function calc_stats($inArray) {
    sort($inArray);
    //print_r($inArray);

    $count = count($inArray);
    if($count == 0) {
        $min = 0;
        $max = 0;
        $first = 0;
        $third = 0;
        $median = 0;
        $lower = 0;
        $upper = 0;
    }
    if($count == 1) {
        $val = $inArray[0];
        $min = $val;
        $max = $val;
        $first = $val;
        $third = $val;
        $median = $val;
        $lower = $val;
        $upper = $val;
    }
    if($count >= 2) {
        $min = min($inArray);
        $max = max($inArray);
        $first = $inArray[round(.25 * ($count + 1) ) -1];
        $third = $inArray[round(.75 * ($count + 1) ) -1];
        $median = ($count % 2 == 0) ?
            ($inArray[($count/2)-1] + $inArray[($count/2)])/2 :
            $inArray[(($count+1)/2)];

        $lower = max($min, round($first - 1.5 * ($third - $first)));
        $upper = min($max, round($third + 1.5 * ($third - $first)));
    }

    return array('count'=>$count,
        'min'=>$min, 'lower'=>$lower,
        'Q1'=>$first, 'med'=>$median, 'Q3'=>$third,
        'upper'=>$upper, 'max'=>$max
    );
}



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

if(isset($_GET['conid'])) {
    $conid=$_GET['conid'];
    $con = fetch_safe_assoc(dbSafeQuery("SELECT * FROM conlist WHERE id=?;", 'i', array($conid)));
} else {
    $con = get_con();
    $conid= $con['id'];
}


$historyQuery = <<<EOS
CREATE TEMPORARY TABLE history (id INT auto_increment PRIMARY KEY)
    SELECT conid, year, diff, cnt_all, cnt_paid
    FROM (
        SELECT R.conid, year(C.enddate) as year
            , datediff(C.enddate, R.create_date) as diff
            , count(R.id) as cnt_all
            , count(CASE WHEN paid>0 THEN R.id ELSE NULL END) as cnt_paid
        FROM reg R
        JOIN conlist C ON (R.conid=C.id)
        WHERE C.id>=?
        GROUP BY R.conid, year(C.enddate), datediff(C.enddate, R.create_date)
        WITH ROLLUP) r
        ORDER BY conid, year, diff;
EOS;

$addlwhere = '';
#$addlwhere = "AND (B.action = 'create' OR B.action = 'upgrade' OR B.action = 'pickup')";
switch($_GET['method']) {
    case 'attendance':
      #  $con = get_con();
        $badgeQ = <<<EOF
SELECT Distinct R.perid, M.shortname as label, R.conid, M.memType
    , FROM_UNIXTIME(FLOOR(UNIX_TIMESTAMP(min(T.complete_date))/900)*900) AS time
    , DATEDIFF(CURRENT_TIMESTAMP(), MIN(T.complete_date)) as diff
FROM atcon_history H
JOIN reg R ON (R.id=H.regid)
JOIN transaction T ON (T.id=H.tid)
JOIN memLabel M ON (M.id=R.memId)
WHERE R.conid>=? AND H.action = 'attach'
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
        while($badge = fetch_safe_assoc($badgeR)) {
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
LEFT OUTER JOIN atcon_history H ON (H.regid = R.id AND H.action!='attach')
WHERE R.create_date < C.startdate and R.conid=? AND H.action is NULL
GROUP BY M.shortname, M.id
ORDER BY M.id;
EOS;
        $preregR = dbSafeQuery($preregQ, 'i', array($conid));
        while ($prereg = fetch_safe_assoc($preregR)) {
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
JOIN atcon_history H ON (H.tid=T.id)
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
        while($histo = fetch_safe_assoc($histoR)) {
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
JOIN atcon_history H ON (H.tid=T.id)
WHERE T.conid=?
GROUP BY t;
EOF;
        $staffR = dbSafeQuery($staffQ, 'i', array($conid));
        $staffing = array();
        while($staff = fetch_safe_assoc($staffR)) {
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
    case "overview":
        $query = <<<EOF
SELECT memCategory AS cat, memType AS type, memAge AS age, label, SUM(cnt) AS cnt, SUM(paid) AS paid
FROM (
    SELECT COUNT(R.id) AS cnt, M.sort_order, M.memCategory, M.memType, M.memAge, M.shortname AS label, SUM(R.paid) AS paid
    FROM reg R
    JOIN memLabel M ON (M.id=R.memId)
    WHERE R.conid=?
    GROUP BY M.sort_order, M.memCategory, M.memType, M.memAge, M.shortname
    ) m
WHERE memCategory is NOT NULL
GROUP BY cat, memType, memAge, label
ORDER BY paid DESC, memCategory DESC, memType ASC, memAge ASC;
EOF;
        $response['query'] = $query;
        $res = dbSafeQuery($query, 'i', array($conid));
        while($resA = fetch_safe_assoc($res)) {
            $cat = $resA['cat']; // membership category (standard, premium)
            $type = $resA['type']; // membership type (full, one-day)
            $age = $resA['age']; // memberhsip age (adult, child, any)
            $label = $resA['label']; // membership label
            $count = $resA['cnt']; // # of matching memberships

            $response['overview'][$cat][$type][$age][$label]= $count;
        }
        $dayRegQ = <<<EOF
SELECT datediff(enddate, current_timestamp())
FROM conlist
WHERE id=?;
EOF;

        $dayRegA = dbSafeQuery($dayRegQ, 'i', array($conid));
        $dayReg = fetch_safe_array($dayRegA);
        if ($dayReg > 0) $response['today'] = $dayReg[0] - $conLen;

        break;
    case "totalMembership":
        dbSafeCmd($historyQuery, 'i', array($minCon));
        $maxRegQ = <<<EOQ
SELECT conid, true AS complete, year, MIN(cnt_all) AS cnt_all, min(cnt_paid) AS cnt_paid
FROM history WHERE conid<=? AND diff IS NULL AND year IS NOT NULL
GROUP BY conid, year;
EOQ;
        $maxRegA = dbSafeQuery($maxRegQ, 'i', array($conid));

        $response['maxReg'] = array();
        while($row = fetch_safe_assoc($maxRegA)) {
            array_push($response['maxReg'], $row);
        }
        break;
    case "preConTrend":
        dbSafeCmd($historyQuery, 'i', array($minCon));
        $dayRegQ = <<<EOF
SELECT datediff(enddate, current_timestamp())
FROM conlist
WHERE id=?;
EOF;

        $dayRegA = dbSafeQuery($dayRegQ, 'i', array($conid));
        $dayReg = fetch_safe_array($dayRegA);
        $response['today'] = $dayReg[0];
        $statArray = array();
        for ($i=$maxLen; $i >=$conLen; $i--) {
            $localStat = array();
            $pivot_col = "SELECT $i, ";
            $inner = "SELECT conid, sum(cnt_all) as sum_all"
                . ", sum(cnt_paid) as sum_paid, ";
            for($j = $minCon; $j<$conid; $j++) {
                $pivot_col .= "sum(B$j"."_all), sum(B$j"."_paid), ";
                $inner .=  "sum(case when conid=$j then cnt_all else null end) as B$j"."_all"
                    . ", sum(case when conid=$j then cnt_paid else null end) as B$j"."_paid, ";
            }

            $pivot_col .= "sum(B$conid"."_all), sum(B$conid"."_paid) ";
            $inner .= "sum(case when conid=$conid then cnt_all else null end) as B$conid"."_all"
                . ", sum(case when conid=$conid then cnt_paid else null end) as B$conid"."_paid ";

            $inner .= "FROM history WHERE diff > $i group by conid";
            $pivot_col .= "FROM ($inner) i";
            $res = fetch_safe_array(dbQuery($pivot_col));
$response['statQuery'] = $pivot_col;

            $diff = (int)$res[0];
            $res = array_slice($res, 1);
            $all_vals = array();
            $paid_vals = array();
            if($response['today'] <= $diff) {
                $localStat['c_paid'] = array_pop($res);;
                $localStat['c_all'] = array_pop($res);;
            } else {
                $localStat['c_paid'] = array_pop($res);;
                $localStat['c_all'] = array_pop($res);;
                //array_pop($res);
                //array_pop($res);
            }
            $localStat['day'] = - (int)$diff + $conLen;

            for ($j = 0; $j < count($res); $j++) {
                if($j%2==0 && $res[$j]) { array_push($all_vals, $res[$j]); }
                else if($res[$j]) { array_push($paid_vals, $res[$j]); }
            }

            $localStat['all'] = calc_stats($all_vals);
            $localStat['paid'] = calc_stats($paid_vals);
            array_push($statArray, $localStat);
        }

        $response['statArray'] = $statArray;
        break;
    case "modelInput":
        dbSafeCmd($historyQuery, 'i', array($minCon));
        $currentQ = "SELECT min(diff) as diff"
            . ", sum(cnt_all) as total, sum(cnt_paid) as paid"
            . " FROM history WHERE conid = ? and diff is not null;";
        $currentR = dbQuery($currentQ, 'i', array($conid));
        $currentA = fetch_safe_assoc($currentR);
        $diff = $currentA['diff'];
        $inputQ = "SELECT conid, sum(cnt_all) as total, sum(cnt_paid) as paid"
            . " FROM history WHERE conid < $conid AND diff >= $diff"
            . " GROUP BY conid;";
        $preconQ = "SELECT conid, sum(cnt_all) as total, sum(cnt_paid) as paid"
            . " FROM history WHERE conid < $conid AND diff >= $conLen"
            . " GROUP BY conid;";
        $finalQ = "SELECT conid, sum(cnt_all) as total, sum(cnt_paid) as paid"
            . " FROM history WHERE conid < $conid and diff is not null"
            . " GROUP BY conid;";

        $inputR = dbQuery($inputQ);
        $preconR = dbQuery($preconQ);
        $finalR = dbQuery($finalQ);

        $inputA = array();
        $preconA = array();
        $finalA = array();
        while($iRow = fetch_safe_assoc($inputR)) {
            array_push($inputA, $iRow);
        }
        while($pRow = fetch_safe_assoc($preconR)) {
            array_push($preconA, $pRow);
        }
        while($fRow = fetch_safe_assoc($finalR)) {
            array_push($finalA, $fRow);
        }
        $response['current'] = $currentA;
        $response['input'] = $inputA;
        $response['precon'] = $preconA;
        $response['final'] = $finalA;
        break;
    default:
        $response['error']="Invalid Request";
}

ajaxSuccess($response);
?>
