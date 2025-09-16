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
header('Content-Disposition: attachment; filename="newMembers.csv"');

$query = "SELECT P.id, min(Rall.conid), max(Rall.conid), R49.conid, R48.conid, R47.conid, R.paid"
    . " FROM perinfo as P JOIN reg as R on R.perid=P.id and R.conid=50"
    . " LEFT JOIN reg as Rall on Rall.perid=P.id and Rall.conid<50 and Rall.conid>=35"
    . " LEFT JOIN reg as R49 on R49.perid=P.id and R49.conid=49"
    . " LEFT JOIN reg as R48 on R48.perid=P.id and R48.conid=48"
    . " LEFT JOIN reg as R47 on R47.perid=P.id and R47.conid=47"
    . " GROUP BY P.id"
    . ";";


echo "perid, first, last, B49, B48, B47, paid,"
    . "\n";

$reportR = dbQuery($query);
while($reportL = fetch_safe_array($reportR)) {
    for($i = 0 ; $i < count($reportL); $i++) {
        printf("\"%s\",", $reportL[$i]);
    }
    echo "\n";
}
