<?php
require_once "../lib/base.php";

$check_auth = google_init("ajax");
$perm = "registration";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);

if($check_auth == false || (!checkAuth($check_auth['sub'], $perm) &&
                   (!checkAuth($check_auth['sub'], 'atcon')))) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

$user = $check_auth['email'];
$response['user'] = $user;
$userQ = "SELECT id FROM user WHERE email=?;";
$userR = fetch_safe_assoc(dbSafeQuery($userQ, 's', array($user)));
$userid = $userR['id'];
$con = get_conf('con');
$conid=$con['id'];
$nextconid = $conid + 1;

$query = "INSERT INTO transaction (conid, perid, newperid, userid) VALUES(?, ?, ?, ?);";
$values = array($conid);

if (isset($_POST['perid'])) {
    array_push($values, $_POST['perid']);
} else {
    array_push($values, null);
}

if (isset($_POST['newperid'])) {
    array_push($values, $_POST['newperid']);
} else {
    array_push($values, null);
}
array_push($values, $userid);

$transid = dbSafeInsert($query, 'iiii', $values);
$response['create_query'] = $query;
$response['transid'] = $transid;

$keyQ = "SELECT max(atcon_key) FROM atcon WHERE conid=? GROUP BY conid;";
$keyR = fetch_safe_array(dbSafeQuery($keyQ, 'i', array($conid)));
$max_Key = $keyR[0]+1;

$atconQ = "INSERT INTO atcon (conid, atcon_key, transid, perid) VALUES (?, ?, ?, NULL);";

$atconId = dbSafeInsert($atconQ, 'iii', array($conid, $max_Key, $transid));
$atcon = fetch_safe_assoc(dbSafeQuery("SELECT * FROM atcon WHERE id=?", 'i', array($atconId)));

$response['atcon'] = $atcon;

$transQ = <<<EOQ
SELECT T.id as tID, T.create_date as tCreate
    , T.complete_date as tComplete, T.notes as tNotes, P.banned
    , P.id as ownerId, P.address as ownerAddr, P.addr_2 as ownerAddr2
    , concat_ws(' ', P.first_name, P.middle_name, P.last_name, P.suffix) as ownerName
    , concat_ws(' ', P.city, P.state, P.zip) as ownerLocale
    , P.badge_name as ownerBadge, P.email_addr as ownerEmail
    , R.id as badgeId, R.price, R.paid, (IFNULL(R.price,0) - IFNULL(R.paid,0)) as cost, M.label
    , concat_ws('-', M.id, M.memCategory, M.memType, M.memAge) as type
    , R.locked, R.create_trans, IFNULL(R1.id, -1) as nextid
FROM transaction as T
JOIN perinfo as P ON (P.id=T.perid)
LEFT OUTER JOIN reg as R ON (R.perid=P.id AND (R.conid=T.conid OR R.conid=?))
LEFT OUTER JOIN reg as R1 ON (R1.perid=P.id AND R1.conid=?)
LEFT OUTER JOIN memLabel as M ON (M.id=R.memId)
WHERE T.id=? AND T.conid=?;
EOQ;

$trans = fetch_safe_assoc(dbSafeQuery($transQ, 'iiii', array($conid, $nextconid, $transid, $conid)));
$response['transQ'] = $transQ;
$response['result'] = $trans;

$badgeQ = <<<EOQ
SELECT P.address, P.addr_2,  P.badge_name, P.email_addr, P.phone
    , concat_ws(' ', P.city, P.state, P.zip) as locale
    , concat_ws(' ', P.first_name, P.middle_name, P.last_name, P.suffix) as name
    , concat_ws(' ', NP.first_name, NP.middle_name, NP.last_name, NP.suffix) as newname
    , R.id as badgeId, R.price, R.paid, (IFNULL(R.price,0) - IFNULL(R.paid,0)) as cost, R.locked
    , M.memCategory, M.memType, M.memAge, M.label
    , concat_ws('-', M.id, M.memCategory, M.memType, M.memAge) as type
FROM transaction as T
JOIN reg as R ON (R.create_trans=T.id)
LEFT OUTER JOIN perinfo as P ON (P.id=R.perid AND P.id != T.perid)
LEFT OUTER JOIN newperson as NP ON (NP.id=R.newperid AND NP.id != T.newperid)
JOIN memLabel as M ON (M.id=R.memId)
WHERE T.id=?;
EOQ;

$badgeR = dbSafeQuery($badgeQ, 'i', array($transid));
$badges = array();
$total = 0;
while($badge = fetch_safe_assoc($badgeR)) {
    array_push($badges, $badge);
    $total += $badge['paid'];
}
$response['badges'] = $badges;
$response['total'] = $total;

ajaxSuccess($response);
?>
