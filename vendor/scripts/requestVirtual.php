<?php

// library AJAX Processor: requestVirtual.php
// Balticon Registration System
// Author: Syd Weinstein
// enter a space request for the virtual dealers area

require_once('../lib/base.php');
$ini = redirect_https();

require_once('../../lib/email__load_methods.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

require_once "../lib/email.php";

$response = array("post" => $_POST, "get" => $_GET);

$vendor = $_SESSION['id'];
$con = get_conf('con');
$conid=$con['id'];

$response['conid'] = $conid;
$info = get_conf('vendor');

// get limits on the vendor registraiton
$itemCheckQ = "SELECT total, registered, max_per, price_full FROM vendor_reg WHERE conid=? and type='virtual';";
$itemCheckR = dbSafeQuery($itemCheckQ, 'i', array($conid));
if ($itemCheckR->num_rows != 1) {
    $response['error'] = 'Virtual Vendor Spaces are not configured';
    ajaxSuccess($response);
    return;
}
$itemCheck = fetch_safe_assoc($itemCheckR);

// mark that this vendor requested virtual space, and limit them to the max allowed
$requested = 1;
$type = $_POST['virtual'];

if($requested > $itemCheck['max_per']) { $requested = $itemCheck['max_per']; }
if($itemCheck['registered'] + $requested > $itemCheck['total']) {
    $requested = $itemCheck['total'] - $itemCheck['registered'];
}

$response['virtual'] = $type;
$response['requested'] = $requested;
$response['price'] = $itemCheck['price_full'];
// add the request to the system
$v_update = "UPDATE vendors SET request_virtual=true WHERE id=?;";
dbSafeCmd($v_update, 'i', array($vendor));
// update the space request list in the system
$req_insert = <<<EOS
INSERT IGNORE INTO vendor_show (vendor, conid, type, requested, authorized)
VALUES (?, ?, 'virtual', ?, ?);
EOS;
dbSafeInsert($req_insert, 'iiii', array($vendor, $conid, $requested, $requested));

// send them an email
$v_query = "SELECT email FROM vendors where id=?;";
$r_email = fetch_safe_assoc(dbSafeQuery($v_query, 'i', array($vendor)));
$v_email = $r_email['email'];
$response['email']=$v_email;

load_email_procs();

$email = "no send attempt or a failure";
$email_body = request('Virtual Vendor Space', $vendor);
$return_arr = send_email($con['regadminemail'], $v_email, $info['alley'], "Virtual Vendor Request", $email_body, null);

if (array_key_exists('error_code', $return_arr)) {
    $error_code = $return_arr['error_code'];
} else {
    $error_code = null;
}
if (array_key_exists('email_error', $return_arr)) {
    $response['error'] = 'Unable to send request email, error: ' . $return_arr['email_error'] . ', Code: $error-code';
} else {
    if (array_key_exists('message', $response))
        $response['message'] .= "<br/>Request sent for $v_email";
    else
        $response['message'] = "Request sent for $v_email";
}

ajaxSuccess($response);
?>
