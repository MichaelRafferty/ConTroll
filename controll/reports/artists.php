<?php
require_once "../lib/base.php";

$need_login = google_init("page");
$page = "reports";

if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page("index.php");
}


$condata = get_conf("con");
$con = get_conf('con');

header('Content-Type: application/csv');
header('Content-Disposition: attachment; filename="artists.csv"');

$query = <<<EOS
SELECT DISTINCT concat_ws(' ' ,P.first_name,P.middle_name, P.last_name) as name, A.login, max(S.conid) as last_con
    , concat_ws(' ', P.address, P.addr_2, concat(P.city, ', ', P.state, ' ', P.zip)) as address
FROM artshow S
FROM artist A ON (S.artid=A.id)
JOIN perinfo P ON (P.id=A.artist)
WHERE S.conid >= ?
GROUP BY login, name, address;
EOS;

echo "Name, art_name, login, last_con, address"
    . "\n";

$reportR = dbSafeQuery($query, 'i', array($con['minComp']));
while($reportL = fetch_safe_array($reportR)) {
    for($i = 0 ; $i < count($reportL); $i++) {
        printf("\"%s\",", html_entity_decode($reportL[$i], ENT_QUOTES | ENT_HTML401));
    }
    echo "\n";
}
