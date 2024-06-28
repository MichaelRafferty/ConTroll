<?php
require_once('../lib/base.php');
require_once('../lib/getLoginMatch.php');
require_once('../../lib/email__load_methods.php');
require_once('../../lib/cipher.php');
require_once('../lib/sessionManagement.php');

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
        if (stripos(__DIR__, '/Users/syd/') === false || $_SERVER['SERVER_ADDR'] != '127.0.0.1') {
            ajaxSuccess(array('status'=>'error', 'message'=> 'Development login not valid outside of development:'));
            exit();
        }
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
            $response['status'] = 'error';
            $response['message'] = 'No matching emails found';
        } else if ($count == 1) {
            clearSession();  // clean logout
            setSessionVar('id', $matches[0]['id']);
            setSessionVar('idType', $matches[0]['tablename']);
            setSessionVar('idSource', 'dev');
            setSessionVar('tokenExpiration', time() + (999999 * 3600));
            $response['status'] = 'success';
        }

        ajaxSuccess($response);
        break;

    case 'token':
        $message = sendEmailToken($email, false);
        if ($message != null) {
            ajaxSuccess(array('status'=>'error', 'message'=>$message));
            exit;
        }
        break;

    default:
        ajaxSuccess(array('status'=>'error', 'message'=>'Invalid login type - get assistance'));
        exit();
}
