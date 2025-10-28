<?php
// ConTroll Registration System, Copyright 2015-2025, Michael Rafferty, Licensed under the GNU Affero General Public License, Version 3.
// library AJAX Processor: admin_configEditLoadData.php
// Author: Syd Weinstein
// load all objects needed at start of configuration editor

require_once('../lib/base.php');
require_once('../../lib/configEditor.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$check_auth = google_init('ajax');
$perm = 'admin';

$response = array ('post' => $_POST, 'get' => $_GET, 'perm' => $perm);

if ($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
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
$response = loadConfigEditor($perm);

ajaxSuccess($response);
