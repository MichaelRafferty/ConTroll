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
if(isset($_POST) && isset($_POST['user']) && isset($_POST['passwd'])) {
    $user = sql_safe($_POST['user']);
    $passwd = sql_safe($_POST['passwd']);
    $updateUser = sql_safe($_POST['perid']);
    $checkQ = "SELECT * from atcon_auth where perid=$user and passwd='$passwd' and auth='$perm' and conid=$conid;";
    $checkR = dbQuery($checkQ);
    if(isset($checkR) && $checkR != null && $checkR->num_rows >= 1) {
        $newpw = sql_safe($_POST['newpw']);
        $updateQ = "INSERT INTO atcon_auth (perid, auth, conid, passwd) VALUES";
        $first = true;
        if(isset($_POST['data_entry']) && $_POST['data_entry'] == 'on') {
            if(!$first) { $updateQ .= ","; }
            $updateQ .= " ($updateUser, 'data_entry', $conid, '$newpw')";
            $first = false;
        }
        if(isset($_POST['register']) && $_POST['register'] == 'on') {
            if(!$first) { $updateQ .= ","; }
            $updateQ .= " ($updateUser, 'cashier', $conid, '$newpw')";
            $first = false;
        }
        if(isset($_POST['artshow']) && $_POST['artshow'] == 'on') {
            if(!$first) { $updateQ .= ","; }
            $updateQ .= " ($updateUser, 'artshow', $conid, '$newpw')";
            $first = false;
        }
        if(isset($_POST['manager']) && $_POST['manager'] == 'on') {
            if(!$first) { $updateQ .= ","; }
            $updateQ .= " ($updateUser, 'manager', $conid, '$newpw')";
            $first = false;
        }
        if(!$first) {
            $updateQ .= ";";
            dbQuery($updateQ);
            $respoinse['updateQ'] = $updateQ;
            $response['message'] = "User Updated";
        } else {
            $response['message'] = "User Auths Removed";
        }
    } else { 
        $response['message'] = "Incorrect Password Entered";
    }
} else {
    $response['message'] = "Update Failed";
}

ajaxSuccess($response);
?>
