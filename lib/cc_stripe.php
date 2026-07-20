<?php
//  cc_stripe.php - library of modules to add the square php payment API to onlinereg
// uses config variables:
// [cc]
// type=stripe - selects that reg is to use square for credit cards
// remainder: TBD
// does not currently use any other config sections for credit card other than [cc]

require_once("global.php");

// draw_cc_html - exposed function to draw the credit card HTML window
//      $postal_code = postal code to default for form, optional
//

function draw_cc_html($postal_code = "--", $type='all') : string {
    global $libJSversion;

    if ($type == 'js') {
        show_message("JS type integrtations of draw_cc_html are not yet supported", "error");
        return '';
    }

    $sdk = getConfValue('cc', 'websdk', 'https://js.stripe.com/dahlia/stripe.js');
    //$location = getConfValue('cc', 'location', null);
    $pkkey = getConfValue('cc', 'pkkey', null);
    $postalCode = '';
    if ($postal_code != '--') {
        $postalCode = "'postalCode': '$postal_code',\n";
    }

    $html = '';
//    if ($type != 'body') {
//        $html .= <<<EOS
//<script src="$sdk"></script>
//
//<form id = "payment-form">
//    <div class="container-fluid overflow-hidden" id="payment-element"></div>
//    <button id = "submit">Purchase</button>
//    <div class="mt-1 p-1" id="stripe-message"></div>
//</form>
//EOS;
        $html .= <<<EOS
<script src="$sdk"></script>
<script src="jslib/cc_stripe_html.js?v=$libJSversion"></script>
<form id = "payment-form">
    <div class="container-fluid overflow-hidden" id="payment-element"></div>
    <button id="purchase">Purchase</button>
    <div class="mt-1 p-1" id="stripe-message"></div>
</form>
<script type='text/javascript'>
const pkkey="$pkkey";
</script>
EOS;
    return $html;
};

//  from the stripe docs, useful for others, too.
global $unitCurrencies;
$unitCurrencies = array('bif', 'clp', 'djf', 'gnf', 'jpy', 'kmf', 'krw', 'mga', 'pyg', 'rwf', 'ugx', 'vnd', 'vuv', 'xaf', 'xof', 'xpf');

// stripe doesn't multiply all currencies to unit hundreths (2dp currencies), some are zero decimal currencies
function get_currencyMultiplier($currency) {
    global $unitCurrencies;
    if (in_array(strtolower($currency), $unitCurrencies)) {
        return 1;
    }
    return 100;
}

function cc_getCurrency() : string {
    // need to rewrite for stripe, return always correct for now
    $cur = strtolower(getConfValue('con', 'currency', 'USD'));
    // use the country
    $country = strtoupper(getConfValue('cc', 'country', 'US'));
    $stripeDebug = getConfValue('debug', 'stripe', 0);
    $useLogWrite = $stripeDebug > 0;
    $countrySpec = null;

    try {
        if ($stripeDebug & 32) stcc_logObject('cc_stripe/get_currency-countrySpecs->retrieve', $country, $useLogWrite);
        // get a client reference
        $client = new \Stripe\StripeClient(getConfValue('cc', 'key'));
        $countrySpec = $client->countrySpecs->retrieve($country, []);
        if ($stripeDebug & 32) stcc_logObject('cc_stripe/countrySpecs->retrieve response', $countrySpec, $useLogWrite);
    }
    catch (\Stripe\Exception\InvalidRequestException $e) {
        stcc_logException('cc_getCurrency', $e, 'Invalid Request Exception', 'Invalid Country in system configuration, seek assistance.', $useLogWrite);;
    }
    catch (\Stripe\Exception\ApiErrorException $e) {
        stcc_logException('cc_getCurrency', $e, 'other api error', 'Unable to validate currency in system configuration, seek assistance.', $useLogWrite);
    }
    catch (Exception $e) {
        error_log('Another problem occurred, maybe unrelated to Stripe, seek assistance.');
    }

    if ($countrySpec == null) {
        ajaxSuccess(array ('status' => 'error', 'data' => "Error: Country $country not found in Stripe."));
        exit();
    }
    $currencies = $countrySpec->supported_payment_currencies;
    if (in_array($cur, $currencies))
        return $cur;

    ajaxSuccess(array ('status' => 'error', 'data' => "Error: Currency $cur not yet supported in Stripe, seek assistance."));
    exit();
}

