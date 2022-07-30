<?php
global $db_ini;

require_once "../lib/base.php";
require_once "../lib/ajax_functions.php";

$check_auth = google_init("ajax");
$perm = "atcon";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);

if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

if(!isset($_GET)) {
    $response['error'] = "No Data";
    ajaxSuccess($response);
    exit();
}
$con= get_conf("con");
$conid=$con['id'];

$query = <<<EOS
SELECT P.id, CONCAT_WS(' ', first_name, middle_name, last_name) AS full_name, address, addr_2, CONCAT_WS(' ', city, state, zip) AS locale
    , badge_name, email_addr, phone, active, banned, M.label
FROM perinfo P
LEFT OUTER JOIN reg R ON (R.perid=P.id and R.conid=?)
LEFT JOIN memLabel M ON (M.id=R.memId)
WHERE CONCAT_WS(' ', first_name, middle_name, last_name) LIKE ?
ORDER BY R.id, last_name, first_name;
EOS;

if(isset($_GET['full_name'])) {
    $searchString = $_GET['full_name'];
    $searchString = str_replace(" ", "%", $searchString);
    $searchString = '%' . $searchString . '%';
    }
else {
    $searchString = '%';
}
$response['query'] = $query;


$res = dbSafeQuery($query, 'is', array($conid, $searchString));
if(!$res) {
  ajaxSuccess(array(
    "args"=>$_POST,
    "query"=>$query,
    "error"=>"query failed"));
  exit();
}
$results = array('badges'=>array(), 'active' => array(), 'inactive' => array());
while ($row = fetch_safe_assoc($res)) {
    if($row['label'] != '') {
        array_push($results['badges'], $row);
    }
    else if($row['active'] == 'Y') {
        array_push($results['active'], $row);
    } else {
        array_push($results['inactive'], $row);
    }
}

$response['count'] = $res->num_rows;
$response['results'] = $results;

ajaxSuccess($response);
?>
