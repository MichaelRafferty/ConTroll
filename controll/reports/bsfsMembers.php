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
header('Content-Disposition: attachment; filename="clubMember.csv"');

$year = date("Y")-5;

$query = "SELECT P.first_name, P.middle_name, P.last_name, P.address"
        . ", P.city, P.state, P.zip, P.phone, P.email_addr, CONCAT_WS('/', P.badge_name, P.badgeNameL2)"
        . ", B.type, B.year"
    . " FROM club as B JOIN perinfo as P ON P.id=B.perid"
    . " WHERE type in ('eternal', 'life', 'child')"
        . " OR (type = 'annual' and year >= $year)"
    . " ORDER BY type, year DESC"
    . ";";


echo "first_name, middle_name, last_name, address, city, state, zip, phone, email_addr, badge_name, member type, year"
    . "\n";

$reportR = dbQuery($query);
while($reportL = fetch_safe_array($reportR)) {
    for($i = 0 ; $i < count($reportL); $i++) {
        printf("\"%s\",", $reportL[$i]);
    }
    echo "\n";
}
