<?php
require_once('../lib/base.php');
require_once('../../lib/pdfPrintArtShowSheets.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$check_auth = google_init('ajax');
$perm = 'exhibitor';

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
$email  = false;
if (array_key_exists('email', $_GET)) {
    $email = $_GET['email'] == 'true';
}

if (str_contains($eyID, ','))
    $eyIDlist = explode(',', $eyID);
else
    $eyIDlist = array($eyID);

foreach ($eyIDlist as $id) {
    switch ($_GET['type']) {
        case 'bidsheets':
            $response = pdfPrintBidSheets($id, $region, $response, $id == $eyIDlist[0], $id == $eyIDlist[count($eyIDlist) - 1]);
            break;
        case 'printshop':
            $response = pdfPrintShopPriceSheets($id, $region, $response, $id == $eyIDlist[0], $id == $eyIDlist[count($eyIDlist) - 1]);
            break;
        case 'control':
            $response = pdfArtistControlSheet($id, $region, $response, $email, $id == $eyIDlist[0], $id == $eyIDlist[count($eyIDlist) - 1]);
            break;
        default:
    }
}
