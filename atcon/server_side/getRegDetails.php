<?php
require_once "lib/base.php";
require_once "lib/ajax_functions.php";

$perm = "data_entry";

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

$badgeQ = "SELECT M.label, B.action, count(R.id) as count, sum(R.price - R.paid) as amount"
    . " FROM atcon as A"
        . " JOIN atcon_badge as B ON B.atconId=A.id"
        . " JOIN reg as R on R.id=B.badgeId"
        . " JOIN memList as M on M.id=R.memId"
    . " WHERE A.transid=$transid and B.action in ('create', 'yearahead', 'upgrade')"
    . " GROUP BY M.label, B.action;";

$badgeR = dbQuery($badgeQ);

$badgeList = array();
while($badge = fetch_safe_assoc($badgeR)) {
    array_push($badgeList, $badge);
}

$response['badgelist'] = $badgeList;

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
