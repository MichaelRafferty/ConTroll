<?php
require_once "../lib/base.php";

$check_auth = google_init("ajax");
$perm = "art_control";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);

if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

$conf = get_conf('con');
if (!array_key_exists('action', $_POST) || $_POST['action'] != "ValidateBidder") {
    $response['error'] = 'Invalid Calling Sequence';
    ajaxSuccess($response);
    exit();
}

if (!array_key_exists('bidder', $_POST)) {
    $response['error'] = 'No bidder passed';
    ajaxSuccess($response);
    exit();
}

$bidder = $_POST['bidder'];


$bidderQ = <<<EOS
SELECT TRIM(REGEXP_REPLACE(CONCAT_WS(' ', p.first_name, p.middle_name, p.last_name, p.suffix), ' +', ' ')) AS fullName
FROM perinfo p
WHERE id = ?;
EOS;

$bidderR = dbSafeQuery($bidderQ, 'i', array($bidder));
if ($bidderR !== false) {
    if ($bidderR->num_rows != 1) {
        $response['error'] = 'Bidder not found';
        ajaxSuccess($response);
        exit();
    }
    $name = $bidderR->fetch_row()[0];
    $bidderR->free();
}

$response['name'] = $name;

ajaxSuccess($response);
