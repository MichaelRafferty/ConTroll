<?php
global $db_ini;

require_once "../lib/base.php";

$check_auth = google_init("ajax");
$perm = "reg_staff";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);

if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

if(!isset($_POST)) {
    $response['error'] = "No Data";
    ajaxSuccess($response);
    exit();
}

if (!(array_key_exists('remain', $_POST) && array_key_exists('merge',$_POST))) {
    $response['error'] = 'Invalid Calling Sequence';
    ajaxSuccess($response);
    exit();
}

$remain = $_POST['remain'];
$merge = $_POST['merge'];

$checkQ = <<<EOS
SELECT id, last_name, first_name, middle_name, suffix, badge_name, email_addr, address, addr_2, city, state, zip, country
FROM perinfo
WHERE id IN (?,?); 
EOS;

$checkR = dbSafeQuery($checkQ, 'ii', array($remain, $merge));
$values = [];

while ($checkL = $checkR->fetch_row()) {
    if ($checkL[0] == $remain)
        $values['remain'] = $checkL;

    if ($checkL[0] == $merge)
        $values['merge'] = $checkL;
}


$response['values'] = $values;
$error = '';
if (!array_key_exists('remain', $values))
    $error .= 'Remain Perinfo record not found';
if (!array_key_exists('merge', $values)) {
    if ($error != '')
        $error .= '<br/>';
    $error .= 'Merge Perinfo record not found';
}

if ($error != '')
    $response['error'] = $error;

ajaxSuccess($response);
?>
