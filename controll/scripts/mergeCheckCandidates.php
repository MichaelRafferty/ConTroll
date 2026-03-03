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
$conid = getConfValue('con', 'id');

$checkQ = <<<EOS
SELECT p.*,
    TRIM(REGEXP_REPLACE(CONCAT_WS(' ', p.first_name, p.middle_name, p.last_name, p.suffix), ' +', ' ')) AS fullName,
    TRIM(REGEXP_REPLACE(CONCAT_WS(' ', mp.first_name, mp.middle_name, mp.last_name, mp.suffix), ' +', ' ')) AS manager
FROM perinfo p
LEFT OUTER JOIN perinfo mp ON p.managedBy = mp.id
WHERE p.id IN (?,?); 
EOS;

$checkR = dbSafeQuery($checkQ, 'ii', array($remain, $merge));
$values = [];

while ($checkL = $checkR->fetch_assoc()) {
    $bn = badgeNameDefault($checkL['badge_name'], $checkL['badgeNameL2'], $checkL['first_name'], $checkL['last_name']);
    $checkL['badgeName'] = str_replace('</i>', '', str_replace('<i>', '', str_replace('<br/>', '/', $bn)));
    $checkL['policies'] = [];
    if ($checkL['id'] == $remain)
        $values['remain'] = $checkL;

    if ($checkL['id'] == $merge)
        $values['merge'] = $checkL;
}
$checkR->free();
$checkPOLQ = <<<EOS
SELECT *
FROM memberPolicies
WHERE conid = ? AND perid IN (?, ?);
EOS;
$checkR = dbSafeQuery($checkPOLQ, 'iii', array($conid, $remain, $merge));
while ($checkL = $checkR->fetch_assoc()) {
    if ($checkL['perid'] == $merge)
        $values['merge']['policies'][$checkL['policy']] = $checkL['response'];
    else
        $values['remain']['policies'][$checkL['policy']] = $checkL['response'];
}
$checkR->free();

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
