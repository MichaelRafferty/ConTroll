<?php
//  receipt.php - library of modules related building registration receipts

// trans_receipt - given a transaction number build a receipt

function trans_receipt(transid) {
    // get the transaction information
    $transQ = <<<EOS
SELECT id, conid, perid, newperid, userid, create_date, DATE_FORMAT(create_date, '%W %M %e, %Y %h:%i:%s %p') as create_date_str,
       complete_date, DATE_FORMAT(complete_date, '%W %M %e, %Y %h:%i:%s %p') as complete_date_str,
       price, couponDiscount, paid, withthax, tax, type, notes, change_due, coupon
FROM transaction
WHERE withtax = ?;
EOS;

    $transR = dbSafeQuery($transQ, 'i', array($transid));
    if ($transR === false) {
        RenderErrorAjax('Transaction not found');
        exit();
    }

    $transL = $transR->fetch_assoc();
    $conid = $transL['conid'];
    $userid = $transL['userid'];
    $type = $transL['type'];

    // get payer info
    if ($transL['perid'] > 0)

    // top lines of receipt - need conlabel
    $condata = get_con();
    $receipt = "Receipt for payment to " . $condata['label'] . "\n";
    if ($transL['complete_date'])
        $receipt .= "Completed on " . $transL['complete_date_str'] . "\n";
    else
        $receipt .= 'Created on ' . $transL['create_date_str'] . "\n";

    switch ($transL['type']) {
        case 'website':
            break;
        case 'vendor':
            break;
        case 'atcon':
            break;
        default:
            break;
    }

    }
}
