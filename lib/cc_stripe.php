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

    if ($type == 'body') {
        $html =  <<<EOS
<form id="payment-form">
    <div class="container-fluid overflow-hidden" id="payment-element"></div>
    <div class="mt-1 p-1" id="ccPayMessageDiv"></div>
</form>
EOS;
        return $html;
    }

    $sdk = getConfValue('cc', 'websdk', 'https://js.stripe.com/dahlia/stripe.js');
    //$location = getConfValue('cc', 'location', null);
    $pkkey = getConfValue('cc', 'pkkey', null);
    $postalCode = '';
    if ($postal_code != '--') {
        $postalCode = "'postalCode': '$postal_code',\n";
    }

    $html = '';
    $html .= <<<EOS
<script src="$sdk"></script>
<script src="jslib/cc_stripe_html.js?v=$libJSversion"></script>
EOS;
    if ($type != 'js')
        $html .= <<<EOS
<form id="payment-form">
    <div class="container-fluid overflow-hidden" id="payment-element"></div>
    <button class="btn btn-primary btn-sm mt-2" id="card-button">Purchase</button>
    <div class="mt-1 p-1" id="ccPayMessageDiv"></div>
</form>
EOS;
    $html .= <<<EOS
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
        return 1.0;
    }
    return 100.0;
}

