<?php
global $db_ini;

require_once "../lib/base.php";

$check_auth = google_init("ajax");
$perm = "admin";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);

if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

if ((!array_key_exists('action', $_POST)) && $_POST['action'] != 'getMenu') {
    $response['error'] = 'Parameter Error';
    ajaxSuccess($response);
    exit();
}

$con=get_conf('con');
$conid= $con['id'];
$response['conid'] = $conid;

// all memberships (badges) for this conid
$menuQ = <<<EOS
SELECT id, name, page, display, sortOrder
FROM auth
WHERE page = 'Y'
ORDER BY sortOrder;
EOS;

$response['query'] = $menuQ;
$menuItems = [];
$menuR = dbQuery($menuQ);
if ($menuR === false) {
    $response['error'] = "Error in menu fetch query, see logs.";
    ajaxSuccess($response);
    exit();
}
while($menu = $menuR->fetch_assoc()) {
    array_push($menuItems, $menu);
}
$rows = $menuR->num_rows;
$menuR->free();
$response['menu'] = $menuItems;
$response['success'] = "$rows menu rows selected";
ajaxSuccess($response);
