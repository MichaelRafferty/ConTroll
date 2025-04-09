<?php
// library AJAX Processor: admin_deleteTerminsl.php
// Balticon Registration System
// Author: Syd Weinstein
// delete and re-get the list of terminals

require_once('../lib/base.php');
require_once('../../lib/term__load_methods.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$method = 'manager';
$con = get_conf('con');
$conid = $con['id'];
$ajax_request_action = '';
if ($_POST && $_POST['ajax_request_action']) {
    $ajax_request_action = $_POST['ajax_request_action'];
}
if ($ajax_request_action != 'refreshStatus') {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}
if (!check_atcon($method, $conid)) {
    $message_error = 'No permission.';
    RenderErrorAjax($message_error);
    exit();
}

if (!array_key_exists('terminal', $_POST)) {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}

$terminal = $_POST['terminal'];
load_term_procs();
// get the status from the terminal
$apiResult = term_getStatus($terminal);

// update the database
$upSQL = <<<EOS
UPDATE terminals
SET
    productType = ?,
    squareName = ?,
    squareModel = ?,
    version = ?,
    terminalAPIVersion = ?,
    batteryLevel = ?,
    externalPower = ?,
    wifiActive = ?,
    wifiSSID = ?,
    wifiIPAddressV4 = ?,
    wifiIPAddressV6 = ?,
    signalStrength = ?,
    ethernetActive = ?,
    ethernetIPAddressV4 = ?,
    ethernetIPAddressV6 = ?,
    status = ?,
    statusChanged = now()
WHERE name = ?;
EOS;
    $attributes = $apiResult['attributes'];
    $components = $apiResult['components'];
    $application = null;
    $battery = null;
    $wifi = null;
    $ethernet = null;
    foreach ($components as $component) {
        switch ($component['type']) {
            case 'APPLICATION':
                $application = $component;
                break;
            case 'BATTERY':
                $battery = $component;
                break;
            case 'WIFI':
                $wifi = $component;
                break;
            case 'ETHERNET':
                $ethernet = $component;
                break;
        }
    }
    $status = $apiResult['status'];

    // now the fields

    $version = $attributes['version'];
    if ($application) {
        $productType = $application['application_details']['application_type'];
        $terminalAPIVersion = $application['application_details']['version'];
    } else {
        $productType = null;
        $terminalAPIVersion = null;
    }

    $squareName = $attributes['name'];
    $squareModel = $attributes['model'];

    if ($battery) {
        $batteryLevel = $battery['battery_details']['visible_percent'];
        $externalPower = $battery['battery_details']['external_power'];
    } else {
        $batteryLevel = null;
        $externalPower = null;
    }

    if ($wifi) {
        $wifiActive = $wifi['wifi_details']['active'] ? true : false;
        $wifiSSID = $wifi['wifi_details']['ssid'];
        $signalStrength = $wifi['wifi_details']['signal_strength']['value'];
        if (array_key_exists('ip_address_v4', $wifi['wifi_details']))
            $wifiIPAddressV4 = $wifi['wifi_details']['ip_address_v4'];
        else
            $wifiIPAddressV4 = null;
        if (array_key_exists('ip_address_v6', $wifi['wifi_details']))
            $wifiIPAddressV6 = $wifi['wifi_details']['ip_address_v6'];
        else
            $wifiIPAddressV6 = null;
    } else {
        $wifiActive = null;
        $wifiSSID = null;
        $signalStrength = null;
        $wifiIPAddressV4 = null;
        $wifiIPAddressV6 = null;
    }
    if ($ethernet) {
        $ethernetActive = $ethernet['ethernet_details']['active'] ? true : false;
        if (array_key_exists('ip_address_v4', $ethernet['ethernet_details']))
            $ethernetIPAddressV4 = $ethernet['ethernet_details']['ip_address_v4'];
        else
            $ethernetIPAddressV4 = null;
        if (array_key_exists('ip_address_v6', $ethernet['ethernet_details']))
            $ethernetIPAddressV6 = $ethernet['ethernet_details']['ip_address_v6'];
        else
            $ethernetIPAddressV6 = null;
    } else {
        $ethernetActive = null;
        $ethernetIPAddressV4 = null;
        $ethernetIPAddressV6 = null;
    }

    $statusCat = $status['category'];

    $arrVals = array($productType, $squareName, $squareModel, $version, $terminalAPIVersion, $batteryLevel, $externalPower,
        $wifiActive, $wifiSSID, $wifiIPAddressV4, $wifiIPAddressV6, $signalStrength,
        $ethernetActive, $ethernetIPAddressV4, $ethernetIPAddressV6, $statusCat, $terminal);

    $datatypes = 'sssssisisssiissss';
    $updCnt = dbSafeCmd($upSQL, $datatypes, $arrVals);


// fetch the updated terminal record
$terminalSQL = <<<EOS
SELECT *
FROM terminals
WHERE name = ?;
EOS;
$terminalQ = dbSafeQuery($terminalSQL, 's', array($terminal));
if ($terminalQ === false || $terminalQ->num_rows != 1) {
    RenderErrorAjax("Cannot fetch terminal $terminal status.");
    exit();
}
$updatedRow = $terminalQ->fetch_assoc();
$response['updatedRow'] = $updatedRow;
$terminalQ->free();

$response['message'] = "$terminal status updated, $updCnt row updated";
ajaxSuccess($response);
