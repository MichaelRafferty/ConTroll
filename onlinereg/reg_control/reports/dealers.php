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
$page = "reports";

if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page("index.php");
}


$con = get_conf("con");
$conid=$con['id'];

header('Content-Type: application/csv');
header('Content-Disposition: attachment; filename="dealers.csv"');

//  This hardcode needs to move to the config file for dealers
$dealerCoord = 13;

$query = <<<EOS
SELECT CONCAT(P.first_name, ' ', P.last_name) AS name, a.label
FROM badgeList B
JOIN perinfo P ON (P.id=B.perid)
LEFT OUTER JOIN reg R ON (R.perid = P.id)
LEFT OUTER JOIN memList M ON (M.id=R.memId)
LEFT OUTER JOIN ageList A ON (M.memAge = A.ageType AND M.conid = A.conid)
WHERE B.conid = ? AND B.userid = ?;
EOS;

echo "Name, Badge Type\n";

$reportR = dbSafeQuery($query, 'ii', array($conid, $dealerCoord));
while($reportL = fetch_safe_array($reportR)) {
    for($i = 0 ; $i < count($reportL); $i++) {
        printf("\"%s\",", $reportL[$i]);
    }
    echo "\n";
}

?>
