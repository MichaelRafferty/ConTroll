<?php
require_once "lib/base.php";

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

$transid = $_POST['transid'];

$badgeQ = <<<EOS
SELECT M.label, B.action, count(R.id) as count, sum(R.price - R.paid) as amount
FROM atcon A
JOIN atcon_badge B ON (B.atconId=A.id)
JOIN reg R ON (R.id=B.badgeId)
JOIN memList M ON (M.id=R.memId)
WHERE A.transid=? and B.action in ('create', 'yearahead', 'upgrade')
GROUP BY M.label, B.action;
EOS;

$badgeR = dbSafeQuery($badgeQ, 'i', array($transid));

$badgeList = array();
while($badge = fetch_safe_assoc($badgeR)) {
    array_push($badgeList, $badge);
}

$response['badgelist'] = $badgeList;

$transQ = <<<EOS
SELECT T.price, T.paid, T.withtax, T.tax, T.change_due, ROUND(T.withtax + T.change_due,2) as amount
    , P.type, P.description, P.cc, P.cc_approval_code
FROM transaction T
JOIN payments P ON (P.transid=T.id)
WHERE T.id=?;
EOS;

$transR = dbSafeQuery($transQ, 'i', array($transid));

$response['transinfo'] = fetch_safe_assoc($transR);

ajaxSuccess($response);
?>