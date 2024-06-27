<?php
require_once('../lib/base.php');
require_once('../../lib/log.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$response = array('post' => $_POST, 'get' => $_GET);

$con = get_con();
$conid=$con['id'];
$conf = get_conf('con');
$portal_conf = get_conf('portal');

$response['conid'] = $conid;

if (!(array_key_exists('currentPerson', $_POST) && array_key_exists('currentPersonType', $_POST)
    && array_key_exists('existingInterests', $_POST) && array_key_exists('newInterests', $_POST))) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Parameter error - get assistance'));
    exit();
}

if (!(isSessionVar('id') && isSessionVar('idType'))) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Not logged in.'));
    exit();
}

$personId = getSessionVar('id');
$personType = getSessionVar('idType');

$currentPerson = $_POST['currentPerson'];
$currentPersonType = $_POST['currentPersonType'];
$newInterests = json_decode($_POST['newInterests'], true);
$existingInterests = json_decode($_POST['existingInterests'], true);

$response['currentPersonType'] = $currentPersonType;
$response['currentPeron'] = $currentPerson;
$response['personId'] =$personId;
$response['personType'] = $personType;

// find the differences in the interests to update the record

if ($currentPersonType == 'p') {
    $pfield = 'perid';
} else if ($currentPersonType == 'n') {
    $pfield = 'newperid';
}
$updInterest =  <<<EOS
UPDATE memberInterests
SET interested = ?, updateBy = ?
WHERE id = ?;
EOS;
$insInterest = <<<EOS
INSERT INTO memberInterests($pfield, conid, interest, interested, updateBy)
VALUES (?, ?, ?, ?, ?);
EOS;

$rows_upd = 0;
foreach ($existingInterests as $existing) {
    $newVal = array_key_exists($existing['interest'], $newInterests) ? 'Y' : 'N';
    if (array_key_exists('interested', $existing)) {
        if ($newVal != $existing['interested']) { // only update changes
            $upd = 0;
            if ($existing['id'] != null) {
                $upd = dbSafeCmd($updInterest, 'sii', array($newVal, $personId, $existing['id']));
            }
            if ($upd === false || $upd === 0) {
                $newkey = dbSafeInsert($insInterest, 'iissi', array($currentPerson, $conid, $existing['interest'], $newVal, $personId));
                if ($newkey !== false && $newkey > 0)
                    $rows_upd++;
            } else {
                $rows_upd++;
            }
        }
    } else {
        $newkey = dbSafeInsert($insInterest, 'issi', array($currentPerson, $existing['interest'], $newVal, $personId));
        if ($newkey !== false && $newkey > 0)
            $rows_upd++;
    }
}

$response['rows_upd'] = $rows_upd;
$response['status'] = 'success';
$response['message'] = $rows_upd == 0 ? "No changes" : "$rows_upd interests updated";
ajaxSuccess($response);
