<?php
require_once "../lib/base.php";
require_once '../lib/sessionAuth.php';

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$perm = 'reg_staff';
$response = array ('post' => $_POST, 'get' => $_GET, 'perm' => $perm);
$authToken = new authToken('script');
$response['tokenStatus'] = $authToken->checkToken();
if (!$authToken->isLoggedIn() || !$authToken->checkAuth($perm)) {
    $response['error'] = 'Authentication Failed';
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
SELECT id, last_name, first_name, middle_name, suffix, badge_name, badgeNameL2, email_addr, address, addr_2, city, state, zip, country
FROM perinfo
WHERE id IN (?,?); 
EOS;

$checkR = dbSafeQuery($checkQ, 'ii', array($remain, $merge));
$values = [];

while ($checkL = $checkR->fetch_row()) {
    $bn = badgeNameDefault($checkL[6], $checkL[7], $checkL[2], $checkL[1]);
    $checkL[14] = str_replace('</i>', '', str_replace('<i>', '', str_replace('<br/>', '/', $bn)));
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
