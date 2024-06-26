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
header('Content-Disposition: attachment; filename="duplicates.csv"');

# additional possible fields
# , concat(NP1.first_name, ' ', NP1.last_name) as name1
# , concat(NP2.first_name, ' ', NP2.last_name) as name2
$query = <<<EOS
SELECT concat(P.first_name, ' ', P.last_name) as name, R1.id, M1.label, R1.paid, R1.price, R2.id, M2.label, R2.paid, R2.price
FROM reg R1
JOIN perinfo P ON (P.id=R1.perid)
JOIN memLabel M1 ON (M1.id=R1.memId)
JOIN reg R2 ON (R2.conid=R1.conid and R2.perid=R1.perid and R2.id>R1.id)
JOIN memLabel M2 ON (M2.id=R2.memId)
LEFT OUTER JOIN newperson NP1 ON (NP1.id=R1.newperid)
LEFT OUTER JOIN newperson NP2 ON (NP2.id=R2.newperid)
WHERE R1.conid=?
EOS;

echo "Name"
#   . ", name1, name2
. ", first badge, label, price, paid, second badge, label, price, paid\n";

$reportR = dbSafeQuery($query, 'i', array($conid));
while($reportL = fetch_safe_array($reportR)) {
    for($i = 0 ; $i < count($reportL); $i++) {
        printf("\"%s\",", $reportL[$i]);
    }
    echo "\n";
}

?>
