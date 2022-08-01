<?php
if(!isset($_SERVER['HTTPS']) or $_SERVER["HTTPS"] != "on") {
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: https://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]);
    exit();
}

require_once "lib/base.php";
require_once "lib/ajax_functions.php";

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


$user = sql_safe($_POST['user']);
$response['user'] = $user;
/*
$userQ = "SELECT id FROM user WHERE email='regadmin@bsfs.org';";
$userR = fetch_safe_assoc(dbQuery($userQ));
$userid = $userR['id'];
*/
$userid=2;
$con = get_conf('con');
$conid=$con['id'];

$query = "INSERT INTO transaction (conid, perid, newperid, userid)"
    . " VALUES ($conid, ";
    if(isset($_POST['perid'])) { 
        $query .= "'" . sql_safe($_POST['perid']) . "'";
    } else {
        $query .= "NULL";
    }
    $query .= ", ";
    if(isset($_POST['newperid'])) { 
        $query .= "'" . sql_safe($_POST['newperid']) . "'";
    } else {
        $query .= "NULL";
    }
    $query .= ", $userid)";

$transid = dbInsert($query);
$response['create_query'] = $query;
$response['transid'] = $transid;

$keyQ = "SELECT max(atcon_key) FROM atcon WHERE conid=$conid GROUP BY conid;";
$keyR = fetch_safe_array(dbQuery($keyQ));
$max_Key = $keyR[0]+1;

$atconQ = "INSERT INTO atcon (conid, atcon_key, transid, perid) VALUES "
    . "($conid, $max_Key, $transid, $user);";
$atconId = dbInsert($atconQ);
$atcon = fetch_safe_assoc(dbQuery("SELECT * FROM atcon WHERE id=$atconId;"));

$response['atcon'] = $atcon; 

$query = "SELECT T.id as tID, P.id as perid, T.create_date as tCreate, T.complete_date as tComplete, T.notes as tNotes, P.banned, P.id as ownerId, concat_ws(' ', P.first_name, P.middle_name, P.last_name) as ownerName, P.address as ownerAddr, P.addr_2 as ownerAddr2, concat_ws(' ', P.city, P.state, P.zip) as ownerLocale, P.badge_name as ownerBadge, P.email_addr as ownerEmail,R.id as badgeId, R.price, R.paid, (R.price - R.paid) as cost, M.label, concat_ws('-', M.id, M.memCategory, M.memType, M.memAge) as type, R.locked, R.create_trans " .
  "FROM transaction as T JOIN perinfo as P ON P.id=T.perid LEFT OUTER JOIN reg as R ON R.perid=T.perid AND R.conid=T.conid LEFT OUTER JOIN memList as M ON M.id=R.memId " .
  "WHERE M.memCategory != 'cancel' and T.id=$transid AND T.conid=$conid;";

$transQ = "SELECT T.id as tID, T.create_date as tCreate"
    . ", T.complete_date as tComplete, T.notes as tNotes, P.banned"
    . ", P.id as ownerId, P.address as ownerAddr, P.addr_2 as ownerAddr2"
    . ", concat_ws(' ', P.first_name, P.middle_name, P.last_name, P.suffix) as ownerName"
    . ", concat_ws(' ', P.city, P.state, P.zip) as ownerLocale"
    . ", P.badge_name as ownerBadge, P.email_addr as ownerEmail"
    . ", M.memAge as age, P.id as perid"
    . ", R.id as badgeId, R.price, R.paid, (R.price - R.paid) as cost, M.label"
    . ", concat_ws('-', M.id, M.memCategory, M.memType, M.memAge) as type"
    . ", R.locked, R.create_trans"
  . " FROM transaction as T"
    . " JOIN perinfo as P ON P.id=T.perid"
    . " LEFT OUTER JOIN reg as R on R.perid=P.id AND R.conid=T.conid"
    . " LEFT OUTER JOIN memList as M on M.id=R.memId and M.memCategory != 'cancel'"
  . " WHERE T.id=$transid AND T.conid=$conid;";

$trans = fetch_safe_assoc(dbQuery($transQ));
$response['transQ'] = $transQ;
$response['result'] = $trans;

$badgeQ = "SELECT P.address, P.addr_2,  P.badge_name, P.email_addr, P.phone"
    . ", concat_ws(' ', P.city, P.state, P.zip) as locale"
    . ", concat_ws(' ', P.first_name, P.middle_name, P.last_name, P.suffix) as name"
    . ", concat_ws(' ', NP.first_name, NP.middle_name, NP.last_name, NP.suffix) as newname"
    . ", R.id as badgeId, R.price, R.paid, (R.price - R.paid) as cost, R.locked"
    . ", M.memCategory, M.memType, M.memAge, M.label"
    . ", concat_ws('-', M.id, M.memCategory, M.memType, M.memAge) as type"
  . " FROM transaction as T"
    . " JOIN reg as R ON R.create_trans=T.id"
    . " LEFT OUTER JOIN perinfo as P ON P.id=R.perid AND P.id != T.perid"
    . " LEFT OUTER JOIN newperson as NP ON NP.id=R.newperid AND NP.id != T.newperid"
    . " JOIN memList as M ON M.id=R.memId and M.memCategory != 'cancel'"
  . " WHERE T.id=$transid";

$badgeR = dbQuery($badgeQ);
$badges = array();
while($badge = fetch_safe_assoc($badgeR)) {
    array_push($badges, $badge);
}
$response['badges'] = $badges;

ajaxSuccess($response);
?>
