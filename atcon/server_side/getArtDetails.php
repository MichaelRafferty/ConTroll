<?php
require_once "lib/base.php";
require_once "lib/ajax_functions.php";

$perm = "artshow";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);

$con = get_con();
$conid=$con['id'];
$check_auth=false;

if(isset($_POST) && isset($_POST['user']) && isset($_POST['passwd'])) {
    $check_auth = check_atcon($_POST['user'], $_POST['passwd'], $perm, $conid);
}

if(!isset($_POST) || !isset($_POST['transid'])) {
    $response['error'] = "Need Item Info";
    ajaxSuccess($response);
    exit();
}

$transid = sql_safe($_POST['transid']);

$artQ = "SELECT S.amount, S.quantity, I.title, I.item_key, A.art_key"
    . " FROM artsales as S"
        . " JOIN artItems as I ON I.id=S.artid"
        . " JOIN artshow as A on A.id=I.artshow"
    . " WHERE S.transid=$transid";

$artR = dbQuery($artQ);

$artList = array();
while($art = fetch_safe_assoc($artR)) {
    array_push($artList, $art);
}

$response['artlist'] = $artList;

$transQ = "SELECT T.price, T.paid, T.withtax, T.tax, T.change_due"
        . ", ROUND(T.withtax + T.change_due,2) as amount"
        . ", P.type, P.description, P.cc, P.cc_approval_code"
    . " FROM transaction as T"
        . " JOIN payments as P on P.transid=T.id"
    . " WHERE T.id=$transid";

$transR = dbQuery($transQ);

$response['transinfo'] = fetch_safe_assoc($transR);

ajaxSuccess($response);
?>
