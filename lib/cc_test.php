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
//      $postal_code = postal code to default for form, optional
//

function draw_cc_html($postal_code = "--", $type='all') : string {
    $html = '';
    if ($type != 'js') {
        $html .= <<<EOS
<p>This is a test site, it doesn't really take credit cards</p>
Scenario: <select name='ccnum' id="test_ccnum">
	<option value=1>1 - Success</option>
	<option value=2>2 - Failure</option>
</select>
<input type="submit" id="card-button" onclick="makePurchase('test_ccnum', 'card-button')" value="Purchase">
EOS;
    }
    $html .= <<<EOS
<script type="text/javascript">
    function startCC() {
        console.log("startCC called from cc_test");
    }
    
    function resetCCPay(div) {
        return;
    }
</script>
EOS;
    return $html;
}


// get the db currency
function cc_getCurrency() : string {
    return strtoupper(getConfValue('con', 'currency', 'USD'));
}

//  from the stripe docs, useful for others, too.
global $unitCurrencies;
$unitCurrencies = array('bif', 'clp', 'djf', 'gnf', 'jpy', 'kmf', 'krw', 'mga', 'pyg', 'rwf', 'ugx', 'vnd', 'vuv', 'xaf', 'xof', 'xpf');

// stripe doesn't multiply all currencies to unit hundreths (2dp currencies), some are zero decimal currencies
function get_currencyMultiplier($currency) {
    global $unitCurrencies;
    if (in_array(strtolower($currency), $unitCurrencies)) {
        return 1.0;
    }
    return 100.0;
}

