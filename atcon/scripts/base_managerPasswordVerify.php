<?php

// library AJAX Processor: base_managerPasswordVerify.php
// Balticon Registration System
// Author: Syd Weinstein
// validate the managers or current users password, verify they are a manager

require_once('../lib/base.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$con = get_conf('con');
$conid = $con['id'];

// for now manager override is not a su, but a function of manager only
if (!check_atcon('manager', $conid)) {
    $message_error = 'No permission.';
    RenderErrorAjax($message_error);
    exit();
}

$ajax_request_action = '';
if ($_POST && $_POST['ajax_request_action']) {
    $ajax_request_action = $_POST['ajax_request_action'];
}
if ($ajax_request_action != 'managerPasswordVerify') {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}
$response = [];
$user = getSessionVar('user');
$passwd = null;
if (array_key_exists('user', $_POST))
    $user = $_POST['user'];
if (array_key_exists('passwd', $_POST))
    $passwd = $_POST['passwd'];

if (isset($user) && isset($passwd)) {
    $passwd = trim($passwd);
    $q = <<<EOS
SELECT a.auth, u.passwd
FROM atcon_user u 
JOIN atcon_auth a ON (a.authuser = u.id)
WHERE u.perid=? AND u.conid=? AND a.auth = 'manager';
EOS;
    $r = dbSafeQuery($q, 'si', array($user, $conid));
    $upasswd = null;
    if ($r->num_rows <= 0) {
        $response['error'] = 'User is not a manager';
    } else {
        $l = $r->fetch_assoc();
        if (!password_verify($passwd, $l['passwd'])) {
            $response['error'] = 'Invalid password';
        } else {
            $response['manager'] = true;
        }
    }
    mysqli_free_result($r);
}
ajaxSuccess($response);
