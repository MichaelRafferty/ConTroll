<?php
require_once('../lib/base.php');
require_once('../../../lib/pdfPrintArtShowSheets.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$check_auth = google_init('ajax');
$perm = 'vendor';

$response = array('post' => $_POST, 'get' => $_GET, 'perm' => $perm);

if ($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = 'Authentication Failed';
    ajaxSuccess($response);
    exit();
}

if(!array_key_exists('type', $_GET) || !array_key_exists('region', $_GET) || !array_key_exists('eyid', $_GET)) {
    echo "Invalid Arguments\n";
    exit;
}

$eyID = $_GET['eyid'];
$region = $_GET['region'];

switch($_GET['type']) {
    case 'bidsheets':
        $response = pdfPrintBidSheets($eyID, $region, $response);
        break;
    case 'printshop':
        $response = pdfPrintShopPriceSheets($eyID, $region, $response);
        break;
    case 'control':
        $response = pdfArtistControlSheet($eyID, $region, $response);
        break;
    default:
}

?>
