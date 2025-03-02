<?php

// library AJAX Processor: admin_updatePrinters.php
// Balticon Registration System
// Author: Syd Weinstein
// update the server and printers tables after editing

require_once('../lib/base.php');

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
if ($ajax_request_action != 'updatePrinters') {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}
if (!check_atcon($method, $conid)) {
    $message_error = 'No permission.';
    RenderErrorAjax($message_error);
    exit();
}

// updatePrinters - update the servers and printers tables to match the data passed

if (!isset($_POST['printers']) && !isset($_POST['servers'])) {
    ajaxError('No Data');
}
if (array_key_exists('printers', $_POST))
    $printers = $_POST['printers'];
else
    $printers = [];

if (array_key_exists('servers', $_POST))
    $servers = $_POST['servers'];
else
    $servers = [];

//web_error_log('updatePrinters: Servers:');
//var_error_log($servers);
//web_error_log('updatePrinters: Printers:');
//var_error_log($printers);

$servers_updated = 0;
$servers_added = 0;
$printers_deleted = 0;
$printers_updated = 0;
$printers_added = 0;

// First Update Servers
// Necessary to update servers before we delete them due to on cascade delete
$updateLocalSQL = <<<EOS
UPDATE servers
SET serverName = ?, address = ?, location = ?, active = ?
WHERE serverName = ? and local = 1;
EOS;
$updateGlobalSQL = <<<EOS
UPDATE servers
SET location = ?, active = ?
WHERE serverName = ? and local = 0;
EOS;
$insertLocalSQL = <<<EOS
INSERT servers(serverName, address, location, active, local)
VALUES (?,?,?,?, 1);
EOS;
foreach ($servers as $row) {
    $active = $row['active'];
    if ($active === 'true' || ($active !== 'false' && (int)$active > 0))
        $active = 1;
    else
        $active = 0;

    $local = $row['local'];
    if ($local === 'true' || $local > 0)
        $local = 1;
    else
        $local = 0;

    if (array_key_exists('oldServerName', $row) && $row['oldServerName'] != '') { // existing server, update it
        if ($local == 1) {
            $servers_updated += dbSafeCmd($updateLocalSQL, 'sssis', array($row['serverName'], $row['address'], $row['location'], $active, $row['oldServerName']));
        } else {
            $servers_updated += dbSafeCmd($updateGlobalSQL, 'sis', array($row['location'], $active, $row['oldServerName']));
        }
    } else { // new server add it
        if ($local == 1) {
            $servers_added = dbSafeCmd($insertLocalSQL, 'sssi', array($row['serverName'], $row['address'], $row['location'], $active));
        }
    }
}

// Delete all servers not in list of servers passed in servers array
$savelist = [];
foreach ($servers as $row) {
    if (isset($row['serverName'])) {
        $savelist[] = sql_safe($row['serverName']);
    }
    if (isset($row['oldServerName'])) {
        $savelist[] = sql_safe($row['oldServerName']);
    }
}
$no_delete = "'" . implode("','", $savelist) . "'";
$deleteSQL = <<<EOS
DELETE FROM servers
WHERE serverName NOT IN ($no_delete) AND local = 1;
EOS;
//web_error_log("updatePrinters($conid):\nsql:\n$deleteSQL");
$servers_deleted = dbCmd($deleteSQL);
//web_error_log("$servers_deleted deleted from servers");

// now update printers
$existing = array();
$existingPrintersSQL = <<<EOS
SELECT p.serverName, p.printerName, s.local
FROM printers p
JOIN servers s ON (p.serverName = s.serverName)
EOS;
$existingQ = dbQuery($existingPrintersSQL);
while ($printer = $existingQ->fetch_assoc()) {
    $existing[$printer['serverName'] . ':::' . $printer['printerName']] = $printer['local'];
}
mysqli_free_result($existingQ);
$updateLocalPrinterSQL = <<<EOS
UPDATE printers
SET printerName = ?, printerType=?, codePage=?, active=?
WHERE serverName = ? and printerName = ?
EOS;
$updateGlobalPrinterSQL = <<<EOS
UPDATE printers
SET printerType=?, codePage=?, active=?
WHERE serverName = ? and printerName = ?
EOS;
$insertPrinterSQL = <<<EOS
INSERT INTO printers(serverName, printerName, printerType, codePage, active)
VALUES (?,?,?,?,?);
EOS;

foreach ($printers as $row) {
    $key = $row['serverName'] . ':::' . $row['printerName'];
    $active = $row['active'];
    if ($active === 'true' || ($active !== "false" && (int) $active > 0))
        $active = 1;
    else
        $active = 0;

    if (array_key_exists($key, $existing)) { // this printer is both in the post and in the database, update it
        $existing[$key] = 0; // mark it updated
        if (array_key_exists('delete', $row) && $row['delete'] == '') { // global printer
            $printers_updated += dbSafeCmd($updateGlobalPrinterSQL, 'ssiss', array($row['printerType'], $row['codePage'], $active, $row['serverName'], $row['printerName']));
        } else {
            $printers_updated += dbSafeCmd($updateLocalPrinterSQL, 'sssiss', array($row['printerName'], $row['printerType'], $row['codePage'], $active, $row['serverName'], $row['printerName']));
        }
    } else { // insert new printer
        $printers_added += dbSafeCmd($insertPrinterSQL, 'ssssi', array($row['serverName'], $row['printerName'], $row['printerType'], $row['codePage'], $active));
    }
}

$deleteSQL = <<<EOS
DELETE FROM printers
WHERE serverName = ? AND printerName = ?;
EOS;

// Now delete any printers within existing whose value is still 1 (not updated and local)
foreach ($existing as $key => $value) {
    if ($value == 1) { // local and not updated
        $keys = explode(':::', $key);
        $printers_deleted += dbSafeCmd($deleteSQL, 'ss', array($keys[0], $keys[1]));
    }
}

$response['message'] = "$servers_deleted Servers deleted, $servers_updated Servers Updated, $servers_added Servers added<br/>" .
    "$printers_deleted Printers deleted, $printers_updated Printers updated, $printers_added Printers Added";
ajaxSuccess($response);
