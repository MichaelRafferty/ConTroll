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

$query = <<<EOS
SELECT DISTINCT P.id, CONCAT_WS(' ', P.first_name, P.middle_name, P.last_name) AS name, P.email_addr AS email, A.label, P.contact_ok
FROM reg AS R
JOIN perinfo P ON (P.id=R.perid)
JOIN memList M ON (M.id=R.memId)
JOIN ageList A ON (M.memAge = A.ageType and M.conid = A.conid)
WHERE R.paid = R.price and R.conid=?
EOS;

echo "perid, Name, email, badgeType"
    . "\n";

$reportR =dbSafeQuery($query, 'i', array($conid));
while($reportL = fetch_safe_array($reportR)) {
    for($i = 0 ; $i < count($reportL); $i++) {
        printf("\"%s\",", htmlspecialchars_decode($reportL[$i], ENT_QUOTES));
    }
    echo "\n";
}

?>
