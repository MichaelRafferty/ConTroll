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
// this hard code needs to move to the config file
$gohLiaison = 29;

header('Content-Type: application/csv');
header('Content-Disposition: attachment; filename="goh.csv"');

// there was an extra on clause part for badgeList of "and B.conid=50", need to understand why the hardcode?
$query = <<<EOS
SELECT DISTINCT CONCAT(P.first_name, ' ', P.last_name), P.badge_name, A.label
FROM reg R
JOIN badgeList B ON (B.perid=R.perid)
JOIN perinfo P ON (P.id=R.perid)
JOIN memList M ON (M.id=R.memId)
JOIN ageList A ON (M.memAge = A.ageType AND M.conid = A.conid)
WHERE R.conid=? AND ((B.userid=? OR M.memCategory='goh') AND B.conid = M.conid)
ORDER BY A.label, P.last_name, P.first_name;
EOS;

echo "Name, Badge Name, Badge Type\n";

$reportR = dbSafeQuery($query, 'ii', array($conid, $gohLiaison));
while($reportL = fetch_safe_array($reportR)) {
    for($i = 0 ; $i < count($reportL); $i++) {
        printf("\"%s\",", $reportL[$i]);
    }
    echo "\n";
}

?>
