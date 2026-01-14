<?php
require_once "../lib/base.php";
require_once '../lib/sessionAuth.php';

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$perm = 'reports';
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
// this hard code needs to move to the config file  (and is obsolete as perid 29 is not GOH coordinator)
$gohLiaison = 29;

header('Content-Type: application/csv');
header('Content-Disposition: attachment; filename="goh.csv"');

// there was an extra on clause part for badgeList of "and B.conid=50", need to understand why the hardcode?
$query = <<<EOS
SELECT DISTINCT CONCAT_WS(' ', P.first_name, P.last_name), CONCAT_WS('/', P.badge_name, P.badgeNameL2), M.label
FROM reg R
JOIN badgeList B ON (B.perid=R.perid)
JOIN perinfo P ON (P.id=R.perid)
JOIN memLabel M ON (M.id=R.memId)
WHERE R.conid=? AND ((B.user_perid=? OR M.memCategory='goh') AND B.conid = M.conid)
ORDER BY M.label, P.last_name, P.first_name;
EOS;

echo "Name, Badge Name, Badge Type\n";

$reportR = dbSafeQuery($query, 'ii', array($conid, $gohLiaison));
while($reportL = fetch_safe_array($reportR)) {
    for($i = 0 ; $i < count($reportL); $i++) {
        printf("\"%s\",", $reportL[$i]);
    }
    echo "\n";
}