// build the order, pass it to stripe and get the payment intent id (order id)
function cc_buildOrder($results, $useLogWrite = false, $locationId = null) : array {
    $con = get_conf('con');
    $currency = cc_getCurrency();
    $currencyMultiplier = get_currencyMultiplier($currency);
    //web_error_log("currenty = $currency, currencyMultiplier = $currencyMultiplier");
    $stripeDebug = getConfValue('debug', 'stripe', 0);
    $id = null;

    $client = new \Stripe\StripeClient(getConfValue('cc', 'key'));

    $loginPerid = getSessionVar('user_perid');
    $loginNewPerid = null;
    if ($loginPerid == null) {
        $userType = getSessionVar('idType');
        if ($userType == 'p')
            $loginPerid = getSessionVar('id');
        else
            $loginNewperid = getSessionVar('id');
    }

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
        // failures in the exhibitor payments need to delete the regs they were going to product
        $cleanUpRegs = true;
    } else {
        $custid = 't-' . $results['transid'];
    }

    $customerID = null;
    /*
    // look up if customer exists
    $customerLookup = [
        'query' => "customer_account:'$custid'",  // it says we cannot search by customer_account, and I see no way to assign an account to a customer
        'limit' => 10,
    ];

    // query the customer id
    try {
        if ($stripeDebug & 14) stcc_logObject('cc_stripe/customer query', $customerLookup, $useLogWrite);
        $customers = $client->customers->search($customerLookup);
        if ($stripeDebug & 14) stcc_logObject('cc_stripe/customer query response',$customers, $useLogWrite);
    }
    catch (\Stripe\Exception\InvalidRequestException $e) {
        if ($cleanUpRegs)
            cleanRegs($results['badges'], $results['transid']);
        stcc_logException('cc_getCurrency', $e, 'Invalid Request Exception', 'Unable to create the order, seek assistance.', $useLogWrite);;
    }
    catch (\Stripe\Exception\ApiErrorException $e) {
        if ($cleanUpRegs)
            cleanRegs($results['badges'], $results['transid']);
        stcc_logException('cc_getCurrency', $e, 'other api error', 'Unable to create the order, seek assistance.', $useLogWrite);
    }
    catch (Exception $e) {
        if ($cleanUpRegs)
            cleanRegs($results['badges'], $results['transid']);
        error_log('Another problem occurred, maybe unrelated to Stripe, seek assistance.');
    }

    if (array_key_exists('data', $customers))
        $custData = $customers['data'];
    else
        $custData = [];
*/



    // stripe locations appear to apply to devices, not api online???
    /*
    if ($locationId == null) {
        $locationId = getConfValue('cc', 'location', null);
    }
    */

    // stripe customer/invoice/paymentintent api steps

    // 1. build the payment intent and add line items to it
    //  a. build line item associative array
    //  b.  add line items
    //      i. skip over already completed items and not add them to the order
    //      ii. build the discount elements for each line computing each discount amount
    //      iii. build a meta tax structure for each line computing each tax value
    //      iv. deal with rounding for the taxes
    //      v. compute all totals and set all global values for the payment intent
    // 2.  create/get customer
    //  a. retrieve the customer by id
    //  b. if not found, create the customer
    //  c. update their info to make it current
    //  d. add the customer to the payment intent structure
    // 3. call the api using all of the items of the payment intent structure.
    // 4. set up all rtn variables, and return the payment intent id for future use

    $orderLineitems = []; // array (non associative) where each item is a line item of the order in a very basic form
    $itemNumber = 0;
    $orderMetadata = [];  // associative array of items we can't store in the line item structure, as well as overall order metadata
    $orderTax = 0;
    $orderDiscount = 0;
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
        $itemNumber++;
        $orderLineitems[] = [
            'product_code' => 'planPayment',
            'product_name' => mb_substr('Plan Payment: ' .  $planName, 0, 512),
            'quantity' => 1,
            'unit_cost' => round($results['total'] * $currencyMultiplier),
            'tax' => ['total_tax_amount' => 0 ],
            ];
        $orderValue = $results['total'];
        $orderMetadata['in' . $itemNumber] = $notesData['note'];
        $orderMetadata['im' . $itemNumber] = json_encode($notesData['metadata']);
        $itemsBuilt = true;
    }

    // Art Sales
    if ($artSales == 1) {
        $needTaxes = $hasTax;
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
                $quantity = $art['purQuantity'];
                $amount = $art['amount'];
                $notesData = cc_artSalesNotes($art, $results['payorId'], $results['transid']);

                // compute the art line item tax
                if ($hasTax)
                    $tax = cc_computeTax('artSales', $amount);
                else
                    $tax = 0;

                $itemNumber++;
                $orderLineitems[] = [
                    'product_code' => 'art-' . $artId,
                    'product_name' => mb_substr($artistName . ' / ' . $title, 0, 1024),
                    'quantity' => $quantity,
                    'unit_cost' => round($amount * $currencyMultiplier / $quantity),
                    'tax' => ['total_tax_amount' => $tax * $currencyMultiplier ],
                ];
                $orderMetadata['in' . $itemNumber] = $notesData['note'];
                $orderMetadata['im' . $itemNumber] = $notesData['metadata'];

                $orderValue += $art['amount'];
                $orderTax += $tax;
                $lineid++;
            }
        } else {
            if ($cleanUpRegs)
                cleanRegs($results['badges'], $results['transid']);
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

            $orderDiscount += round($results['discount'] * $currencyMultiplier);
            $orderMetadata['orderCoupon'] = $couponName;

            //$orderValue -= $results['discount'];
        }

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

                if (array_key_exists('perid', $badge) && $badge['perid'] != null) {
                    $perid = $badge['perid'];
                } else if (array_key_exists('newperid', $badge) && $badge['newperid'] != null) {
                    $perid = $badge['newperid'];
                } else {
                    $perid = 'tbd';
                }

                $notesData = cc_regNotes($badge, $planName, $results['transid'], $results['custid'], $regid, $rowno);
                if (array_key_exists('balDue', $badge)) {
                    $amount = round($badge['balDue'] * $currencyMultiplier);
                } else if (array_key_exists('paid', $badge)) {
                    $amount = round(($badge['price']-$badge['paid']) * $currencyMultiplier);
                } else {
                    $amount = round($badge['price'] * $currencyMultiplier);
                }

                if (array_key_exists('complete_trans', $badge) && $badge['complete_trans'] > 0 && $amount == 0)
                    continue; // skip paid complete items in order for sending to square

                $addMbr = str_contains(strtolower($badge['shortname']), 'membership') == false &&
                    ($badge['memType'] == 'full' || $badge['memType'] == 'oneday');
                $itemName =  $badge['fname'] . ': ' . $badge['shortname'] .' ' . ($badge['ageshortname'] != 'All' ? $badge['ageshortname'] : '') .
                    ($addMbr ? ' Mbr ' : ' ') . 'for ' . $fullname;

                $itemNumber++;
                $tax = cc_computeTax('membership', $badge['taxable'], $amount);
                $orderLineitems[] = [
                    'product_code' => 'badge-' . $badge['memId'],
                    'product_name' => mb_substr($itemName, 0, 1024),
                    'quantity' => 1,
                    'unit_cost' => $amount,
                    'tax' => ['total_tax_amount' => $tax * $currencyMultiplier ],
                ];
                $orderMetadata['in' . $itemNumber] = $notesData['note'];
                $orderMetadata['im' . $itemNumber] = json_encode($notesData['metadata']);

                //TODO: new plan needs to be worked into system
                /*
                if (array_key_exists('newplan', $results) && $results['newplan'] == 1) {
                    if ($badge['inPlan'])
                        $item->setAppliedDiscounts(array(new Square\Types\OrderLineItemAppliedDiscount([
                            'uid' => 'planDeferment-' . $lineid,
                            'discountUid' => 'planDeferment',
                        ])));
                }
                * end new plan */

                //TODO: line item coupon needs to be worked into system
                /*
                if ($couponDiscount &&
                    (!array_key_exists('status', $badge) || $badge['status'] == 'unpaid' || $badge['status'] == 'plan')) {
                    $cat = $badge['memCategory'];
                    if (in_array($cat, array('standard','supplement','upgrade','add-on', 'virtual'))) {
                        $item->setAppliedDiscounts(array(new Square\Types\OrderLineItemAppliedDiscount([
                            'uid' => 'couponDiscount-' . $lineid,
                            'discountUid' => 'discount' ,
                        ])));
                    }
                }
                * end line item coupon
                */
                //TODO: manager discount needs to be worked into system
                /*
                if ($managerDiscount &&
                    (!array_key_exists('status', $badge) || $badge['status'] == 'unpaid' || $badge['status'] == 'plan')) {
                    $item->setAppliedDiscounts(array(new Square\Types\OrderLineItemAppliedDiscount([
                        'uid' => 'managerDiscount-' . $lineid,
                        'discountUid' => 'discount' ,
                    ])));
                }
                * end Manager Discount
                */
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
        }

        // exhibotor spaces
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
                    $spaceType = 'exhibitSpace';
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

                $itemNumber++;
                $tax = cc_computeTax('space', $amount);
                $orderLineitems[] = [
                    'product_code' => 'space-' . $spaceId,
                    'product_name' => mb_substr($itemName, 0, 1024),
                    'quantity' => 1,
                    'unit_cost' => round($space['approved_price'] * $currencyMultiplier),
                    'tax' => ['total_tax_amount' => $tax * $currencyMultiplier ],
                ];
                $orderMetadata['sn' . $itemNumber] = $notesData['note'];
                $orderMetadata['sm' . $itemNumber] = json_encode($notesData['metadata']);
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

                $itemNumber++;
                $tax = cc_computeTax('shipping', $amount);
                $orderLineitems[] = [
                    'product_code' => 'mailinFee',
                    'product_name' => mb_substr($itemName, 0, 1024),
                    'quantity' => 1,
                    'unit_cost' => round($itemPrice * $currencyMultiplier),
                    'tax' => ['total_tax_amount' => $tax * $currencyMultiplier ],
                ];
                $orderMetadata['min' . $itemNumber] = $notesData['note'];
                $orderMetadata['mim' . $itemNumber] = json_encode($notesData['metadata']);
                $orderValue += $itemPrice;

                // apply taxes to mail in fees based on the artShipping flag
                if ($hasTax)  {
                    // create the Line Item tax record, if there is a tax rate, and the membership is taxable
                    // need to determine the type of space
                    $taxArray = buildCCAppliedTaxArray('artShipping', $lineid);
                    if ($needTaxes == false)
                        $needTaxes = count($taxArray) > 0;
                    $orderMetadata['mit'] = json_encode($taxArray);
                    //TODO apply tax to order tax value
                }
                $lineid++;
            }
        }

        // TODO: if an item is in plan, set the plan discount to apply only to those line items
        /*
        // if a plan, set a discount called deferred payment for plan to the amount not in this payment
        if (array_key_exists('newplan', $results) && $results['newplan'] == 1) {
            // deferment is total of the items - total of the payment
            $deferment = $orderValue - $results['total'];
            $notesData = cc_newPlanNotes($planName, 'TBA', $nonPlanAmt, $downPmt, $balanceDue, $loginPerid, $loginNewperid, $results['transid']);
            // this is the down payment on a payment plan
            $item = new OrderLineItemDiscount ([
                'uid' => 'planDeferment',
                'name' => mb_substr("Payment Deferral Amount: " . $notesData['note'], 0, 128),
                'metadata' => $notesData['metadata'],
                'type' => OrderLineItemDiscountType::FixedAmount->value,
                'amountMoney' => new Money([
                    'amount' => round($deferment * 100),
                    'currency' => $currency,
                ]),
                'scope' => OrderLineItemDiscountScope::LineItem->value,
            ]);
            $orderDiscounts[] = $item;
        }
        */
    }

    $amountDetails = [];
    if ($orderDiscount > 0) {
        $amount_details['discount_amount'] = $orderDiscount;
    }
    $amountDetails['line_items'] = $orderLineitems;

    // get the stripe customer if it exists, if not create it.

    $orderFields = [
        'amount' => round($orderValue * $currencyMultiplier),
        'currency' => $currency,
        'automatic_payment_methods' => [
            'enabled' => true,
        ],
        'confirm' => false,
        'amount_details' => $amountDetails,
        //TODO get/creater customer id 'customer' => $con['id'] . '-' . $custid,
        'description' => $con['conname'] . '-' . $source,
        'metadata' => $orderMetadata,
        //'setup_future_usage' => 'off_session',
    ];


    // TODO taxes
    /*
    if ($needTaxes) {
        $order->setTaxes(buildSquareOrderTaxArray());
    }
    */


    // pass order to stripe and get payment intent id
    try {
        if ($stripeDebug & 14) stcc_logObject('cc_stripe/payment Intent Create-orderFields', $orderFields, $useLogWrite);
        $paymentIntent = $client->paymentIntents->create($orderFields);
        if ($stripeDebug & 14) stcc_logObject('cc_stripe/payment Intent response', $paymentIntent, $useLogWrite);
    }
    catch (\Stripe\Exception\InvalidRequestException $e) {
            if ($cleanUpRegs)
                cleanRegs($results['badges'], $results['transid']);
            stcc_logException('cc_getCurrency', $e, 'Invalid Request Exception', 'Unable to create the order, seek assistance.', $useLogWrite);;
    }
    catch (\Stripe\Exception\ApiErrorException $e) {
        if ($cleanUpRegs)
            cleanRegs($results['badges'], $results['transid']);
            stcc_logException('cc_getCurrency', $e, 'other api error', 'Unable to create the order, seek assistance.', $useLogWrite);
    }
    catch (Exception $e) {
        if ($cleanUpRegs)
            cleanRegs($results['badges'], $results['transid']);
            error_log('Another problem occurred, maybe unrelated to Stripe, seek assistance.');
    }

    $rtn = array();
    $rtn['results'] = $results;
     // need to pass back order id, total_amount, tax_amount,
    $rtn['order'] = $paymentIntent;
    $phpOrder = json_decode(json_encode($paymentIntent), true);
    $rtn['items'] = $orderLineitems;
    $rtn['preTaxAmt'] = $orderValue;
    if (array_key_exists('discount_amount', $amountDetails)) {
        $rtn['discountAmt'] = $amountDetails['discount_amount'] / $currencyMultiplier;
    } else {
        $rtn['discountAmt'] = 0;
    }
    if (array_key_exists('tax', $amountDetails)) {
        $rtn['taxAmt'] = $amountDetails['tax']['total_tax_amount'] / $currencyMultiplier;
    } else {
        $taxAmt = 0;
        foreach ($orderLineitems as $line) {
            if (array_key_exists('tax', $line)) {
                $taxAmt += $line['tax']['total_tax_amount'] / $currencyMultiplier;
            }
        }
        $rtn['taxAmt'] = $taxAmt;
    }
    // build the return array of taxes applied to the order
    $rtnTaxes = [];
    //TODO build tax fetch
    /*
    if ($needTaxes) {
        $taxAmounts = $order->getTaxes();
        foreach ($taxAmounts as $tax) {
            $uid = $tax->getUid();
            $app = $tax->getAppliedMoney();
            $amt = $app->getAmount();
            $rtnTaxes[$uid] = $amt / 100;
        }
    }
    */
    $rtn['taxes'] = $rtnTaxes;
    $rtn['totalAmt'] = $phpOrder['amount'] / $currencyMultiplier;
    // load into the main rtn the items pay order needs directly
    $rtn['orderId'] = $phpOrder['id'];
    $rtn['version'] = '1';
    $rtn['ccType'] = 'stripe';
    $rtn['source'] = $source;
    $rtn['customerId'] = $phpOrder['customer'];
    $rtn['locationId'] = '';
    $rtn['referenceId'] = '';
    $rtn['transid'] = $results['transid'];
    if (array_key_exists('exhibits', $results))
        $rtn['exhibits'] = $results['exhibits'];
    if (array_key_exists('nonce', $results))
        $rtn['exhibits'] = $results['nonce'];

    return $rtn;
}

