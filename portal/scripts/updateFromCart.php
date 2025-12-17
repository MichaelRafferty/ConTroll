<?php
// updateFromCart: Portal: update the reg and transaction records for any cart changes

require_once('../lib/base.php');
require_once('../../lib/log.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$response = array('post' => $_POST, 'get' => $_GET);

$con = get_con();
$conid=$con['id'];
$conf = get_conf('con');
$log = get_conf('log');
$portal_conf = get_conf('portal');

$cc = get_conf('cc');
if (array_key_exists('location_portal', $cc)) {
    $ccLocation = $cc['location_portal'];
} else if (array_key_exists('location', $cc)) {
    $ccLocation = $cc['location'];
} else {
    $ccLocation = 'Unknown';
}

$response['conid'] = $conid;
$response['logmessage'] = '';
$response['message'] = '';

if (!(array_key_exists('person', $_POST) && array_key_exists('cart', $_POST) && array_key_exists('action', $_POST))) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Parameter error - get assistance'));
    exit();
}

if (!(isSessionVar('id') && isSessionVar('idType'))) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Not logged in.'));
    exit();
}

validateLoginId();

// check for being resolved/baned
$resolveUpdates = isResolvedBanned();
$response['resolveUpdates'] = $resolveUpdates;
if ($resolveUpdates != null && array_key_exists('logout', $resolveUpdates) && $resolveUpdates['logout'] == 1) {
    ajaxSuccess($response);
    return;
}

if ($resolveUpdates != null)
    $updateMap = $resolveUpdates['remap'];
else
    $updateMap = [];

$loginId = getSessionVar('id');
$loginType = getSessionVar('idType');
$transId = getSessionVar('transid');
$voidTransId = false; // void the transaction if a free membership was marked paid in this item
// if any changes were made to the transaction (cart add/substract/change, etc.)
// mark this to invalidate the order if it exists
$orderId = null;
$orderDate = null;
$orderIdFetched = false;

$action = $_POST['action'];
logInit($log['reg']);
try {
    $person = json_decode($_POST['person'], true, 512, JSON_THROW_ON_ERROR);
    if ($person == null || (!(array_key_exists('fname', $person) || array_key_exists('first_name', $person) ))) {
        logWrite(array('title'> 'Missing field error trap', 'get' => $_GET, 'post' => $_POST, 'session' => getAllSessionVars()));
        $response['status'] = 'error';
        $response['message'] = 'Error: fname and first_name fields are missing from person, please seek assistance';
        ajaxSuccess($response);
        exit();
    }
}
catch (Exception $e) {
    $msg = 'Caught exception on json_decode: ' . $e->getMessage() . PHP_EOL . 'JSON error: ' . json_last_error_msg() . PHP_EOL;
    $response['status'] = 'error';
    $response['message'] = $msg;
    error_log($msg);
    ajaxSuccess($response);
    exit();
}
try {
    $cart = json_decode($_POST['cart'], true, 512, JSON_THROW_ON_ERROR);
}
catch (Exception $e) {
    $msg = 'Caught exception on json_decode: ' . $e->getMessage() . PHP_EOL . 'JSON error: ' . json_last_error_msg() . PHP_EOL;
    $response['status'] = 'error';
    $response['message'] = $msg;
    error_log($msg);
    ajaxSuccess($response);
    exit();
}

if (array_key_exists('personType', $person)) {
    $personType = $person['personType'];
    $personId = $person['id'];
    if ($personType == 'n' && array_key_exists($personId, $updateMap)) {
        $personType = 'p';
        $personId = $updateMap[$personId];
    }
    $existingPerson = true;
} else {
    $msg = 'Missing field in person data, seek assistance.';
    $response['status'] = 'error';
    $response['message'] = $msg;
    error_log($msg);
    ajaxSuccess($response);
    exit();
}
if ($personId <= 0) {
    $msg = 'Improper person data, seek assistance.';
    $response['status'] = 'error';
    $response['message'] = $msg;
    error_log($msg);
    ajaxSuccess($response);
    exit();
}

// now fetch the order information from the transaction if necessary
if ($transId != null && !$orderIdFetched) {
    // get the current orderId if it exists
    $transOrderQ = <<<EOS
SELECT orderId, orderDate
FROM transaction
WHERE id = ?;
EOS;
    $transOrderR = dbSafeQuery($transOrderQ, 'i', array($transId));
    if ($transOrderR === false || $transOrderR->num_rows != 1) {
        $transId = getNewTransaction($conid, $loginType == 'p' ? $loginId : null, $loginType == 'n' ? $loginId : null);
        $orderIdFetched = true;
    } else {
        $transRow = $transOrderR->fetch_assoc();
        $orderId = $transRow['orderId'];
        $orderDate = $transRow['orderDate'];
        $orderIdFetched = true;
    }
}

