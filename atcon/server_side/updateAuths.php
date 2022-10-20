<?php
require_once "lib/base.php";

$response = array("post" => $_POST, "get" => $_GET);

$con = get_conf("con");
$conid=$con['id'];
$check_auth=false;
$perm = 'manager';
if(isset($_POST) && isset($_POST['user']) && isset($_POST['passwd'])) {
    $user = $_POST['user'];
    $passwd = $_POST['passwd'];
    $updateUser = $_POST['updateUser'];
    $checkQ = "SELECT * from atcon_auth where perid=? and passwd=? and auth=? and conid=?;";
    $checkR = dbSafeQuery($checkQ, 'issi', array($user, $passwd, $perm, $conid));
    if(isset($checkR) && $checkR != null && $checkR->num_rows >= 1) {
        $getPwQ = "SELECT passwd FROM atcon_auth WHERE perid=? and conid=?";
        $getPwR = dbSafeQuery($getPwQ, 'ii', array($updateUser, $conid));
        $userPwA = fetch_safe_assoc($getPwR);
        $newpw = $userPwA['passwd'];
        $cleanQ = "DELETE FROM atcon_auth WHERE perid=? and conid=?;";
        dbSafeCmd($cleanQ, 'ii', array($updateUser, $conid));
        $updateQ = "INSERT INTO atcon_auth (perid, auth, conid, passwd) VALUES";
        $first = true;
        $datatypes = '';
        $values = array();
        if($_POST['data_entry'] == 'true') {
            if(!$first) { $updateQ .= ","; }
            $updateQ .= " (?, 'data_entry', ?, ?)";
            $datatypes .= 'iis';
            $values[] = $updateUser;
            $values[] = $conid;
            $values[] = $newpw;
            $first = false;
        }
        if($_POST['register'] == 'true') {
            if(!$first) { $updateQ .= ","; }
            $updateQ .= " (?, 'cashier', ?, ?)";
            $datatypes .= 'iis';
            $values[] = $updateUser;
            $values[] = $conid;
            $values[] = $newpw;
            $first = false;
        }
        if($_POST['artshow'] == 'true') {
            if(!$first) { $updateQ .= ","; }
            $updateQ .= " (?, 'artshow', ?, ?)";
            $datatypes .= 'iis';
            $values[] = $updateUser;
            $values[] = $conid;
            $values[] = $newpw;
            $first = false;
        }
        if($_POST['manager'] == 'true') {
            if(!$first) { $updateQ .= ","; }
            $updateQ .= " (?, 'manager', ?, ?)";
            $datatypes .= 'iis';
            $values[] = $updateUser;
            $values[] = $conid;
            $values[] = $newpw;
            $first = false;
        }
        if(!$first) {
            $updateQ .= ";";
            $rows = dbSafeCmd($updateQ, $datatypes, $values);
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