function cc_getCurrency() : string {
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
        stcc_logException('cc_getCurrency/country', $e, 'Invalid Request Exception', 'Invalid Country in system configuration, seek assistance.',
            $useLogWrite);;
    }
    catch (\Stripe\Exception\ApiErrorException $e) {
        stcc_logException('cc_getCurrency/country', $e, 'other api error', 'Unable to validate currency in system configuration, seek assistance.',
            $useLogWrite);
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

    $cust_email = '';
    $cust_name = '';
    $cust_phone = '';
    if (array_key_exists('custid', $results)) {
        $custid = $results['custid'];
        if (array_key_exists('vendor', $results)) {
            $vendor = $results['vendor'];
            $cust_email = $vendor['exhibitorEmail'];
            if (array_key_exists('artistName', $vendor) && $vendor['artistName'] != '')
                $cust_name = trim($vendor['artistName']);
            else
                $cust_name = trim($vendor['exhibitorName']);
            $cust_phone = trim($vendor['exhibitorPhone']);
        } else if (array_key_exists('badges', $results) && is_array($results['badges']) && count($results['badges']) > 0) {
            $custType = substr($custid, 0, 1);
            $custNum = substr($custid, 2);
            foreach ($results['badges'] as $badge) {
                if (($custType == 'p' && $custNum == $badge['perid']) || ($custType == 'n' && $custNum == $badge['newperid'])
                    || ($custType == 'r' && $custNum == $badge['badge'])) {
                    $cust_email = trim($badge['email_addr']);
                    $cust_phone = trim($badge['phone']);
                    $cust_name = trim($badge['fullName']);
                    break;
                }
            }
        }
    } else if (array_key_exists('badges', $results) && is_array($results['badges']) && count($results['badges']) > 0) {
        $badge = $results['badges'][0];
        if (array_key_exists('perid', $badge)) {
            $custid = 'p-' . $badge['perid'];
            $custType = 'p';
            $custNum = $badge['perid'];
        } else if (array_key_exists('newperid', $badge)) {
            $custid = 'n-' . $badge['newperid'];
            $custType = 'n';
            $custNum = $badge['newperid'];
        } else {
            $custid = 'r-' . $results['badges'][0]['badge'];
            $custType = 'r';
            $custNum = $results['badges'][0]['badge'];
        }
        $cust_email = trim($badge['email_addr']);
        $cust_phone = trim($badge['phone']);
        $cust_name = trim($badge['fullName']);
    } else if (array_key_exists('exhibits', $results) && array_key_exists('vendorId', $results)) {
        $custid = 'e-' . $results['vendorId'];
        $custType = 'e';
        $custNum = $results['vendorId'];
        $source = $results['exhibits'];
        $vendor = $results['vendor'];
        $cust_email = $vendor['exhibitorEmail'];
        if (array_key_exists('artistName', $vendor) && $vendor['artistName'] != '')
            $cust_name = trim($vendor['artistName']);
        else
            $cust_name = trim($vendor['exhibitorName']);
        $cust_phone = trim($vendor['exhibitorPhone']);

        // failures in the exhibitor payments need to delete the regs they were going to product
        $cleanUpRegs = true;
    } if ($results['source'] == 'artpos') {
        $cust_email = trim($results['email']);
        $cust_phone = trim($results['phone']);
        $cust_name = trim($results['name']);
    } else {
        $custid = 't-' . $results['transid'];
        $custNum = $results['transid'];
        $custType = 't';
    }

    $customerId = null;
    if ($cust_email != '') {
        // we have details to look up a customer
        // try a customer search for:
        // email or phone or fullname (all exact match)
        $query = "email:\"$cust_email\"";
        if ($cust_phone != '') {
            $query .= " OR phone:\"$cust_phone\"";
        }
        if ($cust_name != '') {
            $query .= " OR name:\"$cust_name\"";
        }
        $customerLookup = [
            'query' => $query,
            'limit' => 10,
        ];

        try {
            if ($stripeDebug & 14) stcc_logObject('cc_stripe/buildOrder/customer query', $customerLookup, $useLogWrite);
            $customers = $client->customers->search($customerLookup);
            $customersPHP = json_decode(json_encode($customers), true);
            if ($stripeDebug & 14) stcc_logObject('cc_stripe/buildOrder/customer query response', $customersPHP, $useLogWrite);
        }
        catch (\Stripe\Exception\InvalidRequestException $e) {
            if ($cleanUpRegs)
                cleanRegs($results['badges'], $results['transid']);
            stcc_logException('cc_buildOrder/customer search', $e, 'Invalid Request Exception', 'Unable to create the order, seek assistance.', $useLogWrite);;
        }
        catch (\Stripe\Exception\ApiErrorException $e) {
            if ($cleanUpRegs)
                cleanRegs($results['badges'], $results['transid']);
            stcc_logException('cc_buildOrder/customer search', $e, 'other api error', 'Unable to create the order, seek assistance.', $useLogWrite);
        }
        catch (Exception $e) {
            if ($cleanUpRegs)
                cleanRegs($results['badges'], $results['transid']);
            error_log('Another problem occurred, maybe unrelated to Stripe, seek assistance.');
        }

        if (array_key_exists('data', $customersPHP)) {
            $custData = $customersPHP['data'];
            if (count($custData) == 1)
                $customerId = $custData[0]['id'];
            else {
                // find best match
                $maxMatch = 0;
                foreach ($custData as $cust) {
                    $numMatch = 0;
                    if (array_key_exists ('name', $cust) && $cust['name'] != null && trim(strtolower($cust['name'])) == strtolower($cust_name))
                        $numMatch++;
                    if (array_key_exists ('phone', $cust) && $cust['phone'] != null && trim(strtolower($cust['phone'])) == $cust_phone)
                        $numMatch++;
                    if (array_key_exists ('email', $cust) && $cust['email'] != null && trim(strtolower($cust['email'])) == $cust_email)
                        $numMatch += 2;

                    if ($numMatch > $maxMatch) {
                        $customerId = $cust['id'];
                        $maxMatch = $numMatch;
                    }
                }
            }
        }

        // ok, if it doesn't exist, create one
        if ($customerId == null) {
            $customer = [
                'name' => $cust_name,
                'email' => $cust_email,
                'phone' => $cust_phone,
                'description' => $custid,
                ];

            switch ($custType) {
                case 'p':
                    $query = <<<EOS
SELECT address, addr_2, city, state, zip, country
FROM perinfo
WHERE id = ?;
EOS;
                    break;

                case 'n':
                    $query = <<<EOS
SELECT address, addr_2, city, state, zip, country
FROM newperson
WHERE id = ?;
EOS;
                    break;

                case 'r':
                    $query = <<<EOS
SELECT IFNULL(p.address, n.address) AS address, IFNULL(p.addr_2, n.addr_2) AS addr_2, IFNULL(p.city, n.city) AS city, 
       IFNULL(p.state, n.state) AS state, IFNULL(p.zip, n.zip) AS zip, IFNULL(p.country, n.country) AS country
FROM reg r
LEFT OUTER JOIN perinfo p ON r.perid = p.id
LEFT OUTER JOIN newperson n ON r.newperid = n.id
EOS;
                    break;

                case 'e':
                    $query = <<<EOS
SELECT addr AS address, addr2 AS addr_2, city, state, zip, country
FROM exhibitors
WHERE id = ?;
EOS;

                break;

                case 't':
                    $query = <<<EOS
SELECT IFNULL(p.address, n.address) AS address, IFNULL(p.addr_2, n.addr_2) AS addr_2, IFNULL(p.city, n.city) AS city, 
       IFNULL(p.state, n.state) AS state, IFNULL(p.zip, n.zip) AS zip, IFNULL(p.country, n.country) AS country
FROM transaction t
LEFT OUTER JOIN perinfo p ON t.perid = p.id
LEFT OUTER JOIN newperson n ON t.newperid = n.id
EOS;
                    break;

                default:
                    $query = '';
            }

            if ($query != '') {
                $aR = dbSafeQuery($query, 'i', array($custNum));
                if ($aR !== false && $aR->num_rows == 1) {
                    $addr = $aR->fetch_assoc();
                    $aR->close();

                    $customer['adddress'] = [];
                    if ($addr['address'] != null && $addr['address'] != '' && $addr['address'] != '/r')
                        $customer['address']['line1'] = $addr['address'];
                    if ($addr['addr_2'] != null && $addr['addr_2'] != '' && $addr['addr_2'] != '/r')
                        $customer['address']['line2'] = $addr['addr_2'];
                    if ($addr['city'] != null && $addr['city'] != '' && $addr['city'] != '/r')
                        $customer['address']['city'] = $addr['city'];
                    if ($addr['state'] != null && $addr['state'] != '' && $addr['state'] != '/r')
                    $customer['address']['state'] = $addr['state'];
                    if ($addr['zip'] != null && $addr['zip'] != '' && $addr['zip'] != '/r')
                        $customer['address']['postal_code'] = $addr['zip'];
                    if ($addr['country'] != null && $addr['country'] != '') {
                        $ISO3 = loadCountryConvert();
                        if (array_key_exists($addr['country'], $ISO3)) {
                            $ISO2 = $ISO3[$addr['country']];
                            $customer['address']['country'] = $ISO2;
                        }
                    }
                }
            }

            try {
                if ($stripeDebug & 14) stcc_logObject('cc_stripe/buildOrder/customer create', $customer, $useLogWrite);
                $customer = $client->customers->create($customer);
                $customerPHP = json_decode(json_encode($customer), true);
                if ($stripeDebug & 14) stcc_logObject('cc_sripe/buildOrder/customer create response', $customerPHP, $useLogWrite);
            }
            catch (\Stripe\Exception\InvalidRequestException $e) {
                if ($cleanUpRegs)
                    cleanRegs($results['badges'], $results['transid']);
                stcc_logException('cc_buildOrder/customer create', $e, 'Invalid Request Exception', 'Unable to create the order, seek assistance.',
                    $useLogWrite);;
            }
            catch (\Stripe\Exception\ApiErrorException $e) {
                if ($cleanUpRegs)
                    cleanRegs($results['badges'], $results['transid']);
                stcc_logException('cc_buildOrder/customer create', $e, 'other api error', 'Unable to create the order, seek assistance.', $useLogWrite);
            }
            catch (Exception $e) {
                if ($cleanUpRegs)
                    cleanRegs($results['badges'], $results['transid']);
                error_log('Another problem occurred, maybe unrelated to Stripe, seek assistance.');
            }

            $customerId = $customerPHP['id'];
        }
    }



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

    $orderLineItems = []; // array (non associative) where each item is a line item of the order in a very basic form
    $orderTaxLineitems = []; // array (non associative) same as orderLineItems + stuff to compute the taxes
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
        $item = [
            'product_code' => 'planPayment',
            'product_name' => mb_substr('Plan Payment: ' .  $planName, 0, 512),
            'quantity' => 1,
            'unit_cost' => round($results['total'] * $currencyMultiplier),
            'tax' => ['total_tax_amount' => 0 ],
            ];
        $orderLineItems[] = $item;
        $item['basePriceMoney']  = $item['unit_cost']; // convert to common name for tax comps
        $orderTaxLineItems[] = $item;
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

                $itemNumber++;
                $item = [
                    'product_code' => 'art-' . $artId,
                    'product_name' => mb_substr($artistName . ' / ' . $title, 0, 1024),
                    'quantity' => $quantity,
                    'unit_cost' => round($amount * $currencyMultiplier / $quantity),
                    'tax' => ['total_tax_amount' => 0 ], // placeholder for tax until computed later
                ];
                $orderLineItems[] = $item;
                $item['basePriceMoney'] = $item['unit_cost']; // convert to common name for tax comps
                // compute the art line item tax
                if ($hasTax) {
                    // create the Line Item tax record, art sales are taxable
                    $item['taxes'] = buildCCAppliedTaxArray('artSales', $lineid);
                    $item['taxable'] = count($item['taxes']) > 0 ? 'Y' : 'N';
                }
                $orderTaxLineItems[] = $item;

                $orderMetadata['in' . $itemNumber] = $notesData['note'];
                $orderMetadata['im' . $itemNumber] = json_encode($notesData['metadata']);

                $orderValue += $art['amount'];
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
                $item = [
                    'product_code' => 'badge-' . $badge['memId'],
                    'product_name' => mb_substr($itemName, 0, 1024),
                    'quantity' => 1,
                    'unit_cost' => $amount,
                    'tax' => ['total_tax_amount' => 0 ], // placeholder for after tax computation
                ];
                $orderLineItems[] = $item;
                if ($hasTax)  {
                    if (array_key_exists('taxable', $badge) && $badge['taxable'] == 'Y') {
                        $badgeTaxable = 'taxableMem';
                    } else {
                        $badgeTaxable = 'nontaxMem';
                    }
                    $item['basePriceMoney'] = $item['unit_cost']; // convert to common name for tax comps

                    // create the Line Item tax record, if there is a tax rate, and the membership is taxable
                    $taxArray = buildCCAppliedTaxArray($badgeTaxable, $lineid);
                    if ($needTaxes == false)
                        $needTaxes = count($taxArray) > 0;
                    $item['taxes'] = $taxArray;
                    $item['taxable'] = count($taxArray) > 0 ? 'Y' : 'N';
                }
                $orderTaxLineItems[] = $item;
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

                $itemNumber++;
                $item = [
                    'product_code' => 'space-' . $spaceId,
                    'product_name' => mb_substr($itemName, 0, 1024),
                    'quantity' => 1,
                    'unit_cost' => round($space['approved_price'] * $currencyMultiplier),
                    'tax' => ['total_tax_amount' => 0 ],
                ];
                $orderLineItems[] = $item;
                // compute the art line item tax
                if ($hasTax)  {
                    $item['basePriceMoney']  = $item['unit_cost']; // convert to common name for tax comps
                    // create the Line Item tax record, if there is a tax rate, and the membership is taxable
                    // need to determine the type of space
                    $taxArray = buildCCAppliedTaxArray($spaceType, $lineid);
                    if ($needTaxes == false)
                        $needTaxes = count($taxArray) > 0;
                    $item['taxes'] = $taxArray;
                    $item['taxable'] = count($taxArray) > 0 ? 'Y' : 'N';
                }
                $orderTaxLineItems[] = $item;
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
                $item = [
                    'product_code' => 'mailinFee',
                    'product_name' => mb_substr($itemName, 0, 1024),
                    'quantity' => 1,
                    'unit_cost' => round($itemPrice * $currencyMultiplier),
                    'tax' => ['total_tax_amount' => 0 ],
                ];
                $orderLineItems[] = $item;
                // apply taxes to mail in fees based on the artShipping flag
                if ($hasTax)  {
                    $item['basePriceMoney']  = $item['unit_cost']; // convert to common name for tax comps
                    // create the Line Item tax record, if there is a tax rate, and the membership is taxable
                    // need to determine the type of space
                    $taxArray = buildCCAppliedTaxArray('artShipping', $lineid);
                    if ($needTaxes == false)
                        $needTaxes = count($taxArray) > 0;
                    $item['taxes'] = $taxArray;
                    $item['taxable'] = count($taxArray) > 0 ? 'Y' : 'N';
                }
                $orderTaxLineItems[] = $item;
                $orderMetadata['min' . $itemNumber] = $notesData['note'];
                $orderMetadata['mim' . $itemNumber] = json_encode($notesData['metadata']);
                $orderValue += $itemPrice;
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

    // now compute the taxes for each orderTaxLineItem and set the total tax
    // compute the fields the credit card company would compute
    $orderTax = 0;
    $taxAmounts = [];
    if ($needTaxes) {
        foreach ($orderTaxLineItems as $ord => $item) {
            if (array_key_exists('taxable', $item)) {
                $orderTaxLineItems[$ord]['taxAmounts'] = computeTax($item);
            }
        }
        $taxAmounts = computeTotalTax($orderTaxLineItems);
        $metaTaxAmounts = $taxAmounts;
        // this needs to be converted back to the decimal currenty
        if ($currencyMultiplier != 1) {
            foreach ($metaTaxAmounts as $key => $taxarr) {
                $metaTaxAmounts[$key]['tax'] = $taxarr['tax'] / $currencyMultiplier;
            }
        }
        $orderMetadata['taxesjson'] = json_encode($metaTaxAmounts);
    }
    // now update the tax for each line item
    for ($lineno = 0; $lineno < count($orderTaxLineItems); $lineno++) {
        $taxLine = $orderTaxLineItems[$lineno];
        if (array_key_exists('taxAmounts', $taxLine)) {
            $taxes = $taxLine['taxAmounts'];
            $totalTax = $taxes['totalTax'];
            for ($taxLineno = 1; $taxLineno <= 5; $taxLineno++) {
                $key = 'tax' . $taxLineno;
                if (array_key_exists($key, $taxes)) {
                    $metaKey = 'ol' . ($lineno + 1) . $key;
                    $orderMetadata[$metaKey] = $taxes[$key];
                }
            }
            $orderTax += $totalTax;
            $orderLineItems[$lineno]['tax']['total_tax_amount'] = $totalTax;
        }
    }
    $amountDetails = [];
    if ($orderDiscount > 0) {
        $amount_details['discount_amount'] = $orderDiscount;
    }
    $amountDetails['line_items'] = $orderLineItems;
    $orderMetadata['totalTax'] = $orderTax;

    $orderFields = [
        'amount' => round($orderValue * $currencyMultiplier) + $orderTax,
        'currency' => $currency,
        'automatic_payment_methods' => [
            'enabled' => true,
        ],
        'confirm' => false,
        'amount_details' => $amountDetails,
        'description' => $con['conname'] . '-' . $source,
        'metadata' => $orderMetadata,
        //'setup_future_usage' => 'off_session',
    ];
    if ($customerId != '')
        $orderFields['customer'] = $customerId;

    // pass order to stripe and get payment intent id
    try {
        if ($stripeDebug & 14) stcc_logObject('cc_stripe/buildOrder/payment Intent Create-orderFields', $orderFields, $useLogWrite);
        $paymentIntent = $client->paymentIntents->create($orderFields);
        if ($stripeDebug & 14) stcc_logObject('cc_stripe/buildOrder/payment Intent response', $paymentIntent, $useLogWrite);
    }
    catch (\Stripe\Exception\InvalidRequestException $e) {
            if ($cleanUpRegs)
                cleanRegs($results['badges'], $results['transid']);
            stcc_logException('cc_buildOrder/payment intent create', $e, 'Invalid Request Exception', 'Unable to create the order, seek assistance.',
                $useLogWrite);;
    }
    catch (\Stripe\Exception\ApiErrorException $e) {
        if ($cleanUpRegs)
            cleanRegs($results['badges'], $results['transid']);
            stcc_logException('cc_buildOrder/payment intent create', $e, 'other api error', 'Unable to create the order, seek assistance.', $useLogWrite);
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
    $rtn['items'] = $orderLineItems;
    $rtn['preTaxAmt'] = $orderValue;
    if (array_key_exists('discount_amount', $amountDetails)) {
        $rtn['discountAmt'] = $amountDetails['discount_amount'] / $currencyMultiplier;
    } else {
        $rtn['discountAmt'] = 0;
    }

    // build the return array of taxes applied to the order
    $rtnTaxes = [];
    $taxAmount = 0;
    foreach ($taxAmounts as $taxField => $tax) {
        $rtnTaxes[$taxField]['tax'] = $tax['tax'] / 100;
        $rtnTaxes[$taxField]['name'] = $tax['name'];
        $taxAmount += $tax['tax'];
    }
    $rtn['taxes'] = $rtnTaxes;
    $rtn['taxAmt'] = $taxAmount / 100;
    $rtn['taxAmount'] = $taxAmount / 100;
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
    $stripeDebug = getConfValue('debug', 'square', 0);
    $client = new \Stripe\StripeClient(getConfValue('cc', 'key'));

    $rtn = null;
    try {
        if ($stripeDebug & 14) stcc_logObject('cc_stripe/cancelOrder cancel payment intent', $orderId, $useLogWrite);
        $payment = $client->paymentIntents->cancel($orderId, []);
        $payment = json_decode(json_encode($payment), true);
        if ($stripeDebug & 14) stcc_logObject("cc_Stripe/cancelOrder payment intent response for $orderId", $payment, $useLogWrite);
    }
    catch (\Stripe\Exception\InvalidRequestException $e) {
        stcc_logException('cc_cancelOrder', $e, 'Invalid Request Exception', 'Unable cancel the order, seek assistance.', $useLogWrite);;
    }
    catch (\Stripe\Exception\ApiErrorException $e) {
        stcc_logException('cc_cancelOrder', $e, 'other api error', 'Unable to cancel the order, seek assistance.', $useLogWrite);
    }
    catch (Exception $e) {
        error_log('Another problem occurred, maybe unrelated to Stripe, seek assistance.');
    }

    $rtn = array();
    $rtn['order'] = $payment;
    $rtn['state'] = $payment['status'];
    return $rtn;
}

// fetch an order to get its details
function cc_fetchOrder($source, $orderId, $useLogWrite = false) : array {
    // in stripe, this is the same as get payment as the payment intent is the order
    return cc_getPayment($source, $orderId, $useLogWrite);
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

    // nonce = source (non online credit card) or confirm ressponse (online credit card)
    if (str_starts_with($sourceIdStr, '{')) {
        // online credit card from a payment confirm.
        $confirmToken = json_decode($sourceIdStr, true);
        $card = $confirmToken['payment_method_preview']['card'];
        $sourceId =$card['brand'];
        $expire = $card['exp_month'] . '/' . $card['exp_year'];
        $last4 = $card['last4'];

        // attach the payment confirm to the payment intent
        $orderId = $ccParams['orderId'];
        $id = $orderId;
        $confirmFields = [
            'confirmation_token' => $confirmToken['id'],
            //'setup_future_usage' => 'off_session',
            'return_url' => 'https://controlltest.philcon.org/test1.php',
        ];
        // pass order to stripe and get payment intent id
        $cardDeclineCodes = [
            'card_declined',
            'generic_decline',
            'incorrect_cvc',
            'authentication_required',
            'insufficient_funds',
            'incorrect_card_details',
            'expired_card',
            'suspected_fraud',
            'payment_limit_exceeded',
            'invalid_customer_account',
            'lost_card',
            'stolen_card',
            'processing_error',
            'incorrect_number',
            'card_velocity_exceeded',
        ];
        try {
            if ($stripeDebug & 14) stcc_logObject("cc_stripe/payOrder/payment Intent Confirm of $orderId-confirmFields", $confirmFields, $useLogWrite);
            $paymentIntent = $client->paymentIntents->confirm($orderId, $confirmFields);
            if ($stripeDebug & 14) stcc_logObject("cc_stripe/PayOrder/payment Intent Confirm response of $orderId-paymentIntent", $paymentIntent, $useLogWrite);
        }
        catch (\Stripe\Exception\InvalidRequestException $e) {
            if ($cleanUpRegs)
                cleanRegs($ccParams['badges'], $ccParams['transid']);
            $code = $e->getError()->decline_code;
            if (in_array($code, $cardDeclineCodes)) {
                ajaxSuccess(array ('status' => 'error', 'restoreBtn' => 1, 'data' => 'Error: ' . $e->getError()->message));
                exit();
            }
            stcc_logException('cc_payOrder/confirm', $e, 'Invalid Request Exception', 'Unable process the payment, seek assistance.', $useLogWrite);;
        }
        catch (\Stripe\Exception\ApiErrorException $e) {
            if ($cleanUpRegs)
                cleanRegs($ccParams['badges'], $ccParams['transid']);
            $code = $e->getError()->decline_code;
            if (in_array($code, $cardDeclineCodes)) {
                ajaxSuccess(array ('status' => 'error', 'restoreBtn' => 1, 'data' => 'Error: ' . $e->getError()->message));
                exit();
            }
            stcc_logException('cc_payOrder/confirm', $e, 'other api error', 'Unable to process the payment, seek assistance.', $useLogWrite);
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
            'payParams' => $ccParams,
            'post' => $_POST,
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
                if ($stripeDebug & 14) stcc_logObject("cc_stripe/payOrder/retrive charge $chargeId", $chargeId, $useLogWrite);
                $charge = $client->charges->retrieve($chargeId, []);
                $chargePHP = json_decode(json_encode($charge), true);
                if ($stripeDebug & 14) stcc_logObject("cc_stripe/payOrder/charge response of $chargeId", $chargePHP, $useLogWrite);
            }
            catch (\Stripe\Exception\InvalidRequestException $e) {
                if ($cleanUpRegs)
                    cleanRegs($ccParams['badges'], $ccParams['transid']);
                stcc_logException('cc_payOrder/charge', $e, 'Invalid Request Exception', 'Unable process the payment, seek assistance.', $useLogWrite);;
            }
            catch (\Stripe\Exception\ApiErrorException $e) {
                if ($cleanUpRegs)
                    cleanRegs($ccParams['badges'], $ccParams['transid']);
                stcc_logException('cc_payOrder/charge', $e, 'other api error', 'Unable to process the payment, seek assistance.', $useLogWrite);
            }
            catch (Exception $e) {
                if ($cleanUpRegs)
                    cleanRegs($ccParams['badges'], $ccParams['transid']);
                error_log('Another problem occurred, maybe unrelated to Stripe, seek assistance.');
            }
        }

        $desc = 'Stripe: ' . $sourceId;
        if (array_key_exists('desc', $ccParams) && $ccParams['desc'] != '') {
            $desc .= '; ' . $ccParams['desc'];
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
    try {
        if ($stripeDebug & 14) stcc_logObject("cc_stripe/payComplete/retrive payment intent", $payment['id'], $useLogWrite);
        $newPi = $client->paymentIntents->retrieve($payment['id'], []);
        $newPmt = json_decode(json_encode($newPi), true);
        if ($stripeDebug & 14) stcc_logObject("cc_stripe/payComplete/payment intent response", $newPmt, $useLogWrite);
    }
    catch (\Stripe\Exception\InvalidRequestException $e) {
        stcc_logException('cc_payComplete/retrieve', $e, 'Invalid Request Exception', 'Unable process the payment, seek assistance.', $useLogWrite);;
    }
    catch (\Stripe\Exception\ApiErrorException $e) {
        stcc_logException('cc_payComplete/retrieve', $e, 'other api error', 'Unable to process the payment, seek assistance.', $useLogWrite);
    }
    catch (Exception $e) {
        error_log('Another problem occurred, maybe unrelated to Stripe, seek assistance.');
    }
    $chargePHP = [];
    if (array_key_exists('latest_charge', $newPmt)) {
        $chargeId = $newPmt['latest_charge'];
        if ($chargeId != null && $chargeId != '') {
            try {
                if ($stripeDebug & 14) stcc_logObject("cc_stripe/payComplete/retrive charge $chargeId", $chargeId, $useLogWrite);
                $charge = $client->charges->retrieve($chargeId, []);
                $chargePHP = json_decode(json_encode($charge), true);
                if ($stripeDebug & 14) stcc_logObject("cc_stripe/payComplete/charge response of $chargeId", $chargePHP, $useLogWrite);
            }
            catch (\Stripe\Exception\InvalidRequestException $e) {
                stcc_logException('cc_payComplete/charge', $e, 'Invalid Request Exception', 'Unable process the payment, seek assistance.', $useLogWrite);;
            }
            catch (\Stripe\Exception\ApiErrorException $e) {
                stcc_logException('cc_payComplete/charge', $e, 'other api error', 'Unable to process the payment, seek assistance.', $useLogWrite);
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
    if (array_key_exists('desc', $ccParams) && $ccParams['desc'] != '') {
        $desc .= '; ' . $ccParams['desc'];
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

// fetch an payment to get its details
function cc_getPayment($source, $paymentid, $useLogWrite = false) : array {
    $stripeDebug = getConfValue('debug', 'square', 0);
    $client = new \Stripe\StripeClient(getConfValue('cc', 'key'));
    $currency = cc_getCurrency();
    $currencyMultiplier = get_currencyMultiplier($currency);

    try {
        if ($stripeDebug & 14) stcc_logObject('cc_stripe/getPayment retrieve payment intent', $paymentid, $useLogWrite);
        $payment = $client->paymentIntents->retrieve($paymentid, []);
        $payment = json_decode(json_encode($payment), true);
        if ($stripeDebug & 14) stcc_logObject("cc_stripe/getPayment payment intent response for $paymentid", $payment, $useLogWrite);
    }
    catch (\Stripe\Exception\InvalidRequestException $e) {
        stcc_logException('cc_getPayment', $e, 'Invalid Request Exception', 'Unable retrieve the payment, seek assistance.', $useLogWrite);;
    }
    catch (\Stripe\Exception\ApiErrorException $e) {
        stcc_logException('cc_getPayment', $e, 'other api error', 'Unable to retrieve the payment, seek assistance.', $useLogWrite);
    }
    catch (Exception $e) {
        error_log('Another problem occurred, maybe unrelated to Stripe, seek assistance.');
    }

    // now add the line items for other uses (than terminal)
    try {
        if ($stripeDebug & 14) stcc_logObject('cc_stripe/getPayment retrieve payment line items', $paymentid, $useLogWrite);
        $lineItems = $client->paymentIntents->allAmountDetailsLineItems($paymentid, []);
        $lineItems = json_decode(json_encode($lineItems), true);
        if ($stripeDebug & 14) stcc_logObject("cc_stripe/getPayment retrieve payment line items for $paymentid", $lineItems, $useLogWrite);
    }
    catch (\Stripe\Exception\InvalidRequestException $e) {
        stcc_logException('cc_getPayment/line items', $e, 'Invalid Request Exception', 'Unable retrieve the payment line items, seek assistance.',
            $useLogWrite);;
    }
    catch (\Stripe\Exception\ApiErrorException $e) {
        stcc_logException('cc_getPayment/line items', $e, 'other api error', 'Unable to retrieve the payment line items, seek assistance.', $useLogWrite);
    }
    catch (Exception $e) {
        error_log('Another problem occurred, maybe unrelated to Stripe, seek assistance.');
    }

    if (!array_key_exists('amount_details', $payment)) {
        $payment['amount_details'] = array ();
    }
    $payment['amount_details']['lineItems'] = $lineItems['data'];
    $payment['totalAmountDue'] = $payment['amount'] / $currencyMultiplier;
    $payment['taxAmount'] = $payment['metadata']['totalTax'];
    $payment['customerId'] = $payment['customer'];
    if (array_key_exists('taxesjson', $payment['metadata']))
        $payment['taxes'] = json_decode($payment['metadata']['taxesjson'], true);
    else
        $payment['taxes'] = array();

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
