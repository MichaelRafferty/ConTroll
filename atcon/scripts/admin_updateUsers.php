<?php

// library AJAX Processor: admin_updateUsers.php
// Balticon Registration System
// Author: Syd Weinstein
// update the atcon_user and atcon_auth tables from data edited

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
if ($ajax_request_action != 'updateUsers') {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}
if (!check_atcon($method, $conid)) {
    $message_error = 'No permission.';
    RenderErrorAjax($message_error);
    exit();
}

// updateUsers - update the atcon_user and atcon_auth tables to match the data array passed

if (!isset($_POST['data'])) {
    ajaxError('No Data');
}
//$del_rows = 0; // Set by non if protected dbSafeCmd below.
$add_rows = 0;
$upd_rows = 0;
$perm_rows = 0;

$data = $_POST['data'];
//web_error_log("updateUsers:");
//var_error_log($data);

// first find all the  rows to delete (those not in the data array or this user)
$savelist = [];
$savelist[] = $_SESSION['user']; // not allowed to delete yourself
foreach ($data as $row) {
    if (is_numeric($row['id'])) {
        $savelist[] = $row['id'];
    }
}
$no_delete = implode(',', $savelist);
$deleteSQL = <<<EOS
DELETE FROM atcon_user
WHERE perid NOT IN ($no_delete) AND conid = ?
EOS;
//web_error_log("updateUsers($conid):\nsql:\n$deleteSQL");
$del_rows = dbSafeCmd($deleteSQL, 'i', [$conid]);

// now work on the rows to add - find these by selecting auth_user for the rows
// idmap maps perid to id ($idmap[perid] = id
$existingSQL = <<<EOS
SELECT perid, id
FROM atcon_user
WHERE conid = ?
EOS;
$idmap = [];
$res = dbSafeQuery($existingSQL, 'i', [$conid]);
while ($user = $res->fetch_assoc()) {
    $idmap[strval($user['perid'])] = $user['id'];
}
mysqli_free_result($res);

var_error_log($idmap);

$insSQL = <<<EOS
INSERT INTO atcon_user(perid, conid )
VALUES(?,?);
EOS;
$updHashSQL = <<<EOS
UPDATE atcon_user
SET userhash = MD5(concat(id, perid))
WHERE userhash IS NULL;
EOS;

$updPasswdSQL = <<<EOS
UPDATE atcon_user
SET passwd = ? 
WHERE id = ?;
EOS;
// insert rows that don't exist...
foreach ($data as $row) {
    if (!array_key_exists(strval($row['id']), $idmap)) {
        // not in existing rows, add atcon_user data
        $newid = dbSafeInsert($insSQL, 'ii', [$row['id'], $conid]);
        if ($newid > 0) {
            $idmap[strval($row['id'])] = $newid;
            $add_rows++;
        }
    }
    // now update any rows with passwords
    if (array_key_exists('new_password', $row)) {
        $new_password = $row['new_password'];
        if ($new_password !== '' && $new_password !== '-') {
            $encpasswd = password_hash($new_password, PASSWORD_DEFAULT);
            $upd_rows += dbSafeCmd($updPasswdSQL, 'si', [$encpasswd, $idmap[intval($row['id'])]]);
        }
    }
}
// update all empty user hashes
dbCmd($updHashSQL);

// now for the permissions, valid permissions are
$authlabels = ['manager', 'data_entry', 'cashier', 'artinventory', 'artshow', 'artsales','vol_roll'];

$fetchAuthSQL = <<<EOS
SELECT a.id, a.authuser, a.auth
FROM atcon_user u
JOIN atcon_auth a ON (u.id = a.authuser)
WHERE u.conid = ?
ORDER BY 2,3;
EOS;
$res = dbSafeQuery($fetchAuthSQL, 'i', [$conid]);
$users = [];
$auths = [];
$id = "0";
while ($auth = $res->fetch_assoc()) {
    if ($id != $auth['authuser']) {
        if ($id != "0") {
            $users[strval($id)] = $auths;
            $auths = [];
        }
        $id = $auth['authuser'];
    }
    $auths[$auth['auth']] = $auth['id'];
}
if ($id != 0) {
    $users[strval($id)] = $auths;
}
mysqli_free_result($res);

//web_error_log("Current Auth Dump");
//var_error_log($users);

$delAuthSQL = <<<EOS
DELETE FROM atcon_auth
WHERE id = ?;
EOS;

$addAuthSql = <<<EOS
INSERT INTO atcon_auth(authuser,auth)
VALUES (?,?);
EOS;

// now loop over data, the users should match the data users due to deletes and inserts, as a 1-1
foreach ($data as $row) {
    $perid = $row['id'];
    $id = $idmap[strval($perid)];
    if (array_key_exists(intval($id), $users)) {
        $auths = $users[intval($id)];
    } else {
        $auths = [];
    }

    // Now compare each auth against the data record
    foreach ($authlabels as $auth) {
        $dbexists = array_key_exists($auth, $auths);
        if (array_key_exists($auth, $row)) {
            $dataexists = $row[$auth] === true || $row[$auth] === "true";
        } else {
            $dataexists = false;
        }
        if ($dbexists != $dataexists) {
            if ($dbexists) { // in database, not in data row delete it.
                $perm_rows += dbSafeCmd($delAuthSQL, 's', [$auths[$auth]]);
            }
            if ($dataexists) { // in web data, not in data row, add it
                $perm_rows += dbSafeCmd($addAuthSql, 'ss', [$id, $auth]);
            }
        }
    }
}

$response['message'] = "$del_rows Deleted, $add_rows Added, $upd_rows Updated, $perm_rows Permissions Updated";
ajaxSuccess($response);
