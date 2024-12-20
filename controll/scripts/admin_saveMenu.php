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

if ((!array_key_exists('ajax_request_action', $_POST)) && $_POST['ajax_request_action'] != 'saveMenu') {
    $response['error'] = 'Parameter Error';
    ajaxSuccess($response);
    exit();
}

if ((!array_key_exists('tabledata', $_POST))) {
    $response['error'] = 'Parameter Error';
    ajaxSuccess($response);
    exit();
}

$con=get_conf('con');
$conid= $con['id'];
$response['conid'] = $conid;

try {
    $tabledata = $_POST['tabledata'];
    $menuItems = json_decode($tabledata, true, 512, JSON_THROW_ON_ERROR);
} catch (Exception $e) {
    $msg = 'Caught exception on json_decode: ' . $e->getMessage() . PHP_EOL . 'JSON error: ' . json_last_error_msg() . PHP_EOL;
    $response['error'] = $msg;
    error_log($msg);
    ajaxSuccess($response);
    exit();
}

// loop over the auth table setting new sort order
$upQ = <<<EOS
UPDATE auth
SET sortOrder = ?
WHERE id = ?;
EOS;

$sortOrder = 10;
$rowsUpd = 0;
foreach ($menuItems as $menu) {
    // update the item if the sort order doesn't match
    if ($menu['sortOrder'] != $sortOrder) {
        $rowsUpd += dbSafeCmd($upQ, 'ii', array($sortOrder, $menu['id']));
    }
    $sortOrder += 10;
}

$response['success'] = "$rowsUpd menu rows updated";

// now refetch the menu items
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
    $response['error'] = 'Error in menu fetch query, see logs.';
    ajaxSuccess($response);
    exit();
}
while ($menu = $menuR->fetch_assoc()) {
    array_push($menuItems, $menu);
}
$rows = $menuR->num_rows;
$menuR->free();
$response['menu'] = $menuItems;
ajaxSuccess($response);
?>
