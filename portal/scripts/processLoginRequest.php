<?php
require_once('../lib/base.php');
require_once('../../lib/email__load_methods.php');
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

if (!(array_key_exists('email', $_POST) && array_key_exists('type', $_POST))) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Parameter error - get assistance'));
    exit();
}

$email = $_POST['email'];
$type = $_POST['type'];
$id = null;
if (array_key_exists('id', $_POST))
    $id = $_POST['id'];

switch ($type) {
    case 'dev':
        if ($id != NULL) {
            $regcountQ = <<<EOS
SELECT id, last_name, first_name, middle_name, suffix, email_addr, phone, badge_name, legalName, address, addr_2, city, state, zip, country, creation_date, update_date, active, banned,
    TRIM(REGEXP_REPLACE(CONCAT(IFNULL(last_name, ''), ', ', IFNULL(first_name, ''),' ', IFNULL(middle_name, ''), ' ', IFNULL(suffix, '')), '  *', ' ')) AS fullname
FROM perinfo
WHERE email_addr = ? AND id = ?;
EOS;
            $regcountR = dbSafeQuery($regcountQ, 'si', array($email, $id));
        } else {
            $regcountQ = <<<EOS
SELECT id, last_name, first_name, middle_name, suffix, email_addr, phone, badge_name, legalName, address, addr_2, city, state, zip, country, creation_date, update_date, active, banned,
    TRIM(REGEXP_REPLACE(CONCAT(IFNULL(last_name, ''), ', ', IFNULL(first_name, ''),' ', IFNULL(middle_name, ''), ' ', IFNULL(suffix, '')), '  *', ' ')) AS fullname
FROM perinfo
WHERE email_addr = ? AND first_name != 'Merged' AND middle_name != 'into'
ORDER BY fullname;
EOS;
            $regcountR = dbSafeQuery($regcountQ, 's', array($email));
        }
        if ($regcountR == false) {
            ajaxSuccess(array('status'=>'error', 'message'=>'Query Error - seek assistance'));
            exit();
        }
        $matches = [];
        $count = $regcountR->num_rows;
        while ($person = $regcountR->fetch_assoc()) {
            $matches[] = $person;
            $response['type'] = 'p';
        }
        $regcountR->free();
        $respose['matches'] = $matches;
        $response['count'] = $count;
        if ($count == 1) {
            $_SESSION['id'] = $matches[0]['id'];
            $_SESSION['idType'] = 'p';
            $response['status'] = 'success';
            ajaxSuccess($response);
            exit();
        }

        if ($count == 0) {
            $regcountQ = <<<EOS
SELECT email_addr, id
FROM newperson
WHERE email_addr = ?;
EOS;
            if ($regcountR == false) {
                ajaxSuccess(array('status' => 'error', 'message' => 'Query Error - seek assistance'));
                exit();
            }
            $matches = [];
            $count = $regcountR->num_rows;
            $response['count'] = $count;
            while ($person = $regcountR->fetch_assoc()) {
                $matches[] = $person;
                $response['type'] = 'n';
            }
            $regcountR->free();
            $respose['matches'] = $matches;
            if ($count == 1) {
                $_SESSION['id'] = $matches[0]['id'];
                $_SESSION['idType'] = 'n';
                $response['type'] = 'n';
                $response['status'] = 'success';
                ajaxSuccess($response);
                exit();
            }
        }

        if ($count == 0) {
            $response['error'] = 'No matching emails found';
        } else {
            $response['matches'] = $matches;
        }
        ajaxSuccess($response);
        exit();
        break;

    case 'token':
        // encrypt/decrypt stuff
        $ciphers = openssl_get_cipher_methods();
        $cipher = 'aes-128-cbc';
        $ivlen = openssl_cipher_iv_length($cipher);
        $ivdate = date_create('now');
        $iv = substr(date_format($ivdate, 'YmdzwLLwzdmY'), 0, $ivlen);
        $key = $conid . $conf['label'] . $conf['regadminemail'];

        $parms = array();
        $parms['email'] = $email;
        $parms['type'] = 'token-resp';
        $parms['ts'] = time();
        $string = json_encode($parms);
        $string = urlencode(openssl_encrypt($string, $cipher, $key, 0, $iv));
        $token = $portal_conf['portalsite'] . "/index.php?vid=$string";

        load_email_procs();
        $body = 'Here is the login link you requested for the ' . $conf['label'] . ' Membership Portal.' . PHP_EOL . PHP_EOL . $token . PHP_EOL . PHP_EOL .
            'Click the link to verify your email address' . PHP_EOL;
        $htmlbody = '<p>Here is the login link you requested for the ' . $conf['label'] . ' Membership Portal.</p><p><a href="' . $token . '">' .
            'Click this link to verify your email address' . '</a></p>' . PHP_EOL;

        $return_arr = send_email($conf['regadminemail'], trim($email), /* cc */ null, $conf['label'] . ' Membership Portal Login Link', $body, $htmlbody);

        break;
    default:
        ajaxSuccess(array('status'=>'error', 'message'=>'Invalid login type - get assistance'));
        exit();
}





if(!isset($_SESSION['id'])) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Session Failure'));
    exit();
}

$exhId = $_SESSION['id'];

// which space purchased
if (!array_key_exists('email', $_POST)) {
    ajaxError("invalid calling sequence");
    exit();
}
$email = $_POST['email'];
$receiptTxt = $_POST['text'];
$receiptHTML = $_POST['tables'];

$return_arr = send_email($conf['regadminemail'], $email, null, 'Receipt for Payment', $receiptTxt, $receiptHTML);

if (array_key_exists('error_code', $return_arr)) {
    $error_code = $return_arr['error_code'];
} else {
    $error_code = null;
}
if (array_key_exists('email_error', $return_arr)) {
    $response['error'] = 'Unable to send receipt email, error: ' . $return_arr['email_error'] . ', Code: $error-code';
} else {
    $response['success'] = "Email sent to $email";
}

ajaxSuccess($response);