// build the order structure (fake in this case) to mirror the flow of cc_square
function cc_buildOrder($results, $useLogWrite = false, $locationId = null) : array {
    $con = get_conf('con');
    $id = null;
    $currency = strtoupper(getConfValue('con', 'currency', 'USD'));
    $currencyMultiplier = get_currencyMultiplier($currency);

    $loginPerid = getSessionVar('user_perid');
    $loginNewperid = null;
    if ($loginPerid == null) {
        $userType = getSessionVar('idType');
        if ($userType == 'p')
            $loginPerid = getSessionVar('id');
        else
            $loginNewperid = getSessionVar('id');
    }

    // faking the cc order api steps
    // 1. build order record and price it
    //  a. build order top level container
    //      i. add discounts
    //      ii. add taxes
    //  b. add line items
    //      i. assign which tax line items to ignore
    //      ii. assign which discount line items to ignore
    //      iii. skip over already complete items and not add them to the square order
    //  c. create order
    //  add order id to return items


    $source = 'onlinereg';
    if (array_key_exists('source', $results)) {
        $source = $results['source'];
    }
    $cleanUpRegs = $source == 'onlinereg';
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
    // taxList is an array by tax field id of taxfield, rate and label, it includes the default value from the config file if the db table is empty
    $hasTax = hasTaxRates();
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
            if ($cleanUpRegs)
                cleanRegs($results['badges'], $results['transid']);
            ajaxSuccess(array ('status' => 'error', 'data' => 'Error: Plan payment missing plan information, get assistance.'));
            exit();
        }

        $notesData = cc_planNotes($ep, $results['transid']);
        $item = [
            'uid' => 'planPayment',
            'name' => mb_substr('Plan Payment: ' . $planName, 0, 128),
            'quantity' => 1,
            'note' => $notesData['note'],
            'metadata' => $notesData['metadata'],
            'basePriceMoney' => round($results['total'] * $currencyMultiplier),
        ];
        $orderLineItems[] = $item;
        $orderValue = $results['total'];
        $itemsBuilt = true;
    }

    // Art Sales
    if ($artSales == 1) {
        if (array_key_exists('art', $results) && is_array($results['art']) && count($results['art']) > 0) {
            foreach ($results['art'] as $artItem) {
                if (!array_key_exists('paid', $artItem)) {
                    $artItem['paid'] = 0;
                }
                $artId = $artItem['id'];
                $artistName = $artItem['artistName'];
                $artistNumber = $artItem['exhibitorNumber'];
                $itemKey = $artItem['item_key'];
                $title = $artItem['title'];
                $type = $artItem['type'];
                $priceType = $artItem['priceType'];
                $quantity = $artItem['artSalesQuantity'];
                $amount = $artItem['amount'];
                $notesData = cc_artSalesNotes($artItem, $results['payorId'], $results['transid']);

                $item = [
                    'uid' => 'art' . ($lineid + 1),
                    'name' => mb_substr($artistName, 0, 50) . ' / ' . mb_substr($title, 0, 70),
                    'quantity' => $quantity,
                    'note' => $notesData['note'],
                    'metadata' => $notesData['metadata'],
                    'basePriceMoney' => round($amount * $currencyMultiplier),
                ];
                if ($hasTax) {
                    // create the Line Item tax record, art sales are taxable
                    $taxArray = buildCCAppliedTaxArray('artSales', $lineid);
                    $item['taxes'] = $taxArray;
                    $item['taxable'] = count($taxArray) > 0 ? 'Y' : 'N';
                    if ($needTaxes == false)
                        $needTaxes = count($taxArray) > 0;
                }
                $orderLineItems[] = $item;
                $orderValue += $artItem['amount'];
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
                'amountMoney' => round($results['discount'] * $currencyMultiplier),
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

                $notesData = cc_regNotes($badge, $planName, $results['transid'], $results['custid'], $regid, $rowno);
                if (array_key_exists('balDue', $badge)) {
                    $amount = $badge['balDue'];
                } else {
                    $amount = $badge['price'] - $badge['paid'];
                }

                $addMbr = str_contains(strtolower($badge['shortname']), 'membership') == false &&
                    ($badge['memType'] == 'full' || $badge['memType'] == 'oneday');
                $itemName =  $badge['fname'] . ': ' . $badge['shortname'] .' ' . ($badge['ageshortname'] != 'All' ? $badge['ageshortname'] : '') .
                    ($addMbr ? ' Mbr ' : ' ') . '/ ' . $fullname;
                $item = [
                    'uid' => 'badge' . ($lineid + 1),
                    'name' => mb_substr($itemName, 0, 128),
                    'quantity' => 1,
                    'note' => $notesData['note'],
                    'basePriceMoney' => round($amount * $currencyMultiplier),
                    'metadata' => $notesData['metadata'],
                ];
                if ($hasTax)  {
                    if (array_key_exists('taxable', $badge) && $badge['taxable'] == 'Y') {
                        $badgeTaxable = 'taxableMem';
                    } else {
                        $badgeTaxable = 'nontaxMem';
                    }

                    // create the Line Item tax record, if there is a tax rate, and the membership is taxable
                    $taxArray = buildCCAppliedTaxArray($badgeTaxable, $lineid);
                    if ($needTaxes == false)
                        $needTaxes = count($taxArray) > 0;
                    $item['taxes'] = $taxArray;
                    $item['taxable'] = count($taxArray) > 0 ? 'Y' : 'N';
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
                $orderLineItems[] = $item;
                if (array_key_exists('balDue', $badge)) {
                    $orderValue += $badge['balDue'];
                } else if (array_key_exists('paid', $badge)) {
                    $orderValue += $badge['price'] - $badge['paid'];
                } else {
                    $orderValue += $badge['price'];
                }
                $lineid++;
                $rowno++;
            }

            if (array_key_exists('discount', $results) && $results['discount'] > 0) {
                // apply the coupon discount amounts proportionally, square would do this for us normally
                $totalDiscount = $results['discount'] * $currencyMultiplier;
                if ($totalDiscount > $totalDiscountable) {
                    $totalDiscount /= $currencyMultiplier;
                    $totalDiscountable /= $currencyMultiplier;
                    ajaxSuccess(array('error' =>
                        "The total discount of $totalDiscount cannot exceed the total amount discountable of $totalDiscountable"));
                    exit();
                }
                $discountRemaining = $totalDiscount;
                $orderValue -= $totalDiscount;
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
                                if ($item['basePriceMoney'] > $maxAmt) {
                                    $lastItemNo = $itemNo;
                                    $maxAmt = $item['basePriceMoney'];
                                }
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
                if ($results['exhibits'] == 'artist') {
                    if ($space['artistName'] != '')
                        $itemName .= $space['artistName'];
                    else
                        $itemName .= $space['exhibitorName'];
                    $spaceType = 'artSpace';
                } else {
                    $itemName .= $space['exhibitorName'];
                    $spaceType = 'vendorSpace';
                }
                $incCount = 0;
                $addCount = 0;
                foreach ($results['badges'] as $badge) {
                    if ($badge['memId'] == $space['includedMemId'])
                        $incCount++;
                    if ($badge['memId'] == $space['additionalMemId'])
                        $addCount++;
                }
                $notesData = cc_spaceNotes($space, $results['transid'], $incCount, $addCount);

                $item = [
                    'uid' => 'space-' . $spaceId,
                    'name' => mb_substr($itemName, 0, 128),
                    'quantity' => 1,
                    'note' => $notesData['note'],
                    'metadata' => $notesData['metadata'],
                    'basePriceMoney' => round($space['approved_price'] * $currencyMultiplier),
                ];

                // apply taxes to spaces based on the space taxable flag
                if ($hasTax)  {
                    // create the Line Item tax record, if there is a tax rate, and the membership is taxable
                    // need to determine the type of space
                    $taxArray = buildCCAppliedTaxArray($spaceType, $lineid);
                    if ($needTaxes == false)
                        $needTaxes = count($taxArray) > 0;
                    $item['taxes'] = $taxArray;
                    $item['taxable'] = count($taxArray) > 0 ? 'Y' : 'N';
                }

                $orderLineItems[] = $item;
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
                $notesData = cc_mailFeeNotes($fee, $results['transid']);

                $item = [
                    'uid' => 'region-' . $fee['name'],
                    'name' => mb_substr($itemName, 0, 128),
                    'quantity' => 1,
                    'note' => $notesData['note'],
                    'metadata' => $notesData['metadata'],
                    'basePriceMoney' => round($itemPrice * $currencyMultiplier),
                ];

                // apply taxes to mail in fees based on the artShipping flag
                if ($hasTax)  {
                    // create the Line Item tax record, if there is a tax rate, and the membership is taxable
                    // need to determine the type of space
                    $taxArray = buildCCAppliedTaxArray('artShipping', $lineid);
                    if ($needTaxes == false)
                        $needTaxes = count($taxArray) > 0;
                    $item['taxes'] = $taxArray;
                    $item['taxable'] = count($taxArray) > 0 ? 'Y' : 'N';
                }


                $orderLineItems[] = $item;
                $orderValue += $itemPrice;
                $lineid++;
            }
        }

        // if a plan, set a discount called deferred payment for plan to the amount not in this payment
        if (array_key_exists('newplan', $results) && $results['newplan'] == 1) {
            // deferment is total of the items - total of the payment
            $deferment = $orderValue - $results['total'];
            $notesData = cc_newPlanNotes($planName, 'TBA', $nonPlanAmt, $downPmt, $balanceDue, $loginPerid, $loginNewperid, $results['transid']);
            // this is the down payment on a payment plan
            $item = [
                'uid' => 'planDeferment',
                'name' => mb_substr('Payment Deferral Amount: ' . $notesData['note'], 0, 128),
                'metadata' => $notesData['metadata'],
                'type' => 'FixedAmount',
                'amountMoney' => round($deferment * $currencyMultiplier),
            ];
            $discountAmt += $item['amountMoney'];
            $orderDiscounts[] = $item;
        }
    }

    if ($locationId == null) {
        $locationId = getConfValue('cc', 'location', null);
    }

    $order = [
        'locationId' => $locationId,
        'referenceId' => $con['id'] . '-' . $results['transid'],
        'source' => $con['conname'] . '-' . $source,
        'customerId' => $con['id'] . '-' . $custid,
        'lineItems' => $orderLineItems,
        'discounts' => $orderDiscounts,
    ];

    if ($needTaxes) {
        $order['taxable'] = 'Y';
    }

    // compute the fields the credit card company would compute
    $taxAmount = 0;
    $taxAmounts = [];
    if ($needTaxes) {
        foreach ($orderLineItems as $ord => $item) {
            if (array_key_exists('taxable', $item)) {
                $orderLineItems[$ord]['taxAmounts'] = computeTax($item);
            }
        }
        $taxAmounts = computeTotalTax($orderLineItems);
    }

    $rtn = array ();
    $rtn['results'] = $results;
    // need to pass back order id, total_amount, tax_amount,
    $rtn['order'] = $order;
    $rtn['items'] = $orderLineItems;
    $rtn['preTaxAmt'] = $orderValue;
    $rtn['discountAmt'] = $discountAmt / $currencyMultiplier;

    $rtnTaxes = [];
    foreach ($taxAmounts as $taxField => $tax) {
        $rtnTaxes[$taxField]['tax'] = $tax['tax'] / $currencyMultiplier;
        $rtnTaxes[$taxField]['name'] = $tax['name'];
        $taxAmount += $tax['tax'];
    }
    $rtn['taxes'] = $rtnTaxes;
    $rtn['taxAmt'] = $taxAmount / $currencyMultiplier;
    $rtn['taxAmount'] = $taxAmount / $currencyMultiplier;
    $rtn['totalAmountDue'] = $orderValue + (($taxAmount - $discountAmt) / $currencyMultiplier);
    $rtn['totalAmt'] = $orderValue + (($taxAmount - $discountAmt) / $currencyMultiplier);
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

    unset($_SESSION['ccTestOrder']);
    $_SESSION['ccTestOrder'] = $rtn;
    return $rtn;
}

