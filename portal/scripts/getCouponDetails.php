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

if (!(isSessionVar('id') && isSessionVar('idType'))) {
    ajaxSuccess(array('status'=>'error', 'error'=>'Not logged in.'));
    exit();
}

if (array_key_exists('clear', $_POST)) {
    unsetSessionVar('curCoupon');
    unsetSessionVar('curCouponSerial');
    exit();
}

if (!array_key_exists('code', $_POST)) {
    ajaxSuccess(array('status'=>'error', 'error'=>'Parameter error - get assistance'));
    exit();
}

$loginId = getSessionVar('id');
$loginType = getSessionVar('idType');

$code = $_POST['code'];
$serial = null;
if (array_key_exists('serial', $_POST))
    $serial = $_POST['serial'];

$results = load_coupon_data($code, $serial);
if ($results['status'] == 'success') {
    setSessionVar('curCoupon', $code);
    setSessionVar('curCouponSerial', $serial);
} else {
    unsetSessionVar('curCoupon');
    unsetSessionVar('curCouponSerial');
}

ajaxSuccess($results);
?>
