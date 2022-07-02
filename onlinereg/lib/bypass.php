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
?>
  
    <form id="payment-form">
        <div class="container-fluid overflow-hidden" id="card-container"></div>
        <button id="card-button" type="button" onclick="makePurchase()">Purchase</button>
    </form>
<?php
};

function cc_charge_purchase($results, $ccauth) {
    $rtn = array();
    $rtn['amount'] = $results['total'];
    $rtn['txnfields'] = array('transid','type','category','description','source','amount',
        'txn_time', 'cc','nonce','cc_txn_id','cc_approval_code','receipt_url','status','receipt_id');
    $rtn['tnxtypes'] = array('i', 's', 's', 's', 's', 'd',
            's', 's', 's', 's', 's', 's', 's', 's');
    $rtn['tnxdata'] = array($results['transid'],'other','reg','bypass','online',$results['total'],
        strtotime("now"),'****','**n**','cctxid','bypass','bypass','ok','000');
    $rtn['url'] = '';
    $rtn['rid'] = '000';
    return $rtn;
};
?>
