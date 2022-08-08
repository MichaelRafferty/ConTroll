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
    $updateUser = $_POST['perid'];
    $checkQ = "SELECT * from atcon_auth where perid=? and passwd=? and auth=? and conid=?;";
    $checkR = dbSafeQuery($checkQ, 'issi', array($user, $passed, $perm, $conid));
    if(isset($checkR) && $checkR != null && $checkR->num_rows >= 1) {
        $newpw = $_POST['newpw'];
        $datatypes = '';
        $values = array();
        $updateQ = "INSERT INTO atcon_auth (perid, auth, conid, passwd) VALUES";
        $first = true;
        if(isset($_POST['data_entry']) && $_POST['data_entry'] == 'on') {
            if(!$first) { $updateQ .= ","; }
            $updateQ .= " (? 'data_entry', ?, ?)";
            $datatypes = 'iis';
            $values[] = $updateUser;
            $values[] = $conid;
            $values[] = $newpw;

            $first = false;
        }
        if(isset($_POST['register']) && $_POST['register'] == 'on') {
            if(!$first) { $updateQ .= ","; }
            $updateQ .= " (?, 'cashier', ?, ?)";
            $datatypes = 'iis';
            $values[] = $updateUser;
            $values[] = $conid;
            $values[] = $newpw;
            $first = false;
        }
        if(isset($_POST['artshow']) && $_POST['artshow'] == 'on') {
            if(!$first) { $updateQ .= ","; }
            $updateQ .= " (?, 'artshow', ?, ?)";
            $datatypes = 'iis';
            $values[] = $updateUser;
            $values[] = $conid;
            $values[] = $newpw;
            $first = false;
        }
        if(isset($_POST['manager']) && $_POST['manager'] == 'on') {
            if(!$first) { $updateQ .= ","; }
            $updateQ .= " (?, 'manager', ?, ?)";
            $datatypes = 'iis';
            $values[] = $updateUser;
            $values[] = $conid;
            $values[] = $newpw;
            $first = false;
        }
        if(!$first) {
            $updateQ .= ";";
            dbSafeInsert($updateQ, $datatypes, $values);
            $respoinse['updateQ'] = $updateQ;
            $response['message'] = "User Added";
        } else {
            $response['message'] = "Not permissions granted, user not added.";
        }
    } else {
        $response['message'] = "Incorrect Password Entered";
    }
} else {
    $response['message'] = "Update Failed";
}

ajaxSuccess($response);
?>
