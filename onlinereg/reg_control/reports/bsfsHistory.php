<?php
require_once "../lib/base.php";

$need_login = google_init("page");
$control = get_conf('control');
$page = $control['clubperm'];

if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page("index.php");
}


$con = get_conf("con");
$conid=$con['id'];
$mincon = $con['minComp'];

header('Content-Type: application/csv');
header('Content-Disposition: attachment; filename="bsfsHistory.csv"');

$query = "SELECT P.first_name, P.middle_name, P.last_name, P.address, P.addr_2"
        . ", P.city, P.state, P.zip, P.email_addr"
        . ", CASE WHEN B.type='annual' then B.year ELSE B.type END as status"
        . ", MAX(R.conid) as con"
    . " FROM " . sql_safe($page) . " as B JOIN perinfo as P on P.id=B.perid"
        . " JOIN reg as R on R.perid=P.id"
        . " LEFT JOIN atcon_badge as A ON A.badgeId=R.id"
    . " WHERE R.conid >= $mincon and R.conid <=$conid"
        . " and (R.conid <= 48 or A.action='pickup')"
    . " GROUP BY P.id"
    . " ORDER BY con, status, P.id"
    . ";";


echo "first_name, middle_name, last_name, address, addr_2, city, state, zip, email_addr, status, con"
    . "\n";

$reportR = dbQuery($query);
while($reportL = fetch_safe_array($reportR)) {
    for($i = 0 ; $i < count($reportL); $i++) {
        printf("\"%s\",", $reportL[$i]);
    }
    echo "\n";
}

?>