// fetch an order to get its details (test doesn't keep orders)
function cc_fetchOrder($source, $orderId, $useLogWrite = false) :  array | null {
    return $_SESSION['ccTestOrder'];
}

// stub for cancel order
function cc_cancelOrder($source, $orderId, $useLogWrite = false, $locationId = null) : array {
    $rtn['order'] = $orderId;
    $rtn['state'] = 'CANCELED';
    $rtn['version'] = 2;
    unset($_SESSION['ccTestOrder']);
    return $rtn;
}

// enter a payment against an exist order: build the payment, submit it to square and process the resulting payment
function cc_payOrder($ccParams, $buyer, $useLogWrite = false) {
    if (getConfValue('cc', 'demo', 0) != 1) { // allow demo override on test for cc
        if (getConfValue('cc', 'env', '') != 'sandbox' || getConfValue('reg','test') != 1) {
            ajaxSuccess(array ('status' => 'error', 'data' => 'Something thinks this is a real charge method'));
            exit();
        }
    }

    $loginPerid = getSessionVar('user_perid');
    $loginNewperid = null;
    if ($loginPerid == null) {
        $userType = getSessionVar('idType');
        if ($userType == 'p')
            $loginPerid = getSessionVar('id');
        else
            $loginNewperid = getSessionVar('id');
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
    $cleanUpRegs = $source == 'artist' || $source == 'exhibitor' || $source == 'fan' || $source == 'vendor' || $source == 'onlinereg';

    // set category based on if exhibits is a portal type
    if (array_key_exists('exhibits', $ccParams)) {
        if ($ccParams['exhibits'] == 'vendor')
            $category = 'vendor';
        else
            $category = 'artshow';
    } else if ($ccParams['source'] == 'artsales') {
        $category = 'artsales';
    } else {
        $category = 'reg';
    }

    if (array_key_exists('nonce', $_POST)) {
        $pNonce = $_POST['nonce'];
        if (is_array($pNonce)) {
            if ($pNonce[0] != '1') {
                if ($cleanUpRegs)
                    cleanRegs($ccParams['badges'], $ccParams['transid']);
                ajaxSuccess(array ('status' => 'error', 'data' => 'bad CC number'));
                exit();
            }
        } else {
            if ($pNonce == '')
                $pNonce = 'cc_test';
            else if ($pNonce != '1' && $pNonce != 'admin') {
                if ($cleanUpRegs)
                    cleanRegs($ccParams['badges'], $ccParams['transid']);
                ajaxSuccess(array ('status' => 'error', 'data' => 'bad CC number'));
                exit();
            }
        }
    } else {
        $pNonce = 'cc_test';
    }

    if (array_key_exists('desc', $ccParams))
        $desc = 'cc_test: ' . $ccParams['desc'];
    else
        $desc = 'cc_test';

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
        'txn_time', 'cc','nonce','cc_txn_id','cc_approval_code','receipt_url','status','receipt_id', 'ccPaymentId', 'cashier');
    $rtn['tnxtypes'] = array('i', 's', 's', 's', 's', 'd', 'd', 'd',
        's', 's', 's', 's', 's', 's', 's', 's', 's', 'i');
    $rtn['tnxdata'] = array($ccParams['transid'],$paymentType,$category,$desc,$source,$ccParams['preTaxAmt'], $ccParams['taxAmt'], $total,
        $txtime,$last4,$ccParams['nonce'],$id,$auth,$receipt_url,$status,$receipt_number, $id, $loginPerid);
    $rtn['results'] = $ccParams;
    $rtn['url'] = 'no test receipt';
    $rtn['rid'] = 'test';
    $rtn['locationId'] = $ccParams['locationId'];
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
    $_SESSION['ccTestPayment'] = $rtn;
    return $rtn;
	}

// fetch an order to get its details
function cc_getPayment($source, $paymentid, $useLogWrite = false) : array {
    $currency = strtoupper(getConfValue('con', 'currency', 'USD'));
    $currencyMultiplier = get_currencyMultiplier($currency);
    $ccTestResults = $_SESSION['ccTestPayment'];

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
            'amount' => $ccTestResults['totalAmt'] * $currencyMultiplier,
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
