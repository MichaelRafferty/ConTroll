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

if (!isSessionVar('user')) {
    header('Location: /index.php');
    exit(0);
}

$user_id = getSessionVar('user');
if ($user_id == null) {
    $response['error'] = 'User not logged in';
    ajaxSuccess($response);
    exit();
}

$conid = getConfValue('con', 'id', '-1');

if (!array_key_exists('ajax_request_action', $_POST) || ($_POST['ajax_request_action'] != 'newBidderCheck' && $_POST['ajax_request_action'] != 'newPickupCheck')) {
    $response['error'] = 'Invalid Parameters';
    ajaxSuccess($response);
    exit();
}
$action=$_POST['ajax_request_action'];
$perid = $_POST['perid'];

// get the bidder name
// get initial list of pickup relationships
$pSQL = <<<EOS
SELECT  TRIM(REGEXP_REPLACE(CONCAT_WS(' ', first_name, middle_name, last_name, suffix), ' +', ' ')) AS fullName    
FROM perinfo 
WHERE id = ?;
EOS;
$pR = dbSafeQuery($pSQL, 'i', array($perid));
if ($pR === false) {
    RenderErrorAjax('Query failed, seek assistance');
    exit();
}
if ($pR->num_rows != 1) {
    $response['error'] = 'No person found';
} else {
    $response['fullName']  = $pR->fetch_row()[0];
}
$pR->free();
ajaxSuccess($response);
