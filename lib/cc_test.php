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

function draw_cc_html($cc, $postal_code = "--") : string {
    $html = <<<EOS
<p>This is a test site, it doesn't really take credit cards</p>
Scenario: <select name='ccnum' id="test_ccnum">
	<option value=1>1 - Success</option>
	<option value=2>2 - Failure</option>
</select>
<input type="submit" id="purchase" onclick="makePurchase('test_ccnum', 'purchase')" value="Purchase">
EOS;
    return $html;
}

// build the order structure (fake in this case) to mirror the flow of cc_square
function cc_buildOrder($results, $useLogWrite = false) : array {
    $cc = get_conf('cc');
    $con = get_conf('con');
    $id = null;

    $loginPerid = getSessionVar('user_perid');
    if ($loginPerid == null) {
        $userType = getSessionVar('idType');
        if ($userType == 'p')
            $loginPerid = getSessionVar('id');
    }

    // faking the cc order api steps
    // 1. build order record and price it
    //  a. build order top level container
    //      i. add discounts
    //      ii. add taxes
    //  b. add line items
    //      i. assign which tax line items to ignore
    //      ii. assign which discount line items to ignore
    //  c. create order
    //  add order id to return items


    $source = 'onlinereg';
    if (array_key_exists('source', $results)) {
        $source = $results['source'];
    }
    if (array_key_exists('custid', $results)) {
        $custid = $results['custid'];
    } else if (array_key_exists('badges', $results) && is_array($results['badges']) && count($results['badges']) > 0) {
        $custid = 'r-' . $results['badges'][0]['badge'];
    } else if (array_key_exists('exhibits', $results) && array_key_exists('vendorId', $results)) {
        $custid = 'e-' . $results['vendorId'];
        $source = $results['exhibits'];
    } else {
        $custid = 't-' . $results['transid'];
    }

    $orderLineitems = [];
    $orderDiscounts = [];
    $lineid = 0;
    $orderValue = 0;
    $planName = '';
    $planId = '';
    $downPmt = '';
    $nonPlanAmt = '';
    $balanceDue = '';
    $itemsBuilt = false;
    $taxRate = 0;
    $taxLabel = 'Unconfigured Sales Tax';
    if (array_key_exists('taxRate', $con)) {
        $taxRate = $con['taxRate'];
    }
    if (array_key_exists('taxLabel', $con)) {
        $taxLabel = $con['taxLabel'];
    }
    $needTaxes = false;

    // item rules:
    //  if a plan payment
    //      just one order item, the plan payment itself
    //  if art work //TODO-add online credit card via terminal to artpos
    //      add each art item in the checkout as a single line item
    //  else (memberships + spaces):
    //      each membership is a line item
    //      each space is a line item
    //      if a new plan is being set up, get the items from the plan that are paid, and those to defer

    if (array_key_exists('planPayment', $results))
        $planPayment = $results['planPayment'];
    else
        $planPayment = 0;

    if (array_key_exists('artSales', $results))
        $artSales = $results['artSales'];
    else
        $artSales = 0;

    // new plan is indicated by 'newplan' == 1 in the passed array
    if (array_key_exists('newplan', $results) && $results['newplan'] == 1) {
        if (array_key_exists('planRec', $results) && array_key_exists('plan', $results['planRec']) &&
            array_key_exists('name', $results['planRec']['plan'])) {
            $planName = $results['planRec']['plan']['name'];
            $planId = 'TBA';
            $downPmt = $results['planRec']['downPayment'];
            $nonPlanAmt = $results['planRec']['nonPlanAmt'];
            $balanceDue = $results['planRec']['balanceDue'];
        }
    }

    // plan payment, build the one order line id
    if ($planPayment == 1) {
        if (array_key_exists('existingPlan', $results) && array_key_exists('name', $results['existingPlan'])) {
            $ep = $results['existingPlan'];
            $planName = $ep['name'];
            $planId = $ep['id'];
            if ($ep['perid']) {
                $id = 'p' . $ep['perid'];
            } else if ($ep['newperid']) {
                $id = 'n' . $ep['newperid'];
            }
        } else {
            ajaxSuccess(array ('status' => 'error', 'data' => 'Error: Plan payment missing plan information, get assistance.'));
            exit();
        }

        $note = "Plan Id: $planId, Name: $planName, Perid: $loginPerid";
        $item = [
            'uid' => 'planPayment',
            'name' => mb_substr('Plan Payment: ' . $note, 0, 128),
            'quantity' => 1,
            'note' => $note,
            'basePriceMoney' => round($results['total'] * 100),
        ];
        $orderLineitems[$lineid] = $item;
        $orderValue = $results['total'];
        $itemsBuilt = true;
    }

    // Art Sales placeholder
    if ($artSales == 1) {
        $needTaxes = true;
        ajaxSuccess(array ('status' => 'error', 'data' => 'Error: Art Sales not implemented yet, get assistance.'));
        exit();
    }

    // if not built, it's spaces + memberships
    if (!$itemsBuilt) {
        if (array_key_exists('badges', $results) && is_array($results['badges']) && count($results['badges']) > 0) {
            foreach ($results['badges'] as $badge) {
                if (array_key_exists('fullname', $badge))
                    $fullname = $badge['fullname'];
                else
                    $fullname = trim(trim($badge['fname'] . ' ' . $badge['mname']) . ' ' . $badge['lname']);
                if (array_key_exists('perid', $badge) && $badge['perid'] != null) {
                    $id = 'p' . $badge['perid'];
                } else {
                    if (array_key_exists('newperid', $badge))
                        $id = 'n' . $badge['newperid'];
                    else
                        $id = 'TBA';
                }

                $note = $badge['memId'] . ',' . $id . ': memId, p/n id';
                if ($planName != '') {
                    $note .= ($badge['inPlan'] ? (', Plan: ' . $planName) : ', NotInPlan');
                }
                if (array_key_exists('glNum', $badge) && $badge['glNum'] != '') {
                    $note .= ', ' . $badge['glNum'];
                }

                if (array_key_exists('balDue', $badge)) {
                    $amount = $badge['balDue'];
                } else {
                    $amount = $badge['price'] - $badge['paid'];
                }

                $item = [
                    'uid' => 'badge' . ($lineid + 1),
                    'name' => $badge['age'] . ' Membership for ' . $fullname,
                    'quantity' => 1,
                    'note' => $note,
                    'basePriceMoney' => round($amount * 100),
                ];
                if ($taxRate > 0 && array_key_exists('taxable', $badge) && $badge['taxable'] == 'Y') {
                    // create the Line Item tax record, if there is a tax rate, and the membership is taxable
                    $needTaxes = true;
                    $item['taxable'] = 'Y';
                    $item['taxUid'] = $taxLabel;
                }
                $orderLineitems[$lineid] = $item;
                $orderValue += $badge['price'];
                $lineid++;
            }
        }
        if (array_key_exists('spaces', $results)) {
            foreach ($results['spaces'] as $spaceId => $space) {
                $itemName = $space['description'] . ' of ' . $space['name'] . ' in ' . $space['regionName'] .
                    ' for ';
                if ($results['exhibits'] == 'artist' && $space['artistName'] != '') {
                    $itemName .= $space['artistName'];
                } else {
                    $itemName .= $space['exhibitorName'];
                }
                $note = $space['id'] . ',' . $space['item_purchased'] . ',' . $space['exhibitorId'] . ',' . $space['exhibitorNumber'] .
                    ': id, item, exhId, exhNum';
                if (array_key_exists('glNum', $space) && $space['glNum'] != '') {
                    $note .= ', ' . $space['glNum'];
                }

                $item = [
                    'uid' => 'space-' . $spaceId,
                    'name' => mb_substr($itemName, 0, 128),
                    'quantity' => 1,
                    'note' => $note,
                    'basePriceMoney' => round($space['approved_price'] * 100),
                ];
                $orderLineitems[$lineid] = $item;
                $orderValue += $space['approved_price'];
                $lineid++;
            }
        }

        $discountAmt = 0;
        // TODO: set the lines the coupon applies to specifically using appliedDiscount and line type for the coupon to split it correctly
        // now apply the coupon
        if (array_key_exists('discount', $results) && $results['discount'] > 0) {
            if (array_key_exists('coupon', $results) && $results['coupon'] != null) {
                $coupon = $results['coupon'];
                $couponName = 'Coupon: ' . $coupon['code'] . ' (' . $coupon['name'] . '), Coupon Discount: ' .
                    $coupon['discount'];
            } else {
                $couponName = 'Coupon Applied';
            }

            $item = [
                'uid' => 'couponDiscount',
                'name' => mb_substr($couponName, 0, 128),
                'type' => 'FixedAmount',
                'amountMoney' => round($results['discount'] * 100),
            ];
            $discountAmt += $item['amountMoney'];
            $orderDiscounts[] = $item;
            //$orderValue -= $results['discount'];
        }

        // TODO: if an item is in plan, set the plan discount to apply only to those line items
        // if a plan, set a discount called deferred payment for plan to the amount not in this payment
        if (array_key_exists('newplan', $results) && $results['newplan'] == 1) {
            // deferment is total of the items - total of the payment
            $deferment = $orderValue - $results['total'];
            $note = "Name: $planName, ID: TBA, Non Plan Amt: $nonPlanAmt, Down Payment: $downPmt, Balance Due: $balanceDue, Perid: $loginPerid";
            // this is the down payment on a payment plan
            $item = [
                'uid' => 'planDeferment',
                'name' => mb_substr('Payment Deferral Amount: ' . $note, 0, 128),
                'type' => 'FixedAmount',
                'amountMoney' => round($deferment * 100),
            ];
            $discountAmt += $item['amountMoney'];
            $orderDiscounts[] = $item;
        }
    }

    if (array_key_exists('location', $cc)) {
        $location = $cc['location'];
    } else {
        $location = 'Unknown';
    }
    $order = [
        'locationId' => $location,
        'referenceId' => $con['id'] . '-' . $results['transid'],
        'source' => $con['conname'] . '-' . $source,
        'customerId' => $con['id'] . '-' . $custid,
        'lineItems' => $orderLineitems,
        'discounts' => $orderDiscounts,
    ];

    if ($needTaxes) {
        $order['taxable'] = 'Y';
        $order['name'] = $taxLabel;
        $order['percentage'] = $taxRate;
    }

    // compute the fields the credit card company would compute
    $taxAmount = 0;
    $taxAbleBase = 0;
    $itemTaxTotal = 0;
    if ($needTaxes) {
        foreach ($orderLineitems as $item) {
            if (array_key_exists('taxable', $item)) {
                $item['taxAmount'] = round($item['basePriceMoney'] * $order['percentage'] / 100);
                $itemTaxTotal += $item['taxAmount'];
                $taxAbleBase += $item['basePriceMoney'];
            }
        }
        $taxAmount = round($taxAbleBase * $order['percentage'] / 100);
        if ($taxAmount != $itemTaxTotal) { // fudge last item in list to make the pennies add up
            $item['taxAmount'] += $taxAmount - $itemTaxTotal;
        }
    }

    $rtn = array ();
    $rtn['results'] = $results;
    // need to pass back order id, total_amount, tax_amount,
    $rtn['order'] = $order;
    $rtn['preTaxAmt'] = $orderValue;
    $rtn['discountAmt'] = $discountAmt / 100;
    $rtn['taxAmt'] = $taxAmount / 100;
    $rtn['totalAmt'] = $orderValue + (($taxAmount - $discountAmt) / 100);
    // load into the main rtn the items pay order needs directly
    $rtn['orderId'] = 'O' . time();
    $rtn['source'] = $source;
    $rtn['customerId'] = $order['customerId'];
    $rtn['locationId'] = $order['locationId'];
    $rtn['referenceId'] = $order['referenceId'];
    $rtn['transid'] = $results['transid'];
    if (array_key_exists('exhibits', $results))
        $rtn['exhibits'] = $results['exhibits'];
    if (array_key_exists('nonce', $results))
        $rtn['exhibits'] = $results['nonce'];

    return $rtn;
}

