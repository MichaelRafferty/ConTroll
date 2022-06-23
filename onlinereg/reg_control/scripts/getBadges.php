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
$perm = "reg_admin";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);


if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

$con=get_con();
$conid= $con['id'];

$badgeQ = "SELECT R.create_date, R.change_date, R.price, R.paid"
                . ", R.id as badgeId, P.id as perid, NP.id as np_id"
                . ", concat_ws(' ', P.first_name, P.middle_name, P.last_name, P.suffix) as p_name"
                . ", concat_ws(' ', NP.first_name, NP.middle_name, NP.last_name, NP.suffix) as np_name"
                . ", P.badge_name as p_badge"
                . ", NP.badge_name as np_badge"
                . ", concat_ws('-', M.memCategory, M.memType, M.memAge) as memType"
                . ", M.memCategory as category, M.memType as type"
                . ", M.memAge as age, M.label as label"
            . " FROM reg as R"
            . " JOIN memList as M on M.id=R.memId"
            . " LEFT JOIN perinfo as P ON P.id=R.perid"
            . " LEFT JOIN newperson as NP ON NP.id=R.newperid"
            . " WHERE R.conid=$conid;";

$response['query'] = $badgeQ;

$badges = array();

$badgeA = dbQuery($badgeQ);
while($badge = fetch_safe_assoc($badgeA)) {
    array_push($badges, $badge);
}


$response['badges'] = $badges;

ajaxSuccess($response);
?>
