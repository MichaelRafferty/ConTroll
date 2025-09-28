<?php
require_once "../lib/base.php";

$check_auth = google_init("ajax");
$perm = "club";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);


if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

$clubQ = <<<EOQ
SELECT * FROM clubTypes;
EOQ;

$response['query']=$clubQ;
$response['clubTypes']=array();

$clubR = dbQuery($clubQ);
while($type = $clubR->fetch_assoc()) {
    array_push($response['clubTypes'], $type);
}

ajaxSuccess($response);