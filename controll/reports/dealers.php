<?php
require_once '../lib/sessionAuth.php';

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$perm = 'gen_rpts';
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

header('Content-Type: application/csv');
header('Content-Disposition: attachment; filename="dealers.csv"');

//  This hardcode needs to move to the config file for dealers (and is obsolete, as perid 13 is not dealer coordinator)
$dealerCoord = 13;

$query = <<<EOS
SELECT CONCAT_WS(' ', P.first_name, P.last_name) AS name, M.label
FROM badgeList B
JOIN perinfo P ON (P.id=B.perid)
LEFT OUTER JOIN reg R ON (R.perid = P.id)
LEFT OUTER JOIN memLabel M ON (M.id=R.memId)
WHERE B.conid = ? AND B.user_perid = ?;
EOS;

echo "Name, Badge Type\n";

$reportR = dbSafeQuery($query, 'ii', array($conid, $dealerCoord));
while($reportL = fetch_safe_array($reportR)) {
    for($i = 0 ; $i < count($reportL); $i++) {
        printf("\"%s\",", $reportL[$i]);
    }
    echo "\n";
}
