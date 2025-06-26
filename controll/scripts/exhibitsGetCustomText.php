<?php
global $db_ini;

require_once '../lib/base.php';
require_once '../lib/customText.php';

$check_auth = google_init('ajax');
$perm = 'exhibitor';

$response = array('post' => $_POST, 'get' => $_GET, 'perm' => $perm);

if ($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = 'Authentication Failed';
    ajaxSuccess($response);
    exit();
}

if (!isset($_POST) || !isset($_POST['ajax_request_action']) || !isset($_POST['tablename'])
    || !isset($_POST['indexcol'])) {
    $response['error'] = 'Invalid Parameters';
    ajaxSuccess($response);
    exit();
}

$response['customText'] = getCustomText('exhibitor');
ajaxSuccess($response);
