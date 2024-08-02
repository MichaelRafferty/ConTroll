<?php
require_once('../lib/base.php');
require_once('../../lib/log.php');
require_once('../../lib/interests.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$response = array('post' => $_POST, 'get' => $_GET);

$con = get_con();
$conid=$con['id'];
$conf = get_conf('con');
$portal_conf = get_conf('portal');
$log = get_conf('log');

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
if ($existingInterests == null)
    $existingInterests = array();

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
$interests = getInterests();
foreach ($interests as $interest) {
    $interestName = $interest['interest'];
    $newVal = array_key_exists($interestName, $newInterests) ? 'Y' : 'N';
    if (array_key_exists($interestName, $existingInterests)) {
        // this is an update, there is a record already in the memberInterests table for this interest.
        $existing = $existingInterests[$interestName];
        if (array_key_exists('interested', $existing)) {
            $oldVal = $existing['interested'];
        } else {
            $oldVal = '';
        }
        // only update if changed
        if ($newVal != $oldVal) {
            $upd = 0;
            if ($existing['id'] != null) {
                $upd = dbSafeCmd($updInterest, 'sii', array($newVal, $personId, $existing['id']));
            }
            if ($upd === false || $upd === 0) {
                $newkey = dbSafeInsert($insInterest, 'iissi', array($currentPerson, $conid, $interestName, $newVal, $personId));
                if ($newkey !== false && $newkey > 0)
                    $rows_upd++;
            } else {
                $rows_upd++;
            }
        }
    } else {
        // row doesn't exist in existing interests
        $newkey = dbSafeInsert($insInterest, 'iissi', array($currentPerson, $conid, $interestName, $newVal, $personId));
        if ($newkey !== false && $newkey > 0)
            $rows_upd++;
    }
}

$response['rows_upd'] = $rows_upd;
$response['status'] = 'success';
$response['logmessage'] = $rows_upd == 0 ? "No changes" : "$rows_upd interests updated";
$response['message'] = 'Interests successfully updated';
logInit($log['reg']);
logWrite($response);
ajaxSuccess($response);
