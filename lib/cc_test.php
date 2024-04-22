<?php
//  test.php - library of modules to insert a stub payment mechanism
// uses config variables:
// [cc]
// env="sandbox" or it will fail
// [reg]
// test=1 or it will fail
// 
// 

require_once("global.php");

// draw_cc_html - exposed function to draw the credit card HTML window
//      $cc = array of [cc] section of ini file
//      $postal_code = postal code to default for form, optional
//

function draw_cc_html($cc, $postal_code = "--") {
    $html = <<<EOS
<p>This is a test site, it doesn't really take credit cards</p>
Scenario: <select name='ccnum' id="test_ccnum">
	<option value=1>1 - Success</option>
	<option value=2>2 - Failure</option>
</select>
<input type="submit" id="purchase" onclick="makePurchase('test_ccnum', 'purchase')" value="Purchase">
EOS;
    return $html;
};

function cc_charge_purchase($results, $ccauth) {
    $cc = get_conf('cc');
    //$con = get_conf('con');
    $reg = get_conf('reg');
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

    if(!isset($_POST['nonce'])) {
		ajaxSuccess(array('status'=>'error','data'=>'missing CC information'));
		exit();
	}

	if(($cc['env'] != 'sandbox') || $reg['test'] != 1) {
		ajaxSuccess(array('status'=>'error','data'=>'Something thinks this is a real charge method'));
		exit();
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

	switch($_POST['nonce'][0]) {
		case '1': // success
			$rtn['amount'] = $results['total'];
			$rtn['txnfields'] =  array('transid','type','category','description', 'source','amount', 'txn_time', 'nonce','cc_txn_id','cc_approval_code','receipt_id', 'cashier');
			$rtn['tnxtypes'] = array('i', 's', 's', 's', 's', 'd', 's', 's', 's', 's', 's', 'i');
			$rtn['tnxdata'] = array($results['transid'],'credit',$category, 'test registration', 'online', $results['total'], '00-00-00 00:00:00',$_POST['nonce'],'txn id','000000','txn_id', $user_perid);
            $rtn['url'] = 'no test receipt';
			return $rtn;
		default: 
			ajaxSuccess(array('status'=>'error','data'=>'bad CC number'));
			exit();
	}
};
?>
