<?php
// requestAssocite - either associate on exact email match or send email to associate
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

if (!(array_key_exists('action', $_POST) && array_key_exists('acctId', $_POST) && array_key_exists('email', $_POST))) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Parameter error - get assistance'));
    exit();
}

if (!(isSessionVar('id') && isSessionVar('idType'))) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Not logged in.'));
    exit();
}

$loginId = getSessionVar('id');
$loginType = getSessionVar('idType');
$acctId = $_POST['acctId'];
$email = $_POST['email'];

// first check to see if this person exists
$cQ = <<<EOS
SELECT id, last_name, middle_name, first_name, suffix, email_addr, phone, badge_name, legalName, pronouns, address, addr_2, city, state, zip, country, 
    managedBy, NULL AS managedByNew, lastVerified, 'p' AS personType,
    TRIM(REGEXP_REPLACE(CONCAT(IFNULL(first_name, ''),' ', IFNULL(middle_name, ''), ' ', IFNULL(last_name, ''), ' ', IFNULL(suffix, '')), '  *', ' ')) AS fullname
FROM perinfo
WHERE id=? AND email_addr = ? AND NOT (first_name = 'Merged' AND middle_name = 'into')
UNION
SELECT id, last_name, middle_name, first_name, suffix, email_addr, phone, badge_name, legalName, pronouns, address, addr_2, city, state, zip, country, 
    managedBy, managedByNew, lastVerified, 'n' AS personType,
    TRIM(REGEXP_REPLACE(CONCAT(IFNULL(first_name, ''),' ', IFNULL(middle_name, ''), ' ', IFNULL(last_name, ''), ' ', IFNULL(suffix, '')), '  *', ' ')) AS fullname
FROM newperson
WHERE id=? AND email_addr = ? AND perid IS NULL AND NOT (first_name = 'Merged' AND middle_name = 'into');
EOS;

$cR = dbSafeQuery($cQ, 'isis', array($acctId, $email, $acctId, $email));
if ($cR == false || $cR->num_rows == 0) {
    ajaxSuccess(array('status'=>'error', 'message'=>'No matching account'));
    exit();
}

$personInfo = $cR->fetch_assoc();
$cR->free();
$acctType = $personInfo['personType'];

// we have a match, see if this person is one of our email addresses
if ($loginType == 'p') {
    $cQ = <<<EOS
WITH per AS (
    SELECT email_addr
    FROM perinfo
    WHERE id = ?
    UNION
    SELECT email_addr
    FROM perinfoIdentities
    WHERE perid = ?
)
SELECT count(*)
FROM per
WHERE email_addr = ?;
EOS;
    $cR = dbSafeQuery($cQ, 'iis', array($loginId, $loginId, $email));
} else {
    $cQ = <<<EOS
SELECT count(*) AS matches
FROM newperson
WHERE id = ? AND email_addr = ?;
EOS;
    $cR = dbSafeQuery($cQ, 'is', array($loginId, $email));
}
if ($cR == false || $cR->num_rows == 0) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Error occurred retrieving your email addresses, seek assistance.'));
    exit();
}
$numMatch = $cR->fetch_row()[0];
$cR->free();

if ($numMatch > 0) {
    // this account holder controls this email address, directly attach it.
    if ($acctType == 'p') {
        $uQ = <<<EOS
UPDATE perinfo
SET managedBy = ?, managedReason = 'Req Email Match', updatedBy = ?
WHERE id = ?;
EOS;
    } else if ($acctType == 'n') {
        $pfield = $loginType == 'p' ? 'managedBy' : 'managedByNew';
        $uQ = <<<EOS
UPDATE newperson
SET $pfield = ?, managedReason = 'Req Email Match', updatedBy=?
WHERE id = ?;
EOS;
    }
    $updCnt = dbSafeCmd($uQ, 'iii', array($loginId, $loginId, $loginId));
    if ($updCnt != 1) {
        ajaxSuccess(array('status'=>'error', 'message'=>'Unable to attach, get assistance'));
    } else {
        ajaxSuccess(array('status'=>'success', 'message'=>"$acctId attached to your account because you control that email address"));
    }
    exit();
}

// ok, they exist, they are not one of our identities (email addresses), so we need to send the email
// get the account info of the logged in account holder

