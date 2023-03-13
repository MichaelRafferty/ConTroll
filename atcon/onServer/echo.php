<?php
require_once __DIR__ . "/../lib/base.php";

$response = array("post" => $_POST, "get" => $_GET, "session" => $_SESSION);

$con = get_con();
$conid=$con['id'];
$perm='artinventory';
$response['conid'] = $conid;
$response['perm'] = $perm;

$check_auth = check_atcon($_SESSION['user'], $_SESSION['passwd'], $perm, $conid);
if($check_auth == false) { 
    ajaxSuccess(array('error' => "Authentication Failure"));
}

ajaxSuccess($response);
?>
