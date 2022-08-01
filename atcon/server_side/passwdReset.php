<?php
if(!isset($_SERVER['HTTPS']) or $_SERVER["HTTPS"] != "on") {
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: https://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]);
    exit();
}

require_once "lib/base.php";
require_once "lib/ajax_functions.php";
    
$response = array("post" => $_POST, "get" => $_GET);

$con = get_conf("con");
$conid=$con['id'];
$check_auth=false;
$perm = 'manager';
if(isset($_POST) && isset($_POST['user']) && isset($_POST['passwd']) && isset($_POST['newpw'])) {
    $user = sql_safe($_POST['user']);
    $passwd = sql_safe($_POST['passwd']);
    $resetUser = sql_safe($_POST['resetUser']);
    $newpw = sql_safe($_POST['newpw']);
    $checkQ = "SELECT * from atcon_auth where perid=$user and passwd='$passwd' and auth='$perm' and conid=$conid;";
    $checkR = dbQuery($checkQ);
    if(isset($checkR) && $checkR != null && $checkR->num_rows >= 1) {
        $updateQ = "UPDATE atcon_auth SET passwd='$newpw' WHERE perid=$resetUser;";
        dbQuery($updateQ);
        $response['message'] = "Password Changed";
    } else { 
        $response['message'] = "Incorrect Password Entered";
    }
} else {
    $response['message'] = "Incorrect Password Entered";
}

ajaxSuccess($response);
?>
