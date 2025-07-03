<?php
// createPasskey - create the passkey request, and save the passkey response
require_once('../lib/base.php');
require_once('../../lib/webauthn.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$response = array('post' => $_POST, 'get' => $_GET);

if (!(array_key_exists('action', $_POST) && array_key_exists('email', $_POST) && array_key_exists('source', $_POST))) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Parameter error - get assistance'));
    exit();
}

$action = $_POST['action'];
$email = $_POST['email'];
$source = $_POST['source'];
$loginEmail = getSessionVar('email');
if ($email != $loginEmail) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Your login session has expired, please logout and back in again.'));
    exit();
}

switch ($action) {
    case 'create':
        if (!array_key_exists('displayName', $_POST)) {
            ajaxSuccess(array('status'=>'error', 'message'=>'Parameter error - get assistance'));
            exit();
        }

        // try emulating their method
        $createArgs = json_encode(createWebauthnArgs(bin2hex($email), $email, $_POST['displayName'], $source), JSON_FORCE_OBJECT, 512);
        header('Content-Type: application/json');
        print(json_encode($createArgs));
        exit();

    case 'save':
        $response['message'] = 'Not Yet';
        $response['status'] = 'warn';
    default:
        $response['message'] = 'Invalid action';
        $response['status'] = 'error';
}

ajaxSuccess($response);
