<?php
require_once "../lib/base.php";

$need_login = google_init("page");
$page = "reg_admin";

if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page("index.php");
}


$con = get_conf("con");
$conid=$con['id'];

if(isset($_GET) and isset($_GET['con'])) { $conid=$_GET['con']; }

header('Content-Type: application/csv');
header('Content-Disposition: attachment; filename="allEmails.csv"');

$query = <<<EOS
SELECT DISTINCT P.id, CONCAT_WS(' ', P.first_name, P.middle_name, P.last_name) AS name, P.email_addr AS email, M.label, P.contact_ok
FROM reg AS R
JOIN perinfo P ON (P.id=R.perid)
JOIN memLabel M ON (M.id=R.memId)
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