// an order is no longer valid, cancel it, via an update to Cancelled status
function cc_cancelOrder($source, $orderId, $useLogWrite = false, $locationId = null) : array | null {
    // need to rewrite for Stripe
    /*
    // Try updating the state of the order to CANCELED
    if ($locationId == null)
        $locationId = getConfValue('cc', 'location', null);

    $stripeDebug = getConfValue('debug', 'square', 0);

    $order = new Order([
        'locationId' => $locationId,
        'state' => 'CANCELED',
        'version' => 1,
    ]);

    $body = new Square\Orders\Requests\UpdateOrderRequest([
        'idempotencyKey' => guidv4(),
        'orderId' => $orderId,
        'order' => $order,
    ]);

    $client = new SquareClient(
        token: $cc['token'],
        options: [
            'baseUrl' => $cc['env'] == 'production' ? Environments::Production->value : Environments::Sandbox->value,
            ]);

    // pass update to cancel state to square
    $rtn = null;
    try {
          if ($stripeDebug & 12) stcc_logObject('cc_stripe/Orders API order update-body', $body, $useLogWrite);
          $apiResponse = $client->orders->update($body);
          $order = $apiResponse->getOrder();
          if ($stripeDebug & 12) stcc_logObject('cc_stripe/Orders API order update response', $order, $useLogWrite);
          $rtn = array();
          $rtn['order'] = $order;
          $rtn['state'] = $order->getState();
          $rtn['version'] = $order->getVersion();
      }
      catch (SquareApiException $e) {
          stcc_logException($source, $e, 'Order API update order Exception', 'Order cancel failed', $useLogWrite, false);
      }
      catch (Exception $e) {
          stcc_logException($source, $e, 'Order API error while calling Square', 'Error connecting to Square', $useLogWrite, false);
      }

     */

    $rtn = array(); // placeholder
    return $rtn;
}

