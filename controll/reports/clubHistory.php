<?php
require_once "../lib/base.php";
require_once '../lib/sessionAuth.php';

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$perm = 'club';
$response = array ('post' => $_POST, 'get' => $_GET, 'perm' => $perm);
$authToken = new authToken('script');
$response['tokenStatus'] = $authToken->checkToken();
if (!$authToken->isLoggedIn() || !$authToken->checkAuth($perm)) {
    $response['error'] = 'Authentication Failed';
    ajaxSuccess($response);
    exit();
}

$con = get_conf("con");
$conid=$con['id'];
$mincon = $con['minComp'];

header('Content-Type: application/csv');
header('Content-Disposition: attachment; filename="clubHistory.csv"');

$query = <<<EOS
SELECT P.first_name, P.middle_name, P.last_name, P.address, P.addr_2, P.city, P.state, P.zip, P.email_addr
    , CASE WHEN B.type='annual' 
        THEN B.year
        ELSE B.type 
    END as status
    , MAX(R.conid) as con
FROM club B
JOIN perinfo P ON (P.id=B.perid)
JOIN reg R ON (R.perid=P.id)
LEFT OUTER JOIN regActions H ON (H.regid=R.id)
WHERE R.conid >= ? AND R.conid <= ? AND H.action='print'
GROUP BY P.id
ORDER BY con, status, P.id;
EOS;


echo "first_name, middle_name, last_name, address, addr_2, city, state, zip, email_addr, status, con"
    . "\n";

$reportR = dbSafeQuery($query, 'ii', array($mincon, $conid));
while($reportL = fetch_safe_array($reportR)) {
    for($i = 0 ; $i < count($reportL); $i++) {
        printf("\"%s\",", $reportL[$i]);
    }
    echo "\n";
}
