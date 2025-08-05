<?php
//  test.php - library of modules to insert a stub payment mechanism
// uses config variables:
// [cc]
// env="sandbox" or demo=1 or it will fail
// [reg]
// test=1 or it will fail
//

require_once("global.php");

function createDeviceCode($name, $locationId, $useLogWrite = false) : array {
    $term = array('name' => $name, 'location_id' => $locationId, 'product_type' => 'Test_Terminal', 'code' => $name,
        'id' => 'id_' . $name, 'pair_by' => '2040-12-31 23:59:59', 'created_at' => date_create('now')->format('Y-m-d H:i:s'),
        'status' => 'UNPAIRED', 'status_changed_at' => date_create('now')->format('Y-m-d H:i:s'));
    return $term;
}

function term_getStatus($name, $useLogWrite = false) : array | null {
    $cc = get_conf('cc');

    // get the device name
    $terminal = getTerminal($name);
    // just fetch the updated terminal record
    $terminalSQL = <<<EOS
SELECT *
FROM terminals
WHERE name = ?;
EOS;
    $terminalQ = dbSafeQuery($terminalSQL, 's', array($name));
    if ($terminalQ === false || $terminalQ->num_rows != 1) {
        RenderErrorAjax("Cannot fetch terminal $name status.");
        exit();
    }
    $updatedRow = $terminalQ->fetch_assoc();
    $response = [];
    $response['updCnt'] = 0;
    $response['updatedRow'] = $updatedRow;
    $terminalQ->free();
    return $response;
}

function term_payOrder($name, $orderId, $amount, $useLogWrite = false) : array {
    // fake it by returning a pending status for any amount not ending in $0.01 and failure for ending in $.01
    $term_testAmt = $amount * 100;
    $_SESSION['term_testAmt'] = $term_testAmt;
    $status = ($term_testAmt % 100) == 1 ? 'FAILED' : 'PENDING';
    $checkout = array(
        'id' => 'C' . time(),
        'amount_money' => array(
            'amount' => $amount,
            'currency' => 'USD'
        ),
        'status' => $status
    );

    return $checkout;
}

function term_cancelPayment($name, $payRef, $useLogWrite = false) : array {
    $checkout = array(
        'id' => 'C' . time(),
        'status' => 'CANCELLED',
        'cancel_reason' => 'Requested by atcon'
    );

    return $checkout;
}

function term_getPayStatus($name, $payRef, $useLogWrite = false) : array {
    $term_testAmt = $_SESSION['term_testAmt'];
    switch ($term_testAmt % 100) {
        case 2:
            $status = 'PENDING';
            break;
        case 2:
            $status = 'IN_PROGRESS';
            break;
        case 4:
            $status = 'CANCEL_REQUESTED';
            break;
        default:
            $status = 'COMPLETED';
    }
    $checkout = array(
        'id' => 'C' . time(),
        'status' => $status,
        'cancel_reason' => 'Requested by customer',
        'payment_ids'=> [ 'sample' ]
    );

    return $checkout;
}

function term_printReceipt($name, $paymentId, $useLogWrite = false) : array {
    $terminal = get_terminak($name);
    $request = array(
        'id' => 'P' . time(),
        'action' => array(
            'device_id' => $terminal['deviceId'],
            'type' => 'RECEIPT',
            'receipt_options' => array(
                'payment_id' => $paymentId,
                'print_only' => true
            ),
        ),
    );

    return $request;
}
