<?php
require_once('../../lib/global.php');
global $db_ini;
if (!$db_ini) {
    $db_ini = loadConfFile();
}

if (getConfValue('reg','https') <> 0) {
    if (!isset($_SERVER['HTTPS']) or $_SERVER['HTTPS'] != 'on') {
        header('HTTP/1.1 301 Moved Permanently');
        header('Location: https://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']);
        exit();
    }
}

require_once('../../lib/db_functions.php');
require_once('../../lib/pdfPrintArtShowSheets.php');

db_connect();
if (!session_start()) {
    session_regenerate_id(true);
    session_start();
}

$response = array('post' => $_POST, 'get' => $_GET);
if(!array_key_exists('type', $_GET) || !array_key_exists('region', $_GET) || !isSessionVar('eyID')) {
    echo "Invalid Session\n";
    exit;
}

$eyID = getSessionVar('eyID');
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
