<?php
// createPasskey - create the passkey request, and save the passkey response
require_once('../lib/base.php');
require_once('../../lib/webauthn.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$response = array('post' => $_POST, 'get' => $_GET);

if (!(array_key_exists('action', $_REQUEST) && array_key_exists('email', $_REQUEST) && array_key_exists('source', $_REQUEST))) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Parameter error - get assistance'));
    exit();
}

$action = $_REQUEST['action'];
$email = $_REQUEST['email'];
$source = $_REQUEST['source'];
$response['source'] = $source;
if ($action != 'request' && $action != 'check') {
    $loginEmail = getSessionVar('email');
    $response['email'] = $loginEmail;

    if ($email != $loginEmail) {
        ajaxSuccess(array ('status' => 'error', 'message' => 'Your login session has expired, please logout and back in again.'));
        exit();
    }
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
        if (!array_key_exists('att', $_REQUEST)) {
            ajaxSuccess(array('status'=>'error', 'message'=>'Parameter error - get assistance'));
            exit();
        }

        try {
            $att = json_decode($_REQUEST['att'], true);
        }
        catch (Exception $e) {
            ajaxSuccess(array('status'=>'error', 'message'=>$e->getMessage()));
            exit();
        }

        // now finish up and save the key in the database
        $data = savePasskey($att, getSessionVar('passkeyUserId'), $_REQUEST['email'], $_REQUEST['displayName'], $source);

        $response['passkey'] = $data;
        $response['message'] = "Passkey Created";
        $response['success'] = 'success';
    break;

    case 'delete':
        if (!array_key_exists('id', $_REQUEST)) {
            ajaxSuccess(array('status'=>'error', 'message'=>'Parameter error - get assistance'));
            exit();
        }
        $id = $_REQUEST['id'];
        $delSQL = <<<EOS
DELETE FROM passkeys
WHERE id = ? AND userName = ?;
EOS;
        $numdel = dbSafeCmd($delSQL, 'is', array($id, $email));
        if ($numdel === false || $numdel == 0) {
            $response['status'] = 'warn';
            $response['message'] = 'Passkey not deleted';
        } else {
            $response['status'] = 'success';
            $response['message'] = "Passkey Deleted";
        }
        break;

    case 'request':
        $requestArgs = json_encode(getWebauthnArgs($source));
        header('Content-Type: application/json');
        print $requestArgs;
        exit();

    case 'check':
        if (!array_key_exists('att', $_REQUEST)) {
            ajaxSuccess(array('status'=>'error', 'message'=>'Parameter error - get assistance'));
            exit();
        }

        try {
            $att = json_decode($_REQUEST['att'], true);
        }
        catch (Exception $e) {
            ajaxSuccess(array('status'=>'error', 'message'=>$e->getMessage()));
            exit();
        }

        // now finish up and return if get got a passkey or not
        $data = checkPasskey($att, $source);

        if (array_key_exists('passkey', $data))
            $response['passkey'] = $data['passkey'];

        if (array_key_exists('message', $data))
            $response['message'] = $data['message'];

        if (array_key_exists('status', $data)) {
            $response['status'] = $data['status'];
            if ($data['status'] == 'success') {
                $passkey = $data['passkey'];
                // create the session, we are logged in....
                // first get the items that match
                $matchQ = <<<EOS
SELECT 'p' AS idType, id, email_addr
FROM perinfo
WHERE email_addr = ? AND banned = 'N'
UNION SELECT 'n' AS idType, id, email_addr
FROM newperson
WHERE email_addr = ? AND perid IS NULL
ORDER BY 1 DESC, 2 ASC;
EOS;
                $matchR = dbSafeQuery($matchQ, 'ss', array($passkey['userName'], $passkey['userName']));
                if ($matchR === false || $matchR->num_rows == 0) {
                    $response['status'] = 'error';
                    $response['message'] = 'No membership portal accounts found for that passkey';
                    break;
                }

                $firstMatch = $matchR->fetch_assoc();
                $numMatch = $matchR->num_rows;
                $response['numMatch'] = $numMatch;
                $matchR->free();

                $hrs = getConfigValue('portal', 'emailhrs', 24);
                unsetSessionVar('transId');    // just in case it is hanging around, clear this
                unsetSessionVar('totalDue');   // just in case it is hanging around, clear this
                setSessionVar('id', $firstMatch['id']);
                setSessionVar('idType', $firstMatch['idType']);
                setSessionVar('idSource', 'passkey');
                setSessionVar('tokenType', 'passkey');
                setSessionVar('email', $passkey['userName']);
                setSessionVar('tokenExpiration', time() + ($hrs * 3600));

                if ($numMatch > 1) {
                    setSessionVar('multiple', strtolower($passkey['userName']));
                }
            }
        }
        break;

    default:
        $response['message'] = 'Invalid action';
        $response['status'] = 'error';
}

ajaxSuccess($response);
