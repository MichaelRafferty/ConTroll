<?php
//  cc_square.php - library of modules to add the square php payment API to onlinereg
// uses config variables:
// [cc]
// type=square - selects that reg is to use square for credit cards
// appid=[APPID] - appliction ID from the square developer portal, be it sandbox or production
// token=[TOKEN] - auth token from the square developer portal
// location=[LOCATION] - location id from the square developer portal
// does not currently use any other config sections for credit card other than [cc]

require_once("global.php");

// draw_cc_html - exposed function to draw the credit card HTML window
//      $cc = array of [cc] section of ini file
//      $postal_code = postal code to default for form, optional
//

function draw_cc_html($cc, $postal_code = "--") : string {
    $sdk = $cc['webpaysdk'];
    $appid = $cc['appid'];
    $location = $cc['location'];
    $postalCode = '';
    if ($postal_code != '--') {
        $postalCode = "'postalCode': '$postal_code',\n";
    }

    $html = <<<EOS
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
                      "fontSize": '12px',
                  },
                  "@media screen and (max-width: 600px)": {
                      "input": {
                          "fontSize": "16px",
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

      document.addEventListener('DOMContentLoaded', async function () {
         if (!window.Square) {
            throw new Error('Square.js failed to load properly');
          }    
          
          startCCPay();
      });
  </script>
    <form id="payment-form">
        <div class="container-fluid overflow-hidden" id="card-container"></div>
        <button id="card-button" type="button">Purchase</button>
    </form>
EOS;
    return $html;
};

use Square\Environments;
use Square\SquareClient;
use Square\Exceptions\SquareApiException;
use Square\Payments\Requests\CreatePaymentRequest;
use Square\Types\Currency;
use Square\Types\Money;
use Square\Types\CreateOrderRequest;
use Square\Types\Order;
use Square\Types\OrderSource;
use Square\Types\OrderLineItem;
use Square\Types\OrderLineItemItemType;
use Square\Types\OrderLineItemDiscount;
use Square\Types\OrderLineItemDiscountScope;
use Square\Types\OrderLineItemDiscountType;

function cc_getCurrency($con) : string {
    if (array_key_exists('currency', $con)) {
        $cur = strtoupper($con['currency']);
        $cur = strtoupper(substr($cur, 0, 1)) . substr($cur, 1);
        $curT = Currency::from($cur);
        if ($curT) {
            $currency = $curT->value;
        } else {
            ajaxSuccess(array ('status' => 'error', 'data' => 'Error: Currency ' . $con['currency'] .
                ' not yet supported in Square, seek assistance.'));
            exit();
        }
    } else
        $currency = Currency::Usd->value;

    return $currency;
}

// build the order, pass it to square and get the order id
function cc_buildOrder($results, $useLogWrite = false) : array {
    $cc = get_conf('cc');
    $con = get_conf('con');
    $debug = get_conf('debug');
    if (array_key_exists('square', $debug))
        $squareDebug = $debug['square'];
    else
        $squareDebug = 0;
    $id = null;

    $client = new SquareClient(
        token: $cc['token'],
        options: [
            'baseUrl' => $cc['env'] == 'production' ? Environments::Production->value : Environments::Sandbox->value,
    ]);
    $currency = cc_getCurrency($con);

    $loginPerid = getSessionVar('user_perid');
    if ($loginPerid == null) {
        $userType = getSessionVar('idType');
        if ($userType == 'p')
            $loginPerid = getSessionVar('id');
    }

    // square order api steps
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

    // SDK 41, builds the parts then passes them into the body
    // line items first
    // then order
    // then body

    // SDK API Quick Reference: order lines (possible values from constructor)
    //	$this->uid = $values['uid'] ?? null;
    //	$this->name = $values['name'] ?? null;
    //	$this->quantity = $values['quantity'];
    //	$this->quantityUnit = $values['quantityUnit'] ?? null;  (leave out for 'each')
    //	$this->note = $values['note'] ?? null;
    //	$this->catalogObjectId = $values['catalogObjectId'] ?? null;  (NOT USED by ConTroll)
    //	$this->catalogVersion = $values['catalogVersion'] ?? null;  (NOT USED by ConTroll)
    //	$this->variationName = $values['variationName'] ?? null;  (NOT USED by ConTroll)
    //	$this->itemType = $values['itemType'] ?? null;
    //	$this->metadata = $values['metadata'] ?? null;  (NOT USED by ConTroll)
    //	$this->modifiers = $values['modifiers'] ?? null;  (NOT USED by ConTroll)
    //	$this->basePriceMoney = $values['basePriceMoney'] ?? null;
    //	$this->variationTotalPriceMoney = $values['variationTotalPriceMoney'] ?? null;  (NOT USED by ConTroll)
    //	$this->pricingBlocklists = $values['pricingBlocklists'] ?? null;    (Both discounts and taxes are in this item)
    //	$this->appliedTaxes = $values['appliedTaxes'] ?? null;     (if scope Line, then input else computed)
    //	$this->appliedDiscounts = $values['appliedDiscounts'] ?? null;     (if scope Line, then input else computed)
    // fields only in the response/getOrder
    //	$this->appliedTaxes = $values['appliedTaxes'] ?? null;     (Computed field by Square, R/O)
    //	$this->appliedDiscounts = $values['appliedDiscounts'] ?? null;     (Computed field by Square, R/O)
    //	$this->appliedServiceCharges = $values['appliedServiceCharges'] ?? null;     (Computed field by Square, R/O)
    //	$this->grossSalesMoney = $values['grossSalesMoney'] ?? null;     (Computed field by Square, R/O)
    //	$this->totalTaxMoney = $values['totalTaxMoney'] ?? null;     (Computed field by Square, R/O)
    //	$this->totalDiscountMoney = $values['totalDiscountMoney'] ?? null;     (Computed field by Square, R/O)
    //	$this->totalMoney = $values['totalMoney'] ?? null;     (Computed field by Square, R/O)
    //	$this->totalServiceChargeMoney = $values['totalServiceChargeMoney'] ?? null;     (Computed field by Square, R/O)

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
    $taxuid = 'salestax';
    if (array_key_exists('taxRate', $con)) {
        $taxRate = $con['taxRate'];
    }
    if (array_key_exists('taxLabel', $con)) {
        $taxLabel = $con['taxLabel'];
    }
    if (array_key_exists('taxuid', $con)) {
        $taxuid = $con['taxuid'];
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
        $item = new OrderLineItem ([
            'itemType' => OrderLineItemItemType::Item->value,
            'uid' => 'planPayment',
            'name' => mb_substr('Plan Payment: ' . $note, 0, 128),
            'quantity' => 1,
            'note' => $note,
            'basePriceMoney' => new Money([
                'amount' =>$results['total'] * 100,
                'currency' => $currency,
            ]),
        ]);
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
                if (!array_key_exists('paid', $badge)) {
                    $badge['paid'] = 0;
                }
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
                    $amount = $badge['balDue'] * 100;
                } else {
                    $amount = ($badge['price']-$badge['paid']) * 100;
                }

                $itemName =  $badge['label'] . (($badge['memType'] == 'full' || $badge['memType'] == 'oneday') ? ' Membership' : '') .
                    ' for ' . $fullname;
                $item = new OrderLineItem ([
                    'itemType' => OrderLineItemItemType::Item->value,
                    'uid' => 'badge' . ($lineid + 1),
                    'name' => mb_substr($itemName, 0, 128),
                    'quantity' => 1,
                    'note' => $note,
                    'basePriceMoney' => new Money([
                        'amount' => $amount,
                        'currency' => $currency,
                    ]),
                ]);
                if ($taxRate > 0 && array_key_exists('taxable', $badge) && $badge['taxable'] == 'Y') {
                    // create the Line Item tax record, if there is a tax rate, and the membership is taxable
                    $needTaxes = true;
                    $item->setAppliedTaxes(array(new Square\Types\OrderLineItemAppliedTax([
                        'uid' => 'badge-tax-' . ($lineid + 1),
                        'taxUid' => $taxuid,
                    ])));
                }
                if (array_key_exists('newplan', $results) && $results['newplan'] == 1) {
                    if ($badge['inPlan'])
                        $item->setAppliedDiscounts(array(new Square\Types\OrderLineItemAppliedDiscount([
                            'uid' => 'planDeferment-' . $lineid,
                            'discountUid' => 'planDeferment',
                        ])));
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

                $item = new OrderLineItem([
                    'itemType' => OrderLineItemItemType::Item->value,
                    'uid' => 'space-' . $spaceId,
                    'name' => mb_substr($itemName, 0, 128),
                    'quantity' => 1,
                    'note' => $note,
                    'basePriceMoney' => new Money([
                        'amount' => $space['approved_price'] * 100,
                        'currency' => $currency,
                    ]),
                ]);
                $orderLineitems[$lineid] = $item;
                $orderValue += $space['approved_price'];
                $lineid++;
            }
        }

        if (array_key_exists('mailInFee', $results)) {
            foreach ($results['mailInFee'] as $fee) {
                $item = new OrderLineItem([
                    'itemType' => OrderLineItemItemType::Item->value,
                    'uid' => 'region-' . $fee['yearId'],
                    'name' => 'Mail in Fee for ' . $fee['name'],
                    'quantity' => 1,
                    'note' => 'Mail in fee',
                    'basePriceMoney' => new Money([
                        'amount' => $fee['amount'] * 100,
                        'currency' => $currency,
                        ]),
                ]);
                $orderLineitems[$lineid] = $item;
                $orderValue += $fee['amount'];
                $lineid++;
            }
        }

        // TODO: set the lines the coupon applies to specifically using appliedDiscount and line type for the coupon to split it correctly
        // now apply the coupon
        if (array_key_exists('discount', $results) && $results['discount'] > 0) {
            if (array_key_exists('coupon', $results) && $results['coupon'] != null) {
                $coupon = $results['coupon'];
                $couponName = 'Coupon: ' . $coupon['code'] . ' (' . $coupon['name'] . '), Coupon Discount: ' .
                    $coupon['discount'];
            }
            else {
                $couponName = 'Coupon Applied';
            }

            $item = new OrderLineItemDiscount ([
                'uid' => 'couponDiscount',
                'name' => mb_substr($couponName, 0, 128),
                'type' => OrderLineItemDiscountType::FixedAmount->value,
                'amountMoney' => new Money([
                    'amount' => $results['discount'] * 100,
                    'currency' => $currency,
                ]),
                'scope' => OrderLineItemDiscountScope::Order->value,
            ]);
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
            $item = new OrderLineItemDiscount ([
                'uid' => 'planDeferment',
                'name' => mb_substr("Payment Deferral Amount: " . $note, 0, 128),
                'type' => OrderLineItemDiscountType::FixedAmount->value,
                'amountMoney' => new Money([
                    'amount' => $deferment * 100,
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
        'locationId' => $cc['location'],
        'referenceId' => $con['id'] . '-' . $results['transid'],
        'source' => new OrderSource([
            'name' => $con['conname'] . '-' . $source
        ]),
        'customerId' => $con['id'] . '-' . $custid,
        'lineItems' => $orderLineitems,
        'discounts' => $orderDiscounts,
    ]);

    if ($needTaxes) {
        $order->setTaxes(array(new Square\Types\OrderLineItemTax([
            'uid' => $taxuid,
            'name' => $taxLabel,
            'type' => Square\Types\OrderLineItemTaxType::Additive->value,
            'percentage' => $taxRate,
            'scope' => Square\Types\OrderLineItemTaxScope::LineItem->value,
        ])));
    }

    // build the order request from it's parts
    $body = new CreateOrderRequest([
        'idempotencyKey' => guidv4(),
        'order' => $order,
    ]);

    // pass order to square and get order id

    try {
        if ($squareDebug) sqcc_logObject(array ('Orders API order create', $body), $useLogWrite);
        $apiResponse = $client->orders->create($body);
        $order = $apiResponse->getOrder();
        if ($squareDebug) sqcc_logObject(array ('Orders API order response', $order), $useLogWrite);
    }
    catch (SquareApiException $e) {
        sqcc_logException($source, $e, 'Order API create order Exception', 'Order create failed', $useLogWrite);
    }
    catch (Exception $e) {
        sqcc_logException($source, $e, 'Order API error while calling Square', 'Error connecting to Square', $useLogWrite);
    }

    $rtn = array();
    $rtn['results'] = $results;
     // need to pass back order id, total_amount, tax_amount,
    $rtn['order'] = $order;
    $rtn['preTaxAmt'] = $orderValue;
    $rtn['discountAmt'] = $order->getTotalDiscountMoney()->getAmount() / 100;
    $rtn['taxAmt'] = $order->getTotalTaxMoney()->getAmount() / 100;
    $rtn['taxLabel'] = $taxLabel;
    $rtn['totalAmt'] = $order->getTotalMoney()->getAmount() / 100;
    // load into the main rtn the items pay order needs directly
    $rtn['orderId'] = $order->getId();
    $rtn['source'] = $source;
    $rtn['customerId'] = $order->getCustomerId();
    $rtn['locationId'] = $order->getLocationId();
    $rtn['referenceId'] = $order->getReferenceId();
    $rtn['transid'] = $results['transid'];
    if (array_key_exists('exhibits', $results))
        $rtn['exhibits'] = $results['exhibits'];
    if (array_key_exists('nonce', $results))
        $rtn['exhibits'] = $results['nonce'];

    return $rtn;
}

// an order is no longer valid, cancel it, via an update to Cancelled status
function cc_cancelOrder($source, $orderId, $useLogWrite = false) : void {
    // At present the API does not let you cancle orders, and this code does not work
    //TODO: if Square writes a cancel, this code needs rewriting
    /*
    $cc = get_conf('cc');
    $debug = get_conf('debug');
    if (array_key_exists('square', $debug))
        $squareDebug = $debug['square'];
    else
        $squareDebug = 0;

    $order = new Order([
        'locationId' => $cc['location'],
        'state' => 'CANCELLED',
    ]);

    $body = new CreateOrderRequest([
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
    try {
          if ($squareDebug) sqcc_logObject(array ('Orders API order create', $body), $useLogWrite);
          $apiResponse = $client->orders->create($body);
          $order = $apiResponse->getOrder();
          if ($squareDebug) sqcc_logObject(array ('Orders API order response', $order), $useLogWrite);
      }
      catch (SquareApiException $e) {
          sqcc_logException($source, $e, 'Order API create order Exception', 'Order create failed', $useLogWrite);
      }
      catch (Exception $e) {
          sqcc_logException($source, $e, 'Order API error while calling Square', 'Error connecting to Square', $useLogWrite);
      }
    */
}
// fetch an order to get its details
function cc_fetchOrder($source, $orderId, $useLogWrite = false) : array {
    $cc = get_conf('cc');
    $debug = get_conf('debug');
    if (array_key_exists('square', $debug))
        $squareDebug = $debug['square'];
    else
        $squareDebug = 0;

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
        if ($squareDebug) sqcc_logObject(array ('Orders API order create', $body), $useLogWrite);
        $apiResponse = $client->orders->get($body);
        $order = $apiResponse->getOrder();
        if ($squareDebug) sqcc_logObject(array ('Orders API order response', $order), $useLogWrite);
    }
    catch (SquareApiException $e) {
        sqcc_logException($source, $e, 'Order API create order Exception', 'Order create failed', $useLogWrite);
    }
    catch (Exception $e) {
        sqcc_logException($source, $e, 'Order API error while calling Square', 'Error connecting to Square', $useLogWrite);
    }
    $rtn = array();
    $rtn['totalAmountDue'] = $order->getTotalMoney()->getAmount() / 100;
    $rtn['taxAmount'] = $order->getTotalTaxMoney()->getAmount() / 100;
    $rtn['totalDiscountAmount'] = $order->getTotalDiscountMoney()->getAmount() / 100;
    $rtn['netAmountDue'] = $order->getNetAmountDueMoney()->getAmount() / 100;
    $rtn['netAmount'] = $order->getNetAmounts()->getTotalMoney()->getAmount() / 100;
    $rtn['customerId'] = $order->getCustomerId();

    return $rtn;
}

// enter a payment against an exist order: build the payment, submit it to square and process the resulting payment
function cc_payOrder($ccParams, $buyer, $useLogWrite = false) {
    $con = get_conf('con');
    $cc = get_conf('cc');
    $currency = cc_getCurrency($con);
    $debug = get_conf('debug');
    if (array_key_exists('square', $debug))
        $squareDebug = $debug['square'];
    else
        $squareDebug = 0;

    $source = 'onlinereg';
    if (array_key_exists('source', $ccParams)) {
        $source = $ccParams['source'];
    }

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
    }
    // sanitize the email address to avoid empty and refused
    if ($buyer['email'] == '/r' || $buyer['email'] == null)
        $buyer['email'] = '';
    if ($buyer['phone'] == '/r' || $buyer['phone'] == null)
        $buyer['phone'] = '';

    $sourceId = $ccParams['nonce'];
    $buyerSuppliedMoney = $ccParams['total'] + $ccParams['change'];

    // nonce = card id if card, CASH or EXTERNAL (check, other credit card clearer)
    $pbodyArgs = array(
        'idempotencyKey' => guidv4(),
        'sourceId' => $sourceId,
        'amountMoney' => new Money([
            'amount' => $ccParams['total'] * 100,
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
                'amount' => $buyerSuppliedMoney * 100,
                'currency' => $currency,
                ]),
            'changeBackMoney' => new Money([
                'amount' => $ccParams['change'] * 100,
                'currency' => $currency,
            ]),
        ]);
    }

    if ($sourceId == 'EXTERNAL') {
        $pbodyArgs['externalDetails'] = new Square\Types\ExternalPaymentDetails([
            'type' => $ccParams['externalType'],
            'source' => $ccParams['desc'],
        ]);
    }

    $pbody = new CreatePaymentRequest($pbodyArgs);

    $client = new SquareClient(
        token: $cc['token'],
        options: [
            'baseUrl' => $cc['env'] == 'production' ? Environments::Production->value : Environments::Sandbox->value,
        ]);

    try {
        if ($squareDebug) sqcc_logObject(array ('Payments API create', $pbody), $useLogWrite);
        $apiResponse = $client->payments->create($pbody);
        $payment = $apiResponse->getPayment();
        if ($squareDebug) sqcc_logObject(array ('Payments API Response', $payment), $useLogWrite);
    }
    catch (SquareApiException $e) {
        web_error_log('Order Square API Exception: ' . $e->getMessage());
        $ebody = json_decode($e->getBody(),true);
        $errors = $ebody['errors'];
        if ($errors) {
            if ($squareDebug) sqcc_logObject(array ('Payment returned non-success', $errors), $useLogWrite);
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

                ajaxSuccess(array ('status' => 'error', 'data' => "Payment Error: $msg"));
                exit();
            }
        }

        ajaxSuccess(array ('status' => 'error', 'data' => 'Error: Error connecting to Square'));
        exit();
    }
    catch (Exception $e) {
        sqcc_logException($source, $e, 'Payment API error while calling Square', 'Error connecting to Square', $useLogWrite);
    }
    $id = $payment->getId();
    $approved_amt = ($payment->getApprovedMoney()->getAmount()) / 100;
    $status = $payment->getStatus();
    $last4 = $payment->getCardDetails()->getCard()->getLast4();
    $receipt_url = $payment->getReceiptUrl();
    $auth = $payment->getCardDetails()->getAuthResultCode();
    $desc = 'Square: ' . $payment->getApplicationDetails()->getSquareProduct();
    $txtime = $payment->getCreatedAt();
    $receipt_number = $payment->getReceiptNumber();

    // set category based on if exhibits is a portal type
    if (array_key_exists('exhibits', $ccParams)) {
       $category =  $ccParams['exhibits'];
    } else {
        $category = 'reg';
    }

    $rtn = array();
    $rtn['amount'] = $approved_amt;
    $rtn['txnfields'] = array('transid','type','category','description','source','pretax', 'tax', 'amount',
        'txn_time', 'cc','nonce','cc_txn_id','cc_approval_code','receipt_url','status','receipt_id', 'cashier');
    $rtn['tnxtypes'] = array('i', 's', 's', 's', 's', 'd', 'd', 'd',
            's', 's', 's', 's', 's', 's', 's', 's', 'i');
    $rtn['tnxdata'] = array($ccParams['transid'],'credit',$category,$desc,$source,$ccParams['preTaxAmt'], $ccParams['taxAmt'], $approved_amt,
        $txtime,$last4,$ccParams['nonce'],$id,$auth,$receipt_url,$status,$receipt_number, $loginPerid);
    $rtn['url'] = $receipt_url;
    $rtn['rid'] = $receipt_number;
    $rtn['payment'] = $payment;
    return $rtn;
}

