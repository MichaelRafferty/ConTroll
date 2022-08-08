<?php
require_once "lib/base.php";

$perm="manager";

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

$users = array();
$query = <<<EOS
ELECT perid, concat(P.first_name, ' ', P.last_name) as name, auth
FROM atcon_auth A 
JOIN perinfo P ON (P.id=A.perid)
WHERE conid=?;
EOS;
$userQ = dbSafeQuery($query, 'i', array($conid));
while($user = fetch_safe_assoc($userQ)) {
    if(isset($users[$user['perid']])) {
        $users[$user['perid']][$user['auth']] = 'checked';
    } else {
        $users[$user['perid']] = array(
            'id' => $user['perid'],
            'name' => $user['name']
        );
        $users[$user['perid']][$user['auth']] = 'checked';
    }
}
$response['users'] = $users;

ajaxSuccess($response);
?>
