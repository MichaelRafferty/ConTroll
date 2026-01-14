<?php
// ConTroll Registration System, Copyright 2015-2026, Michael Rafferty, Licensed under the GNU Affero General Public License, Version 3.
// library AJAX Processor: configEditLoadData.php
// Author: Syd Weinstein
// load all objects needed at start of configuration editor

require_once('../lib/base.php');
require_once('../../lib/configEditor.php');
require_once '../lib/sessionAuth.php';

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

if (!array_key_exists('perm', $_POST)) {
    $response['error'] = 'Parameter Error';
    ajaxSuccess($response);
    exit();
}
$perm = $_POST['perm'];

$response = array ('post' => $_POST, 'get' => $_GET, 'perm' => $perm);
$authToken = new authToken('script');
$response['token'] = $authToken->checkToken();
if (!$authToken->isLoggedIn() || !$authToken->checkAuth($perm)) {
    $response['error'] = 'Authentication Failed';
    ajaxSuccess($response);
    exit();
}

if (!array_key_exists('load_type', $_POST)) {
    $response['error'] = 'Parameter Error';
    ajaxSuccess($response);
    exit();
}

$con=get_conf('con');
$conid= $con['id'];
setConfigDirs();
$response = loadConfigEditor($perm, $authToken);

ajaxSuccess($response);
