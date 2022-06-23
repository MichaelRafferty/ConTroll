<?php
global $ini;
if (!$ini)
    $ini = parse_ini_file(__DIR__ . "/../../../config/reg_conf.ini", true);
if ($ini['reg']['https'] <> 0) {
    if(!isset($_SERVER['HTTPS']) or $_SERVER["HTTPS"] != "on") {
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: https://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]);
        exit();
    }
}

require_once "../lib/base.php";
require_once "../lib/ajax_functions.php";

$check_auth = google_init("ajax");
$perm = "vendor";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);


if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

$con = get_conf('con');
$conid=$con['id'];


$query = "SELECT V.id as id, name, website, description, publicity"
        . ", A.requested as alleyRequest, A.authorized as alleyAuth, A.purchased as alleyPurch"
        . ", D6.requested as dealerRequest, D6.authorized as dealerAuth, D6.purchased as dealerPurch"
        . ", DX.requested as d10Request, DX.authorized as d10Auth, DX.purchased as d10Purch"
    . " FROM vendors as V"
        . " LEFT JOIN vendor_show as A ON A.vendor=V.id and A.type='alley' and A.conid=$conid"
        . " LEFT JOIN vendor_show as D6 ON D6.vendor=V.id and D6.type='dealer_6' and D6.conid=$conid"
        . " LEFT JOIN vendor_show as DX ON DX.vendor=V.id and DX.type='dealer_10' and DX.conid=$conid"
    . " WHERE V.id='" . sql_safe($_GET['vendor']). "';";
$response['query']=$query;
$resp = dbQuery($query);

if ($resp->num_rows == 0) { $response['error'] = "No Info Found";}
else { $response=fetch_safe_assoc($resp); }

ajaxSuccess($response);
?>
