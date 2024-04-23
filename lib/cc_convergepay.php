<?php
//  convergepay.php - library of modules to add the ??? php payment API to onlinereg
// uses config variables:
// [cc]
// type=convergepay - selects that reg is to use ??? for credit cards
// ssl_merchant_id = [Merchant ID]
// ssl_user_id = [USERID]
// ssl_pin = [AUTHTOKEN]
// [cc-conf]
// ssl_transaction_type = "CCSALE"
// ssl_show_form = "false"
// ssl_description = [merchange description]
// [cc-connect]
// host = "api.convergepay.com"
// site = "/VirtualMerchant/process.do"
// test_mode = "false"

// draw_cc_html - exposed function to draw the credit card HTML window
//      $cc = array of [cc] section of ini file
//      $postal_code = postal code to default for form, optional
//

function draw_cc_html($cc, $postal_code = "--") {
    $options = '';
    for ($yr = 0; $yr < 8; $yr++) {
        $options .= "<option value='" . date('y', strtotime(date('Y', time()) . " + $yr year")) . "'>" .
            date('Y', strtotime(date('Y', time()) . " + $yr year")) . "</option>\n";
    }

    $html = <<<EOS
   Exp Date: <select class='ccard' name='expmo' size=1 required='required'>
        <option value='01'>01 January</option>
        <option value='02'>02 February</option>
        <option value='03'>03 March</option>
        <option value='04'>04 April</option>
        <option value='05'>05 May</option>
        <option value='06'>06 June</option>
        <option value='07'>07 July</option>
        <option value='08'>08 August</option>
        <option value='09'>09 September</option>
        <option value='10'>10 October</option>
        <option value='11'>11 November</option>
        <option value='12'>12 December</option>
      </select> /
      <select class='ccard' name='expyr' size=1 required='required'>
        $options
      </select>
      <input type="submit" id="purchase" onclick="makePurchase()" value="Purchase">
      <br/>
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

    $cclink = get_conf('cc-connect');

    $ccsale = array(
        'ssl_merchant_id'=>$ccauth['ssl_merchant_id'],
        'ssl_user_id'=>$ccauth['ssl_user_id'],
        'ssl_pin'=>$ccauth['ssl_pin'],
        'ssl_transaction_type'=>'CCSALE',
        'ssl_test_mode'=>$cclink['test_mode'],
        'ssl_show_form'=>'false',
        'ssl_amount'=>$results['$total'],
        'ssl_description'=>"Balticon Online Registration",
        'ssl_result_format'=>'ASCII',
        'ssl_avs_address'=>$_POST['street'],
        'ssl_city'=>$_POST['city'],
        'ssl_state'=>$_POST['state'],
        'ssl_avs_zip'=>$_POST['zip'],
        'ssl_country'=>$_POST['country'],
        'ssl_first_name'=>$_POST['fname'],
        'ssl_last_name'=>$_POST['lname'],
        'ssl_email'=>$_POST['email'],
        'ssl_cardholder_ip'=>$_SERVER['REMOTE_ADDR']
        );

    $sale_log_keys = array(
        "ssl_amount",
        "ssl_avs_address",
        "ssl_avs_country",
        "ssl_cardholder_ip"
    );

    //log request keys
    $log_resp = array_intersect_key($ccsale, array_flip($sale_log_keys));
    logWrite(array('transid'=>$results['transid'], 'user'=>$_SESSION['user_id'], 'response'=>$log_resp));

    if(!isset($_POST['ccnum']) || !isset($_POST['cvv']) || 
        !isset($_POST['expmo'])|| !isset($_POST['expyr'])) {
        ajaxSuccess(array('status'=>'error','data'=>'missing CC information'));
        exit();
    } else {
        $ccsale['ssl_card_number'] = preg_replace('/\s+/', '', $_POST['ccnum']);
        $ccsale['ssl_cvv2cvc2']=$_POST['cvv'];
        $ccsale['ssl_cvv2cvc2_indicator']=1;
        $ccsale['ssl_exp_date']=$_POST['expmo'].$_POST['expyr'];
    }

    $args = "";
    foreach($ccsale as $key => $value) {
        if($args != "") { $args .= "&"; }
        $args.="$key=$value";
    }

    $url = "https://".$cclink['host'].$cclink['site'];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

    $resp_array = array();
    $response = curl_exec($ch);
    $error_string = curl_error($ch);

    $response_lines = preg_split("/\n/", $response);
    foreach($response_lines as $line) {
        $line_array = preg_split("/=/", $line);
        if($line_array[1]!="") {
            $resp_array[$line_array[0]]=$line_array[1];
        }
    }
    
    $log_keys = array(
        "ssl_result_message",
        "ssl_amount",
        "ssl_txn_id",
        "ssl_txn_time",
        "ssl_approval_code",
        "ssl_status",
        "errorCode",
        "errorName",
        "errorMessage"
        );
    
    $db_keys = array(
        "ssl_result_message",
        "ssl_amount",
        "ssl_txn_id",
        "ssl_txn_time",
        "ssl_approval_code",
        "ssl_status",
        "ssl_card_number",
        "ssl_description"
        );
    $log_resp = array_intersect_key($resp_array, array_flip($log_keys));
    $db_resp = array_intersect_key($resp_array, array_flip($db_keys));

    //log cc processor response
    logWrite(array('transid'=>$results['transid'], 'user'=>$_SESSION['user_id'], 'response'=>$log_resp));
    
    if(isset($resp_array['errorCode'])) {
        ajaxSuccess(array('status'=>'error','data'=>$resp_array['errorMessage']));
        exit();
    }

    $rtn = array();
    $rtn['amount'] = $db_resp['ssl_amount'];

    // set category based on if exhibits is a portal type
    if (array_key_exists('exhibits', $results)) {
        if ($results['exhibits'] == 'vendor')
            $category = 'vendor';
        else
            $category = 'artshow';
    } else {
        $category = 'reg';
    }

    if(isset($resp_array['ssl_result_message']) && // cc approved
        ($resp_array['ssl_result_message']=='APPROVAL' or $resp_array['ssl_result_message']=='APPROVED' )) {

        $rtn['txnfields'] = array('transid','type',$category,'description','source','amount',
            'txn_time', 'cc','cc_txn_id','cc_approval_code','receipt_id','cashier');
        $rtn['tnxtypes'] = array('i', 's', 's', 's', 's', 'd',
            's', 's', 's', 's', 's','i');
        $rtn['tnxdata'] = array($results['transid'],'credit','reg',$db_resp['ssl_description'],'online',$db_resp['ssl_amount'],
            $db_resp['ssl_txn_time'],$db_resp['ssl_card_number'],$db_resp['ssl_txn_id'],$db_resp['ssl_approval_code'],$db_resp['ssl_txn_id'],$user_perid);
        $rtn['url'] = null;
        $rtn['rid'] = $db_resp['ssl_txn_id'];
        return $rtn;
    }

    return null;
};

?>
