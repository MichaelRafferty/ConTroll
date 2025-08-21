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
    $cleanupRegs = $source == 'onlinereg';
    if (array_key_exists('custid', $results)) {
        $custid = $results['custid'];
    } else if (array_key_exists('badges', $results) && is_array($results['badges']) && count($results['badges']) > 0) {
        $badge = $results['badges'][0];
        if (array_key_exists('perid', $badge)) {
            $custid = 'p-' . $badge['perid'];
        } else if (array_key_exists('newperid', $badge)) {
            $custid = 'n-' . $badge['newperid'];
        } else {
            $custid = 'r-' . $results['badges'][0]['badge'];
        }
    } else if (array_key_exists('exhibits', $results) && array_key_exists('vendorId', $results)) {
        $custid = 'e-' . $results['vendorId'];
        $source = $results['exhibits'];
        $cleanUpRegs = true;
    } else {
        $custid = 't-' . $results['transid'];
    }

    $orderLineItems = [];
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
    //  if art work
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

    $discountAmt = 0;

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
            if ($cleanupRegs)
                cleanRegs($results['badges'], $results['transid']);
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
        $orderLineItems[$lineid] = $item;
        $orderValue = $results['total'];
        $itemsBuilt = true;
    }

    // Art Sales
    if ($artSales == 1) {
        $needTaxes = true;
        if (array_key_exists('art', $results) && is_array($results['art']) && count($results['art']) > 0) {
            foreach ($results['art'] as $art) {
                if (!array_key_exists('paid', $art)) {
                    $art['paid'] = 0;
                }
                $artId = $art['id'];
                $artistName = $art['artistName'];
                $artistNumber = $art['exhibitorNumber'];
                $itemKey = $art['item_key'];
                $title = $art['title'];
                $type = $art['type'];
                $priceType = $art['priceType'];
                $quantity = $art['artSalesQuantity'];
                $amount = $art['amount'];

                $item = [
                    'uid' => 'art' . ($lineid + 1),
                    'name' => mb_substr($artistName, 0, 50) . ' / ' . mb_substr($title, 0, 70),
                    'quantity' => $quantity,
                    'note' => $artId . ':' . $artistNumber . ',' . $itemKey . '; ' . $type . ',' . $priceType,
                    'basePriceMoney' => round($amount * 100),
                ];
                if ($taxRate > 0) {
                    // create the Line Item tax record, if there is a tax rate, and the membership is taxable
                    $needTaxes = true;
                    $item['taxable'] = 'Y';
                    $item['taxUid'] = $taxLabel;
                }
                $orderLineItems[$lineid] = $item;
                $orderValue += $art['amount'];
                $lineid++;
            }
        } else {
            ajaxSuccess(array ('status' => 'error', 'data' => 'Error: Art Data not passed, get assistance.'));
            exit();
        }

        $itemsBuilt = true;
    }

    // if not built, it's spaces + memberships
    if (!$itemsBuilt) {
        $couponDiscount = false;
        $managerDiscount = false;
        // create the coupon or discount amount, if it exists
        if (array_key_exists('discount', $results) && $results['discount'] > 0) {
            if (array_key_exists('coupon', $results) && $results['coupon'] != null) {
                $coupon = $results['coupon'];
                $couponName = 'Coupon: ' . $coupon['code'] . ' (' . $coupon['name'] . '), Coupon Discount: ' . $coupon['discount'];
                $couponDiscount = true;
            } else {
                $coupon = null;
                $couponName = 'Discount Applied';
                $managerDiscount = true;
            }

            $item = [
                'uid' => 'discount',
                'name' => mb_substr($couponName, 0, 128),
                'type' => 'FixedAmount',
                'amountMoney' => round($results['discount'] * 100),
            ];
            $discountAmt += $item['amountMoney'];
            $orderDiscounts[] = $item;
        }

        $totalDiscountable = 0;
        if (array_key_exists('badges', $results) && is_array($results['badges']) && count($results['badges']) > 0) {
            $rowno = 0;
            foreach ($results['badges'] as $badge) {
                if (!array_key_exists('paid', $badge)) {
                    $badge['paid'] = 0;
                }
                if (array_key_exists('fullName', $badge))
                    $fullname = $badge['fullName'];
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

                // deal with mixed case usages and perid/newperid
                if (array_key_exists('regid', $badge)) {
                    $regid = $badge['regid'];
                } else if (array_key_exists('regId', $badge)) {
                    $regid = $badge['regId'];
                } else {
                    $regid = 'tbd';
                }

                if (array_key_exists('perid', $badge)) {
                    $perid = $badge['perid'];
                } else if (array_key_exists('newperid', $badge)) {
                    $perid = $badge['newperid'];
                } else {
                    $perid = 'tbd';
                }

                $note = $badge['memId'] . ',' . $id . ',' . $regid . ': memId, p/n id, regid';
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

                $metadata = array ('regid' => $regid, 'perid' => $perid, 'memid' => $badge['memId'], 'rowno' => $rowno);
                $addMbr = str_contains(strtolower($badge['shortname']), 'membership') == false &&
                    ($badge['memType'] == 'full' || $badge['memType'] == 'oneday');
                $itemName =  $badge['fname'] . ': ' . $badge['shortname'] .' ' . ($badge['ageshortname'] != 'All' ? $badge['ageshortname'] : '') .
                    ($addMbr ? ' Mbr ' : ' ') . '/ ' . $fullname;
                $item = [
                    'uid' => 'badge' . ($lineid + 1),
                    'name' => mb_substr($itemName, 0, 128),
                    'quantity' => 1,
                    'note' => $note,
                    'basePriceMoney' => round($amount * 100),
                    'metadata' => $metadata,
                ];
                if ($taxRate > 0 && array_key_exists('taxable', $badge) && $badge['taxable'] == 'Y') {
                    // create the Line Item tax record, if there is a tax rate, and the membership is taxable
                    $needTaxes = true;
                    $item['taxable'] = 'Y';
                    $item['taxUid'] = $taxLabel;
                }

                if (array_key_exists('newplan', $results) && $results['newplan'] == 1) {
                    if ($badge['inPlan'])
                        $item['applied_discounts'][] = 'planDeferment';
                }

                if ($couponDiscount &&
                    (!array_key_exists('status', $badge) || $badge['status'] == 'unpaid' || $badge['status'] == 'plan')) {
                    $cat = $badge['memCategory'];
                    if (in_array($cat, array ('standard', 'supplement', 'upgrade', 'add-on', 'virtual'))) {
                        $item['applied_discounts'][] = array ('uid' => 'couponDiscount', 'applied_amount' => 0);
                        $totalDiscountable += $item['basePriceMoney'];
                    }
                }
                if ($managerDiscount &&
                    (!array_key_exists('status', $badge) || $badge['status'] == 'unpaid' || $badge['status'] == 'plan')) {
                    $item['applied_discounts'][] = array ('uid' => 'managerDiscount', 'applied_amount' => 0);
                    $totalDiscountable += $item['basePriceMoney'];
                }
                $orderLineItems[$lineid] = $item;
                $orderValue += $badge['price'];
                $lineid++;
                $rowno++;
            }

            if (array_key_exists('discount', $results) && $results['discount'] > 0) {
                // apply the coupon discount amounts proportionally, square would do this for us normally
                $totalDiscount = $results['discount'] * 100;
                $discountRemaining = $totalDiscount;
                $lastItemNo = -1;
                $maxAmt = -1;
                for ($itemNo = 0; $itemNo < count($orderLineItems); $itemNo++) {
                    $item = $orderLineItems[$itemNo];
                    if (array_key_exists('applied_discounts', $item)) {
                        for ($discountNo = 0; $discountNo < count($item['applied_discounts']); $discountNo++) {
                            $discount = $item['applied_discounts'][$discountNo];
                            if ($discount['uid'] == 'couponDiscount' || $discount['uid'] == 'managerDiscount') {
                                $thisItemDiscount = round(($item['basePriceMoney'] * $totalDiscount) / $totalDiscountable);
                                if ($thisItemDiscount > $discountRemaining)
                                    $thisItemDiscount = $discountRemaining;
                                $discountRemaining -= $thisItemDiscount;
                                if ($item['basePriceMoney'] > $maxAmt)
                                    $lastItemNo = $itemNo;
                                $orderLineItems[$itemNo]['applied_discounts'][$discountNo]['applied_amount'] = $thisItemDiscount;
                            }
                        }
                    }
                }
                // deal with rounding error by fudging largest item
                if ($discountRemaining > 0 && $lastItemNo >= 0) {
                    $orderLineItems[$itemNo]['applied_discounts'][$discountNo]['applied_amount'] += $discountRemaining;
                }
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
                    ',' . $space['includedMemberships'] . ': id, item, exhId, exhNum, includedMem';
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
                $orderLineItems[$lineid] = $item;
                $orderValue += $space['approved_price'];
                $lineid++;
            }
        }

        if (array_key_exists('mailInFee', $results)) {
            foreach ($results['mailInFee'] as $fee) {
                // because it expects an array, the array of an empty element needs to be skipped
                if ((!array_key_exists('amount', $fee)) || $fee['amount'] <= 0)
                    continue;
                $itemName = 'Mail-in Fee for ' . $fee['name'];
                $itemPrice = $fee['amount'];
                $note = 'Mail-in fee';
                if (array_key_exists('glNum', $fee) && $fee['glNum'] != null && $fee['glNum'] != '')
                    $note .= ", GL: " . $fee['glNum'];
                $item = [
                    'uid' => 'region-' . $fee['name'],
                    'name' => mb_substr($itemName, 0, 128),
                    'quantity' => 1,
                    'note' => mb_substr($note, 0, 128),
                    'basePriceMoney' => round($itemPrice * 100),
                ];
                $order_lineitems[$lineid] = $item;
                $orderValue += $itemPrice;
                $lineid++;
            }
        }

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
        'lineItems' => $orderLineItems,
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
        foreach ($orderLineItems as $item) {
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
    $rtn['items'] = $orderLineItems;
    $rtn['preTaxAmt'] = $orderValue;
    $rtn['discountAmt'] = $discountAmt / 100;
    $rtn['taxAmt'] = $taxAmount / 100;
    $rtn['taxLabel'] = $taxLabel;
    $rtn['totalAmt'] = $orderValue + (($taxAmount - $discountAmt) / 100);
    // load into the main rtn the items pay order needs directly
    $rtn['orderId'] = 'O' . time();
    $rtn['source'] = $source;
    $rtn['customerId'] = $order['customerId'];
    $rtn['locationId'] = $order['locationId'];
    $rtn['referenceId'] = $order['referenceId'];
    if ($artSales != 1)
        $rtn['transid'] = $results['transid'];
    if (array_key_exists('exhibits', $results))
        $rtn['exhibits'] = $results['exhibits'];
    if (array_key_exists('nonce', $results))
        $rtn['exhibits'] = $results['nonce'];

    $_SESSION['ccTestResults'] = $rtn;
    return $rtn;
}

// fetch an order to get its details (stub, bypass and test don't keep orders)
function cc_fetchOrder($source, $orderId, $useLogWrite = false) :  null {
    return null;
}

// stub for cancel order
function cc_cancelOrder($source, $orderId, $useLogWrite = false) : array {
    $rtn['order'] = $orderId;
    $rtn['state'] = 'CANCELED';
    $rtn['version'] = 2;
    return $rtn;
}

// enter a payment against an exist order: build the payment, submit it to square and process the resulting payment
function cc_payOrder($ccParams, $buyer, $useLogWrite = false) {
    $cc = get_conf('cc');
    $reg = get_conf('reg');

    if ((!array_key_exists('demo', $cc)) || $cc['demo'] != 1) { // allow demo override on test for cc
        if ($cc['env'] != 'sandbox' || getConfValue('reg','test') != 1) {
            ajaxSuccess(array ('status' => 'error', 'data' => 'Something thinks this is a real charge method'));
            exit();
        }
    }

    $loginPerid = getSessionVar('user_perid');
    if ($loginPerid == null) {
        $userType = getSessionVar('idType');
        if ($userType == 'p')
            $loginPerid = getSessionVar('id');
    }
    // sanitize the email address to avoid empty and refused
    if ($buyer['email'] == '/r' || $buyer['email'] == null)
        $buyer['email'] = '';
    if ($buyer['phone'] == '/r' || $buyer['phone'] == null)
        $buyer['phone'] = '';

    $source = 'onlinereg';
    if (array_key_exists('source', $ccParams)) {
        $source = $ccParams['source'];
    }
    $cleanupRegs = $source == 'artist' || $source == 'exhibitor' || $source == 'fan' || $source == 'vendor' || $source == 'onlinereg';

    // set category based on if exhibits is a portal type
    if (array_key_exists('exhibits', $ccParams)) {
        if ($ccParams['exhibits'] == 'vendor')
            $category = 'vendor';
        else
            $category = 'artshow';
    } else {
        $category = 'reg';
    }

    if (array_key_exists('nonce', $_POST)) {
        $pNonce = $_POST['nonce'];
        if (is_array($pNonce)) {
            if ($pNonce[0] != '1') {
                if ($cleanupRegs)
                    cleanRegs($ccParams['badges'], $ccParams['transid']);
                ajaxSuccess(array ('status' => 'error', 'data' => 'bad CC number'));
                exit();
            }
        } else {
            if ($pNonce == '')
                $pNonce = 'cc_test';
            else if ($pNonce != '1') {
                if ($cleanupRegs)
                    cleanRegs($ccParams['badges'], $ccParams['transid']);
                ajaxSuccess(array ('status' => 'error', 'data' => 'bad CC number'));
                exit();
            }
        }
    } else {
        $pNonce = 'cc_test';
    }

    $desc = 'cc_test: test reg';
    $paymentType = 'credit';
    $sourceId = $ccParams['nonce'];
    if ($sourceId == 'CASH') {
        $paymentType = 'cash';
    }

    if ($sourceId == 'EXTERNAL') {
        $paymentType = $ccParams['externalType'];
    }

    if (array_key_exists('change', $ccParams)) {
        $change = $ccParams['change'];
    } else {
        $change = 0;
    }
    $txtime = '00-00-00 00:00:00';
    $receipt_url = 'cc_test: No Receipt';
    $auth = 'cctest';
    $status = 'COMPLETED';
    $receipt_number = 'test';
    $last4 = '0000';
    $id='test';
    $total = $ccParams['total'];

    $rtn = array();
    $rtn['txnfields'] = array('transid','type','category','description','source','pretax', 'tax', 'amount',
        'txn_time', 'cc','nonce','cc_txn_id','cc_approval_code','receipt_url','status','receipt_id', 'paymentId', 'cashier');
    $rtn['tnxtypes'] = array('i', 's', 's', 's', 's', 'd', 'd', 'd',
        's', 's', 's', 's', 's', 's', 's', 's', 's', 'i');
    $rtn['tnxdata'] = array($ccParams['transid'],$paymentType,$category,$desc,$source,$ccParams['preTaxAmt'], $ccParams['taxAmt'], $total,
        $txtime,$last4,$ccParams['nonce'],$id,$auth,$receipt_url,$status,$receipt_number, $id, $loginPerid);
    $rtn['results'] = $ccParams;
    $rtn['url'] = 'no test receipt';
    $rtn['rid'] = 'test';
    $rtn['payment'] = null;
    $rtn['paymentType'] = $paymentType;
    $rtn['preTaxAmt'] = $ccParams['preTaxAmt'];
    $rtn['taxAmt'] = $ccParams['taxAmt'];
    $rtn['auth'] = $auth;
    $rtn['paymentId'] = $id;
    $rtn['last4'] = $last4;
    $rtn['txTime'] = $txtime;
    $rtn['status'] = $status;
    $rtn['transId'] = $ccParams['transid'];
    $rtn['category'] = $category;
    $rtn['description'] = $desc;
    $rtn['source'] = $source;
    $rtn['amount'] = $total;
    $rtn['nonce'] = $ccParams['nonce'];
    $rtn['change'] = $change;
    return $rtn;
	}

// fetch an order to get its details
function cc_getPayment($source, $paymentid, $useLogWrite = false) : array {
    $ccTestResults = $_SESSION['ccTestResults'];

    $cc = get_conf('cc');

    $payment = [
        'id' => 'testSystem',
        'created_at' => '2099-01-01 00:00:00',
        'amount_money' => [
            'amount' =>  $_SESSION['term_testAmt'],
            'currency' => 'USD',
            ],
        'source_type' => 'CARD',
        'status' => 'COMPLETED',
        'card_details' => [
            'status' => 'CAPTURED',
            'card' => [
                'card_brand' => 'test',
                'last_4' => '1111',
                'exp_month' => '12',
                'exp_year' => '2099',
                'card_type' => 'TEST',
                'prepaid_type' => 'NOT_PREPAID',
                'bin' => '411111',
                'fingerprint' => 'line nonce',
                ],
            'entry_method' => 'TEST',
            'cvv_status' => 'CVV_ACCCEPTED',
            'avs_status' => 'AVS_ACCEPTED',
            'auth_result_code' => 'test12',
            'statement_description' => 'test statement',
            ],
        'location_id' => $ccTestResults['locationId'],
        'order_id' => $ccTestResults['orderId'],
        'total_money' => [
            'amount' => $ccTestResults['totalAmt'] * 100,
            'currency' => 'USD',
        ],
        'approved_money' => [
            'amount' => $_SESSION['term_testAmt'],
            'currency' => 'USD',
        ],
        'application_details' => [
            'square_product' => 'Controll Test Harness',
            'application_id' => 'cc_test',
        ],
        'receipt_number' => 'test',
        'receipt_url' => 'https://test.test/receipt/test',
    ];

    return $payment;
}
