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
$page = "reg_admin";

if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page("index.php");
}


$con = get_conf("con");
$conid=$con['id'];

header('Content-Type: application/csv');
header('Content-Disposition: attachment; filename="reg_report.csv"');

//if($_POSE and $_POST['con']) {
    //$conid=sql_safe($_POST['con']);
//}
$query = "SELECT R.id, concat_ws(' ', P.first_name, P.last_name) as name, concat_ws(' ', P.address, P.addr_2, P.city, P.state, P.zip) as addr, P.zip as locale, P.country, P.email_addr, M.label, R.price, R.paid, R.create_date, MIN(B.date) FROM reg as R JOIN perinfo as P on P.id=R.perid JOIN memList as M on M.id=R.memId LEFT JOIN atcon_badge as B on B.badgeId=R.id and B.action='attach' and B.date > '2019-05-22' WHERE R.conid=53 GROUP BY P.id;";

echo "id, name, addr, local, country, email, badge type, price, paid, create_date, pickup_date"
    . "\n";

$reportR = dbQuery($query);
while($reportL = fetch_safe_array($reportR)) {
    for($i = 0 ; $i < count($reportL); $i++) {
        printf("\"%s\",", $reportL[$i]);
    }
    echo "\n";
}

?>
