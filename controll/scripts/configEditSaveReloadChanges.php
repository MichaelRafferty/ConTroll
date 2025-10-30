<?php
// ConTroll Registration System, Copyright 2015-2025, Michael Rafferty, Licensed under the GNU Affero General Public License, Version 3.
// library AJAX Processor: configEditLoadData.php
// Author: Syd Weinstein
// load all objects needed at start of configuration editor

require_once('../lib/base.php');
require_once('../../lib/configEditor.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$check_auth = google_init('ajax');
if (!array_key_exists('perm', $_POST)) {
    $response['error'] = 'Parameter Error';
    ajaxSuccess($response);
    exit();
}
$perm = $_POST['perm'];

$response = array ('post' => $_POST, 'get' => $_GET, 'perm' => $perm);

if ($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = 'Authentication Failed';
    ajaxSuccess($response);
    exit();
}

if (!array_key_exists('action', $_POST)) {
    $response['error'] = 'Parameter Error';
    ajaxSuccess($response);
    exit();
}

try {
    $fields = json_decode($_POST['fields'], true, 512, JSON_THROW_ON_ERROR);
} catch (Exception $e) {
    $msg = 'Caught exception on json_decode: ' . $e->getMessage() . PHP_EOL . 'JSON error: ' . json_last_error_msg() . PHP_EOL;
    $response['error'] = $msg;
    error_log($msg);
    ajaxSuccess($response);
    exit();
}

$user_perid = $_SESSION['user_perid'];
setConfigDirs();

// create a lock file on the reg_conf.ini
$status = configLock($user_perid);
if ($status != '') {
    $response['error'] = $status;
    error_log($status);
    ajaxSuccess($response);
    exit();
}

// now check that there is nothing different in the newer file
$status = checkCurrent($fields);
if ($status != '') {
    $response['warn'] = $status;
    error_log($status);
    configUnlock($user_perid);
    ajaxSuccess($response);
    exit();
}

// ok, we now have no conflicts, write out the new file
$status = updateConfig($fields);

$auths = getAuths($check_auth['sub']);
$response = loadConfigEditor($perm, $auths);
$response['message'] = $status;

configUnlock($user_perid);
ajaxSuccess($response);
