<?php
require_once "../lib/base.php";

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
SELECT R.conid, R.perid, M.label, R.create_user, R.create_date, max(H.logdate) AS date
FROM reg R
JOIN memList M ON (M.id=R.memId)
LEFT OUTER JOIN reg_history H ON (H.regid=R.id AND H.action='print')
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
SELECT R.conid, H.logdate as date, H.action, H.notes as comment
FROM reg R
JOIN reg_history H ON (H.regid=R.id)
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
