<?php

require_once "../lib/base.php";

$check_auth = google_init("ajax");
$perm = "vendor";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);


if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

$vendorQ = <<<EOS
SELECT V.id
FROM perinfo P 
JOIN vendors V ON (V.email=P.email_addr)
WHERE P.id=?;
EOS;
$resultR = dbSafeQuery($vendorQ, 'i', array($_GET['perid']));
$result = fetch_safe_assoc($resultR);

$response['vendor'] = $result['id'];

ajaxSuccess($response);
?>
