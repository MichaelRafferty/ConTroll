<?php
require_once "../lib/base.php";

$need_login = google_init("page");
$page = "reports";

if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page("index.php");
}


$con = get_conf("con");
$conid=$con['id'];

if(isset($_GET) && isset($_GET['conid'])) { $conid=$_GET['conid']; }

header('Content-Type: application/csv');
header('Content-Disposition: attachment; filename="hotel_registration.csv"');

// need to understand additional select item of // , min(B.date)" that was commented out at end of list
// with added group by field of " // , B.action which wasn't in select list
// added join commented out  //. " JOIN atcon_badge as B on B.badgeId=R.id"
// which used the where clause of  //  and B.action='pickup'"
$query = <<<EOS
SELECT DISTINCT TRIM(CONCAT_WS(' ', P.first_name, P.last_name)), REPLACE(CONCAT_WS('\n', P.address, P.addr_2, CONCAT(P.city, ', ', P.state, ' ', P.zip)), '\n\n', '\n')
    , M.label, U.name, U.email
FROM reg R
JOIN perinfo P ON (P.id=R.perid)
JOIN memLabel M ON (M.id=R.memId)
LEFT OUTER JOIN user U ON (U.id=R.create_user)
WHERE R.conid=?
GROUP BY P.last_name, P.first_name
ORDER BY P.last_name, P.first_name, M.id;
EOS;


echo "Name, Address, Member Type, Authorizing User, Authorizing Email\n";

$reportR = dbSafeQuery($query, 'i', array($conid));
while($reportL = fetch_safe_array($reportR)) {
    for($i = 0 ; $i < count($reportL); $i++) {
        printf("\"%s\",", html_entity_decode($reportL[$i], ENT_QUOTES | ENT_HTML401));
    }
    echo "\n";
}

?>
