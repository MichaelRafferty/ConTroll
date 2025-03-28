<?php
require_once "../lib/base.php";

$need_login = google_init("page");
$page = "reg_admin";

if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page("index.php");
}


$con = get_conf("con");
$conid=$con['id'];

header('Content-Type: application/csv');
header('Content-Disposition: attachment; filename="reg_report.csv"');

//if($_POSE and $_POST['con']) {
    //$conid=$_POST['con'];
//}

//hardcode: why the hard coded B.date in this this report, and the hard code to b53, need to generalize what we want this to do going forward
// make need full group by, as it's only a partial list right now
$query = <<<EOS
SELECT R.id, TRIM(P.last_name) AS name
    , P.zip as locale, M.label, R.create_date, MIN(H.logdate) AS date
FROM reg R
JOIN perinfo P ON (P.id=R.perid)
JOIN memLabel M ON (M.id=R.memId)
LEFT OUTER JOIN regActions H ON (H.regid=R.id AND H.action='attach')
WHERE R.conid=?
GROUP BY P.id;
EOS;

echo "name, zip, label, create_date, pickup_date\n";

$reportR = dbSafeQuery($query, 'i', array($conid));
while($reportL = fetch_safe_array($reportR)) {
    for($i = 0 ; $i < count($reportL); $i++) {
        printf("\"%s\",", $reportL[$i]);
    }
    echo "\n";
}

?>
