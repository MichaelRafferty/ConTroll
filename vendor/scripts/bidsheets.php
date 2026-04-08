<?php
require_once('../../lib/global.php');
global $appSessionPrefix;
loadConfFile();

if (getConfValue('reg','https') <> 0) {
    if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != 'on') {
        header('HTTP/1.1 301 Moved Permanently');
        header('Location: https://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']);
        exit();
    }
}

require_once('../../lib/db_functions.php');
require_once('../../lib/pdfPrintArtShowSheets.php');

db_connect();
$appSessionPrefix = 'Ctrl/Exhibitor/';
$result = session_start();

$response = array('post' => $_POST, 'get' => $_GET);
if(!array_key_exists('type', $_GET) || !array_key_exists('region', $_GET) || !isSessionVar('eyID')) {
    echo "Invalid Session\n";
    exit;
}

$eyID = getSessionVar('eyID');
$conid = getConfValue('con', 'id');
if (array_key_exists('region', $_REQUEST))
    $region = $_REQUEST['region'];
else {
    $response['error'] = 'Invalid calling sequence';
    echo "<h1>Invalid calling sequence</h1>\n";
    return $response;
}

if (array_key_exists('type', $_REQUEST)) {
    $type = $_REQUEST['type'];
} else {
    $type = 'unknown';
}

if ($type == 'control' &&  array_key_exists('conid', $_REQUEST)) {
    $conyear = $_REQUEST['conid'];
    if ($conid != $conyear && $conyear > 0) {
        // translate region to the appropriate region for that year
        $cyQ = <<<EOS
SELECT erycy.id, exycy.id
FROM exhibitsRegionYears ery 
JOIN exhibitsRegionYears erycy ON ery.exhibitsRegion = erycy.exhibitsRegion
join exhibitorYears exy ON exy.id = ?
join exhibitorYears exycy ON exy.exhibitorId = exycy.exhibitorId and exycy.conid = erycy.conid
where ery.id = ? and erycy.conid = ?;
EOS;
        $cyR = dbSafeQuery($cyQ, 'iii', array($eyID, $region, $conyear));
        if ($cyR !== false && $cyR->num_rows == 1) {
            [$region, $eyID] = $cyR->fetch_row();
            $cyR->free();
        }
    }
}


switch($_REQUEST['type']) {
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
        echo "<h1>Error Invalid sheet type, please seek assistance</h1\n";

}
