<?php
//  cc_stripe.php - library of modules to add the square php payment API to onlinereg
// uses config variables:
// [cc]
// type=stripe - selects that reg is to use square for credit cards
// remainder: TBD
// does not currently use any other config sections for credit card other than [cc]

require_once("global.php");

// draw_cc_html - exposed function to draw the credit card HTML window
//      $cc = array of [cc] section of ini file
//      $postal_code = postal code to default for form, optional
//

function draw_cc_html($cc, $postal_code = "--", $type='all') : string {
    // need to create the place holder and then use elements api to add the content using the payment intent
    /*
    $sdk = $cc['webpaysdk'];
    $appid = $cc['appid'];
    $location = $cc['location'];
    $postalCode = '';
    if ($postal_code != '--') {
        $postalCode = "'postalCode': '$postal_code',\n";
    }

    $html = '';
    if ($type != 'body') {
        $html .= <<<EOS
<script src="$sdk"></script>
<!-- Configure the Web Payments SDK and Card payment method -->
 <script type="text/javascript">
      ;
      var payments = null;
    
      async function startCCPay() {
          const appId = '$appid';
          const locationId = '$location';
          const payments = Square.payments(appId, locationId);
          const card = await payments.card({
              $postalCode        
              "style": {
                  ".input-container": {
                      "borderColor": "blue",
                      "borderWidth": "2px",
                      "borderRadius": "12px",
                  },
                  "input": {
                      "color": "blue",
                      "fontSize": '24px',
                  },
                  "@media screen and (max-width: 600px)": {
                      "input": {
                          "fontSize": "24px",
                      }
                  }
              }
          });
          document.getElementById("card-button").removeAttribute("hidden");
          await card.attach('#card-container');

          async function eventHandler(event) {
              event.preventDefault();

              try {
                  const result = await card.tokenize();
                  if (result.status === 'OK') {
                      //console.log(`Payment token is ' + result.token);
                      makePurchase(result.token, "card-button");
                  }
              } catch (e) {
                  console.error(e);
              }
          };
          const cardButton = document.getElementById('card-button');
          cardButton.addEventListener('click', eventHandler);
      }
EOS;
    }
    if ($type == 'js') {
        $html .= <<<EOS
    
    function startCC() {
        if (!window.Square) {
            throw new Error('Square.js failed to load properly');
        }    
          
      startCCPay();
      } 
            
EOS;
    }
    if ($type == 'all') {
        $html .= <<<EOS
      
      document.addEventListener('DOMContentLoaded', async function () {
         if (!window.Square) {
            throw new Error('Square.js failed to load properly');
          }    
          
          startCCPay();
      });
EOS;
    }
    if ($type == 'all' || $type == 'js') {
        $html .= "</script>\n";
    }

    if ($type != 'js') {
        $html .= <<<EOS
<form id = "payment-form">
    <div class="container-fluid overflow-hidden" id = "card-container"></div>
    <button id = "card-button" type = "button"> Purchase</button>
</form>
EOS;
        }
    */
    $html = "<b>Stripe payment form: Not Yet</b>";
    return $html;
};

//  from the stripe docs
global $stripeUnitCurrencies;
$stripeUnitCurrencies = array('bif', 'clp', 'djf', 'gnf', 'jpy', 'kmf', 'krw', 'mga', 'pyg', 'rwf', 'ugx', 'vnd', 'vuv', 'xaf', 'xof', 'xpf');

// stripe doesn't multiply all currencies to unit hundreths (2dp currencies), some are zero decimal currencies
function get_currencyMultiplier($currency) {
    global $stripeUnitCurrencies;
    if (in_array($currency, $stripeUnitCurrencies)) {
        return 1;
    }
    return 100;
}

