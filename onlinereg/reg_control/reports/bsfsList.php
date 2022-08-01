<?php
require_once "../lib/base.php";

$need_login = google_init("page");
$page = "bsfs";

if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page("index.php");
}


$con = get_conf("con");
$conid=$con['id'];

header('Content-Type: application/csv');
header('Content-Disposition: attachment; filename="bsfs.csv"');

$query = "SELECT concat(P.last_name, ',', P.first_name),"
    . " CASE B.type WHEN 'life' THEN '(LM)' WHEN 'child' THEN '(CL)'"
        . " WHEN 'eternal' THEN '(EM)' WHEN 'annual' THEN concat('(',SUBSTRING(B.year, 2,2),')')"
        . " END"
    . " FROM bsfs as B JOIN perinfo as P ON P.id=B.perid"
    . " WHERE type in ('life', 'child', 'eternal', 'annual')"
    . " ORDER BY P.last_name, P.first_name"
    . ";";


echo "BSFS Business Meeting Attendence List"
    . "\n";

$reportR = dbQuery($query);
while($reportL = fetch_safe_array($reportR)) {
    for($i = 0 ; $i < count($reportL); $i++) {
        printf("\"%s\",", $reportL[$i]);
    }
    echo "\n";
}

?>