$num_del = 0;
$num_ins = 0;
// now for the cart
$updateTransPrice = false;
if (sizeof($cart) > 0) {
    foreach ($cart as $cartRow) {
        if (array_key_exists('toDelete', $cartRow) && $cartRow['toDelete'] == true && $cartRow['status'] == 'unpaid') {
            // first verify it's qualified for deletion
            $cQ = <<<EOS
SELECT id, perid, newperid, status, price, paid, couponDiscount, create_trans
FROM reg
WHERE id = ?
EOS;
            $cR = dbSafeQuery($cQ, 'i', array($cartRow['id']));
            if ($cR === false || $cR->num_rows != 1) {
                $response['message'] .= "<br/>Cannot find membership " . $cartRow['id'] . " to delete, continuing with the remaining transactions.";
                continue;
            }
            $item = $cR->fetch_assoc();
            $cR->free();
            if ($item['perid'] != $personId && $item['newperid'] != $personId) {
                $response['message'] .= '<br/>Membership ' . $cartRow['id'] . ' does not belong to you, continuing with the remaining transactions.';
                continue;
            }
            if ($item['price'] == 0 || ($item['couponDiscount'] + $item['paid']) == $item['price']) {
                $response['message'] .= '<br/>Membership ' . $cartRow['id'] . ' is not eligible for deletion, continuing with the remaining transactions.';
                continue;
            }

            $num_del += dbSafeCmd('DELETE FROM reg WHERE id = ?;', 'i', array($cartRow['id']));
            if ($item['create_trans'] == $transId) {
                $updateTransPrice = true;
            }

            continue;
        }
        if ($cartRow['status'] == 'in-cart') {
            if ($transId == null) {
                $transId = getNewTransaction($conid, $loginType == 'p' ? $loginId : null, $loginType == 'n' ? $loginId : null);
            }
            // insert the new reg record into the cart
            $iQ = <<<EOS
INSERT INTO reg(conid, perid, newperid, create_trans, complete_trans, price, status, create_user, memId)
SELECT ?, IFNULL(p.id, n.perid) AS perid, n.id AS newperid, ?, ?, ?, ?, ?, m.id
FROM memList m
LEFT OUTER JOIN perinfo p ON p.id = ?
LEFT OUTER JOIN newperson n ON n.id = ?
WHERE m.id = ?;
EOS;
            $typeStr = 'iiidsiiii';
            $valArray = array(
                $cartRow['conid'],
                $transId,
                $cartRow['price'] > 0 ? null : $transId,
                $cartRow['price'],
                $cartRow['price'] > 0 ? 'unpaid' : 'paid',
                $loginId,
                $personType == 'p' ? $personId : -1,
                $personType == 'n' ? $personId : -1,
                $cartRow['memId']
            );
            $new_cartid = dbSafeInsert($iQ, $typeStr, $valArray);
            if ($new_cartid === false || $new_cartid < 0) {
                $response['message'] .= "<br/>Error adding membership " . $cartRow['id'] . " continuing with the remaining transactions.";
            } else {
                $num_ins++;
                $updateTransPrice = true;
                if ($cartRow['price'] == 0)
                    $voidTransId = true;
            }
        }
    }
    if ($updateTransPrice) {
        // we changed a reg for this transaction, cancel any pending order and recompute the price portion of the record
        if ($orderId != null) {
            cc_cancelOrder('portal', $orderId, true, $ccLocation);
            $orderId = null;
            $orderDate = null;
        }
        $uQ = <<<EOS
UPDATE transaction t
JOIN (
    SELECT sum(price) AS total
    FROM reg
    WHERE create_trans = ? AND status IN ('unpaid', 'paid', 'plan', 'upgraded')
    ) s
SET price = s.total, orderId = NULL, orderDate = null
WHERE id = ?;
EOS;
        dbSafeCmd($uQ, 'ii', array($transId, $transId));
    }
    $response['logmessage'] .= "$num_del Memberships Deleted, $num_ins Memberships Inserted" . PHP_EOL;
    logWrite(array('con'=>$con['name'], 'trans'=>$transId, 'action' => 'cart updated', 'cart' => $cart, 'updatedBy' => $loginId));
}

if ($voidTransId) {
    // check to see if the price in the transaction = the paid for the transaction
    $cQ = <<<EOS
SELECT price, couponDiscountCart, couponDiscountReg, paid
FROM transaction 
WHERE id = ?;
EOS;
    $cR = dbSafeQuery($cQ, 'i', array($transId));
    if ($cR !== false) {
        if ($cR->num_rows == 1) {
            $cTrans = $cR->fetch_assoc();
            $cR->free();
            if ($cTrans['price'] == $cTrans['paid'] + $cTrans['couponDiscountCart'] + $cTrans['couponDiscountReg']) {
                // ok this transaction is 'complete', mark it so
                $uT = <<<EOS
UPDATE transaction
SET complete_date = NOW()
WHERE id = ?;
EOS;
                $num_upd = dbSafeCmd($uT, 'i', array ($transId));
                if ($num_upd == 1)
                    unsetSessionVar('transId');
            }
        }
    }
}

if ($response['message'] == '') {
    $response['status'] = 'success';
    $response['message'] = 'All information updated successfully';
} else {
    $response['status'] = 'warn';
}

logInit($log['reg']);
logWrite($response);

ajaxSuccess($response);
