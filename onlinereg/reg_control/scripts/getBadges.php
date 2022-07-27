<?php
global $db_ini;

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

$badgeQ = <<<EOS
SELECT R.create_date, R.change_date, R.price, R.paid, R.id AS badgeId, P.id AS perid, NP.id AS np_id
    , CONCAT_WS(' ', P.first_name, P.middle_name, P.last_name, P.suffix) AS p_name
    , CONCAT_WS(' ', NP.first_name, NP.middle_name, NP.last_name, NP.suffix) AS np_name
    , P.badge_name AS p_badge, NP.badge_name AS np_badge
    , CONCAT_WS('-', M.memCategory, M.memType, M.memAge) as memTyp
    , M.memCategory AS category, M.memType AS type, M.memAge AS age, A.label AS label
FROM reg R
JOIN memList M ON(M.id=R.memId)
JOIN ageList A ON (M.conid = A.conid AND M.memAge = A.ageType)
LEFT OUTER JOIN perinfo P ON (P.id=R.perid)
LEFT OUTER JOIN newperson NP ON (NP.id=R.newperid)
WHERE R.conid=?;
EOS;

$response['query'] = $badgeQ;

$badges = array();

$badgeA = dbSafeQuery($badgeQ, 'i', array($conid));
while($badge = fetch_safe_assoc($badgeA)) {
    array_push($badges, $badge);
}


$response['badges'] = $badges;

ajaxSuccess($response);
?>
