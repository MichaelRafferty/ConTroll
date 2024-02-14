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
SELECT R.create_trans as TID, CONCAT_WS(' ', P.first_name, P.last_name) AS name 
    , P.email_addr, M.label, R.paid, R.create_date as date
    , count(distinct H.tid) as printcount, min(H.logdate) as printdate
    , T.type
FROM reg R
JOIN perinfo P ON (P.id=R.perid)
JOIN memLabel M ON (M.id=R.memId)
LEFT JOIN reg_history H ON (H.regid=R.id and H.action='print')
LEFT JOIN transaction T ON (T.id=R.create_trans)
WHERE R.conid=?
GROUP BY P.id
ORDER BY R.create_date
EOS;

echo "TID, name, email, type, amount_paid, date, printed, first printed, transaction type\n";

$reportR = dbSafeQuery($query, 'i', array($conid+1));
while($reportL = fetch_safe_array($reportR)) {
    for($i = 0 ; $i < count($reportL); $i++) {
        printf("\"%s\",", $reportL[$i]);
    }
    echo "\n";
}

?>
