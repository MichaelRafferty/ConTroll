<?php
global $db_ini;
if (!$db_ini) {
    $db_ini = parse_ini_file(__DIR__ . '/../../config/reg_conf.ini', true);
}

if ($db_ini['reg']['https'] <> 0) {
    if (!isset($_SERVER['HTTPS']) or $_SERVER['HTTPS'] != 'on') {
        header('HTTP/1.1 301 Moved Permanently');
        header('Location: https://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']);
        exit();
    }
}

require_once('../../lib/db_functions.php');
require_once('../../lib/pdfPrintArtShowSheets.php');

db_connect();
session_start();

$db_conf = get_conf('mysql');
if (array_key_exists('php_timezone', $db_conf)) {
    date_default_timezone_set($db_conf['php_timezone']);
} else {
    date_default_timezone_set('America/New_York'); // default if not configured
}

$response = array('post' => $_POST, 'get' => $_GET);
if(!array_key_exists('type', $_GET) || !array_key_exists('region', $_GET) || !array_key_exists('eyID', $_SESSION)) {
    echo "Invalid Session\n";
    exit;
}

$eyID = $_SESSION['eyID'];
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
