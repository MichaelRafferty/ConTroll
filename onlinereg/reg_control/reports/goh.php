<?php
require_once "../lib/base.php";

$need_login = google_init("page");
$page = "reports";

if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page("index.php");
}


$con = get_conf("con");
$conid=$con['id'];
// this hard code needs to move to the config file  (and is obsolete as perid 29 is not GOH coordinator)
$gohLiaison = 29;

header('Content-Type: application/csv');
header('Content-Disposition: attachment; filename="goh.csv"');

// there was an extra on clause part for badgeList of "and B.conid=50", need to understand why the hardcode?
$query = <<<EOS
SELECT DISTINCT CONCAT(P.first_name, ' ', P.last_name), P.badge_name, M.label
FROM reg R
JOIN badgeList B ON (B.perid=R.perid)
JOIN perinfo P ON (P.id=R.perid)
JOIN memLabel M ON (M.id=R.memId)
WHERE R.conid=? AND ((B.user_perid=? OR M.memCategory='goh') AND B.conid = M.conid)
ORDER BY M.label, P.last_name, P.first_name;
EOS;

echo "Name, Badge Name, Badge Type\n";

$reportR = dbSafeQuery($query, 'ii', array($conid, $gohLiaison));
while($reportL = fetch_safe_array($reportR)) {
    for($i = 0 ; $i < count($reportL); $i++) {
        printf("\"%s\",", $reportL[$i]);
    }
    echo "\n";
}

?>
