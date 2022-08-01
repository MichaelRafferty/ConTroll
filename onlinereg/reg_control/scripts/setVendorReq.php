<?php
global $db_ini;

require_once "../lib/base.php";

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

$vendor =sql_safe($_POST['vendor']);

$updateAlley="INSERT INTO vendor_show (vendor, conid, type, requested, authorized, purchased) VALUES"
    . "('$vendor','$conid','alley','".sql_safe($_POST['alleyRequest'])."'"
    . ",'".sql_safe($_POST['alleyAuth'])."'"
    . ",'".sql_safe($_POST['alleyPurch'])."')"
    . " ON DUPLICATE KEY UPDATE"
    . " requested='".sql_safe($_POST['alleyRequest'])."'"
    . ", authorized='".sql_safe($_POST['alleyAuth'])."'"
    . ", purchased='".sql_safe($_POST['alleyPurch'])."';";
dbQuery($updateAlley);

$updateDealer="INSERT INTO vendor_show (vendor, conid, type, requested, authorized, purchased) VALUES"
    . "('$vendor','$conid','dealer_6','".sql_safe($_POST['dealerRequest'])."'"
    . ",'".sql_safe($_POST['dealerAuth'])."'"
    . ",'".sql_safe($_POST['dealerPurch'])."')"
    . " ON DUPLICATE KEY UPDATE"
    . " requested='".sql_safe($_POST['dealerRequest'])."'"
    . ", authorized='".sql_safe($_POST['dealerAuth'])."'"
    . ", purchased='".sql_safe($_POST['dealerPurch'])."';";
dbQuery($updateDealer);

$updateD10="INSERT INTO vendor_show  (vendor, conid, type, requested, authorized, purchased) VALUES"
    . "('$vendor','$conid','dealer_10','".sql_safe($_POST['d10Request'])."'"
    . ",'".sql_safe($_POST['d10Auth'])."'"
    . ",'".sql_safe($_POST['d10Purch'])."')"
    . " ON DUPLICATE KEY UPDATE"
    . " requested='".sql_safe($_POST['d10Request'])."'"
    . ", authorized='".sql_safe($_POST['d10Auth'])."'"
    . ", purchased='".sql_safe($_POST['d10Purch'])."';";
dbQuery($updateD10);



$query = "SELECT V.id as id, name, website, description, publicity"
        . ", A.requested as alleyRequest, A.authorized as alleyAuth, A.purchased as alleyPurch"
        . ", D6.requested as dealerRequest, D6.authorized as dealerAuth, D6.purchased as dealerPurch"
        . ", DX.requested as d10Request, DX.authorized as d10Auth, DX.purchased as d10Purch"
    . " FROM vendors as V"
        . " LEFT JOIN vendor_show as A ON A.vendor=V.id and A.type='alley' and A.conid=$conid"
        . " LEFT JOIN vendor_show as D6 ON D6.vendor=V.id and D6.type='dealer_6' and D6.conid=$conid"
        . " LEFT JOIN vendor_show as DX ON DX.vendor=V.id and DX.type='dealer_10' and DX.conid=$conid"
    . " WHERE V.id='$vendor';";
$response['query']=$query;
$resp = dbQuery($query);

if ($resp->num_rows == 0) { $response['error'] = "No Info Found";}
else { $response=fetch_safe_assoc($resp); }

ajaxSuccess($response);
?>
