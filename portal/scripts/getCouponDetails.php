<?php
// addIdentity - request a confirm email to add an identity to your account.
require_once('../lib/base.php');
require_once(__DIR__ . '/../../lib/db_functions.php');
require_once(__DIR__ . '/../../lib/ajax_functions.php');
require_once(__DIR__ . '/../../lib/coupon.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$response = array('post' => $_POST, 'get' => $_GET);

$conf = get_conf('con');
$conid=$conf['id'];
$response['conid'] = $conid;

if (!array_key_exists('code', $_POST)) {
    ajaxSuccess(array('status'=>'error', 'error'=>'Parameter error - get assistance'));
    exit();
}

if (!(isSessionVar('id') && isSessionVar('idType'))) {
    ajaxSuccess(array('status'=>'error', 'error'=>'Not logged in.'));
    exit();
}

$loginId = getSessionVar('id');
$loginType = getSessionVar('idType');

$code = $_POST['code'];
$serial = null;
if (array_key_exists('serial', $_POST))
    $serial = $_POST['serial'];

$results = load_coupon_data($code, $serial);
ajaxSuccess($results);
?>
