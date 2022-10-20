<?php
require_once "lib/base.php";

$perm="data_entry";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);

$con = get_con();
$conid=$con['id'];
$check_auth=false;
if(isset($_POST) && isset($_POST['user']) && isset($_POST['passwd'])) {
    $check_auth = check_atcon($_POST['user'], $_POST['passwd'], $perm, $conid);
}

if($check_auth == false) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}


$user = $_POST['user'];
$response['user'] = $user;
/*
$userQ = "SELECT id FROM user WHERE email='regadmin@bsfs.org';";
$userR = fetch_safe_assoc(dbQuery($userQ));
$userid = $userR['id'];
*/
$userid=2;
$con = get_conf('con');
$conid=$con['id'];

$query = "INSERT INTO transaction (conid, perid, newperid, userid) VALUES(?,?,?,?);";
$values = array($conid);

if (isset($_POST['perid'])) {
    $values[] = $_POST['perid'];
} else {
    $values[] = null;
}

if(isset($_POST['newperid'])) {
    $values[] = $_POST['newperid'];
} else {
    $values[] = null;
}
$values[] = $userid;

$transid = dbSafeInsert($query, 'iiii', $values);
$response['create_query'] = $query;
$response['transid'] = $transid;

$keyQ = "SELECT max(atcon_key) FROM atcon WHERE conid=? GROUP BY conid;";
$keyR = fetch_safe_array(dbSafeQuery($keyQ, 'i', array($conid)));
$max_Key = $keyR[0]+1;

$atconQ = "INSERT INTO atcon (conid, atcon_key, transid, perid) VALUES (?,?,?,?);";
$atconId = dbSafeInsert($atconQ, 'iiii', array($conid, $max_Key, $transid, $user));
$atcon = fetch_safe_assoc(dbSafeQuery("SELECT * FROM atcon WHERE id=?;", 'i', array($atconId)));

$response['atcon'] = $atcon;

// the following query was defined and never used.
//$query = <<<EOS
//SELECT T.id as tID, P.id as perid, T.create_date as tCreate, T.complete_date as tComplete, T.notes as tNotes, P.banned, P.id as ownerId
//    , concat_ws(' ', P.first_name, P.middle_name, P.last_name) as ownerName, P.address as ownerAddr, P.addr_2 as ownerAddr2
//    , concat_ws(' ', P.city, P.state, P.zip) as ownerLocale, P.badge_name as ownerBadge, P.email_addr as ownerEmail
//    ,R.id as badgeId, R.price, R.paid, (R.price - R.paid) as cost, M.label, concat_ws('-', M.id, M.memCategory, M.memType, M.memAge) as type, R.locked, R.create_trans
//FROM transaction T
//JOIN perinfo  P ON (P.id=T.perid)
//LEFT OUTER JOIN reg R ON (R.perid=T.perid AND R.conid=T.conid)
//LEFT OUTER JOIN memList M ON (M.id=R.memId)
//WHERE M.memCategory != 'cancel' and T.id=$transid AND T.conid=$conid;
//EOS;

$transQ = <<<EOS
SELECT T.id as tID, T.create_date as tCreate, T.complete_date as tComplete, T.notes as tNotes, P.banned, P.id as ownerId
    , P.address as ownerAddr, P.addr_2 as ownerAddr2, concat_ws(' ', P.first_name, P.middle_name, P.last_name, P.suffix) as ownerName
    , concat_ws(' ', P.city, P.state, P.zip) as ownerLocale, P.badge_name as ownerBadge, P.email_addr as ownerEmail
    , M.memAge as age, P.id as perid, R.id as badgeId, R.price, R.paid, (R.price - R.paid) as cost, M.label
    , concat_ws('-', M.id, M.memCategory, M.memType, M.memAge) as type, R.locked, R.create_trans
FROM transaction  T
JOIN perinfo P ON (P.id=T.perid)
LEFT OUTER JOIN reg R ON (R.perid=P.id AND R.conid=T.conid)
LEFT OUTER JOIN memList M ON (M.id=R.memId and M.memCategory != 'cancel')
WHERE T.id=? AND T.conid=?;
EOS;

$trans = fetch_safe_assoc(dbSafeQuery($transQ, 'ii', array($transid, $conid)));
$response['transQ'] = $transQ;
$response['result'] = $trans;

$badgeQ = <<<EOS
SELECT P.address, P.addr_2,  P.badge_name, P.email_addr, P.phone, concat_ws(' ', P.city, P.state, P.zip) as locale
    , concat_ws(' ', P.first_name, P.middle_name, P.last_name, P.suffix) as name, concat_ws(' ', NP.first_name, NP.middle_name, NP.last_name, NP.suffix) as newname
    , R.id as badgeId, R.price, R.paid, (R.price - R.paid) as cost, R.locked, M.memCategory, M.memType, M.memAge, M.label
    , concat_ws('-', M.id, M.memCategory, M.memType, M.memAge) as type
FROM transaction T
JOIN reg R ON (R.create_trans=T.id)
JOIN memList as M ON (M.id=R.memId and M.memCategory != 'cancel')
LEFT OUTER JOIN perinfo P ON (P.id=R.perid AND P.id != T.perid)
LEFT OUTER JOIN newperson NP ON (NP.id=R.newperid AND NP.id != T.newperid)
WHERE T.id=?;
EOS;

$badgeR = dbSafeQuery($badgeQ, 'i', array($transid));
$badges = array();
while($badge = fetch_safe_assoc($badgeR)) {
    array_push($badges, $badge);
}
$response['badges'] = $badges;

ajaxSuccess($response);
?>
