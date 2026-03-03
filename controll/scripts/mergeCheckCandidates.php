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

// first check if the to merge is a manager of anyone
$checkQ = <<<EOS
SELECT COUNT(*)
FROM perinfo 
WHERE managedBy = ?;
EOS;

$checkR = dbSafeQuery($checkQ, 'i', array($merge));
$mc = $checkR->fetch_row()[0];
if ($mc > 0) {
    $response['error'] = 'The "perid to merge in remain" person manages others. This is a conflict, cannot continue with this merge.';
    ajaxSuccess($response);
    exit();
}

$checkQ = <<<EOS
SELECT COUNT(*)
FROM newperson 
WHERE managedBy = ? AND perid IS NULL;
EOS;
$checkR = dbSafeQuery($checkQ, 'i', array($merge));
$mc = $checkR->fetch_row()[0];
if ($mc > 0) {
    $response['error'] = 'The "perid to merge in remain" person manages people yet to be matched. This is a conflict, cannot continue with this merge.';
    ajaxSuccess($response);
    exit();
}

$checkQ = <<<EOS
SELECT p.*,
    TRIM(REGEXP_REPLACE(CONCAT_WS(' ', p.first_name, p.middle_name, p.last_name, p.suffix), ' +', ' ')) AS fullName,
    TRIM(REGEXP_REPLACE(CONCAT_WS(' ', mp.first_name, mp.middle_name, mp.last_name, mp.suffix), ' +', ' ')) AS manager,
    TRIM(REGEXP_REPLACE(CONCAT_WS(' ', p.address, p.addr_2, p.city, p.state, p.zip, p.country), ' +', ' ')) AS fullAddr
FROM perinfo p
LEFT OUTER JOIN perinfo mp ON p.managedBy = mp.id
WHERE p.id IN (?,?); 
EOS;

$checkR = dbSafeQuery($checkQ, 'ii', array($remain, $merge));
$values = [];

while ($checkL = $checkR->fetch_assoc()) {
    $checkL['badgeNameDef'] = badgeNameDefault($checkL['badge_name'], $checkL['badgeNameL2'], $checkL['first_name'], $checkL['last_name']);
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
