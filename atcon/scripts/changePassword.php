<?php
// library AJAX Processor: change_passwd
// Balticon Registration System
// Author: Syd Weinstein

require_once('../lib/base.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

// change_passwd:
//  uses user identifier from SESSION (user and userhash) and POST variables from the AJAX call: old and new passwords
//  validates old password and if correct updates it to the new password, it trusts the javascript to verify new and
//  confirm_new are the same.
function change_passwd($conid) {
    //$response = array('post' => $_POST, 'get' => $_GET);
    $response = array();

    // get the parameters to validate the existing password (and check that new is also passed
    if (isset($_POST) && isset($_POST['old']) && isset($_POST['new']) && isset($_SESSION['user'])) {
        $user = $_SESSION['user'];
        $userhash = $_SESSION['userhash'];
        $oldpw = $_POST['old'];
        $newpw = $_POST['new'];

        // Passwords are in the atcon_user table and are unique to the perid (user) and validated to be the proper
        // user by using the userhash computed for this user.  There is only one record per user per conid.
        $checkQ = <<<EOS
SELECT passwd
FROM atcon_user
WHERE perid=? and userhash=? and conid=?;
EOS;
        $checkR = dbSafeQuery($checkQ, 'isi', array($user, $userhash, $conid));
        if ($checkR->num_rows != 1) {
            RenderErrorAjax('Invalid User Parameters');
            exit();
        }
        $checkL = fetch_safe_assoc($checkR);

        // using the PHP password encryption, so only password_verify in PHP can validate the password
        if (!password_verify($oldpw, $checkL['passwd'])) {
            $response['error'] = 'Incorrect Current Password';
        } else {
            $new_enc_passwd = password_hash($newpw, PASSWORD_DEFAULT);
            $updateQ = <<<EOS
UPDATE atcon_user
SET passwd=? 
WHERE perid=? and userhash=? and conid=?;
EOS;
            $rows = dbSafeCmd($updateQ, 'sisi', array($new_enc_passwd, $user, $userhash, $conid));
            if ($rows == 1) {
                $response['message'] = 'Password Changed';
            } else {
                $response['error'] = 'Error occurred changing the password';
            }
        }
        echo json_encode($response) . "\n";
    } else {
        RenderErrorAjax('Missing Parameters');
        exit();
    }
}

// outer ajax wrapper
// method - permission required to access this AJAX function
// action - passed in from the javascript
$method = 'manager';
$con = get_conf('con');
$conid = $con['id'];
$ajax_request_action = '';
if ($_POST && $_POST['ajax_request_action']) {
    $ajax_request_action = $_POST['ajax_request_action'];
}
if ($ajax_request_action == '' ) {
    exit();
}
if (!check_atcon($method, $conid)) {
    $message_error = 'No permission.';
    RenderErrorAjax($message_error);
    exit();
}
switch ($ajax_request_action) {
    case 'change_passwd':
        change_passwd($conid);
        break;
    default:
        $message_error = 'Internal error.';
        RenderErrorAjax($message_error);
        exit();
}