// fetch an order to get its details
function cc_fetchOrder($source, $orderId, $useLogWrite = false) : array {
    // need to rewrite for stripe
    /*
    $stripeDebug = getConfValue('debug', 'square', 0);

    $body = new Square\Orders\Requests\GetOrdersRequest([
        'orderId' => $orderId,
    ]);

    $client = new SquareClient(
        token: $cc['token'],
        options: [
            'baseUrl' => $cc['env'] == 'production' ? Environments::Production->value : Environments::Sandbox->value,
        ]);

    // pass update to cancel state to square
    try {
        if ($stripeDebug & 12) stcc_logObject('cc_stripe/Orders API order create-body', $body, $useLogWrite);
        $apiResponse = $client->orders->get($body);
        $order = $apiResponse->getOrder();
        if ($stripeDebug & 12) stcc_logObject('cc_stripe/Orders API order response', $order, $useLogWrite);
    }
    catch (SquareApiException $e) {
        stcc_logException($source, $e, 'Order API create order Exception', 'Order fetch failed', $useLogWrite);
    }
    catch (Exception $e) {
        stcc_logException($source, $e, 'Order API error while calling Square', 'Error connecting to Square', $useLogWrite);
    }
    $rtn = array();
    $rtn['totalAmountDue'] = $order->getTotalMoney()->getAmount() / 100;
    $rtn['taxAmount'] = $order->getTotalTaxMoney()->getAmount() / 100;
    // build the return array of taxes applied to the order
    $taxAmounts = $order->getTaxes();
    $rtnTaxes = [];
    if (is_array($taxAmounts)) { // there have to be taxes to do this loop
        foreach ($taxAmounts as $tax) {
            $uid = $tax->getUid();
            $app = $tax->getAppliedMoney();
            $amt = $app->getAmount();
            $rtnTaxes[$uid] = $amt / 100;
        }
    }
    $rtn['taxes'] = $rtnTaxes;
    $rtn['totalDiscountAmount'] = $order->getTotalDiscountMoney()->getAmount() / 100;
    $rtn['netAmountDue'] = $order->getNetAmountDueMoney()->getAmount() / 100;
    $rtn['netAmount'] = $order->getNetAmounts()->getTotalMoney()->getAmount() / 100;
    $rtn['customerId'] = $order->getCustomerId();
    $rtn['order'] = $order;
     */

    $rtn = array(); // placeholder
    return $rtn;
}

