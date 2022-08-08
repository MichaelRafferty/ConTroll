<?php
require_once "lib/base.php";
    
$response = array("post" => $_POST, "get" => $_GET);

$con = get_conf("con");
$conid=$con['id'];
$check_auth=false;
$perm = 'manager';
if(isset($_POST) && isset($_POST['user']) && isset($_POST['passwd']) && isset($_POST['newpw'])) {
    $user = $_POST['user'];
    $passwd = $_POST['passwd'];
    $resetUser = $_POST['resetUser'];
    $newpw = $_POST['newpw'];
    $checkQ = "SELECT * from atcon_auth where perid=? and passwd=? and auth=? and conid=?;";
    $checkR = dbSafeQuery($checkQ, 'issi', array($user, $passwd, $perm, $conid));
    if(isset($checkR) && $checkR != null && $checkR->num_rows >= 1) {
        $updateQ = "UPDATE atcon_auth SET passwd=? WHERE perid=?;";
        dbSafeCmd($updateQ, 'si', array($newpw, $resetUser));
        $response['message'] = "Password Changed";
    } else { 
        $response['message'] = "Incorrect Password Entered";
    }
} else {
    $response['message'] = "Incorrect Password Entered";
}

ajaxSuccess($response);
?>
