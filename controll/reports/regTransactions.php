<?php
require_once "../lib/base.php";
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
header('Content-Disposition: attachment; filename="reg_transactions.csv"');

$query = <<<EOS
SELECT T.id, Y.type, Y.description, COUNT(DISTINCT R.perid) AS people, COUNT(DISTINCT P.email_addr) AS emails, T.create_date, SUM(R.paid) AS reg_paid, T.paid, Y.amount
FROM memList M
JOIN reg R ON (R.memId=M.id)
JOIN perinfo P ON (P.id=R.perid)
JOIN transaction T ON (T.id=R.create_trans)
JOIN payments Y ON (Y.transid=T.id)
WHERE M.conid=? and M.memCategory IN ('standard', 'yearahead')
GROUP BY T.id 
ORDER BY emails;
EOS;

echo "First Name, Last Name, Email, Type, Price, Transaction, Total, Method, Description, Paid\n";

$reportR = dbSafeQuery($query, 'i', array($conid));
while($reportL = fetch_safe_array($reportR)) {
    for($i = 0 ; $i < count($reportL); $i++) {
        printf("\"%s\",", $reportL[$i]);
    }
    echo "\n";
}