function cc_charge_purchase($results, $buyer, $useLogWrite=false) {
    $cc = get_conf('cc');
    //$con = get_conf('con');
    $reg = get_conf('reg');
	$loginPerid = getSessionVar('user_perid');
	if ($loginPerid == null) {
		$userType = getSessionVar('idType');
		if ($userType == 'p')
			$loginPerid = getSessionVar('id');
	}

    if(!isset($_POST['nonce'])) {
		ajaxSuccess(array('status'=>'error','data'=>'missing CC information'));
		exit();
	}

    if ((!array_key_exists('demo', $cc)) || $cc['demo'] != 1) { // allow demo override on test for cc
        if (($cc['env'] != 'sandbox') || $reg['test'] != 1) {
            ajaxSuccess(array ('status' => 'error', 'data' => 'Something thinks this is a real charge method'));
            exit();
        }
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
			$rtn['txnfields'] =  array('transid','type','category','description', 'source','pretax', 'tax', 'amount', 'txn_time', 'nonce','cc_txn_id',
			'cc_approval_code','receipt_id', 'cashier');
			$rtn['tnxtypes'] = array('i', 's', 's', 's', 's', 'd', 'd', 'd', 's', 's', 's', 's', 's', 'i');
			$rtn['tnxdata'] = array($results['transid'],'credit',$category, 'test registration', 'online', $results['pretax'], $results['tax'], $results['total'],	'00-00-00 00:00:00',
			$_POST['nonce'],'txn id','000000','txn_id', $loginPerid);
            $rtn['url'] = 'no test receipt';
            $rtn['rid'] = 'test';
			return $rtn;
		default: 
			ajaxSuccess(array('status'=>'error','data'=>'bad CC number'));
			exit();
	}
};
?>