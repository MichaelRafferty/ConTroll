<?php
global $db_ini;

require_once "../lib/base.php";
$check_auth = google_init("ajax");
$perm = "admin";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);

if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

$type = $_POST['type'];

$con=get_con();
$conid= $con['id'];
$nextconid = $conid + 1;

$policySQL = <<<EOS
SELECT p.policy, p.prompt, p.description, p.sortOrder, p.required, p.defaultValue, p.createDate, p.updateDate, p.updateBy, p.active,
       p.policy as policyKey, count(*) AS uses
FROM policies p
LEFT OUTER JOIN memberPolicies mP ON p.policy = mP.policy
GROUP BY p.policy, p.prompt, p.description, p.sortOrder, p.required, p.defaultValue, p.createDate, p.updateDate, p.updateBy, p.active
ORDER BY sortorder, policy;
EOS;

$result = dbQuery($policySQL);
$policies = array();
while($memage = $result->fetch_assoc()) {
    array_push($policies, $memage);
}
$response['policies'] = $policies;

ajaxSuccess($response);
?>
