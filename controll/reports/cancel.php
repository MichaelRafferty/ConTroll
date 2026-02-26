<?php
require_once "../lib/base.php";
require_once '../lib/sessionAuth.php';

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$perm = 'admin';
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
header('Content-Disposition: attachment; filename="canceled_memberships.csv"');

$query = <<<EOS
SELECT T.id, P.first_name, P.last_name, P.email_addr, P.address, P.addr_2, P.city, P.state, P.zip, P.country, M.label, R.paid
    , Y.type, Y.description, Y.amount, Y.txn_time, Y.cc_txn_id
FROM memLabel M
JOIN reg R ON (R.memId=M.id)
JOIN transaction T ON (T.id=R.create_trans)
JOIN perinfo P ON (P.id=R.perid)
JOIN payments Y ON (Y.transid=T.id)
WHERE M.memCategory in ('cancel') and M.conid=?
ORDER BY txn_time;
EOS;

echo "ID, First Name, Last Name, Email, Addr_1, Addr_2, City, State, Zip, Country, Action, Amount, Method, Source, Amount2, Time, Transaction\n";

$reportR = dbSafeQuery($query, 'i', array($conid));
while($reportL = fetch_safe_array($reportR)) {
    for($i = 0 ; $i < count($reportL); $i++) {
        printf("\"%s\",", $reportL[$i]);
    }
    echo "\n";
}