// enter a payment against an exist order: build the payment, submit it to square and process the resulting payment
function cc_payOrder($ccParams, $buyer, $useLogWrite = false) {
    $con = get_conf('con');
    $currency = cc_getCurrency();
    $currencyMultiplier = get_currencyMultiplier($currency);
    $stripeDebug = getConfValue('debug', 'stripe', 0);

    //web_error_log("currenty = $currency, currencyMultiplier = $currencyMultiplier");
    $client = new \Stripe\StripeClient(getConfValue('cc', 'key'));

    // empty the return variables
    $auth = '';
    $txtime = '';
    $last4 = '';
    $auth = '';
    $nonce = '';
    $receipt_url = '';
    $receipt_number = '';
    $desc = '';
    $status = '';
    $approved_amt = 0;
    $payment = null;

    $source = 'onlinereg';
    if (array_key_exists('source', $ccParams)) {
        $source = $ccParams['source'];
    }
    $cleanUpRegs = $source == 'artist' || $source == 'exhibitor' || $source == 'fan' || $source == 'vendor' || $source == 'onlinereg';

    // 1. create payment for order
    //  a. extract the confirm id from the nonce structure passed in
    //  b. attach the confirm to the payment intent for this order and process it
    //  c. handle any next actions required
    //      this is a TBD just throw an appropriate error for now.
    // 2. parse return results to return the proper information
    // failure fall through

    $loginPerid = getSessionVar('user_perid');
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

    $sourceIdStr = $ccParams['nonce'];
    if (array_key_exists('change', $ccParams)) {
        $change = $ccParams['change'];
    } else {
        $change = 0;
    }
    $buyerSuppliedMoney = $ccParams['total'] + $change;
    $paymentType = 'credit';

    // nonce = source or confirm ressponse
    if (str_starts_with($sourceIdStr, '{')) {
        $confirmToken = json_decode($sourceIdStr, true);
        $card = $confirmToken['payment_method_preview']['card'];
        $sourceId =$card['brand'];
        $expire = $card['exp_month'] . '/' . $card['exp_year'];
        $last4 = $card['last4'];

        // attach the payment confirm to the payment intent
        $orderId = $ccParams['orderId'];
        $desc = 'Stripe: ' . $sourceId;
        $id = $orderId;
        $confirmFields = [
            'confirmation_token' => $confirmToken['id'],
            //'setup_future_usage' => 'off_session',
            'return_url' => 'https://controlltest.philcon.org/test1.php',
        ];
        // pass order to stripe and get payment intent id
        try {
            if ($stripeDebug & 14) stcc_logObject("cc_stripe/payment Intent Confirm of $orderId-confirmFields", $confirmFields, $useLogWrite);
            $paymentIntent = $client->paymentIntents->confirm($orderId, $confirmFields);
            if ($stripeDebug & 14) stcc_logObject("cc_stripe/payment Intent Confirm response of $orderId-paymentIntent", $paymentIntent, $useLogWrite);
        }
        catch (\Stripe\Exception\InvalidRequestException $e) {
            if ($cleanUpRegs)
                cleanRegs($ccParams['badges'], $ccParams['transid']);
            stcc_logException('cc_payOrder', $e, 'Invalid Request Exception', 'Unable process the payment, seek assistance.', $useLogWrite);;
        }
        catch (\Stripe\Exception\ApiErrorException $e) {
            if ($cleanUpRegs)
                cleanRegs($ccParams['badges'], $ccParams['transid']);
            stcc_logException('cc_payOrder', $e, 'other api error', 'Unable to process the payment, seek assistance.', $useLogWrite);
        }
        catch (Exception $e) {
            if ($cleanUpRegs)
                cleanRegs($ccParams['badges'], $ccParams['transid']);
            error_log('Another problem occurred, maybe unrelated to Stripe, seek assistance.');
        }
    } else {
        ajaxSuccess(array('error' => "Non credit payments not yet implimented"));
        exit();
    }

    // if type is charge, get the charge response as well
    $charge = null;
    $payment = json_decode(json_encode($paymentIntent), true);
    if (array_key_exists('status', $payment) && $payment['status'] == 'requires_action') {
        // return the payment intent
        ajaxSuccess(array(
            'status' => 'next',
            'intentStatus' => $payment['status'],
            'clientSecret' => $payment['client_secret'],
            )
        );
        exit();
    }
    $chargePHP = [];
    if (array_key_exists('latest_charge', $payment)) {
        $chargeId = $payment['latest_charge'];
        if ($chargeId != null && $chargeId != '') {
            try {
                if ($stripeDebug & 14) stcc_logObject("cc_stripe/retrive charge $chargeId", $chargeId, $useLogWrite);
                $charge = $client->charges->retrieve($chargeId, []);
                $chargePHP = json_decode(json_encode($charge), true);
                if ($stripeDebug & 14) stcc_logObject("cc_stripe/charge response of $chargeId", $chargePHP, $useLogWrite);
            }
            catch (\Stripe\Exception\InvalidRequestException $e) {
                if ($cleanUpRegs)
                    cleanRegs($ccParams['badges'], $ccParams['transid']);
                stcc_logException('cc_payOrder', $e, 'Invalid Request Exception', 'Unable process the payment, seek assistance.', $useLogWrite);;
            }
            catch (\Stripe\Exception\ApiErrorException $e) {
                if ($cleanUpRegs)
                    cleanRegs($ccParams['badges'], $ccParams['transid']);
                stcc_logException('cc_payOrder', $e, 'other api error', 'Unable to process the payment, seek assistance.', $useLogWrite);
            }
            catch (Exception $e) {
                if ($cleanUpRegs)
                    cleanRegs($ccParams['badges'], $ccParams['transid']);
                error_log('Another problem occurred, maybe unrelated to Stripe, seek assistance.');
            }
        }

        $approved_amt = $chargePHP['amount_captured'] / $currencyMultiplier;
        $card = $charge['payment_method_details']['card'];
        $last4 = $card['last4'];
        $nonce = $card['brand'] . '-' . $last4;
        $auth = substr($card['fingerprint'], 0, 16);
        $receipt_url = $chargePHP['receipt_url'];
        $receipt_number = $chargePHP['id'];
        $id = $chargePHP['id'];
        $txtime = date('Y/m/d H:i:s T', $chargePHP['created']);
        $status = $chargePHP['outcome']['seller_message'];
    }

    /*
    $pbodyArgs = array(
        'idempotencyKey' => guidv4(),
        'sourceId' => $sourceId,
        'amountMoney' => new Money([
            'amount' => round($ccParams['total'] * 100),
            'currency' => $currency,
            ]),
        'orderId' => $ccParams['orderId'],
        'autocomplete' => true,
        'locationId' => $ccParams['locationId'],
        'referenceId' => $con['id'] . '-' . $ccParams['transid'] . '-' . time(),
        'note' => "$source payment from " . $ccParams['source'],
    );
    if ($buyer['email'] != '')
        $pbodyArgs['buyerEmailAddress'] = $buyer['email'];
    if ($buyer['phone'] != '') {
        $phone = phoneNumberNormalize($buyer);
        if ($phone != '')
            $pbodyArgs['buyerPhoneNumber'] = $phone;
    }

    if ($sourceId == 'CASH') {
        // add cash fields
        $pbodyArgs['cashDetails'] = new Square\Types\CashPaymentDetails([
            'buyerSuppliedMoney' => new Money([
                'amount' => round($buyerSuppliedMoney * 100),
                'currency' => $currency,
                ]),
            'changeBackMoney' => new Money([
                'amount' => round($change * 100),
                'currency' => $currency,
            ]),
        ]);
        $paymentType = 'cash';
    }

    if ($sourceId == 'EXTERNAL') {
        $pbodyArgs['externalDetails'] = new Square\Types\ExternalPaymentDetails([
            'type' => $ccParams['externalType'],
            'source' => $ccParams['desc'],
        ]);
        $paymentType = $ccParams['externalType'];
    }

    $pbody = new CreatePaymentRequest($pbodyArgs);

    $id = $payment->getId();
    $status = $payment->getStatus();
    if ($sourceId == 'CARD') {
        $approved_amt = ($payment->getApprovedMoney()->getAmount()) / 100;
        $last4 = $payment->getCardDetails()->getCard()->getLast4();
        $auth = $payment->getCardDetails()->getAuthResultCode();
    } else {
        $last4 = '';
        $auth = '';
        $approved_amt = $ccParams['total'];
    }
    $receipt_url = $payment->getReceiptUrl();
    $desc = 'Square: ' . $payment->getApplicationDetails()->getSquareProduct();
    $txtime = $payment->getCreatedAt();
    $receipt_number = $payment->getReceiptNumber();

    */

    // set category based on if exhibits is a portal type
    if (array_key_exists('exhibits', $ccParams)) {
       $category =  $ccParams['exhibits'];
    } else if ($ccParams['source'] == 'artsales') {
        $category = 'artsales';
    } else {
        $category = 'reg';
    }

    $rtn = array();
    $rtn['txnfields'] = array('transid','type','category','description','source','pretax', 'tax', 'amount',
        'txn_time', 'cc','nonce','cc_txn_id','cc_approval_code','receipt_url','status','receipt_id', 'ccPaymentId','cashier');
    $rtn['tnxtypes'] = array('i', 's', 's', 's', 's', 'd', 'd', 'd',
            's', 's', 's', 's', 's', 's', 's', 's', 's', 'i');
    $rtn['tnxdata'] = array($ccParams['transid'],$paymentType,$category,$desc,$source,$ccParams['preTaxAmt'], $ccParams['taxAmt'], $approved_amt,
        $txtime,$last4,$nonce,$id,$auth,$receipt_url,$status,$receipt_number, $orderId, $loginPerid);
    $rtn['url'] = $receipt_url;
    $rtn['rid'] = $receipt_number;
    $rtn['payment'] = $payment;
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
    $rtn['amount'] = $approved_amt;
    $rtn['nonce'] = $nonce;
    $rtn['change'] = $change;
    return $rtn;
}


