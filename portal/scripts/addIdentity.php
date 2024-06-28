<?php
// addIdentity - request a confirm email to add an identity to your account.
require_once('../lib/base.php');
require_once('../../lib/log.php');
require_once('../../lib/cipher.php');
require_once('../../lib/email__load_methods.php');

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

if (!(array_key_exists('action', $_POST) && array_key_exists('provider', $_POST) && array_key_exists('email', $_POST))) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Parameter error - get assistance'));
    exit();
}

if (!(isSessionVar('id') && isSessionVar('idType'))) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Not logged in.'));
    exit();
}

$loginId = getSessionVar('id');
$loginType = getSessionVar('idType');
$provider = $_POST['provider'];
$email = $_POST['email'];

if ($provider == NULL || $provider == '')
    $provider = 'allow';

// first check to see if this identity exists
$cQ = <<<EOS
SELECT perid, provider, email_addr
FROM perinfoIdentities
WHERE provider=? AND email_addr = ?
EOS;

$cR = dbSafeQuery($cQ, 'ss', array($provider, $email));
if ($cR == false) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Error checking identity list, get help'));
    exit();
}

if ($cR->num_rows != 0) {
    ajaxSuccess(array('status'=>'error', 'message'=>'This entity already exists'));
    exit();
}

// we just need the presence / absence, we never need to retrieve the data
$cR->free();

// we have a match, see if this email address is someone elses or is already one of our email addresses and just add it for the new provider is one of our email addresses
$cQ = <<<EOS
SELECT COUNT(*) AS emails
FROM perinfoIdentities
WHERE email_addr = ? AND perid != ?;
EOS;
$cR = dbSafeQuery($cQ, 'si', array($email, $loginId));
if ($cR == false || $cR->num_rows == 0) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Error checking if this email belongs to someone else'));
    exit();
}

$counts = $cR->fetch_row()[0];
$cR->free();
if ($counts > 0) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Someone else already has this email address in their identity list, if this email is yours contact the registation admin for assistance.'));
    exit();
}

$cQ = <<<EOS
SELECT COUNT(*) AS emails
FROM perinfoIdentities
WHERE email_addr = ? AND perid = ?;
EOS;
$cR = dbSafeQuery($cQ, 'si', array($email, $loginId));
if ($cR == false || $cR->num_rows == 0) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Error checking if this email belongs to you'));
    exit();
}

$counts = $cR->fetch_row()[0];
$cR->free();

if ($counts > 0) {
    // one of ours, just add it
    $iQ = <<<EOS
INSERT into perinfoIdentities(perid, provider, email_addr)
VALUES (?, ?, ?);
EOS;
    $iKey = dbSafeInsert($iQ, 'iss', array($loginId, $provider, $email));
    if ($iKey === false) {
        ajaxSuccess(array('status'=>'error', 'message'=>'Unable to add identity, get assistance'));
    } else {
        ajaxSuccess(array('status'=>'success', 'message'=>"$provider:$email attached to your account because you control that email address"));
    }
    exit();
}

$cQ = <<<EOS
SELECT id, last_name, middle_name, first_name, suffix, email_addr, phone, badge_name, legalName, address, addr_2, city, state, zip, country, 
    share_reg_ok, contact_ok, managedBy, NULL AS managedByNew, lastVerified, 'p' AS personType,
    TRIM(REGEXP_REPLACE(CONCAT(IFNULL(first_name, ''),' ', IFNULL(middle_name, ''), ' ', IFNULL(last_name, ''), ' ', IFNULL(suffix, '')), '  *', ' ')) AS fullname
FROM perinfo
WHERE id=?;
EOS;
$cR = dbSafeQuery($cQ, 'i', array($loginId));
if ($cR == false || $cR->num_rows == 0) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Unable to get your information'));
    exit();
}
$loginInfo = $cR->fetch_assoc();
$cR->free();

$waittime = 1; // hours
$ts = timeSinceLastToken('identity', $email);
if ($ts != null && $ts < $waittime * 60 * 60) {
    $hrs = $waittime - floor($ts/(60*60));
    ajaxSuccess(array('status'=>'error', 'message'=>"There already is an outstanding identity request to $provider:$email.<br/>" .
            "Please check your spam folder for the request.<br/>Please wait $hrs hours before trying again."));
    exit;
}

// encrypt/decrypt stuff
$cipherParams = getAttachCipher();

$insQ = <<<EOS
INSERT INTO portalTokenLinks(email, action, source_ip)
VALUES(?, 'identity', ?);
EOS;
$insid = dbSafeInsert($insQ, 'ss', array($email, $_SERVER['REMOTE_ADDR']));
if ($insid != false) {
    web_error_log('Error inserting tracking ID for email link');
}

$parms = array();
$parms['email'] = $email;           // address to verify via email
$parms['type'] = 'identity';          // verify type
$parms['ts'] = time();              // when requested for timeout check
$parms['lid'] = $insid;             // id in portalTokenLinks table
$parms['provider'] = $provider;         // provider to set up
$parms['loginId'] = $loginId;       // who is requesting the identity
$parms['email'] = $email;   // what email to set up
$string = json_encode($parms);  // convert object to json for making a string out of it, which is encrypted in the next line
$string = urlencode(openssl_encrypt($string, $cipherParams['cipher'], $cipherParams['key'], 0, $cipherParams['iv']));
$token = $portal_conf['portalsite'] . "/respond.php?action=identity&vid=$string";     // convert to link for emailing

load_email_procs();
$loginFullname = $loginInfo['fullname'];
$loginEmail = $loginInfo['email_addr'];

$body = "Dear $loginFullname," . PHP_EOL . PHP_EOL .
    "You requested to manage your provider and email address  of $provider:$email." . PHP_EOL . PHP_EOL .
    "Please click the link below to verify that you own this email address." . PHP_EOL . PHP_EOL . $token . PHP_EOL . PHP_EOL;

$htmlbody = "<p>Dear $loginFullname,</p>" . PHP_EOL .
    "<p>You requested to manage your provider and email address  of $provider:$email.</p>" . PHP_EOL .
    '<p>Please click the link below to verify that you own this email address.<p>' . PHP_EOL .
    '<p><a href="' . $token . '">Click this link to verify you own this email address</a></p>' .    PHP_EOL;

$return_arr = send_email($conf['regadminemail'], trim($email), /* cc */ null, $conf['label'] . ' Membership Portal Add Identity Request', $body, $htmlbody);
if (array_key_exists('error_code', $return_arr)) {
    $error_code = $return_arr['error_code'];
} else {
    $error_code = null;
}
if (array_key_exists('email_error', $return_arr)) {
    $response['status'] = 'error';
    $response['message'] = 'Unable to send receipt email, error: ' . $return_arr['email_error'] . ', Code: $error-code';
} else {
    $response['status'] = 'success';
    $response['message'] = "Identity request set to $email";
}
ajaxSuccess($response);
