<?php
// ConTroll Registration System, Copyright 2015-2026, Michael Rafferty, Licensed under the GNU Affero General Public License, Version 3.
// library AJAX Processor: artAltPickup_updateGetData.php
// Author: Syd Weinstein
// update the authorization data and get the new table

require_once "../lib/base.php";

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$user_id = getSessionVar('user');
if ($user_id == null) {
    $response['error'] = 'User not logged in';
    ajaxSuccess($response);
    exit();
}

$conid = getConfValue('con', 'id', '-1');

if (!array_key_exists('ajax_request_action', $_POST)) {
    $response['error'] = 'Parameter Missing';
    ajaxSuccess($response);
    exit();
}
$message = '';
$errors = '';
$action=$_POST['ajax_request_action'];
switch ($action) {
    case 'loadInitialData':
        // nothing special to do here
        break;
    case 'save':
        // add / update rows
        try {
            $rows = json_decode($_POST['rows'], true);
        } catch (Exception $e) {
            $response['error'] = 'Invalid JSON Passed, seek assistance.';
            ajaxSuccess($response);
            exit();
        }
        $insQY = <<<EOS
INSERT INTO artshowAltPickupAuth (conid, bidderPerid, pickupPerid, createdBy, createDate, active, deactivateDate, deactivatedBy)
VALUES (?, ?, ?, ?, NOW(), 'Y', null, null);;
EOS;
        $insDY = 'iiii';
        $insQN = <<<EOS
INSERT INTO artshowAltPickupAuth (conid, bidderPerid, pickupPerid, createdBy, createDate, active, deactivateDate, deactivatedBy)
VALUES (?, ?, ?, ?, NOW(), 'N', NOW(), ?);;
EOS;
        $insDN = 'iiiii';
        $updQY = <<<EOS
UPDATE artshowAltPickupAuth SET active = 'Y', deactivateDate = null, deactivatedBy = null
WHERE conid = ? AND bidderPerid = ? AND pickupPerid = ?;
EOS;
        $updDY = 'iii';
        $updQN = <<<EOS
UPDATE artshowAltPickupAuth SET active = 'N', deactivateDate = NOW(), deactivatedBy = ?
WHERE conid = ? AND bidderPerid = ? AND pickupPerid = ?;
EOS;
        $updDN = 'iiii';

        $numIns = 0;
        $numUpd = 0;
        foreach ($rows as $row) {
            $active = $row['active'];
            if ((!array_key_exists('createdBy', $row)) || $row['createdBy'] == null || $row['createdBy'] == '') {
                if ($active == 'Y') {
                    $insData = array ($conid, $row['bidderPerid'], $row['pickupPerid'], $user_id);
                    $newKey = dbSafeInsert($insQY, $insDY, $insData);
                } else {
                    $insData = array ($conid, $row['bidderPerid'], $row['pickupPerid'], $user_id, $user_id);
                    $newKey = dbSafeInsert($insQN, $insDN, $insData);
                }
                if ($newKey === false) {
                    $errors .= "Unable to insert row " . $row['bidderPerid'] . ', ' . $row['pickupPerid'] . ", $active<br/>";
                } else {
                    $numIns++;
                }
            } else {
                if ($active == 'Y') {
                    $updData = array ($conid, $row['bidderPerid'], $row['pickupPerid']);
                    $updResult = dbSafeCmd($updQY, $updDY, $updData);
                    if ($updResult === false) {
                        $errors .= "Unable to update row " . $row['bidderPerid'] . ', ' . $row['pickupPerid'] . ", $active<br/>";
                    } else {
                        $numUpd += $updResult;
                    }
                } else {
                    $updData = array ($user_id, $conid, $row['bidderPerid'], $row['pickupPerid']);
                    $updResult = dbSafeCmd($updQN, $updDN, $updData);
                    if ($updResult === false) {
                        $errors .= 'Unable to update row ' . $row['bidderPerid'] . ', ' . $row['pickupPerid'] . ", $active<br/>";
                    } else {
                        $numUpd += $updResult;
                    }
                }
            }
        }
        $message = "$numIns rows inserted, $numUpd rows updated<br/>";
        break;
}

// now get the data table
// get initial list of pickup relationships
$pSQL = <<<EOS
SELECT a.*, pb.first_name, pb.middle_name, pb.last_name,
       TRIM(REGEXP_REPLACE(CONCAT_WS(' ', pb.first_name, pb.middle_name, pb.last_name, pb.suffix), ' +', ' ')) AS bidderFullName,
       TRIM(REGEXP_REPLACE(CONCAT_WS(' ', pp.first_name, pp.middle_name, pp.last_name, pp.suffix), ' +', ' ')) AS pickupFullName
FROM artshowAltPickupAuth a
LEFT OUTER JOIN perinfo pb ON pb.id = a.bidderPerid
LEFT OUTER JOIN perinfo pp ON pp.id = a.pickupPerid
WHERE conid = ?;
EOS;
$pR = dbSafeQuery($pSQL, 'i', array($conid));
if ($pR === false) {
    RenderErrorAjax('Query failed, seek assistance');
    exit();
}
$pickupAuths = [];
$ordinal = 0;
while ($pL = $pR->fetch_assoc()) {
    $pL['ordinal'] = $ordinal++;
    $pickupAuths[] = $pL;
}
$pR->free();
$response['authList'] = $pickupAuths;
if ($errors != '') {
    $response['error'] = $errors . '<br/>' . "$ordinal rows found";
} else {
    $response['message'] = $message . "$ordinal rows found";
}
ajaxSuccess($response);
