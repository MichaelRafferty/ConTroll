<?php
global $db_ini;

require_once '../lib/base.php';

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$check_auth = google_init('ajax');
$perm = 'admin';

$response = array('post' => $_POST, 'get' => $_GET, 'perm' => $perm);

if ($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = 'Authentication Failed';
    ajaxSuccess($response);
    exit();
}

$con = get_conf('con');
$conid = $con['id'];

if (!isset($_POST) || !isset($_POST['gettype'])) {
    $response['error'] = 'Missing Information';
    ajaxSuccess($response);
    exit();
}

$gettype = $_POST['gettype'];
$response['gettype'] = $gettype;

// first get all the vendor spaces for this year
$vendorSpacesQ = <<<EOS
SELECT * 
FROM vendorSpaces
WHERE conid = ?;
EOS;

$vendorSpaces = array();
$vendorSpacesR = dbSafeQuery($vendorSpacesQ, 'i', array($conid));
while($space = $vendorSpacesR->fetch_assoc()) {
    array_push($vendorSpaces, $space);
}
$response['vendorSpaces'] = $vendorSpaces;

// now the prices for those spaces
$vendorSpacePricesQ = <<<EOS
SELECT vsp.* 
FROM vendorSpaces vs
JOIN vendorSpacePrices vsp ON (vsp.spaceId = vs.id)
WHERE vs.conid = ?
ORDER BY sortOrder;
EOS;

$vendorSpacePrices = array();
$vendorSpacePricesR = dbSafeQuery($vendorSpacePricesQ, 'i', array($conid));
while($price = $vendorSpacePricesR->fetch_assoc()) {
    array_push($vendorSpacePrices, $price);
}
$response['vendorSpacePrices'] = $vendorSpacePrices;

// mow the memList items for the pulldown of membership types
$memListQ = <<<EOS
SELECT * 
FROM memList
WHERE conid = ?
AND memCategory IN ('artist','dealer','vendor','fan','virtual')
ORDER BY sort_order;
EOS;

$memList = array();
$memListR = dbSafeQuery($memListQ, 'i', array($conid));
while($mem = $memListR->fetch_assoc()) {
    array_push($memList, $mem);
}
$response['memList'] = $memList;

ajaxSuccess($response);
