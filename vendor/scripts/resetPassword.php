<?php
require_once('../lib/base.php');
require_once('../../lib/email__load_methods.php');
require_once '../lib/email.php';

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$response = array('post' => $_POST, 'get' => $_GET);

global $con;
$con = get_con();
$conid=$con['id'];
$conf = get_conf('con');
$vendor_conf = get_conf('vendor');

$response['conid'] = $conid;

$login = "";
if(($_SERVER['REQUEST_METHOD'] == "GET") and isset($_GET['login'])) { 
    $login = strtolower($_GET['login']);
}
else if(($_SERVER['REQUEST_METHOD'] == "POST") and isset($_POST['login'])) { 
    $login = strtolower($_POST['login']);
} 

if($login == "") {
    ajaxError('No Email Provided');
    exit();
}

$response = array('email' => $login);

$str = str_shuffle(
'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#%^&*()-{}|_'
.
'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#%^&*()-{}|_'
);

$infoQ = "SELECT id, contactEmail, need_new FROM vendors WHERE contactEmail = ?;";
$infoR = dbSafeQuery($infoQ, 's', array($login));

$len = rand(10,16);
$start = rand(0,strlen($str)-$len);
$newpasswd = substr($str, $start, $len);
$hash = password_hash($newpasswd, PASSWORD_DEFAULT);


// build email here
$portalType = $_POST['type'];
$portalName = $_POST['name'];
$reply = $vendor_conf[$portalType];
$dest = $vendor_conf[$portalType . 'site'];

// should we tell them they goofed on the email?  this allows for phishing for email addresses of vendors and artists
if(($infoR->num_rows == 0) or ($infoR->num_rows > 1)){
    $response['status'] = 'error';
    $response['error'] = "No user found with that email";
    ajaxSuccess($response);
    exit();
}

$info = $infoR->fetch_assoc();
if($info['need_new']) {
    $response['status'] = 'error';
    $response['error'] = 'A password reset email has previously been sent.  If you are still having problems loging into your account please contact ' . $reply . ' for assistance.';
    ajaxSuccess($response);
    exit();
}

$updateQ = "UPDATE vendors SET need_new=1, password=? where id=?;";
$email = "no send attempt or a failure";
load_email_procs();
$num_rows = dbSafeCmd($updateQ, 'si', array($hash, $info['id']));
if ($num_rows != 1) {
    $response['status'] = 'error';
    $response['error'] = "Database update error";
} else {
    $response['status'] = 'success';
    $response['message'] = '<p>A password reset email has been sent to ' . $login . ' please change your password as soon as you login.<br/>' .
        'Please check your spam folder, but if you did not receive an email, or have any other problems, please contact ' . $reply . ' for assistance.
        </p>';
}

$return_arr = send_email($conf['regadminemail'], $login, null, 'Password Reset Request', vendorReset($newpasswd, $dest, $portalName, $reply), null);

if (array_key_exists('error_code', $return_arr)) {
    $error_code = $return_arr['error_code'];
} else {
    $error_code = null;
}
if (array_key_exists('email_error', $return_arr)) {
    $response['status'] = 'error';
    $response['error'] = 'Unable to send receipt email, error: ' . $return_arr['email_error'] . ', Code: $error-code';
} else {
    $respose['status'] = 'success';
    $response['email'] = 'Request sent';
}


ajaxSuccess($response);
exit();
