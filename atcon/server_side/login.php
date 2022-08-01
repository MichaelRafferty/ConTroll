<?php
require_once "lib/base.php";
require_once "lib/ajax_functions.php";

$response = array("post" => $_POST, "get" => $_GET);
$con = get_con();
$conid= $con['id'];

//error_log("login.php");
//var_error_log($_POST);
if(isset($_POST) && isset($_POST['user']) && isset($_POST['passwd'])) {
    $q = "SELECT auth FROM atcon_auth WHERE perid='" . sql_safe($_POST['user']) . "'"
            . " AND passwd='" . sql_safe($_POST['passwd']) . "'"
            . " AND conid=$conid;";
    $r = dbquery($q);
    if($r->num_rows > 0) {
        $response['success'] = 1;
        $auths=array();
        while($l = fetch_safe_assoc($r)) {
            array_push($auths, $l['auth']);
        }
        $response['auth']=$auths;
    } else { $response['success'] = 0; }
} else { $response['success'] = 0; }

ajaxSuccess($response);
?>
