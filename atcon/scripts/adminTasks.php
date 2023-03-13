<?php
// library AJAX Processor: adminTasks.php
// Balticon Registration System
// Author: Syd Weinstein
// Perform tasks under the admin page about ATCON users

require_once('../lib/base.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

// authUsers: return an array of objects of valid users of ATCON with their permission atoms
function authUsers($conid) : void
{
    $users = array();
    $query = <<<EOS
SELECT perid, concat(P.first_name, ' ', P.last_name) as name, A.auth
FROM atcon_user U
LEFT OUTER JOIN atcon_auth A ON (A.authuser = U.id)
JOIN perinfo P ON (P.id=U.perid)
WHERE conid=?
ORDER BY perid, auth;
EOS;
    $userQ = dbSafeQuery($query, 'i', array($conid));
    while ($user = fetch_safe_assoc($userQ)) {
        $perid = $user['perid'];
        if (isset($users[$perid])) {
            $users[$perid][$user['auth']] = true;
        } else {
            $users[$perid] = array(
                'id' => $perid,
                'name' => $user['name'],
                'delete' => "🗑", //html_entity_decode("&#x1F5D1;"),
            );
            $users[$perid][$user['auth']] = true;
        }
    }
    mysqli_free_result($userQ);
    $data = array();
    foreach ($users as $user){
        $data[] = $user;
    }
    $response['users'] = $data;
    $response['userid'] = $_SESSION['user'];

    ajaxSuccess($response);
}


// searchUsers: given a search string, return a list of potential users who match that string for first/middle/last name or badge_name.
// if the search string is numeric, use the perid as the search parameter
function searchUsers($conid):void {
    if (!isset($_POST['search_string'])) {
        ajaxError('No Data');
    }
    $search_string = $_POST['search_string'];
    $response['search'] = $search_string;
    $response['message'] = 'ok';

    if (is_numeric($search_string)) {
        $searchSql = <<<EOS
SELECT p.id, first_name, last_name, badge_name, email_addr
FROM perinfo p
LEFT OUTER JOIN atcon_user a ON (a.perid = p.id and a.conid = ?)
WHERE a.id is NULL AND p.id = ?
EOS;
        $typestr = 'ii';
        $params = array($conid, $search_string);
    } else {
        $searchSql = <<<EOS
SELECT p.id, first_name, last_name, badge_name, email_addr
FROM perinfo p
LEFT OUTER JOIN atcon_user a ON (a.perid = p.id and a.conid = ?)
WHERE a.id is NULL AND
    (concat_ws(' ', first_name, middle_name, last_name) LIKE ? OR badge_name like ?);
EOS;
        $search_string = '%' . str_replace(' ', '%', $search_string) . '%';
        $typestr = 'iss';
        $params = array($conid, $search_string, $search_string);
    }

    $res = dbSafeQuery($searchSql, $typestr, $params);
    if (!$res) {
        ajaxSuccess(array(
            'args' => $_POST,
            'query' => $searchSql,
            'error' => 'query failed'));
        exit();
    }
    $results = array();
    while ($row = fetch_safe_assoc($res)) {
        array_push($results, $row);
    }
    mysqli_free_result($res);

    $response['data'] = $results;
    $response['rows'] = sizeof($results);
    $response['message'] = $response['rows'] . " matching users found";
    ajaxSuccess($response);
}

// updateUsers - update the atcon_user and atcon_auth tables to match the data array passed
function updateUsers($conid):void {
    if (!isset($_POST['data'])) {
        ajaxError('No Data');
    }
    $del_rows = 0;
    $add_rows = 0;
    $upd_rows = 0;
    $perm_rows = 0;

    $data = $_POST['data'];
    web_error_log("updateUsers:");
    var_error_log($data);

    // first find all rows to delete (those not in the data array or this user)
    $savelist = array();
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
    web_error_log("updateUsers($conid):\nsql:\n$deleteSQL");
    $del_rows = dbSafeCmd($deleteSQL, 'i', array($conid));

    // now work on the rows to add - find these by selecting auth_user for the rows
    // idmap maps perid to id ($idmap[perid] = id
    $existingSQL = <<<EOS
SELECT perid, id
FROM atcon_user
WHERE conid = ?
EOS;
    $idmap = array();
    $res = dbSafeQuery($existingSQL, 'i', array($conid));
    while ($user = fetch_safe_assoc($res)) {
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
            $newid = dbSafeInsert($insSQL, 'ii', array($row['id'], $conid));
            if ($newid > 0) {
                $idmap[strval($user['id'])] = $newid;
                $add_rows++;
            }
        }
        // now update any rows with passwords
        if (array_key_exists('new_password', $row)) {
            $new_password = $row['new_password'];
            if ($new_password !== '' && $new_password !== '-') {
                $encpasswd = password_hash($new_password, PASSWORD_DEFAULT);
                $upd_rows += dbSafeCmd($updPasswdSQL, 'si', array($encpasswd, $idmap[intval($row['id'])]));
            }
        }

    }
    // update all empty user hashes
    dbCmd($updHashSQL);

    // now for the permissions, valid permissions are
    $authlabels = array('manager', 'data_entry', 'cashier', 'artinventory', 'artsales');

    $fetchAuthSQL = <<<EOS
SELECT a.id, a.authuser, a.auth
FROM atcon_user u
JOIN atcon_auth a ON (u.id = a.authuser)
WHERE u.conid = ?
ORDER BY 1,2;
EOS;
    $res = dbSafeQuery($fetchAuthSQL, 'i', array($conid));
    $users = array();
    $auths = array();
    $id = "0";
    while ($auth = fetch_safe_assoc($res)) {
        if ($id != $auth['authuser']) {
            if ($id != "0") {

                $users[strval($id)] = $auths;
                $auths = array();
            }
            $id = $auth['authuser'];
        }
        $auths[$auth['auth']] = $auth['id'];
    }
    if ($id != 0) {
        $users[strval($id)] = $auths;
    }
    mysqli_free_result($res);

    web_error_log("Current Auth Dump");
    var_error_log($users);

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
            $auths = array();
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
                    $perm_rows += dbSafeCmd($delAuthSQL, 's', array($auths[$auth]));
                }
                if ($dataexists) { // in web data, not in data row, add it
                    $perm_rows += dbSafeCmd($addAuthSql, 'ss', array($id, $auth));
                }
            }
        }
    }

    $response['message'] = "$del_rows Deleted, $add_rows Added, $upd_rows Updated, $perm_rows Permissions Updated";
    ajaxSuccess($response);
}
// outer ajax wrapper
// method - permission required to access this AJAX function
// action - passed in from the javascript

$method = 'manager';
$con = get_conf('con');
$conid = $con['id'];
$ajax_request_action = '';
if ($_POST && $_POST['ajax_request_action']) {
    $ajax_request_action = $_POST['ajax_request_action'];
}
if ($ajax_request_action == '' ) {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}
if (!check_atcon($method, $conid)) {
    $message_error = 'No permission.';
    RenderErrorAjax($message_error);
    exit();
}
switch ($ajax_request_action) {
    case 'authUsers':
        authUsers($conid);
        break;
    case 'searchUsers':
        searchUsers($conid);
        break;
    case 'updateUsers':
        updateUsers($conid);
        break;
    default:
        $message_error = 'Internal error.';
        RenderErrorAjax($message_error);
        exit();
}
