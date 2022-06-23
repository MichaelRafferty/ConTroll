<?php
require_once "../lib/base.php";
require_once "../lib/ajax_functions.php";

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

if(isset($_GET['conid'])) {
    $conid=sql_safe($_GET['conid']);
    $con = fetch_safe_assoc(dbQuery("SELECT * FROM conlist WHERE id=$conid;"));
} else {
    $con = get_con();
    $conid= $con['id'];
}


$historyQuery = <<<EOF
CREATE TEMPORARY TABLE history AS (
    SELECT conid, year, diff, cnt_all, cnt_paid
    FROM (
        SELECT R.conid, year(C.enddate) as year
            , datediff(C.enddate, R.create_date) as diff
            , count(R.id) as cnt_all
            , count(CASE WHEN paid>0 THEN R.id ELSE NULL END) as cnt_paid
        FROM reg R
        JOIN conlist C ON (R.conid=C.id)
        WHERE (R.memType IS NULL OR (R.memType != 'B' and R.memType != 'V'))
           AND C.id>=$minCon
        GROUP BY C.id, datediff(C.enddate, R.create_date)
        WITH ROLLUP) r
        ORDER BY conid, year, diff);
EOF;

$addlwhere = '';
#$addlwhere = "AND (B.action = 'create' OR B.action = 'upgrade' OR B.action = 'pickup')";
switch($_GET['method']) {
    case 'attendance':
      #  $con = get_con();
        $badgeQ = <<<EOF
SELECT Distinct R.perid, M.label
    , R.conid, M.memType
    , from_unixtime(FLOOR(unix_timestamp(min(T.complete_date))/900)*900) as time
    , datediff(current_timestamp(), min(T.complete_date)) as diff
FROM atcon as A
JOIN atcon_badge AS B ON B.atconId=A.id
JOIN reg as R ON R.id=B.badgeId
JOIN transaction as T ON T.id=A.transid
JOIN memList as M ON M.id=R.memId
WHERE A.conid>=$conid
    AND (B.action = 'attach')
    $addlwhere
GROUP BY R.perid, M.label, R.conid, M.memType ORDER BY time, M.memType;
EOF;

        $badgeR = dbQuery($badgeQ);
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
        $preregQ = <<<EOF
SELECT M.id, M.label, count(distinct R.perid) as c
FROM reg as R
JOIN memList as M ON M.id=R.memId
JOIN conlist as C on C.id=R.conid
LEFT JOIN atcon_badge as B ON B.badgeId = R.id AND B.action!='attach'
WHERE R.create_date < C.startdate and R.conid=$conid
AND B.action is NULL
GROUP BY M.label, M.id
ORDER BY M.id;
EOF;
        $preregR = dbQuery($preregQ);
        while ($prereg = fetch_safe_assoc($preregR)) {
            if(isset($badgeList['prereg'][$prereg['label']])) {
                $badgeList['prereg'][$prereg['label']] += $prereg['c'];
            } else {
                $badgeList['prereg'][$prereg['label']] = $prereg['c'];
            }
        }


# OLD WHeRE CLAUSE: AND (B.action='attach')
        $histoQ = <<<EOF
SELECT count(distinct A.id) as trans, count(distinct B.badgeId) as badge
    , IF(T.complete_date is not null
    ,from_unixtime(FLOOR(unix_timestamp(T.complete_date)/900)*900)
    ,from_unixtime(FLOOR(unix_timestamp(T.create_date)/900)*900)) as time
    , datediff(current_timestamp(), T.complete_date) as diff
    , M.memType
FROM atcon as A
JOIN conlist as C on C.id=A.conid
JOIN atcon_badge as B on B.atconid=A.id
JOIN transaction as T on T.id=A.transid
JOIN reg as R on R.id=B.badgeId
JOIN memList as M on M.id=R.memId
WHERE A.conid=$conid
    AND (B.action='pickup' or B.action='create' or B.action='upgrade')
    AND T.create_date >= C.startdate - INTERVAL 1 Day
GROUP BY time, diff, memType ORDER BY time;
EOF;

        $histoR = dbQuery($histoQ);
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
SELECT count(distinct P.cashier) as reg
    , count(distinct A.perid) as de
    , from_unixtime(FLOOR(unix_timestamp(P.time)/900)*900) as t
FROM transaction as T
LEFT JOIN payments as P ON P.transid=T.id and P.cashier is not null
LEFT JOIN atcon as A ON A.transid=T.id and A.perid is not null
WHERE T.conid=$conid and time is not null
GROUP BY t;
EOF;
        $staffR = dbQuery($staffQ);
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
SELECT memCategory as cat, memType as type, memAge as age, label, cnt
FROM (
    SELECT count(R.id) as cnt, M.sort_order, M.memCategory, M.memType, M.memAge, M.label
    FROM reg as R
    JOIN memList as M ON M.id=R.memId
    WHERE R.conid=$conid
    GROUP BY M.sort_order, M.memCategory, M.memType, M.memAge, M.label
    ) m
WHERE memCategory is NOT NULL
ORDER BY sort_order ASC, memCategory DESC, memType ASC, memAge ASC;
EOF;
        $response['query'] = $query;
        $res = dbQuery($query);
        while($resA = fetch_safe_assoc($res)) {
            $cat = $resA['cat']; // membership category (standard, premium)
            $type = $resA['type']; // membership type (full, one-day)
            $age = $resA['age']; // memberhsip age (adult, child, any)
            $label = $resA['label']; // membership label
            $count = $resA['cnt']; // # of matching memberships

            $response['overview'][$cat][$type][$age][$label]= $count;
        }

        break;
    case "totalMembership":
        dbQuery($historyQuery);
        $maxRegQ = <<<EOQ
SELECT conid, true as complete, year, min(cnt_all) AS cnt_all, min(cnt_paid) AS cnt_paid
FROM history WHERE conid<=? AND diff is null
GROUP BY conid, year;
EOQ;
        $maxRegA = dbSafeQuery($maxRegQ, 'i', array($conid));

        $response['maxReg'] = array();
        while($row = fetch_safe_assoc($maxRegA)) {
            array_push($response['maxReg'], $row);
        }
        break;
    case "preConTrend":
        if($conid==1) { $conid=51; }
        dbQuery($historyQuery);
        $dayRegQ = <<<EOF
SELECT datediff(enddate, current_timestamp())
FROM conlist
WHERE id=$conid;
EOF;

        $dayRegA = dbQuery($dayRegQ);
        $dayReg = fetch_safe_array($dayRegA);
        $response['today'] = $dayReg[0];
        $statArray = array();
        for ($i=$maxLen; $i >=4; $i--) {
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
            $localStat['day'] = - (int)$diff + 4;

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
        dbQuery($historyQuery);
        $currentQ = "SELECT min(diff) as diff"
            . ", sum(cnt_all) as total, sum(cnt_paid) as paid"
            . " FROM history WHERE conid = $conid and diff is not null;";
        $currentR = dbQuery($currentQ);
        $currentA = fetch_safe_assoc($currentR);
        $diff = $currentA['diff'];
        $inputQ = "SELECT conid, sum(cnt_all) as total, sum(cnt_paid) as paid"
            . " FROM history WHERE conid < $conid AND diff >= $diff"
            . " GROUP BY conid;";
        $preconQ = "SELECT conid, sum(cnt_all) as total, sum(cnt_paid) as paid"
            . " FROM history WHERE conid < $conid AND diff >= 4"
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
