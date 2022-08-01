<?php
if(!isset($_SERVER['HTTPS']) or $_SERVER["HTTPS"] != "on") {
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: https://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]);
    exit();
}

require_once "lib/base.php";
require_once "lib/ajax_functions.php";
$perm="manager";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);

$con = get_con();
$conid=$con['id'];
$check_auth=false;
if(isset($_POST) && isset($_POST['user']) && isset($_POST['passwd'])) {
    $check_auth = check_atcon($_POST['user'], $_POST['passwd'], $perm, $conid);
}

if($check_auth == false) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}


$users = array();
$userQ = dbQuery("SELECT perid, concat(P.first_name, ' ', P.last_name) as name, auth FROM atcon_auth as A JOIN perinfo as P on P.id=A.perid where conid=$conid;");
while($user = fetch_safe_assoc($userQ)) {
    if(isset($users[$user['perid']])) {
        $users[$user['perid']][$user['auth']] = 'checked';
    } else {
        $users[$user['perid']] = array(
            'id' => $user['perid'],
            'name' => $user['name']
        );
        $users[$user['perid']][$user['auth']] = 'checked';
    }
}
$response['users'] = $users;

ajaxSuccess($response);
?>
