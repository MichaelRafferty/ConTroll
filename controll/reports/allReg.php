<?php
require_once "../lib/base.php";

$need_login = google_init("page");
$page = "reports";

if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page("index.php");
}


$con = get_conf("con");
$conid=$con['id'];

header('Content-Type: application/csv');
header('Content-Disposition: attachment; filename="members.csv"');

$query = <<<EOS
SELECT R.conid, M.label, M.price, count(R.id) as num_badges
FROM reg R
JOIN memLabel M ON (M.id=R.memId)
GROUP BY R.conid, M.label, M.price
ORDER BY R.conid, M.label, M.price
EOS;

echo $con['conname']. " #, Membership Type, Price, Number of Badges\n";

$reportR = dbQuery($query);
while($reportL = fetch_safe_array($reportR)) {
    for($i = 0 ; $i < count($reportL); $i++) {
        printf("\"%s\",", $reportL[$i]);
    }
    echo "\n";
}
