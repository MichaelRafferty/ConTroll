 <?php
// library AJAX Processor: artpos_processPayment.php
// ConTroll Registration System
// Author: Syd Weinstein
// create payment record for art sales and send same to credit card provider

require_once '../lib/base.php';
require_once('../../lib/log.php');
require_once('../../lib/cc__load_methods.php');
require_once('../../lib/term__load_methods.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$con = get_conf('con');
$conid = $con['id'];
$ajax_request_action = '';
if ($_POST && $_POST['ajax_request_action']) {
    $ajax_request_action = $_POST['ajax_request_action'];
}
if ($ajax_request_action != 'processPayment') {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}

if (!check_atcon('artsales', $conid)) {
    $message_error = 'No permission.';
    RenderErrorAjax($message_error);
    exit();
}

$user_id = $_POST['user_id'];
if ($user_id != getSessionVar('user')) {
    ajaxError('Invalid credentials passed');
}

$user_perid = $user_id;
$paymentType = 'credit'; // default type
$source = 'artsales';

$log = get_conf('log');
$con = get_conf('con');
$debug = get_conf('debug');
$ini = get_conf('reg');
$cc = get_conf('cc');
load_cc_procs();
logInit($log['term']);

$conid = $con['id'];
$upd_rows = 0;
$cupd_rows = 0;

// processPayment - Art Sales
//  orderId: pay_currentOrderId,
//  new_payment: prow,
//  change: crow,
//  nonce: nonce,
//  user_id: user_id,
//  car_art: artJSON,
//  payor: currentPerson

$master_tid = $_POST['pay_tid'];
if ($master_tid <= 0) {
    ajaxError('No current transaction in process');
}

if (!array_key_exists('payor', $_POST)) {
    ajaxError('No payor passed');
    exit();
}
$payor = $_POST['payor'];
if (!array_key_exists('id', $payor)) {
    ajaxError('Invalid payor passed');
    exit();
}
if ($payor['id'] <= 0) {
    ajaxError('Invalid payor passed');
    exit();
}
$perid = $payor['id'];

$orderId = $_POST['orderId'];
if ($orderId == null || $orderId == '') {
    ajaxError('No current order in process');
}

$new_payment = $_POST['new_payment'];
if (!array_key_exists('amt', $new_payment) || $new_payment['amt'] <= 0) {
    ajaxError('invalid payment amount passed: payment <= 0');
    return;
}
$amt = (float) $new_payment['amt'];

if (array_key_exists('pretax', $new_payment))
    $preTaxAmt = $new_payment['pretax'];
else
    $preTaxAmt = $amt;

if (array_key_exists('taxAmt', $_POST))
    $taxAmt = $_POST['taxAmt'];
else
    $taxAmt = 0;

if ($amt != $preTaxAmt + $taxAmt) {
    ajaxError('Invalid payment amount passed: preTax + Tax != Amount');
    return;
}

try {
    $cart_art = json_decode($_POST['cart_art'], true, 512, JSON_THROW_ON_ERROR);
}
catch (Exception $e) {
    $msg = 'Caught exception on json_decode: ' . $e->getMessage() . PHP_EOL . 'JSON error: ' . json_last_error_msg() . PHP_EOL;
    $response['error'] = $msg;
    error_log($msg);
    ajaxSuccess($response);
    exit();
}
if (sizeof($cart_art) <= 0) {
    ajaxError('The cart is empty');
    return;
}

$override = $_POST['override'];
if (array_key_exists('poll', $_POST)) {
    $poll = $_POST['poll'];
} else {
    $poll = 0;
}

// we need an available terminal, so get the latest status
if ($new_payment['type'] == 'terminal') {
    load_term_procs();
    $terminal = getSessionVar('terminal');
    $name = $terminal['name'];
    $statusResponse = term_getStatus($name);
    $termStatus = $statusResponse['updatedRow'];

    if ($override == 1) {  // force the operation to continue, try to cancel anything in progress
        resetTerminalStatus($name);
        if ($termStatus['currentPayment'] != null && $termStatus['currentPayment'] != '') {
            term_cancelPayment($name, $termStatus['currentPayment'], true);
        }
        if ($termStatus['currentOrder'] != null && $termStatus['currentOrder'] != '$orderId') {
            cc_cancelOrder('artsales', $orderId, true);
        }
    } else {
        $status = $termStatus['status'];
        $inUseBy = $termStatus['currentOperator'];
        $controllStatus = $termStatus['controllStatus'];
        $currentOrder = $termStatus['currentOrder'];
        $currentPayment = $termStatus['currentPayment'];
        if ($status != 'AVAILABLE' || ($inUseBy > 0 && ($inUseBy != $user_id || $poll == 0))) {
            $msg = "Terminal $name is not available, it's status is $status";
            if ($inUseBy != null && $inUseBy != '') {
                if ($inUseBy != $user_id) {
                    $operatorNameSQL = <<<EOS
SELECT TRIM(REGEXP_REPLACE(CONCAT(IFNULL(first_name, ''),' ', IFNULL(middle_name, ''), ' ', 
    IFNULL(last_name, ''), ' ', IFNULL(suffix, '')), '  *', ' ')) AS fullname
FROM perinfo
WHERE id = ?;
EOS;
                    $inUseName = '';
                    $operatorR = dbSafeQuery($operatorNameSQL, 'i', array ($inUseBy));
                    if ($operatorR !== false) {
                        if ($operatorR->num_rows == 1) {
                            $inUseName = $operatorR->fetch_row()[0];
                        }
                        $operatorR->free();
                    }

                    $msg .= " and is in use by $inUseName ($inUseBy)";
                }
            }
            if ($controllStatus != null && $controllStatus != '') {
                $msg .= "<br/>And the system says it is in use for $controllStatus<br/>For order $currentOrder and payment operation $currentPayment";
            }
            $response['termStatus'] = $termStatus;
            $response['inUseBy'] = $inUseBy;
            $response['status'] = $status;
            $response['warn'] = $msg;
            ajaxSuccess($response);
            exit();
        }
    }
}

$crow = null;
if (array_key_exists('change', $_POST)) {
    $crow = $_POST['change'];
    $response['crow'] = $crow;
}

$payor_email = $payor['email_addr'];
$payor_phone = $payor['phone'];
$payor_perid = $payor['id'];
$payor_country = $payor['country'];

$pay_tid_amt = -1;
if (array_key_exists('pay_tid_amt', $_POST)) {
    $pay_tid_amt = $_POST['pay_tid_amt'];
}

// see if we need to change the master transaction perid, only do this if the amount paid is = 0
if ($pay_tid_amt == 0) {
    // ok, not current payor_perid and no payment yet
    $chgTP = <<<EOS
UPDATE transaction
SET perid = ?
WHERE id = ?;
EOS;
    $chgTPC = dbSafeCmd($chgTP, 'ii', array($payor_perid, $master_tid));
}

$change = 0;
if ($amt > 0) {
    if ($new_payment['type'] != 'terminal') {
        // cash, online credit card (square), cash, external: (offline credit card, check, discount, coupon)
        //      everything now goes to square
        $nonce = 'EXTERNAL';
        $externalType = 'OTHER';
        $desc = '';
        switch ($new_payment['type']) {
            case 'cash':
                $nonce = 'CASH';
                if ($crow)
                    $change = -$crow['amt'];
                break;
            case 'online':
                $nonce = $new_payment['nonce'];
                break;
            case 'discount':
                $desc = 'disc: ';
                break;
            case 'credit':
                $externalType = 'CARD';
                $desc = 'offline cc';
                break;
            case 'check':
                $externalType = 'CHECK';
                $desc = 'Chk No: ' . $new_payment['checkno'];
                break;
        }

        if ($desc == '')
            $desc = $new_payment['desc'];
        else
            $desc = mb_substr($desc . '/' . $new_payment['desc'], 0, 64);

        $ccParam = array (
            'transid' => $master_tid,
            'counts' => 0,
            'price' => null,
            'art' => $cart_art,
            'taxAmt' => $taxAmt,
            'preTaxAmt' => $preTaxAmt,
            'total' => $amt,
            'orderId' => $orderId,
            'nonce' => $nonce,
            'coupon' => null,
            'externalType' => $externalType,
            'desc' => $desc,
            'source' => $source,
            'change' => $change,
            'locationId' => $cc['location'],
        );

        //log requested badges
        logWrite(array ('type' => 'online', 'con' => $con['conname'], 'trans' => $master_tid, 'results' => $ccParam));
        load_cc_procs();
        $rtn = cc_payOrder($ccParam, $payor, true);
        if ($rtn === null) {
            ajaxSuccess(array ('error' => 'Credit card not approved'));
            exit();
        }
    } else {
        // this is a terminal do a terminal pay request
        if ($poll == 1) {
            $checkout = term_getPayStatus($name, $termStatus['currentPayment'], true);
            if ($checkout == null) {
                ajaxSuccess(array ('error' => "Unable to get payment status from terminal $name"));
                exit();
            }
            $status = $checkout['status'];
            switch ($status) {
                case 'CANCELED':
                    resetTerminalStatus($name);
                    ajaxSuccess(array ('error' => "The terminal cancelled the payment due to " . $checkout['cancel_reason'] .
                        '<br/>If the customer still wishes to pay for the transaction, ' .
                        'please click pay again to start a new payment session with the terminal'));
                    exit();

                case 'IN_PROGRESS':
                case 'PENDING':
                    ajaxSuccess(array ('error' => 'The terminal is still busy processing the payment.' .
                        '<br/>Please wait until the customer has finished paying and try again.'));
                    exit();

                case 'CANCEL_REQUESTED':
                    resetTerminalStatus($name);
                    ajaxSuccess(array ('error' => 'The terminal is working on cancelling the payment.' .
                        '<br/>Please wait until the terminal resets to the splash screen and then' .
                        '<br/>If the customer still wishes to pay for the transaction, ' .
                        'please click pay again to start a new payment session with the terminal'));
                    exit();
            }
            if ($status != 'COMPLETED') {
                ajaxSuccess(array ('error' => "Terminal returned  an unknown payment status of '$status', please seek assistance"));
                exit();
            }
            // get the payment id to get the payment details
            $paymentIds = $checkout['payment_ids'];
            if (count($paymentIds) > 1) {
                web_error_log("pos_processPayment: terminal: returned more than one paymentId");
                web_error_log($paymentIds);
            }
            $paymentId = $paymentIds[0];
            $payment = cc_getPayment('artsales', $paymentId, true);

            $approved_amt = $payment['approved_money']['amount'] / 100;
            $category = 'artsales';
            $desc = $payment['application_details']['square_product'];
            $txtime = $payment['created_at'];
            $receiptNumber = $payment['receipt_number'];
            $receiptUrl = $payment['receipt_url'];
            $last4 = $payment['card_details']['card']['last_4'];
            $id = $payment['id'];
            $location_id = $payment['location_id'];
            $auth = $payment['card_details']['auth_result_code'];
            $nonce = $payment['card_details']['card']['fingerprint'];
            $status = $payment['status'];
            switch ($payment['source_type']) {
                case 'CARD':
                    $paymentType = 'credit';
                    break;
                case 'BANK_ACCOUNT':
                    $paymentType = 'check';
                    break;
                case 'CASH':
                    $paymentType = 'cash';
                    break;
                default:
                    $paymentType = 'other';
            }
            if (array_key_exists('preTaxAmt', $_POST))
                $preTaxAmt = $_POST['preTaxAmt'];
            else
                $preTaxAmt = $_POST['totalAmtDue'];

            if (array_key_exists('taxAmt', $_POST))
                $taxAmt = $_POST['taxAmt'];
            else
                $taxAmt = 0;

            if ($desc == '')
                $desc = $new_payment['desc'];
            else
                $desc = mb_substr($desc . '/' . $new_payment['desc'], 0, 64);

            $rtn = array ();
            $rtn['amount'] = $approved_amt;
            $rtn['txnfields'] = array ('transid', 'type', 'category', 'description', 'source', 'pretax', 'tax', 'amount',
                'txn_time', 'cc', 'nonce', 'cc_txn_id', 'cc_approval_code', 'receipt_url', 'status', 'receipt_id', 'cashier');
            $rtn['tnxtypes'] = array ('i', 's', 's', 's', 's', 'd', 'd', 'd',
                's', 's', 's', 's', 's', 's', 's', 's', 'i');
            $rtn['tnxdata'] = array ($master_tid, 'credit', $category, $desc, $source, $preTaxAmt, $taxAmt, $approved_amt,
                $txtime, $last4, $nonce, $id, $auth, $receiptUrl, $status, $receiptNumber, $user_perid);
            $rtn['url'] = $receiptUrl;
            $rtn['rid'] = $receiptNumber;
            $rtn['paymentType'] = $paymentType;
            $rtn['payment'] = $payment;
            $rtn['preTaxAmt'] = $preTaxAmt;
            $rtn['taxAmt'] = $taxAmt;
            $rtn['paymentId'] = $paymentId;
            $rtn['auth'] = $auth;
            $rtn['last4'] = $last4;
            $rtn['txTime'] = $txtime;
            $rtn['status'] = $status;
            $rtn['transId'] = $master_tid;
            $rtn['category'] = $category;
            $rtn['description'] = $desc;
            $rtn['source'] = $source;
            $rtn['amount'] = $approved_amt;
            $rtn['nonce'] = $nonce;

            resetTerminalStatus($name);
        } else {
            // this is the send the request to the terminal, then we need a separate poll section to get it back and continue to record the payment.
            $checkout = term_payOrder($name, $orderId, $amt, true);
            $status = $checkout['status'];
            if ($status == 'PENDING') {
                $updQ = <<<EOS
UPDATE terminals
SET currentOperator = ?, currentOrder = ?, currentPayment = ?, controllStatus = ?, controllStatusChanged = NOW()
WHERE name = ?;
EOS;
                $upd = dbSafeCmd($updQ, 'issss', array ($user_id, $orderId, $checkout['id'], $status, $name));
                if ($upd === false) {
                    ajaxSuccess(array ('error' => "Unable to update terminal ($name) status"));
                    exit();
                }
                $response['status'] = 'success';
                $response['poll'] = 1;
                $response['id'] = $checkout['id'];
                $response['message'] = "Payment request sent to terminal $name,<br/>" .
                    'click "Payment Complete when payment has been made or "Cancel Payment" to cancel the request.';
                ajaxSuccess($response);
                exit();
            }
            ajaxSuccess(array ('error' => "Unable to send payment request to terminal $name"));
            exit();
        }
    }

    $approved_amt = $rtn['amount'];
    $type = $rtn['paymentType'];
    $preTaxAmt = $rtn['preTaxAmt'];
    $taxAmt = $rtn['taxAmt'];
    $paymentId = $rtn['paymentId'];
    $receiptUrl = $rtn['url'];
    $receiptNumber = $rtn['rid'];
    $paymentType = $rtn['paymentType'];
    $auth = $rtn['auth'];
    $payment = $rtn['payment'];
    $last4 = $rtn['last4'];
    $txTime = $rtn['txTime'];
    $status = $rtn['status'];
    $transId = $rtn['transId'];
    $category = $rtn['category'];
    $description = $rtn['description'];
    $source = $rtn['source'];
    $nonce = $rtn['nonce'];
    if ($nonce == 'EXTERNAL')
        $nonceCode = $ccParam['externalType'];
    else
        $nonceCode = $nonce;
    $complete = round($approved_amt,2) == round($amt,2);

    // now add the payment and process to which rows it applies
    $insPmtSQL = <<<EOS
INSERT INTO payments(transid, type,category, description, source, pretax, tax, amount, time, cc_approval_code, cashier, 
    cc, nonce, cc_txn_id, txn_time, receipt_url, receipt_id, userPerid, status, paymentId)
VALUES (?,?,'reg',?,'cashier',?,?,?,now(),?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);
EOS;
    $typestr = 'issdddsissssssiss';
    $paramarray = array ($master_tid, $paymentType, $desc, $preTaxAmt, $taxAmt, $approved_amt, $auth, $user_perid,
        $last4, $nonceCode, $paymentId, $txTime, $receiptUrl, $receiptNumber, $user_perid, $status, $paymentId);
    $new_pid = dbSafeInsert($insPmtSQL, $typestr, $paramarray);

    if ($new_pid === false) {
        ajaxError('Error adding payment to database');
        return;
    }
    $new_payment['id'] = $new_pid;
} else {
    $complete = true;
}

$response['prow'] = $new_payment;
$response['message'] = "1 payment added";

$index = 0;
$updArtSalesSQL = <<<EOS
UPDATE artSales
SET paid = ?, transid = ?, quantity = ?, amount = ?
WHERE id = ?;
EOS;
$atypestr = 'diidi';

$updArtSalesQtySQL = <<<EOS
UPDATE artSales
SET quantity = ?, amount = ?
WHERE id = ?;
EOS;
$aqtypestr = 'idi';

$updQuantitySQL = <<<EOS
UPDATE artItems
SET quantity = CASE WHEN quantity - ? < 0 THEN 0 ELSE quantity - ? END
WHERE id = ?;
EOS;
$uqstr = 'iii';

$updStatusSQL = <<<EOS
UPDATE artItems
SET status = 'Quicksale/Sold', bidder = ?, final_price = ?
WHERE id = ?;
EOS;
$usstr = 'idi';

$updArtSalesStatusSQL = <<<EOS
UPDATE artSales
SET status = ?
WHERE id = ?;
EOS;
$usrstr = 'si';

foreach ($cart_art as $cart_row) {
    if ($cart_row['display_price'] == '')
        $cart_row['display_price'] = 0;
    
    if ($cart_row['amount'] == null || $cart_row['amount'] == '')
        $cart_row['amount'] = $cart_row['display_price'];

    if ($cart_row['paid'] == null ||$cart_row['paid'] == '')
        $cart_row['paid'] = 0;

    $unpaid = $cart_row['amount'] - $cart_row['paid'];
    $quantity = $cart_row['purQuantity'];
    if ($unpaid > 0) {
        $amt_paid = min($art, $unpaid);
        $cart_row['paid'] += $amt_paid;
        $cart_art[$cart_row['index']] = $cart_row;
        $art -= $amt_paid;
        $upd_rows += dbSafeCmd($updArtSalesSQL, $atypestr, array($cart_row['paid'], $master_tid, $quantity, $cart_row['amount'], $cart_row['artSalesId']));

        // change status of items sold by quick sale to quicksale sold, decrease quantity of print items
        if (round($cart_row['amount'],2) == round($cart_row['paid'],2)) {
            $upd_cart += dbSafeCmd($updQuantitySQL, $uqstr, array($quantity, $quantity, $cart_row['id']));

            if ($cart_row['priceType'] == 'Quick Sale') {
                $upd_cart += dbSafeCmd($updStatusSQL, $usstr, array($perid, $cart_row['paid'], $cart_row['id']));
                $upd_rows += dbSafeCmd($updArtSalesStatusSQL, $usrstr, array('Quicksale/Sold', $cart_row['artSalesId']));
            }
            if ($cart_row['type'] == 'print') {
                $upd_rows += dbSafeCmd($updArtSalesStatusSQL, $usrstr, array('Purchased/Released', $cart_row['artSalesId']));
            }
        }
    } else {
        if (array_key_exists('updSales', $cart_row) && $cart_row['updSales'] == true) {
            $upd_rows += dbSafeCmd($updArtSalesQtySQL, $aqtypestr, array($quantity, $cart_row['amount'], $cart_row['artSalesId']));
        }
    }
}

$updCompleteSQL = <<<EOS
UPDATE transaction
SET paid = IFNULL(paid,'0.00') + ?
WHERE id = ?;
EOS;
$completed = dbSafeCmd($updCompleteSQL, 'di', array($approved_amt, $master_tid));

$completed = 0;
if ($complete) {
    // payment is in full, mark transaction complete
    $updCompleteSQL = <<<EOS
UPDATE transaction
SET complete_date = NOW(), change_due = ?, orderId = ?
WHERE id = ?;
EOS;
    $completed = dbSafeCmd($updCompleteSQL, 'ids', array($change, $orderId, $master_tid));
}

$response['pay_amt'] = $new_payment['amt'];
$response['message'] .= ", $upd_rows memberships updated" . $completed == 1 ? ", transaction completed." : ".";
$response['update_art'] = $cart_art;
ajaxSuccess($response);

function resetTerminalStatus($name) {
    $updQ = <<<EOS
UPDATE terminals
SET currentOperator = 0, currentOrder = '', currentPayment = '', controllStatus = '', controllStatusChanged = now()
WHERE name = ?;
EOS;
    dbSafeCmd($updQ, 's', array($name));
}