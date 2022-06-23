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

if(isset($_GET) and isset($_GET['con'])) { $conid=sql_safe($_GET['con']); }

header('Content-Type: application/csv');
header('Content-Disposition: attachment; filename="allEmails.csv"');

$query = "SELECT DISTINCT P.id, concat_ws(' ', P.first_name, P.middle_name, P.last_name) as name, P.email_addr as email, M.label, P.contact_ok FROM reg as R JOIN perinfo as P on P.id=R.perid JOIN memList as M on M.id=R.memId WHERE R.paid = R.price and R.conid=$conid";

echo "perid, Name, email, badgeType"
    . "\n";

$reportR = dbQuery($query);
while($reportL = fetch_safe_array($reportR)) {
    for($i = 0 ; $i < count($reportL); $i++) {
        printf("\"%s\",", htmlspecialchars_decode($reportL[$i], ENT_QUOTES));
    }
    echo "\n";
}

?>
