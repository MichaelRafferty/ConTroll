<?php
global $db_ini;

require_once '../lib/base.php';
$check_auth = google_init('ajax');
$perm = 'vendor';

$response = array('post' => $_POST, 'get' => $_GET, 'perm' => $perm);

if ($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = 'Authentication Failed';
    ajaxSuccess($response);
    exit();
}

$con = get_con();
$conid = $con['id'];

if (!(array_key_exists('vendorId', $_POST) && array_key_exists('spaceId',$_POST) && array_key_exists('id',$_POST) && array_key_exists('operation',$_POST))) {
      ajaxError('No Data');
      return;
}

$vendorId = $_POST['vendorId'];
$spaceId = $_POST['spaceId'];
$vsID = $_POST['id'];
$operation = $_POST['operation'];

if ($operation == 'approve') {
    $updateQ = <<<EOS
UPDATE vendor_space
SET item_approved = ?
WHERE id = ?
EOS;
    $approved = $_POST['sr_approved'];
    if ($approved == 0)
        $approved = null;
    $numRows = dbSafeCmd($updateQ, 'ii', array($approved, $vsID));
    if ($numRows == 1)
        $response['success'] = 'Space Approved';
    else if ($numRows == 0)
        $response['success'] = 'Nothing to update';
    else
        $response['error'] = 'Error occured updated database';

    ajaxSuccess($response);
    return;
}

/*
if(is_numeric($_POST['alleyRequest'])) { 
$auth = $_POST['alleyAuth']; 
$purch = $_POST['alleyPurch'];
if(!is_numeric($auth)) { $auth = 0;}
if(!is_numeric($purch)) { $purch = 0;}
$updateAlley="INSERT INTO vendor_show (vendor, conid, type, requested, authorized, purchased) VALUES"
    . "('$vendor','$conid','alley','".sql_safe($_POST['alleyRequest'])."'"
    . ",'".sql_safe($auth)."'"
    . ",'".sql_safe($purch)."')"
    . " ON DUPLICATE KEY UPDATE"
    . " requested='".sql_safe($_POST['alleyRequest'])."'"
    . ", authorized='".sql_safe($auth)."'"
    . ", purchased='".sql_safe($purch)."';";
dbQuery($updateAlley);
}

if(is_numeric($_POST['dealerRequest'])) { 
$auth = $_POST['dealerAuth']; 
$purch = $_POST['dealerPurch'];
if(!is_numeric($auth)) { $auth = 0;}
if(!is_numeric($purch)) { $purch = 0;}
$updateAlley="INSERT INTO vendor_show (vendor, conid, type, requested, authorized, purchased) VALUES"
    . "('$vendor','$conid','dealer_6','".sql_safe($_POST['dealerRequest'])."'"
    . ",'".sql_safe($auth)."'"
    . ",'".sql_safe($purch)."')"
    . " ON DUPLICATE KEY UPDATE"
    . " requested='".sql_safe($_POST['dealerRequest'])."'"
    . ", authorized='".sql_safe($auth)."'"
    . ", purchased='".sql_safe($purch)."';";
dbQuery($updateAlley);
}

if(is_numeric($_POST['d10Request'])) { 
$auth = $_POST['d10Auth']; 
$purch = $_POST['d10Purch'];
if(!is_numeric($auth)) { $auth = 0;}
if(!is_numeric($purch)) { $purch = 0;}
$updateAlley="INSERT INTO vendor_show (vendor, conid, type, requested, authorized, purchased) VALUES"
    . "('$vendor','$conid','dealer_10','".sql_safe($_POST['d10Request'])."'"
    . ",'".sql_safe($auth)."'"
    . ",'".sql_safe($purch)."')"
    . " ON DUPLICATE KEY UPDATE"
    . " requested='".sql_safe($_POST['d10Request'])."'"
    . ", authorized='".sql_safe($auth)."'"
    . ", purchased='".sql_safe($purch)."';";
dbQuery($updateAlley);
}

$query = "SELECT V.id as id, name, website, description, publicity"
        . ", A.requested as alleyRequest, A.authorized as alleyAuth, A.purchased as alleyPurch"
        . ", D6.requested as dealerRequest, D6.authorized as dealerAuth, D6.purchased as dealerPurch"
        . ", DX.requested as d10Request, DX.authorized as d10Auth, DX.purchased as d10Purch"
    . " FROM vendors as V"
        . " LEFT JOIN vendor_show as A ON A.vendor=V.id and A.type='alley' and A.conid=$conid"
        . " LEFT JOIN vendor_show as D6 ON D6.vendor=V.id and D6.type='dealer_6' and D6.conid=$conid"
        . " LEFT JOIN vendor_show as DX ON DX.vendor=V.id and DX.type='dealer_10' and DX.conid=$conid"
    . " WHERE V.id='$vendor';";
$response['query']=$query;
$resp = dbQuery($query);

if ($resp->num_rows == 0) { $response['error'] = "No Info Found";}
else { $response=fetch_safe_assoc($resp); }

ajaxSuccess($response);
?>
*/
