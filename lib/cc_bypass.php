<?php
//  bypass.php - library of modules to short circuit payment for testing of code
// uses config variables:
// [cc]
// type=bypass - selects that reg iis not to deal with payment



// draw_cc_html - exposed function to draw the credit card HTML window
//      $cc = array of [cc] section of ini file
//      $postal_code = postal code to default for form, optional
//

function draw_cc_html($cc, $postal_code = "--") {
    $html = <<<EOS
    <form id="payment-form">
        <div class="container-fluid overflow-hidden" id="card-container"></div>
        <button id="card-button" type="button" onclick="makePurchase('1', 'card-button')">Purchase</button>
    </form>
EOS;
    return $html;
};

function cc_charge_purchase($results, $ccauth) {
    if (isset($_SESSION)) {
        if (array_key_exists('user_perid', $_SESSION)) {
            $user_perid = $_SESSION['user_perid'];
        } else {
            $user_perid = null;
        }
        if (array_key_exists('user_id', $_SESSION)) {
            $user_id = $_SESSION['user_id'];
        } else {
            $user_id = null;
        }
    } else {
        $user_perid = null;
        $user_id = null;
    }

    $rtn = array();
    $rtn['amount'] = $results['total'];
    $rtn['txnfields'] = array('transid','type','category','description','source','amount',
        'txn_time', 'cc','nonce','cc_txn_id','cc_approval_code','receipt_url','status','receipt_id','cashier','userid');
    $rtn['tnxtypes'] = array('i', 's', 's', 's', 's', 'd',
            's', 's', 's', 's', 's', 's', 's', 's','i','i');
    $rtn['tnxdata'] = array($results['transid'],'other','reg','bypass','online',$results['total'],
        strtotime("now"),'****','**n**','cctxid','bypass','bypass','ok','000',$user_perid, $user_id);
    $rtn['url'] = '';
    $rtn['rid'] = '000';
    return $rtn;
};
?>