if ($loginType == 'p') {
    $table = 'perinfo';
} else {
    $table = 'newperson';
}

$cQ = <<<EOS
SELECT id, last_name, middle_name, first_name, suffix, email_addr, phone, badge_name, legalName, pronouns, address, addr_2, city, state, zip, country, 
    managedBy, NULL AS managedByNew, lastVerified, 'p' AS personType,
    TRIM(REGEXP_REPLACE(CONCAT(IFNULL(first_name, ''),' ', IFNULL(middle_name, ''), ' ', IFNULL(last_name, ''), ' ', IFNULL(suffix, '')), '  *', ' ')) AS fullname
FROM $table
WHERE id=?;
EOS;
$cR = dbSafeQuery($cQ, 'i', array($loginId));
if ($cR == false || $cR->num_rows == 0) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Unable to get your information'));
    exit();
}
$loginInfo = $cR->fetch_assoc();
$cR->free();

$waittime = 8; // hours
$ts = timeSinceLastToken('attach', $email);
if ($ts != null && $ts < $waittime * 60 * 60) {
    $hrs = $waittime - floor($ts/(60*60));
    ajaxSuccess(array('status'=>'error', 'message'=>"There already is an outstanding attach request to $email.<br/>" .
            "Have them check their spam folder for the request.<br/>Please wait $hrs hours before trying again."));
    exit;
}

// encrypt/decrypt stuff
$cipherParams = getAttachCipher();

$insQ = <<<EOS
INSERT INTO portalTokenLinks(email, action, source_ip)
VALUES(?, 'attach', ?);
EOS;
$insid = dbSafeInsert($insQ, 'ss', array($email, $_SERVER['REMOTE_ADDR']));
if ($insid != false) {
    web_error_log('Error inserting tracking ID for email link');
}

$parms = array();
$parms['email'] = $email;           // address to verify via email
$parms['type'] = 'attach';          // verify type
$parms['ts'] = time();              // when requested for timeout check
$parms['lid'] = $insid;             // id in portalTokenLinks table
$parms['acctId'] = $acctId;         // person to attach
$parms['acctType'] = $acctType;         // person to attach
$parms['loginId'] = $loginId;       // who is requesting the attach
$parms['loginType'] = $loginType;   // id in portalTokenLinks table
$parms['managerEmail'] = $loginInfo['email_addr'];
$string = json_encode($parms);  // convert object to json for making a string out of it, which is encrypted in the next line
$string = urlencode(openssl_encrypt($string, $cipherParams['cipher'], $cipherParams['key'], 0, $cipherParams['iv']));
$token = $portal_conf['portalsite'] . "/respond.php?action=attach&vid=$string";     // convert to link for emailing

load_email_procs();
$loginFullname = $loginInfo['fullname'];
$loginEmail = $loginInfo['email_addr'];
$personFullname = $personInfo['fullname'];

$body = "Dear $personFullname," . PHP_EOL . PHP_EOL .
    "$loginFullname requested to manage your " . $conf['label'] . " account.  They did this by entering your account id and email address." . PHP_EOL . PHP_EOL .
    "If you have any questions about this request, please contact them at $loginEmail." . PHP_EOL . PHP_EOL .
    "If you agree to this request and wish them to have access to your account, please click the link below." . PHP_EOL . PHP_EOL . $token . PHP_EOL . PHP_EOL;

$htmlbody = "<p>Dear $personFullname,</p>" . PHP_EOL .
    "<p>$loginFullname requested to manage your " . $conf['label'] . ' account.  They did this by entering your account id and email address.</p>' . PHP_EOL .
    "<p>If you have any questions about this request, please contact them at $loginEmail.</p>" . PHP_EOL .
    '<p>If you agree to this request and wish them to have access to your account, please click the link below.<p>' . PHP_EOL .
    '<p><a href="' . $token . '">Click this link to approve the management request</a></p>' .    PHP_EOL;

$return_arr = send_email($conf['regadminemail'], trim($email), /* cc */ null, $conf['label'] . ' Membership Portal Account Managment Request', $body, $htmlbody);
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
    $response['message'] = "Management request set to $email";
}
ajaxSuccess($response);