// cc_payComplete - compute the return array for a payment complete async call
function cc_payComplete($ccParams, $paymentIntent, $useLogWrite) {
    $con = get_conf('con');
    $currency = cc_getCurrency();
    $currencyMultiplier = get_currencyMultiplier($currency);
    $stripeDebug = getConfValue('debug', 'stripe', 0);

    //web_error_log("currenty = $currency, currencyMultiplier = $currencyMultiplier");
    $client = new \Stripe\StripeClient(getConfValue('cc', 'key'));

    // build the non ccParams fields again:
    $auth = '';
    $txtime = '';
    $last4 = '';
    $nonce = '';
    $receipt_url = '';
    $receipt_number = '';
    $desc = '';
    $status = '';
    $sourceId = 'unknown';
    $approved_amt = 0;
    $payment = json_decode(json_encode($paymentIntent), true);
    $paymentType = 'credit';


    $loginPerid = getSessionVar('user_perid');
    if ($loginPerid == null) {
        $userType = getSessionVar('idType');
        if ($userType == 'p')
            $loginPerid = getSessionVar('id');
        else
            $loginNewperid = getSessionVar('id');
    }

    // set category based on if exhibits is a portal type
    if (array_key_exists('exhibits', $ccParams)) {
        $category =  $ccParams['exhibits'];
    } else if ($ccParams['source'] == 'artsales') {
        $category = 'artsales';
    } else {
        $category = 'reg';
    }

    $source = 'onlinereg';
    if (array_key_exists('source', $ccParams)) {
        $source = $ccParams['source'];
    }

    $sourceIdStr = $ccParams['nonce'];
    if (array_key_exists('change', $ccParams)) {
        $change = $ccParams['change'];
    } else {
        $change = 0;
    }
    $buyerSuppliedMoney = $ccParams['total'] + $change;

    // nonce = source or confirm ressponse
    if (str_starts_with($sourceIdStr, '{')) {
        $confirmToken = json_decode($sourceIdStr, true);
        $card = $confirmToken['payment_method_preview']['card'];
        $sourceId = $card['brand'];
        $expire = $card['exp_month'] . '/' . $card['exp_year'];
        $last4 = $card['last4'];
    }


    // get the payment intent fresh to get the latest charge record
    $newPi = $client->paymentIntents->retrieve($payment['id']);
//    $newPmt = json_decode(json_encode($newPi), true);
    try {
        if ($stripeDebug & 14) stcc_logObject("cc_stripe/retrive payment intent", $payment['id'], $useLogWrite);
        $newPi = $client->paymentIntents->retrieve($payment['id'], []);
        $newPmt = json_decode(json_encode($newPi), true);
        if ($stripeDebug & 14) stcc_logObject("cc_stripe/payment intent response", $newPmt, $useLogWrite);
    }
    catch (\Stripe\Exception\InvalidRequestException $e) {
        stcc_logException('cc_payOrder', $e, 'Invalid Request Exception', 'Unable process the payment, seek assistance.', $useLogWrite);;
    }
    catch (\Stripe\Exception\ApiErrorException $e) {
        stcc_logException('cc_payOrder', $e, 'other api error', 'Unable to process the payment, seek assistance.', $useLogWrite);
    }
    catch (Exception $e) {
        error_log('Another problem occurred, maybe unrelated to Stripe, seek assistance.');
    }
    $chargePHP = [];
    if (array_key_exists('latest_charge', $newPmt)) {
        $chargeId = $newPmt['latest_charge'];
        if ($chargeId != null && $chargeId != '') {
            try {
                if ($stripeDebug & 14) stcc_logObject("cc_stripe/retrive charge $chargeId", $chargeId, $useLogWrite);
                $charge = $client->charges->retrieve($chargeId, []);
                $chargePHP = json_decode(json_encode($charge), true);
                if ($stripeDebug & 14) stcc_logObject("cc_stripe/charge response of $chargeId", $chargePHP, $useLogWrite);
            }
            catch (\Stripe\Exception\InvalidRequestException $e) {
                stcc_logException('cc_payOrder', $e, 'Invalid Request Exception', 'Unable process the payment, seek assistance.', $useLogWrite);;
            }
            catch (\Stripe\Exception\ApiErrorException $e) {
                stcc_logException('cc_payOrder', $e, 'other api error', 'Unable to process the payment, seek assistance.', $useLogWrite);
            }
            catch (Exception $e) {
                error_log('Another problem occurred, maybe unrelated to Stripe, seek assistance.');
            }
        }

        $approved_amt = $chargePHP['amount_captured'] / $currencyMultiplier;
        $card = $charge['payment_method_details']['card'];
        $last4 = $card['last4'];
        $nonce = $card['brand'] . '-' . $last4;
        $auth = substr($card['fingerprint'], 0, 16);
        $receipt_url = $chargePHP['receipt_url'];
        $receipt_number = $chargePHP['id'];
        $id = $chargePHP['id'];
        $txtime = date('Y/m/d H:i:s T', $chargePHP['created']);
        $payment = $newPmt;
    }

    $status = $newPmt['status'];

    $orderId = $ccParams['orderId'];
    $desc = 'Stripe: ' . $sourceId;

    $rtn = array();
    $rtn['txnfields'] = array('transid','type','category','description','source','pretax', 'tax', 'amount',
        'txn_time', 'cc','nonce','cc_txn_id','cc_approval_code','receipt_url','status','receipt_id', 'ccPaymentId','cashier');
    $rtn['tnxtypes'] = array('i', 's', 's', 's', 's', 'd', 'd', 'd',
        's', 's', 's', 's', 's', 's', 's', 's', 's', 'i');
    $rtn['tnxdata'] = array($ccParams['transid'],$paymentType,$category,$desc,$source,$ccParams['preTaxAmt'], $ccParams['taxAmt'], $approved_amt,
        $txtime,$last4,$nonce,$id,$auth,$receipt_url,$status,$receipt_number, $orderId, $loginPerid);
    $rtn['url'] = $receipt_url;
    $rtn['rid'] = $receipt_number;
    $rtn['payment'] = $payment;
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
    $rtn['amount'] = $approved_amt;
    $rtn['nonce'] = $nonce;
    $rtn['change'] = $change;
    return $rtn;
}

