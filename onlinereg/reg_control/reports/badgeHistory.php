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
$mincomp = $con['minComp'];

header('Content-Type: application/csv');
header('Content-Disposition: attachment; filename="badgeHistory.csv"');

if(!(isset($_GET) and isset($_GET['perid']))) {
    echo  "conid, perid, membership\n";
    exit();
}

$perid = $_GET['perid'];

$query = <<<EOS
SELECT R.conid, R.perid, M.label, R.create_user, R.create_date, max(B.date)
FROM reg R
JOIN memLabel M ON (M.id=R.memId)
LEFT OUTER JOIN atcon_badge B ON (B.badgeId=R.id AND B.action='pickup')
WHERE R.conid >= ? and R.perid = ?
GROUP BY R.conid, R.perid, M.label, R.create_user, R.create_date
ORDER BY R.conid;
EOS;

echo "Convention, Perid, Membership, Created By, Created On, Pickup\n";

$reportR = dbSafeQuery($query, 'ii', array($mincomp, $perid));
while($reportL = fetch_safe_array($reportR)) {
    for($i = 0 ; $i < count($reportL); $i++) {
        printf("\"%s\",", $reportL[$i]);
    }
    echo "\n";
}

$secondQ = <<<EOS
SELECT R.conid, A.date, A.action, A.comment
FROM reg R
JOIN atcon_badge A ON (A.badgeId=R.id)
WHERE R.perid=?
EOS;

$secondR = dbSafeQuery($secondQ, 'i', array($perid));

echo "\n\nCon, Date, Action, Comment\n";

while($reportL = fetch_safe_array($secondR)) {
    for($i = 0 ; $i < count($reportL); $i++) {
        printf("\"%s\",", $reportL[$i]);
    }
    echo "\n";
}

?>
