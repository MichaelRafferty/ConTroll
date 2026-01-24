<?php
// ConTroll Registration System, Copyright 2015-2025, Michael Rafferty, Licensed under the GNU Affero General Public License, Version 3.
// library AJAX Processor: resetPassword.php
// Author: Syd Weinstein
// check if a reset password token can be sent, and if so, create and send the token to the email posted
require_once('../lib/base.php');
require_once('../../lib/email__load_methods.php');
require_once '../lib/email.php';

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$response = array('post' => $_POST, 'get' => $_GET);

$conid=getConfValue('con', 'id');
$response['conid'] = $conid;
$vendor_conf = get_conf('vendor');

$login = "";
if(($_SERVER['REQUEST_METHOD'] == "POST") and isset($_POST['login'])) {
    $login = strtolower($_POST['login']);
} 

if($login == "") {
    $response['status'] = 'error';
    $response['error'] = 'No email provided';
    ajaxSuccess($response);
    exit();
}

$response = array('email' => $login);

$infoQ = <<<EOS
SELECT id, email, action, source_ip, createdTS, useCnt, useIP, useTS
FROM portalTokenLinks
WHERE email = ? AND action = 'password' AND useCnt = 0;
EOS;
$infoR = dbSafeQuery($infoQ, 's', array($login));
if ($infoR === false) {
    $response['status'] = 'error';
    $response['error'] = 'Reset password query error, seek assistance';
    ajaxSuccess($response);
    exit();
}

// check to see if there is a unused request
$req = null;
while ($infoL = $infoR->fetch_assoc()) {
    $req = $infoL;
    break;
}
$infoR->free();

if ($req != null) {
    // there is an outstanding unused request that is less than one hour old, tell the user to wait and try again
    $create = strtotime($req['createdTS']);
    $now = time();
    $diff = round((31 + ($now - $create)) / 60, 0);
    if ($diff < 60) {
        $remaining = 60 - $diff;
        $response['status'] = 'error';
        $response['error'] = "There is an outstanding reset request for $login, please wait $remaining minutes before trying a new reset request";
        ajaxSuccess($response);
        exit();
    }
}

// create the token here
$insQ = <<<EOS
INSERT INTO portalTokenLinks(email, action, source_ip)
VALUES(?, 'password', ?);
EOS;
$insid = dbSafeInsert($insQ, 'ss', array($login, $_SERVER['REMOTE_ADDR']));
if ($insid === false) {
    web_error_log('Error inserting tracking ID for email link');
    $response['status'] = 'error';
    $response['error'] = 'Error inserting tracking ID for email link, seek assistance';
    ajaxSuccess($response);
    exit();
}

$parms = array();
$parms['email'] = $login;       // address to reset passwords
$parms['type'] = 'password-reset';  // verify type
$parms['ts'] = time();          // when requested for timeout check
$parms['lid'] = $insid;         // id in portalTokenLinks table
$string = json_encode($parms);  // convert object to json for making a string out of it, which is encrypted in the next line
$string = encryptCipher($string, true);

// build email here
$portalType = $_POST['type'];
$portalName = $_POST['name'];
$reply = $vendor_conf[$portalType];
$dest = $vendor_conf[$portalType . 'site'];
$token = $dest. "/passwordReset.php?vid=$string";     // convert to link for emailing
$textBody = vendorReset($token, $login, $portalName, $reply);
$email = "no send attempt or a failure";
load_email_procs();
$response['status'] = 'success';
$response['message'] = '<p>A password reset email has been sent to ' . $login . '. This email is only valid for a single use within the next hour.<br/>' .
    'Please check your spam folder, but if you did not receive an email, or have any other problems, please contact ' . $reply . ' for assistance.
    </p>';

$return_arr = send_email(getConfValue('con', 'regadminemail'), $login, null, "$portalName Password Reset Request", $textBody, null);

if (array_key_exists('error_code', $return_arr)) {
    $error_code = $return_arr['error_code'];
} else {
    $error_code = null;
}
if (array_key_exists('email_error', $return_arr)) {
    $response['status'] = 'error';
    $response['error'] = 'Unable to send receipt email, error: ' . $return_arr['email_error'] . ', Code: $error_code';
} else {
    $respose['status'] = 'success';
    $response['email'] = 'Request sent';
}


ajaxSuccess($response);
exit();