// fetch an order to get its details
function cc_getPayment($source, $paymentid, $useLogWrite = false) : array {
    $cc = get_conf('cc');
    $debug = get_conf('debug');
    if (array_key_exists('square', $debug))
        $squareDebug = $debug['square'];
    else
        $squareDebug = 0;

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
        if ($squareDebug) sqcc_logObject(array ('Payments API get payment', $body), $useLogWrite);
        $apiResponse = $client->payments->get($body);
        $payment = $apiResponse->getPayment();
        if ($squareDebug) sqcc_logObject(array ('Payments API get payment', $payment), $useLogWrite);
    }
    catch (SquareApiException $e) {
        sqcc_logException($source, $e, 'Payments API get payment Exception', 'get payment failed', $useLogWrite);
    }
    catch (Exception $e) {
        sqcc_logException($source, $e, 'Payments API error while calling Square', 'Error connecting to Square', $useLogWrite);
    }

    return $payment;
}

function sqcc_logObject($objArray, $useLogWrite = false) : void {
    if ($useLogWrite) {
        logWrite($objArray);
    } else {
        web_error_log($objArray[0]);
        var_error_log($objArray[1]);
    }
}

function sqcc_logException($name, $e, $message, $ajaxMessage, $useLogWrite = false) : void {
    web_error_log("$message:" . $e->getMessage());
    $ebody = json_decode($e->getBody(), true);
    $errors = $ebody['errors'];
    if ($errors) {
        if ($useLogWrite) {
            logWrite("$message: returned non-success");
        }
        web_error_log("$message: returned non-success");
        foreach ($errors as $error) {
            $cat = $error['category'];
            $code = $error['code'];
            $detail = $error['detail'];
            if ($useLogWrite) {
                logWrite("Name: $name, Cat: $cat: Code $code, Detail: $detail");
            }
            web_error_log("Name: $name, Cat: $cat: Code $code, Detail: $detail");
            exit();
        }
    }
    ajaxSuccess(array ('status' => 'error', 'data' => "Error: $ajaxMessage, see logs."));
    exit();
}