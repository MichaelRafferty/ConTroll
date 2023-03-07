<?php
require_once "lib/base.php";
    
$response = array("post" => $_POST, "get" => $_GET);

$con = get_conf("con");
$conid=$con['id'];
$check_auth=false;
if(isset($_POST) && isset($_POST['user']) && isset($_POST['passwd']) && isset($_POST['newpasswd'])) {
    $user = $_SESSION['user'];
    $passwd = $_POST['passwd'];
    $newpw = $_POST['newpasswd'];
    $checkQ = <<<EOS
SELECT passwd from atcon_user where perid=? and conid=?;
EOS;

    $checkR = dbSafeQuery($checkQ, 'is', array($user, $passwd));
    if(isset($checkR) && $checkR != null && $checkR->num_rows >= 1) {
        $updateQ = "UPDATE atcon_auth SET passwd=? WHERE perid=?;";
        dbSafeCmd($updateQ, 'si', array($newpw, $user));
        $response['message'] = "Password Changed";
    } else { 
        $response['message'] = "Incorrect Password Entered";
    }
} else {
    $response['message'] = "Incorrect Password Entered";
}

ajaxSuccess($response);
?>
