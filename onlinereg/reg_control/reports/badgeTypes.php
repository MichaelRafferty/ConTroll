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
header('Content-Disposition: attachment; filename="badgeTypes.csv"');

$query = <<<EOS
SELECT M.conid, M.label, M.price, M.startdate, M.enddate
FROM memLabel M
WHERE M.conid >= ?
ORDER BY M.conid, M.sort_order, M.startdate, M.enddate, M.label;
EOS;

echo "conid, label, price, startdate, enddate\n";

$reportR = dbSafeQuery($query, 'i', array($conid));
while($reportL = fetch_safe_array($reportR)) {
    for($i = 0 ; $i < count($reportL); $i++) {
        printf("\"%s\",", $reportL[$i]);
    }
    echo "\n";
}

?>
