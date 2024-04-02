<?php
require_once("../lib/base.php");
require_once("../../lib/auctionPrintSheets.php");

$con = get_con();
$conid= $con['id'];
$vendor_conf = get_conf('vendor');
$render_url = $vendor_conf['renderer'];
$debug_conf = get_conf('debug');
if(!array_key_exists('render', $debug_conf)) { $debug_conf['render'] = 0; }

global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$response = array('post' => $_POST, 'get' => $_GET);
if($debug_conf['render']) { 
    $response['renderer'] = $render_url; 
    $response['session'] = $_SESSION;
}


if(!array_key_exists('type', $_GET) || !array_key_exists('region', $_GET) || !array_key_exists('eyID', $_SESSION)) {
    ajaxError('Invalid Session');
    exit;
}

$eyID = $_SESSION['eyID'];
$region = $_GET['region'];

$config = array(
    'con' => $con, 
    'render_url' => $render_url, 
    'debug' =>$debug_conf
    );

switch($_GET['type']) {
    case 'bidsheets':
        $response = bidsheets($eyID, $region, $response, $config);
        break;
    case 'printshop':
        $response = copysheets($eyID, $region, $response, $config);
        break;
    case 'control':
    default:
}


if($response['status'] != 'Success') {
    ajaxSuccess($response);
}

?>
