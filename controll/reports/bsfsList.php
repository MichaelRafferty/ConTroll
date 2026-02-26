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

header('Content-Type: application/csv');
header('Content-Disposition: attachment; filename="club.csv"');

$query = <<<EOS
SELECT CONCAT(P.last_name, ',', P.first_name) AS name,
CASE B.type
    WHEN 'life' THEN '(LM)'
    WHEN 'child' THEN '(CL)'
    WHEN 'eternal' THEN '(EM)' 
    WHEN 'annual' THEN concat('(',SUBSTRING(B.year, 2,2),')')
END AS code
FROM club B 
JOIN perinfo P ON P.id=B.perid
WHERE type in ('life', 'child', 'eternal', 'annual')
ORDER BY P.last_name, P.first_name;
EOS;

echo "Club Business Meeting Attendence List"
    . "\n";

$reportR = dbQuery($query);
while($reportL = fetch_safe_array($reportR)) {
    for($i = 0 ; $i < count($reportL); $i++) {
        printf("\"%s\",", $reportL[$i]);
    }
    echo "\n";
}
