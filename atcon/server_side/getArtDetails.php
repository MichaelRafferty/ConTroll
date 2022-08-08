<?php
require_once "lib/base.php";

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

$transid = $_POST['transid'];

$artQ = <<<EOS
SELECT S.amount, S.quantity, I.title, I.item_key, A.art_key
FROM artsales S
JOIN artItems I ON (I.id=S.artid)
JOIN artshow A ON (A.id=I.artshow)
WHERE S.transid=?;
EOS;

$artR = dbSafeQuery($artQ, 'i', array($transid));

$artList = array();
while($art = fetch_safe_assoc($artR)) {
    array_push($artList, $art);
}

$response['artlist'] = $artList;

$transQ = <<<EOS
SELECT T.price, T.paid, T.withtax, T.tax, T.change_due, ROUND(T.withtax + T.change_due,2) as amount, P.type, P.description, P.cc, P.cc_approval_code
FROM transaction T
JOIN payments P ON (P.transid=T.id)
WHERE T.id=?;
EOS;

$transR = dbSafeQuery($transQ, 'i', array($transid));

$response['transinfo'] = fetch_safe_assoc($transR);

ajaxSuccess($response);
?>
