<?php
require_once "../lib/base.php";
require_once '../lib/getCouponData.php';

$check_auth = google_init("ajax");
$user_email = $check_auth['email'];
$perm = "reg_admin";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);

if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['status'] = 'error';
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

if (!array_key_exists('couponId', $_POST)) {
    $response['status'] = 'error';
    $response['error'] = 'Calling sequence error';
    ajaxSuccess($response);
    exit();
}

$con = get_conf('con');
$conid = $con['id'];

// get the user id for createdby
$usergetQ = <<<EOS
SELECT id
FROM user
WHERE email = ?;
EOS;
$userid = null;
$usergetR = dbSafeQuery($usergetQ, 's', array($user_email));
if ($usergetR !== false) {
    $userL = $usergetR->fetch_assoc();
    if ($userL) {
        $userid = $userL['id'];
    }
}

// check for required fields
$paramarray = array($userid);
$missingfields = '';
$fields = 'code,name,oneUse,couponType,discount';
foreach (explode(',', $fields) as $field) {
    $val = nullPostField($field);
    if ($val == null)
        $missingfields .= "," . $field;
    else
        $paramarray[] = $val;
}

// any of the required fields missing?
if ($missingfields != "") {
    $response['status'] = 'error';
    $response['error'] = 'Missing fields:' . mb_substr($missingfields, 1);
    ajaxSuccess($response);
}

$fields = "createBy," . $fields;
$updstr = implode('= ?,', explode(',', $fields)) . "=?";
$valstr = '?,?,?,?,?,?,?';
$fields = 'conid,' . $fields;
$updtypestr = 'ississ';
$instypestr = 'iississ';
$updparamarray = $paramarray;
array_unshift($paramarray, $conid);
$insparamarray = $paramarray;

// now add the optional fields
$optstrfields = 'startDate,endDate,minTransaction,maxTransaction';
$optintfields = 'memId,minMemberships,maxMemberships,limitMemberships,maxRedemption';
foreach (explode(',', $optstrfields) as $field) {
    $val = nullPostField($field);
    if ($val != null) {
        $fields .= "," . $field;
        $valstr .= ",? ";
        $instypestr .= 's';
        $insparamarray[] = $val;
    }
        // always updatefield
        $updstr .= ', ' . $field . '=?';
        $updtypestr .= 's';
        if ($field == 'startDate' && $val == null)
            $val = '1900-01-01 00:00:00';
        if ($field == 'endDate' && $val == null)
            $val = '2100-12-31 00:00:00';
        $updparamarray[] = $val;
}
foreach (explode(',', $optintfields) as $field) {
    $val = nullPostField($field);
    if ($val != null) {
        $fields .= ',' . $field;
        $valstr .= ',? ';
        $instypestr .= 'i';
        $insparamarray[] = $val;
    }
    // always udpate
    $updstr .= ', ' . $field . '=?';
    $updtypestr .= 'i';
    $updparamarray[] = $val;
}

$addSQL = <<<EOS
INSERT INTO coupon($fields)
VALUES($valstr);
EOS;

$upateSQL = <<<EOS
UPDATE coupon
SET $updstr
WHERE id = ?;
EOS;

$couponId = nullPostField('couponId');
if ($couponId != null) {
    // update
    $updtypestr .= 'i';
    $updparamarray[] = $couponId;
    $numupd = dbSafeCmd($upateSQL, $updtypestr, $updparamarray);
    if ($numupd === false) {
        $response['status'] = 'error';
        $response['error'] = 'Update failed';
        ajaxSuccess($response);
        exit();
    }
    if ($numupd == 0)
        $response['message'] = 'No changes to ' . $couponId;
    else
        $response['message'] = 'Coupon ' . $couponId . ' updated';
} else {
    // insert
    $newid = dbSafeInsert($addSQL, $instypestr, $insparamarray);
    if ($newid === false) {
        $response['status'] = 'error';
        $response['error'] = 'Add failed';
        ajaxSuccess($response);
        exit();
    }
    $response['message'] = 'Coupon ' . $newid . ' added';
}

// reload the new array of coupons
$response = getCouponData($response);

ajaxSuccess($response);

function nullPostField($field) {
    if (array_key_exists($field, $_POST))
        $val = $_POST[$field];
    else
        $val = '';

    if ($val == '')
        $val = null;

    return $val;
}
?>