function cc_getCurrency($con) : string {
    // need to rewrite for stripe, return always correct for now
    $cur = strtolower(getConfValue('con', 'currency', 'USD'));
    // use the country
    $country = strtoupper(getConfValue('cc', 'country', 'US'));
    $stripeDebug = getConfValue('debug', 'stripe', 0);
    $useLogWrite = $stripeDebug > 0;
    $countrySpec = null;

    try {
        if ($stripeDebug & 14) stcc_logObject(array ('countrySpecs->retrieve', $country), $useLogWrite);
        // get a client reference
        $client = new \Stripe\StripeClient(getConfValue('cc', 'key'));
        $countrySpec = $client->countrySpecs->retrieve($country, []);
        if ($stripeDebug & 14) stcc_logObject(array ('countrySpecs->retrieve response', json_decode(json_encode($countrySpec), true)), $useLogWrite);
    }
    catch (\Stripe\Exception\InvalidRequestException $e) {
        stcc_logException('cc_getCurrency', $e, 'Invalid Request Exception', 'Invalid Country in system configuration, seek assistance.', $useLogWrite);;
    }
    catch (\Stripe\Exception\ApiErrorException $e) {
        sqcc_logException('cc_getCurrency', $e, 'other api error', 'Unable to validate currency in system configuration, seek assistance.', $useLogWrite);
    }
    catch (Exception $e) {
        error_log('Another problem occurred, maybe unrelated to Stripe.');
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
    $cc = get_conf('cc');
    $con = get_conf('con');
    $currency = cc_getCurrency($con);
    $currencyMultiplier = get_currencyMultiplier($currency);
    web_error_log("currenty = $currency, currencyMultiplier = $currencyMultiplier");
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
    // look up if customer exists

    // stripe locations appear to apply to devices, not api online???
    /*
    if ($locationId == null) {
        $locationId = $cc['location'];
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
        $orderMetadata[] = ['in . $itemNumber' => $notesData['note']];
        $orderMetadata[] = ['im . $itemNumber' => $notesData['metadata']];
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
                $orderMetadata[] = ['in . $itemNumber' => $notesData['note']];
                $orderMetadata[] = ['im . $itemNumber' => $notesData['metadata']];

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

            $item = new OrderLineItemDiscount ([
                'uid' => 'discount',
                'name' => mb_substr($couponName, 0, 128),
                'type' => OrderLineItemDiscountType::FixedAmount->value,
                'amountMoney' => new Money([
                    'amount' => round($results['discount'] * 100),
                    'currency' => $currency,
                ]),
                'scope' => OrderLineItemDiscountScope::Order->value,
            ]);
            $orderDiscounts[] = $item;
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
                    $amount = round($badge['balDue'] * 100);
                } else if (array_key_exists('paid', $badge)) {
                    $amount = round(($badge['price']-$badge['paid']) * 100);
                } else {
                    $amount = round($badge['price'] * 100);
                }

                if (array_key_exists('complete_trans', $badge) && $badge['complete_trans'] > 0 && $amount == 0)
                    continue; // skip paid complete items in order for sending to square

                $addMbr = str_contains(strtolower($badge['shortname']), 'membership') == false &&
                    ($badge['memType'] == 'full' || $badge['memType'] == 'oneday');
                $itemName =  $badge['fname'] . ': ' . $badge['shortname'] .' ' . ($badge['ageshortname'] != 'All' ? $badge['ageshortname'] : '') .
                    ($addMbr ? ' Mbr ' : ' ') . 'for ' . $fullname;
                $item = new OrderLineItem ([
                    'itemType' => OrderLineItemItemType::Item->value,
                    'uid' => 'badge' . ($lineid + 1),
                    'name' => mb_substr($itemName, 0, 128),
                    'quantity' => 1,
                    'note' => $notesData['note'],
                    'metadata' => $notesData['metadata'],
                    'basePriceMoney' => new Money([
                        'amount' => $amount,
                        'currency' => $currency,
                    ]),
                ]);

                // apply taxes to badge memberships based on the taxable override flags for taxable vs. non taxable
                if ($hasTax)  {
                    if (array_key_exists('taxable', $badge) && $badge['taxable'] == 'Y') {
                        $badgeTaxable = 'taxableMem';
                    } else {
                        $badgeTaxable = 'nontaxMem';
                    }

                    // create the Line Item tax record, if there is a tax rate, and the membership is taxable
                    $taxArray = buildSquareAppliedTaxArray($badgeTaxable, $lineid);
                    if ($needTaxes == false)
                        $needTaxes = count($taxArray) > 0;
                    $item->setAppliedTaxes($taxArray);
                }

                if (array_key_exists('newplan', $results) && $results['newplan'] == 1) {
                    if ($badge['inPlan'])
                        $item->setAppliedDiscounts(array(new Square\Types\OrderLineItemAppliedDiscount([
                            'uid' => 'planDeferment-' . $lineid,
                            'discountUid' => 'planDeferment',
                        ])));
                }

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
                if ($managerDiscount &&
                    (!array_key_exists('status', $badge) || $badge['status'] == 'unpaid' || $badge['status'] == 'plan')) {
                    $item->setAppliedDiscounts(array(new Square\Types\OrderLineItemAppliedDiscount([
                        'uid' => 'managerDiscount-' . $lineid,
                        'discountUid' => 'discount' ,
                    ])));
                }
                $orderLineitems[] = $item;
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

                $item = new OrderLineItem([
                    'itemType' => OrderLineItemItemType::Item->value,
                    'uid' => 'space-' . $spaceId,
                    'name' => mb_substr($itemName, 0, 128),
                    'quantity' => 1,
                    'note' => $notesData['note'],
                    'metadata' => $notesData['metadata'],
                    'basePriceMoney' => new Money([
                        'amount' => round($space['approved_price'] * 100),
                        'currency' => $currency,
                    ]),
                ]);

                // apply taxes to spaces based on the space taxable flag
                if ($hasTax)  {
                    // create the Line Item tax record, if there is a tax rate, and the membership is taxable
                    // need to determine the type of space
                    $taxArray = buildSquareAppliedTaxArray($spaceType, $lineid);
                    if ($needTaxes == false)
                        $needTaxes = count($taxArray) > 0;
                    $item->setAppliedTaxes($taxArray);
                }

                $orderLineitems[] = $item;
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

                $item = new OrderLineItem([
                    'itemType' => OrderLineItemItemType::Item->value,
                    'uid' => 'region-' . str_replace(' ', '-', $fee['name']),
                    'name' => mb_substr($itemName, 0, 128),
                    'quantity' => 1,
                    'note' => $notesData['note'],
                    'metadata' => $notesData['metadata'],
                    'basePriceMoney' => new Money([
                        'amount' => round($itemPrice * 100),
                        'currency' => $currency,
                        ]),
                ]);

                // apply taxes to mail in fees based on the artShipping flag
                if ($hasTax)  {
                    // create the Line Item tax record, if there is a tax rate, and the membership is taxable
                    // need to determine the type of space
                    $taxArray = buildSquareAppliedTaxArray('artShipping', $lineid);
                    if ($needTaxes == false)
                        $needTaxes = count($taxArray) > 0;
                    $item->setAppliedTaxes($taxArray);
                }

                $orderLineitems[] = $item;
                $orderValue += $itemPrice;
                $lineid++;
            }
        }

        // TODO: if an item is in plan, set the plan discount to apply only to those line items
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
    }

    // order (constructor array variables)
    //	$this->id = $values['id'] ?? null;
    //	$this->locationId = $values['locationId'];
    //	$this->referenceId = $values['referenceId'] ?? null;
    //	$this->source = $values['source'] ?? null;
    //	$this->customerId = $values['customerId'] ?? null;
    //	$this->lineItems = $values['lineItems'] ?? null;
    //	$this->taxes = $values['taxes'] ?? null;
    //	$this->discounts = $values['discounts'] ?? null;
    //	$this->serviceCharges = $values['serviceCharges'] ?? null;
    //	$this->fulfillments = $values['fulfillments'] ?? null;
    //	$this->returns = $values['returns'] ?? null;
    //	$this->returnAmounts = $values['returnAmounts'] ?? null;
    //	$this->netAmounts = $values['netAmounts'] ?? null;
    //	$this->roundingAdjustment = $values['roundingAdjustment'] ?? null;
    //	$this->tenders = $values['tenders'] ?? null;
    //	$this->refunds = $values['refunds'] ?? null;
    //	$this->metadata = $values['metadata'] ?? null;
    //	$this->createdAt = $values['createdAt'] ?? null;
    //	$this->updatedAt = $values['updatedAt'] ?? null;
    //	$this->closedAt = $values['closedAt'] ?? null;
    //	$this->state = $values['state'] ?? null;
    //	$this->version = $values['version'] ?? null;
    //	$this->totalMoney = $values['totalMoney'] ?? null;
    //	$this->totalTaxMoney = $values['totalTaxMoney'] ?? null;
    //	$this->totalDiscountMoney = $values['totalDiscountMoney'] ?? null;
    //	$this->totalTipMoney = $values['totalTipMoney'] ?? null;
    //	$this->totalServiceChargeMoney = $values['totalServiceChargeMoney'] ?? null;
    //	$this->ticketName = $values['ticketName'] ?? null;
    //	$this->pricingOptions = $values['pricingOptions'] ?? null;
    //	$this->rewards = $values['rewards'] ?? null;
    //	$this->netAmountDueMoney = $values['netAmountDueMoney'] ?? null;


    $order = new Order([
        'locationId' => $locationId,
        'referenceId' => $con['id'] . '-' . $results['transid'],
        'source' => new OrderSource([
            'name' => $con['conname'] . '-' . $source
        ]),
        'customerId' => $con['id'] . '-' . $custid,
        'lineItems' => $orderLineitems,
        'discounts' => $orderDiscounts,
    ]);

    if ($needTaxes) {
        $order->setTaxes(buildSquareOrderTaxArray());
    }

    // build the order request from it's parts
    $body = new CreateOrderRequest([
        'idempotencyKey' => guidv4(),
        'order' => $order,
    ]);

    // pass order to square and get order id

    try {
        if ($stripeDebug & 14) sqcc_logObject(array ('Orders API order create', json_decode(json_encode($body), true)), $useLogWrite);
        $apiResponse = $client->orders->create($body);
        $order = $apiResponse->getOrder();
        if ($stripeDebug & 14) sqcc_logObject(array ('Orders API order response', json_decode(json_encode($order), true)), $useLogWrite);
    }
    catch (SquareApiException $e) {
        if ($cleanUpRegs)
            cleanRegs($results['badges'], $results['transid']);
        sqcc_logException($source, $e, 'Order API create order Exception', 'Order create failed', $useLogWrite);
    }
    catch (Exception $e) {
        if ($cleanUpRegs)
            cleanRegs($results['badges'], $results['transid']);
        sqcc_logException($source, $e, 'Order API error while calling Square', 'Error connecting to Square', $useLogWrite);
    }

    $rtn = array();
    $rtn['results'] = $results;
     // need to pass back order id, total_amount, tax_amount,
    $rtn['order'] = $order;
    $phpOrder = json_decode(json_encode($order), true);
    $rtn['items'] = $phpOrder['line_items'];
    $rtn['preTaxAmt'] = $orderValue;
    $rtn['discountAmt'] = $order->getTotalDiscountMoney()->getAmount() / 100;
    $rtn['taxAmt'] = $order->getTotalTaxMoney()->getAmount() / 100;
    // build the return array of taxes applied to the order
    $rtnTaxes = [];
    if ($needTaxes) {
        $taxAmounts = $order->getTaxes();
        foreach ($taxAmounts as $tax) {
            $uid = $tax->getUid();
            $app = $tax->getAppliedMoney();
            $amt = $app->getAmount();
            $rtnTaxes[$uid] = $amt / 100;
        }
    }
    $rtn['taxes'] = $rtnTaxes;
    $rtn['totalAmt'] = $order->getTotalMoney()->getAmount() / 100;
    // load into the main rtn the items pay order needs directly
    $rtn['orderId'] = $order->getId();
    $rtn['version'] = $order->getVersion();
    $rtn['source'] = $source;
    $rtn['customerId'] = $order->getCustomerId();
    $rtn['locationId'] = $order->getLocationId();
    $rtn['referenceId'] = $order->getReferenceId();
    $rtn['transid'] = $results['transid'];
    if (array_key_exists('exhibits', $results))
        $rtn['exhibits'] = $results['exhibits'];
    if (array_key_exists('nonce', $results))
        $rtn['exhibits'] = $results['nonce'];

    $rtn = array(); // placeholder
    return $rtn;
}

// an order is no longer valid, cancel it, via an update to Cancelled status
function cc_cancelOrder($source, $orderId, $useLogWrite = false, $locationId = null) : array | null {
    // need to rewrite for Stripe
    /*
    // Try updating the state of the order to CANCELED
    $cc = get_conf('cc');
    if ($locationId == null)
        $locationId = $cc['location'];

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
          if ($stripeDebug & 12) sqcc_logObject(array ('Orders API order update', $body), $useLogWrite);
          $apiResponse = $client->orders->update($body);
          $order = $apiResponse->getOrder();
          if ($stripeDebug & 12) sqcc_logObject(array ('Orders API order update response', $order), $useLogWrite);
          $rtn = array();
          $rtn['order'] = $order;
          $rtn['state'] = $order->getState();
          $rtn['version'] = $order->getVersion();
      }
      catch (SquareApiException $e) {
          sqcc_logException($source, $e, 'Order API update order Exception', 'Order cancel failed', $useLogWrite, false);
      }
      catch (Exception $e) {
          sqcc_logException($source, $e, 'Order API error while calling Square', 'Error connecting to Square', $useLogWrite, false);
      }

     */

    $rtn = array(); // placeholder
    return $rtn;
}

// fetch an order to get its details
function cc_fetchOrder($source, $orderId, $useLogWrite = false) : array {
    $cc = get_conf('cc');
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
        if ($stripeDebug & 12) sqcc_logObject(array ('Orders API order create', $body), $useLogWrite);
        $apiResponse = $client->orders->get($body);
        $order = $apiResponse->getOrder();
        if ($stripeDebug & 12) sqcc_logObject(array ('Orders API order response', $order), $useLogWrite);
    }
    catch (SquareApiException $e) {
        sqcc_logException($source, $e, 'Order API create order Exception', 'Order fetch failed', $useLogWrite);
    }
    catch (Exception $e) {
        sqcc_logException($source, $e, 'Order API error while calling Square', 'Error connecting to Square', $useLogWrite);
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
    $cc = get_conf('cc');
    $currency = cc_getCurrency($con);
    $stripeDebug = getConfValue('debug', 'stripe', 0);
    // need to rewrite for stripe
    /*

    $source = 'onlinereg';
    if (array_key_exists('source', $ccParams)) {
        $source = $ccParams['source'];
    }
    $cleanUpRegs = $source == 'artist' || $source == 'exhibitor' || $source == 'fan' || $source == 'vendor' || $source == 'onlinereg';

    // 1. create payment for order
    //  a. create payment object with order id and payment amount plus credit card nonce
    //  b. pass payment to payment processor
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

    $sourceId = $ccParams['nonce'];
    if (array_key_exists('change', $ccParams)) {
        $change = $ccParams['change'];
    } else {
        $change = 0;
    }
    $buyerSuppliedMoney = $ccParams['total'] + $change;
    $paymentType = 'credit';

    // nonce = card id if card, CASH or EXTERNAL (check, other credit card clearer)
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

    $client = new SquareClient(
        token: $cc['token'],
        options: [
            'baseUrl' => $cc['env'] == 'production' ? Environments::Production->value : Environments::Sandbox->value,
        ]);

    try {
        if ($stripeDebug & 14) sqcc_logObject(array ('Payments API create', json_decode(json_encode($pbody), true)), $useLogWrite);
        $apiResponse = $client->payments->create($pbody);
        $payment = $apiResponse->getPayment();
        if ($stripeDebug & 14) sqcc_logObject(array ('Payments API Response', json_decode(json_encode($payment), true)), $useLogWrite);
    }
    catch (SquareApiException $e) {
        web_error_log('Order Square API Exception: ' . $e->getMessage());
        $ebody = json_decode($e->getBody(),true);
        $errors = $ebody['errors'];
        if ($errors) {
            if ($stripeDebug) sqcc_logObject(array ('Payment returned non-success', $errors), $useLogWrite);
            foreach ($errors as $error) {
                $cat = $error['category'];
                $code = $error['code'];
                $detail = $error['detail'];
                if ($useLogWrite) {
                    logWrite('Transid: ' . $ccParams['transid'] . " Cat: $cat: Code $code, Detail: $detail");
                }
                web_error_log('Transid: ' . $ccParams['transid'] . " Cat: $cat: Code $code, Detail: $detail");

                switch ($code) {
                    case 'GENERIC_DECLINE':
                        $msg = 'Card Declined';
                        break;
                    case 'CVV_FAILURE':
                        $msg = 'Authorization error: Invalid CVV';
                        break;
                    case 'ADDRESS_VERIFICATION_FAILURE':
                        $msg = 'Address Verification Failure: Zip Code';
                        break;
                    case 'INVALID_EXPIRATION':
                        $msg = 'Authorization error: Invalid Expiration Date';
                        break;
                    default:
                        $msg = $code;
                }
                if ($useLogWrite) {
                    logWrite('Square card payment error for ' . $ccParams['transid'] . " of $msg");
                }
                web_error_log('Square card payment error for ' . $ccParams['transid'] . " of $msg");

                if ($cleanUpRegs)
                    cleanRegs($ccParams['badges'], $ccParams['transid']);
                ajaxSuccess(array ('status' => 'error', 'data' => "Payment Error: $msg"));
                exit();
            }
        }
        if ($cleanUpRegs)
            cleanRegs($ccParams['badges'], $ccParams['transid']);
        ajaxSuccess(array ('status' => 'error', 'data' => 'Error: Error connecting to Square'));
        exit();
    }
    catch (Exception $e) {
        if ($cleanUpRegs)
            cleanRegs($ccParams['badges'], $ccParams['transid']);
        sqcc_logException($source, $e, 'Payment API error while calling Square', 'Error connecting to Square', $useLogWrite);
    }
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
        $txtime,$last4,$ccParams['nonce'],$id,$auth,$receipt_url,$status,$receipt_number, $id, $loginPerid);
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
    $rtn['nonce'] = $ccParams['nonce'];
    $rtn['change'] = $change;
    */

    $rtn = array(); // placeholder
    return $rtn;
}

// fetch an payment to get its details
function cc_getPayment($source, $paymentid, $useLogWrite = false) : array {
    $cc = get_conf('cc');
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
        if ($stripeDebug & 14) sqcc_logObject(array ('Payments API get payment', $body), $useLogWrite);
        $apiResponse = $client->payments->get($body);
        $payment = json_decode(json_encode($apiResponse->getPayment()), true);
        if ($stripeDebug & 14) sqcc_logObject(array ('Payments API get payment', $payment), $useLogWrite);
    }
    catch (SquareApiException $e) {
        sqcc_logException($source, $e, 'Payments API get payment Exception', 'get payment failed', $useLogWrite);
    }
    catch (Exception $e) {
        sqcc_logException($source, $e, 'Payments API error while calling Square', 'Error connecting to Square', $useLogWrite);
    }

     */

    $payment = array(); // placeholder
    return $payment;
}

function stcc_logObject($objArray, $useLogWrite = false) : void {
    if ($useLogWrite) {
        logWrite($objArray);
    } else {
        web_error_log($objArray[0]);
        // stretched out for debugging breaksteps to see it in the debugger
        $response = json_encode($objArray[1]);
        $response = json_decode($response, true);
        var_error_log($response, true);
    }
}

function stcc_logException($name, $e, $message, $ajaxMessage, $useLogWrite = false, $doExit = true) : void {
    error_log("$message:" . $e->getMessage());
    web_error_log("$message:" . $e->getMessage());
    if ($doExit) {
        ajaxSuccess(array ('status' => 'error', 'data' => "Error: $ajaxMessage<br/>Ask them to check the logs."));
        exit();
    }
}
