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

if(isset($_GET) && isset($_GET['conid'])) { $conid=$_GET['conid']; }

header('Content-Type: application/csv');
header('Content-Disposition: attachment; filename="participants.csv"');

// query had commented out field of " // , min(B.date)"
// and commented out where of //  and B.action='pickup'"
$query = <<<EOS
SELECT DISTINCT P.first_name, P.last_name, P.email_addr, P.id, M.label
FROM reg R
JOIN perinfo P ON (P.id=R.perid)
JOIN memLabel M ON (M.id=R.memId)
WHERE R.conid=? AND M.label LIKE '%Participant%' AND M.memAge='all';
EOS;

echo "First Name, Last Name, Email, ID, Reg type\n";

$reportR = dbSafeQuery($query, 'i', array($conid));
while($reportL = fetch_safe_array($reportR)) {
    for($i = 0 ; $i < count($reportL); $i++) {
        printf("\"%s\",", html_entity_decode($reportL[$i], ENT_QUOTES | ENT_HTML401));
    }
    echo "\n";
}
