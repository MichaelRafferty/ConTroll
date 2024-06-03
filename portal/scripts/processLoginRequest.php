<?php
require_once('../lib/base.php');
require_once('../lib/getLoginMatch.php');
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
        $loginData = getLoginMatch($email, $id);
        if (is_array($loginData))
            $matches = $loginData['matches'];
        else {
            ajaxSuccess(array('status'=>'error', 'message'=> $loginData));
            exit();
        }
        $response['matches'] = $matches;
        $response['count'] = sizeof($matches);
        $count = sizeof($matches);

        if ($count == 0) {
            $response['error'] = 'No matching emails found';
        } else if ($count == 1) {
            $_SESSION['id'] = $matches[0]['id'];
            $_SESSION['idType'] = $matches[0]['tablename'];
            $response['status'] = 'success';
        }

        ajaxSuccess($response);
        break;

    case 'token':
        // encrypt/decrypt stuff
        $ciphers = openssl_get_cipher_methods();
        $cipher = 'aes-128-cbc';
        $ivlen = openssl_cipher_iv_length($cipher);
        $ivdate = date_create('now');
        $iv = substr(date_format($ivdate, 'YmdzwLLwzdmY'), 0, $ivlen);
        $key = $conid . $conf['label'] . $conf['regadminemail'];

        $insQ = <<<EOS
INSERT INTO portalTokenLinks(email, source_ip)
VALUES(?, ?);
EOS;
        $insid = dbSafeInsert($insQ, 'ss', array($email, $_SERVER['REMOTE_ADDR']));
        if ($insid != false) {
            web_error_log('Error inserting tracking ID for email link');
        }

        $parms = array();
        $parms['email'] = $email;
        $parms['type'] = 'token-resp';
        $parms['ts'] = time();
        $parms['lid'] = $insid;
        $string = json_encode($parms);
        $string = urlencode(openssl_encrypt($string, $cipher, $key, 0, $iv));
        $token = $portal_conf['portalsite'] . "/index.php?vid=$string";

        load_email_procs();
        $body = 'Here is the login link you requested for the ' . $conf['label'] . ' Membership Portal.' . PHP_EOL . PHP_EOL . $token . PHP_EOL . PHP_EOL .
            'Click the link to verify your email address' . PHP_EOL;
        $htmlbody = '<p>Here is the login link you requested for the ' . $conf['label'] . ' Membership Portal.</p><p><a href="' . $token . '">' .
            'Click this link to verify your email address' . '</a></p>' . PHP_EOL;

        $return_arr = send_email($conf['regadminemail'], trim($email), /* cc */ null, $conf['label'] . ' Membership Portal Login Link', $body, $htmlbody);
        if (array_key_exists('error_code', $return_arr)) {
            $error_code = $return_arr['error_code'];
        } else {
            $error_code = null;
        }
        if (array_key_exists('email_error', $return_arr)) {
            $response['error'] = 'Unable to send receipt email, error: ' . $return_arr['email_error'] . ', Code: $error-code';
        }
        ajaxSuccess($response);
        break;

    default:
        ajaxSuccess(array('status'=>'error', 'message'=>'Invalid login type - get assistance'));
        exit();
}