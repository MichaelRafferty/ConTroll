<?php
require_once "lib/base.php";

$response = array("post" => $_POST, "get" => $_GET);

$perm="data_entry";
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

if(!isset($_POST)) {
    $response['error'] = "No Data";
    ajaxSuccess($response);
    exit();
}
$con= get_conf("con");
$conid=$con['id'];

$query = <<<EOS
SELECT P.id, concat_ws(' ', first_name, middle_name, last_name) as full_name, address, addr_2, concat_ws(' ', city, state, zip) as locale, badge_name, 
    email_addr, phone, active, banned, M.label
FROM perinfo P
LEFT OUTER JOIN reg R ON (R.perid=P.id and R.conid=?)
LEFT OUTER JOIN memList M ON (M.id=R.memId and M.memCategory != 'cancel')
WHERE (
EOS;

$datatypes = 'i';
$values = array($conid);

if(isset($_POST['full_name'])) {
    $searchString = $_POST['full_name'];
    $searchString = "'%" . str_replace(" ", "%", $searchString) . "%'";
    $query .= "concat_ws(' ', first_name, middle_name, last_name) LIKE "
        . $searchString . " OR badge_name LIKE "
        . $searchString;
    }

$query .= ") ORDER BY R.id, last_name, first_name;";
$response['query'] = $query;


$res = dbSafeQuery($query, $datatypes, $values);
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
$response['query'] = $query;

ajaxSuccess($response);
?>
