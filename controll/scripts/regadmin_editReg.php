<?php
global $db_ini;

require_once "../lib/base.php";
require_once "../../lib/paymentPlans.php";

$check_auth = google_init("ajax");
$perm = "reg_admin";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);

if ($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

if (array_key_exists('user_perid', $_SESSION)) {
    $user_perid = $_SESSION['user_perid'];
} else {
    ajaxError('Invalid credentials passed');
    return;
}

if (!isset($_POST) || !isset($_POST['old']) || !isset($_POST['id'])|| !isset($_POST['new']) || !isset($_POST['action'])
    || $_POST['action'] != 'edit') {
    $response['error'] = "Invalid Parameters";
    ajaxSuccess($response);
    exit();
}

$con = get_conf('con');
$conid = $con['id'];

$old = $_POST['old'];
$new = $_POST['new'];
$badgeId = $_POST['id'];

$changedMemId = $old['memId'] != $new['memId'];
$changedPrice = ((float) $old['price']) != ((float) $new['price']);
$changedPaid = ((float) $old['paid']) != ((float) $new['paid']);
$changedDiscount =  ((float) $old['couponDiscount']) != ((float) $new['couponDiscount']);
$changedCoupon = ((int) $old['coupon']) != ((int) $new['coupon']);
$changedStatus = $old['status'] != $new['status'];

$changes = $changedMemId || $changedPrice || $changedPaid || $changedDiscount || $changedCoupon || $changedStatus;
if (!$changes) {
    $response['message'] = 'Nothing to change';
    ajaxSuccess($response);
    exit();

}

$recast = false;
$upQ = "UPDATE reg SET ";
$typeStr = '';
$valuearr = [];
if ($changedMemId) {
    $upQ .= 'memId = ?, ';
    $typeStr .= 'i';
    $valuearr[] = (int) $new['memId'];
    $recast = true;
}

if ($changedPrice) {
    $upQ .= 'price = ?, ';
    $typeStr .= 'd';
    $valuearr[] = (float) $new['price'];
    $recast = true;
}

if ($changedPaid) {
    $upQ .= 'paid = ?, ';
    $typeStr .= 'd';
    $valuearr[] = (float) $new['paid'];
    $recast = true;
}

if ($changedDiscount) {
    $upQ .= 'couponDiscount = ?, ';
    $typeStr .= 'd';
    $valuearr[] = (float) $new['couponDiscount'];
    $recast = true;
}

if ($changedCoupon) {
    $upQ .= 'coupon = ?, ';
    $typeStr .= 'i';
    $valuearr[] = (int) $new['coupon'];
}

$newSt = $new['status'];
if ($newSt != 'plan' && $new['price'] > ($new['paid'] + $new['couponDiscount'])) {
    $newStatus = 'unpaid';
} else if ($changedStatus) {
    $newStatus = $new['status'];
} else {
    if ($newSt == 'paid' || $newSt == 'plan' || $newSt == 'unpaid') {
        if ($new['price'] == ($new['paid'] + $new['couponDiscount']))
            $newStatus = 'paid';
        else if ($newSt != 'plan')
            $newStatus = 'unpaid';
        else
            $newStatus = 'plan';
    }
}

$upQ .= "status = ?, updatedBy = ? WHERE id = ?;";
$typeStr .= 'sii';
$valuearr[] = $newStatus;
$valuearr[] = $user_perid;
$valuearr[] = $old['badgeId'];

$num_upd = dbSafeCmd($upQ, $typeStr, $valuearr);

if ($recast && $old['status'] == 'plan') {
    $getPlanQ = <<<EOS
SELECT planId
FROM reg
WHERE id = ?;
EOS;
    $getPlanR = dbSafeQuery($getPlanQ, 'i', array($old['badgeId']));
    if ($getPlanR !== false) {
        if ($getPlanR->num_rows == 1) {
            $planId = $getPlanR->fetch_row()[0];
            recomputePaymentPlan($planId);
        }
        $getPlanR->free();
    }
}

if ($num_upd < 0) {
    $response['error'] = 'Error updating reg';
} else {
    $response['message'] = 'Reg successfully updated';
}
ajaxSuccess($response);
?>
