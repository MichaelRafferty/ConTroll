<?php
require_once "../lib/base.php";

$need_login = google_init("page");
$page = "reports";

if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page("index.php");
}


$condata = get_conf("con");
$con = get_conf('con');
$conid=$con['id'];

if(isset($_GET) && isset($_GET['conid'])) { $conid=sql_safe($_GET['conid']); }

header('Content-Type: application/csv');
header('Content-Disposition: attachment; filename="artists.csv"');

$query = "SELECT DISTINCT concat_ws(' ' ,P.first_name,P.middle_name, P.last_name) as name"
        . ", A.login, max(S.conid) as last_con"
        . ", concat_ws(' ', P.address, P.addr_2, concat(P.city, ', ', P.state, ' ', P.zip)) as address"
    . " FROM artist as A JOIN perinfo as P on P.id=A.artist JOIN artshow as S on S.artid=A.id"
    . " WHERE S.conid >= '" . sql_safe($con['minComp']) . "'"
    . " GROUP BY login, name, address"
    . ";";


echo "Name, art_name, login, last_con, address"
    . "\n";

$reportR = dbQuery($query);
while($reportL = fetch_safe_array($reportR)) {
    for($i = 0 ; $i < count($reportL); $i++) {
        printf("\"%s\",", html_entity_decode($reportL[$i], ENT_QUOTES | ENT_HTML401));
    }
    echo "\n";
}

?>
