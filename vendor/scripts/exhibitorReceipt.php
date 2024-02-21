<?php
require_once('../lib/base.php');
require_once('../../lib/email__load_methods.php');
require_once('../../lib/log.php');
require_once('../../lib/reg_receipt.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$response = array('post' => $_POST, 'get' => $_GET);

global $con;
$con = get_con();
$conid=$con['id'];
$conf = get_conf('con');
$vendor_conf = get_conf('vendor');

$response['conid'] = $conid;

$ccauth = get_conf('cc');
load_email_procs();

$log = get_conf('log');
logInit($log['vendors']);

$email = "no send attempt or a failure";

if(!isset($_SESSION['id'])) { ajaxSuccess(array('status'=>'error', 'message'=>'Session Failure')); exit; }

$exhId = $_SESSION['id'];

$response = array("post" => $_POST, "get" => $_GET);

// which space purchased
if (!array_key_exists('regionYearId', $_POST)) {
    ajaxError("invalid calling sequence");
    exit();
}
$regionYearId = $_POST['regionYearId'];

$response = trans_receipt(null, $exhId, $regionYearId);
ajaxSuccess($response);
