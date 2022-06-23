<?php
global $db_ini;
require_once "../lib/base.php";
require_once "../lib/ajax_functions.php";

$check_auth = google_init("ajax");
$perm = "admin";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);


if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

// mapping of check boxes to authorizations (should this move to the database?)

$sets = array(
    'base' => array('overview'),
    'admin' => array('admin'),
    'comp_entry' => array('badge', 'search'),
    'registration' => array('people', 'registration', 'search', 'badge'),
    'reg_admin' => array('reg_admin', 'reports'),
    'artshow_admin' => array('people', 'artist', 'artshow', 'art_control', 'art_sales', 'search', 'reports', 'vendor'),
    'artshow' => array('art_control', 'search'),
    'atcon' => array('monitor','atcon', 'atcon_checkin','atcon_register'),
    'vendor' => array('people','search','reports','vendor'),
    $db_ini['control']['clubperm'] => array($db_ini['control']['clubperm'], 'reports', 'search', 'people'),
    'Virtual' => array('virtual')
);
$authQ = "Select name, id from auth;";
$authR = dbQuery($authQ);

$auth_set = array(); $auth_num = array();


while($auth = fetch_safe_assoc($authR)) {
    $auth_set[$auth['name']] = $auth['id'];
    $auth_num[$auth['id']] = $auth['name'];
}

$user = 0;
if($_POST['action'] == 'create') {
    $createQ = "INSERT IGNORE INTO user (name, email) VALUES (?, ?);";
    $user = dbSafeInsert($createQ, "ss", array($_POST['name'], $_POST['email']));
    $response['create'] = $user;
} else {
    $user = $_POST['user_id'];
}

if (($_POST['action'] == 'update') or ($_POST['action'] == 'create')) {
    $insertQ = "INSERT IGNORE INTO user_auth(user_id, auth_id) VALUES(?, ?);";
    $deleteQ = "DELETE FROM user_auth WHERE user_id = ? AND auth_id = ?;";
    $auth = dbSafeInsert($insertQ, "ii", array($user, $auth_set['overview']));
    $auths = 0;

    // Compute which auths to set for this user
    $user_auths = array();
    $user_auths[$auth_set['overview']] = 1;

    foreach ($sets as $n => $perms) {
        if($_POST[$n] == "on") {
            foreach($perms as $v) {
                $user_auths[$auth_set[$v]] = 1;
            }
        }
    }

    // now for each possible auth value determine if we need to add or delete it

    foreach ($auth_set as $name => $id) {
        if (array_key_exists($id, $user_auths)) {
            error_log("Insert $name for $id, $user");
            dbSafeInsert($insertQ, "ii", array($user, $id));
            $auths++;
        } else {
             error_log("Delete $name for $id, $user");
             dbSafeCmd($deleteQ, "ii", array($user, $id));
        }
    }
    $response['query'] = $auths;
}

if($_POST['action'] == 'clear') {
    error_log("deleting user $user");
    $deleteQ = "DELETE FROM user_auth WHERE user_id = ?;";
    $deleted = dbSafeCmd($deleteQ, 'i', array($user));
    $deleteQ = "DELETE FROM user WHERE id = ?;";
    $deleted += dbSafeCmd($deleteQ, 'i', array($user));
    $response['query'] = $deleted;
}

ajaxSuccess($response);
?>
