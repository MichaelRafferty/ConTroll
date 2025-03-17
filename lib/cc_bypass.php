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

function cc_charge_purchase($results, $buyer, $useLogWrite=false) {
    $loginPerid = getSessionVar('user_perid');
    if ($loginPerid == null) {
        $userType = getSessionVar('idType');
        if ($userType == 'p')
            $loginPerid = getSessionVar('id');
    }

    // set category based on if exhibits is a portal type
    if (array_key_exists('exhibits', $results)) {
        if ($results['exhibits'] == 'vendor')
            $category = 'vendor';
        else
            $category = 'artshow';
    } else {
        $category = 'reg';
    }

    $rtn = array();
    $rtn['amount'] = $results['total'];
    $rtn['txnfields'] = array('transid','type',$category,'description','source','pretax', 'tax', 'amount',
        'txn_time', 'cc','nonce','cc_txn_id','cc_approval_code','receipt_url','status','receipt_id','cashier');
    $rtn['tnxtypes'] = array('i', 's', 's', 's', 's', 'd', 'd', 'd',
                             's', 's', 's', 's', 's', 's', 's', 's','i');
    $rtn['tnxdata'] = array($results['transid'],'other','reg','bypass','online',$results['pretax'], $results['tax'], $results['total'],
        strtotime("now"),'****','**n**','cctxid','bypass','bypass','ok','000',$loginPerid);
    $rtn['url'] = '';
    $rtn['rid'] = '000';
    return $rtn;
};
?>
