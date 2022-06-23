<?php
global $ini;
if (!$ini)
    $ini = parse_ini_file(__DIR__ . "/../../../config/reg_conf.ini", true);
if ($ini['reg']['https'] <> 0) {
    if(!isset($_SERVER['HTTPS']) or $_SERVER["HTTPS"] != "on") {
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: https://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]);
        exit();
    }
}

require_once "../lib/base.php";
require_once "../lib/ajax_functions.php";

$need_login = google_init("page");
$page = "admin";

if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page("index.php");
}


$con = get_conf("con");
$conid=$con['id'];



header('Content-Type: application/csv');
header('Content-Disposition: attachment; filename="badgeHistory.csv"');

if(!(isset($_GET) and isset($_GET['perid']))) {
    echo  "conid, perid, membership\n"; exit();
}

$perid = sql_safe($_GET['perid']);

$query = "SELECT R.conid, R.perid, M.label, R.create_user, R.create_date"
        . ", max(B.date)"
    . " FROM reg as R JOIN memList as M ON M.id=R.memId"
        . " LEFT JOIN atcon_badge as B on B.badgeId=R.id and B.action='pickup'"
    . " WHERE R.conid >= 49 and R.perid = '$perid'"
    . " GROUP BY R.conid, R.perid, M.label, R.create_user, R.create_date"
    . " ORDER BY R.conid"
    . ";";


echo "Convention, Perid, Membership, Created By, Created On, Pickup"
    . "\n";

$reportR = dbQuery($query);
while($reportL = fetch_safe_array($reportR)) {
    for($i = 0 ; $i < count($reportL); $i++) {
        printf("\"%s\",", $reportL[$i]);
    }
    echo "\n";
}

$secondQ = "SELECT R.conid, A.date, A.action, A.comment FROM reg as R JOIN atcon_badge as A on A.badgeId=R.id WHERE R.perid=$perid";
$secondR = dbQuery($secondQ);

echo "\n\nCon, Date, Action, Comment\n";

while($reportL = fetch_safe_array($secondR)) {
    for($i = 0 ; $i < count($reportL); $i++) {
        printf("\"%s\",", $reportL[$i]);
    }
    echo "\n";
}

?>
