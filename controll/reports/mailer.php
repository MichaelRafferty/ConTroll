<?php
require_once "../lib/base.php";
require_once '../lib/sessionAuth.php';

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$perm = 'reg_admin';
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
header('Content-Disposition: attachment; filename="mailer.csv"');

$query = <<<EOS
SELECT DISTINCT concat_ws(' ', P.first_name, P.middle_name, P.last_name, P.suffix) as name
    , P.addr_2 as company, P.address, P.city, P.state, P.zip, P.country"
    , P.phone, P.email_addr"
    , max(R.conid) as last_con
FROM perinfo AS P
JOIN reg as R ON R.perid=P.id
WHERE R.conid >$conid-5
    AND P.address IS NOT NULL and P.last_name IS NOT NULL
    AND P.banned != 'Y'
    AND P.address != '' AND P.address != '\n' AND P.zip != 0
GROUP BY P.address, P.city, P.state, P.zip
ORDER BY TRIM(concat_ws(',', P.last_name, P.first_name, P.middle_name));
EOS;

echo "name, company, address, city, state, zip, country, phone, email_addr, badge name, last_con"
    . "\n";

$reportR = dbQuery($query);
while($reportL = fetch_safe_array($reportR)) {
    for($i = 0 ; $i < count($reportL); $i++) {
        printf("\"%s\",", htmlspecialchars_decode($reportL[$i], ENT_QUOTES));
    }
    echo "\n";
}