// fetch an payment to get its details
function cc_getPayment($source, $paymentid, $useLogWrite = false) : array {
    // need to rewrite for stripe
    /*
    $stripeDebug = getConfValue('debug', 'square', 0);

    $body = new Square\Payments\Requests\GetPaymentsRequest([
        'paymentId' => $paymentid,
    ]);

    $client = new SquareClient(
        token: $cc['token'],
        options: [
            'baseUrl' => $cc['env'] == 'production' ? Environments::Production->value : Environments::Sandbox->value,
        ]);

    // pass update to cancel state to square
    try {
        if ($stripeDebug & 14) stcc_logObject('cc_stripe/Payments API get payment-body', $body, $useLogWrite);
        $apiResponse = $client->payments->get($body);
        $payment = json_decode(json_encode($apiResponse->getPayment()), true);
        if ($stripeDebug & 14) stcc_logObject('cc_Stripe/Payments API get payment-payment', $payment, $useLogWrite);
    }
    catch (SquareApiException $e) {
        stcc_logException($source, $e, 'Payments API get payment Exception', 'get payment failed', $useLogWrite);
    }
    catch (Exception $e) {
        stcc_logException($source, $e, 'Payments API error while calling Square', 'Error connecting to Square', $useLogWrite);
    }

     */

    $payment = array(); // placeholder
    return $payment;
}

function stcc_logObject($message, $objArray, $useLogWrite = false) : void {
    $response = json_encode($objArray);
    $response = json_decode($response, true);
    if ($useLogWrite) {
        labeled_logWrite($message, $response);
    } else {
        labeled_error_log($message, $response);
    }
}

function stcc_logException($name, $e, $message, $ajaxMessage, $useLogWrite = false, $doExit = true) : void {
    labeled_error_log("$message:" . $e->getMessage(), $e);
    if ($doExit) {
        ajaxSuccess(array ('status' => 'error', 'data' => "Error: $ajaxMessage<br/>Ask them to check the logs."));
        exit();
    }
}
