<?php
require_once('../lib/base.php');
require_once(__DIR__ . "/../../lib/db_functions.php");
require_once(__DIR__ . "/../../lib/ajax_functions.php");
require_once(__DIR__ . "/../../lib/coupon.php");

if(!isset($_POST) || !isset($_POST['code'])) {
    ajaxSuccess(array('status'=>'error', 'error'=>"Error: no code")); exit();
}

$condata = get_con();
$con = get_conf('con');

$code = $_POST['code'];

$results = load_coupon_data($code);
ajaxSuccess($results);
?>
