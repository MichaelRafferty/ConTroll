<?php
// createPasskey - create the passkey request, and save the passkey response
require_once('../lib/base.php');
require_once('../../lib/webauthn.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$post = trim(file_get_contents('php://input'));
if ($post) {
    $post = json_decode($post, null, 512, JSON_THROW_ON_ERROR);
}

$response = array('post' => $_POST, 'get' => $_GET);

if (!(array_key_exists('action', $_REQUEST) && array_key_exists('email', $_REQUEST) && array_key_exists('source', $_REQUEST))) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Parameter error - get assistance'));
    exit();
}

$action = $_REQUEST['action'];
$email = $_REQUEST['email'];
$source = $_REQUEST['source'];
$loginEmail = getSessionVar('email');
if ($email != $loginEmail) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Your login session has expired, please logout and back in again.'));
    exit();
}

switch ($action) {
    case 'create':
        if (!array_key_exists('displayName', $_REQUEST)) {
            ajaxSuccess(array('status'=>'error', 'message'=>'Parameter error - get assistance'));
            exit();
        }

        $userIdHex = hash('sha256', $email);
        // try emulating their method
        $createArgs = json_encode(createWebauthnArgs($userIdHex, $email, $_REQUEST['displayName'], $source));
        header('Content-Type: application/json');
        print $createArgs;
        exit();

    case 'save':
        if (!(array_key_exists('cred', $_REQUEST) && array_key_exists('att', $_REQUEST))) {
            ajaxSuccess(array('status'=>'error', 'message'=>'Parameter error - get assistance'));
            exit();
        }

        $data = savePasskey($_REQUEST['cred'], $_REQUEST['att'], $userId, $userName, $userDisplayName);
        $response['passkey'] = $data;
        $response['message'] = "Passkey Created";
        $response['success'] = 'success';
    break;

    default:
        $response['message'] = 'Invalid action';
        $response['status'] = 'error';
}

ajaxSuccess($response);
