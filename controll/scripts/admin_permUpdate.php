<?php
require_once "../lib/base.php";
require_once "../lib/sets.php";

$check_auth = google_init("ajax");
$perm = "admin";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);


if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

// mapping of check boxes to authorizations (should this move to the database?)

$sets = get_admin_sets();
$authQ = "Select name, id from auth;";
$authR = dbQuery($authQ);

$auth_set = array(); $auth_num = array();


while($auth = $authR->fetch_assoc()) {
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
    $auths = 0;

    // Compute which auths to set for this user
    $user_auths = array();
    foreach ($sets as $n => $perms) {
        if (array_key_exists($n, $_POST) && $_POST[$n] == "on") {
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
