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

function draw_cc_html($cc, $postal_code = "--") {
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
use Square\Exceptions\SquareException;
use Square\Payments\Requests\CreatePaymentRequest;
use Square\Types\CashPaymentDetails;
use Square\Orders;
use Square\Orders\OrdersClient;
use Square\Types;
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

// charge the purchase making a customer, order, and payment
//TODO Need to add the tax section to SQUARE, need to lookup how to do this in the API, right now it expects tax to be 0 passed in.
function cc_charge_purchase($results, $buyer, $useLogWrite=false) {
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
    if (array_key_exists('currency', $con)) {
        switch (strtolower($con['currency'])) {
            case 'usd':
                $currency = Currency::Usd->value;
                break;
            case 'cad':
                $currency = Currency::Cad->value;
                break;
            default:
                $cur = Currency::tryFrom($con['currency']);
                if ($cur) {
                    $currency = $cur->value;
                    break;
                }
                ajaxSuccess(array ('status' => 'error', 'data' => 'Error: Currency ' . $con['currency'] .
                    ' not yet supported in cc_square, seek assistance.'));
                exit();
        }
    } else
        $currency = Currency::Usd->value;

    $loginPerid = getSessionVar('user_perid');
    if ($loginPerid == null) {
        $userType = getSessionVar('idType');
        if ($userType == 'p')
            $loginPerid = getSessionVar('id');
    }

    // square api steps
    // 1. create order record and price it
    //  a. create order top level container
    //  b. add line items
    //  c. pass order to order end point and get order id
    // 2. create payment
    //  a. create payment object with order id and payment amount plus credit card nonce
    //  b. pass payment to payment processor
    // 3. parse return results to return the proper information
    // failure fall through

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

    // order lines
    $order_lineitems = [];
    $orderDiscounts = [];
    $lineid = 0;
        $order_value = 0;
    if (array_key_exists('planPayment', $results))
        $planPayment = $results['planPayment'];
    else
        $planPayment = 0;

    $planName = '';
    $planId = '';
    $downPmt = '';
    $nonPlanAmt = '';
    $balanceDue = '';
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
        }
    }

    if ($planPayment == 0) {
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
                    $amount = $badge['balDue'] * 100;
                } else {
                    $amount = $badge['price'] * 100;
                }

                $item = new OrderLineItem ([
                    'itemType' => OrderLineItemItemType::Item->value,
                    'uid' => 'badge' . ($lineid + 1),
                    'name' => $badge['age'] . ' Membership for ' . $fullname,
                    'quantity' => 1,
                    'note' => $note,
                    'basePriceMoney' => new Money([
                        'amount' => $amount,
                        'currency' => $currency,
                    ]),
                ]);
                $order_lineitems[$lineid] = $item;
                $order_value += $badge['price'];
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
                $order_lineitems[$lineid] = $item;
                $order_value += $space['approved_price'];
                $lineid++;
            }
        }

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
            $order_value -= $results['discount'];
        }

        // if a plan, set a discount called deferred payment for plan to the amount not in this payment
        if (array_key_exists('newplan', $results) && $results['newplan'] == 1) {
            // deferment is total of the items - total of the payment
            $deferment = $order_value - $results['total'];
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
                'scope' => OrderLineItemDiscountScope::Order->value,
            ]);
            $orderDiscounts[] = $item;
        }
    } else {
        // this is a plan payment make the order just the plan payment
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
        $order_lineitems[$lineid] = $item;
    }

    // order item from it's line items
    $order = new Order([
        'locationId' => $cc['location'],
        'referenceId' => $con['id'] . '-' . $results['transid'],
        'source' => new OrderSource([
            'name' => $con['conname'] . '-' . $source
        ]),
        'customerId' => $con['id'] . '-' . $custid,
        'lineItems' => $order_lineitems,
        'discounts' => $orderDiscounts,
    ]);

    $testLineItems = $order->getLineItems();

    // build the order request from it's parts
    $body = new CreateOrderRequest([
        'idempotencyKey' => guidv4(),
        'order' => $order,
    ]);

    // pass order to square and get order id

    try {
        if ($squareDebug) {
            if ($useLogWrite) {
                logWrite(array('message' => 'Orders API order create', 'order'=>$body));
            } else {
                web_error_log("orders api order create"); var_error_log($body);
            }
        }
        $apiResponse = $client->orders->create($body);

        if ($squareDebug) {
            if ($useLogWrite) {
                logWrite(array ('order apiResponse' => $apiResponse));
            }
            else {
                web_error_log("order apiResponse");
                var_error_log($apiResponse);
            }
        }

       if ($errors = $apiResponse->getErrors()) {
            if ($useLogWrite) {
                logWrite(array ('ordersApi' => 'Order returned non-success'));
            }
            web_error_log('Order returned non-success');

            $errorreturn = null;
            foreach ($errors as $error) {
                if ($useLogWrite) {
                    logWrite(array ('Category' => $error->getCategory(), 'Code' => $error->getCode(), 'Detail' => $error->getDetail(), 'Field' => $error->getField()));
                }
                var_error_log("Cat: " . $error->getCategory() . ": Code " . $error->getCode() . ". Detail: " . $error->getDetail() . ", [" . $error->getField() . "]");

                if ($errorreturn == null)
                    $errorreturn = array ('status' => 'error', 'data' => "Order Error: " . $error->getCategory() . "/" . $error->getCode() . ": " . $error->getDetail() . "[" . $error->getField() . "]");
            }
            if ($errorreturn == null)
                $errorreturn = array ('status' => 'error', 'data' => 'UnknownOrder Error');
            ajaxSuccess($errorreturn);
            exit();
        }
    }
    catch (SquareApiException $e) {
        web_error_log('Order Square API Exception: ' . $e->getMessage());
        ajaxSuccess(array ('status' => 'error', 'data' => 'Error: Error connecting to Square'));
        exit();
    }
    catch (ApiException $e) {
        if ($useLogWrite) {
            logWrite('Order received error while calling Square: ' . $e->getMessage());
        }
        web_error_log("Order received error while calling Square: " . $e->getMessage());

        ajaxSuccess(array('status'=>'error','data'=>"Error: Error connecting to Square"));
        exit();
    }

    $order = $apiResponse->getOrder();

    if ($squareDebug) {
        if ($useLogWrite) {
            logWrite(array ('CALLED WITH' => $results['total']));
        } else {
            web_error_log("CALLED WITH " . $results['total']);
        }
    }
    // sanitize the email address to avoid empty and refused
    if ($buyer['email'] == '/r' || $buyer['email'] == null)
        $buyer['email'] = '';
    if ($buyer['phone'] == '/r' || $buyer['phone'] == null)
        $buyer['phone'] = '';

    $pbodyArgs = array(
        'idempotencyKey' => guidv4(),
        'sourceId' => $results['nonce'],
        'amountMoney' => new Money([
                                       'amount' => $results['total'] * 100,
                                       'currency' => $currency,
                                       'orderId' => $order->getId(),
                                   ]),
        'autocomplete' => true,
        'customerId' => $order->getCustomerId(),
        'locationId' => $order->getLocationId(),
        'referenceId' => $order->getReferenceId(),
        'note' => 'Online payment from ' . $source
    );
    if ($buyer['email'] != '')
        $pbodyArg['buyerEmailAddress'] = $buyer['email'];
    if ($buyer['phone'] != '') {
        $phone = phoneNumberNormalize($buyer);
        if ($phone != '')
            $pbodyArgs['buyerPhoneNumber'] = $phone;
    }

    $pbody = new CreatePaymentRequest($pbodyArgs);
    if ($squareDebug) {
        if ($useLogWrite) {
            logWrite(array ('payment' => $pbody));
        }
        else {
            web_error_log('payment:');
            var_error_log($pbody);
        }
    }

    try {
        $apiResponse = $client->payments->create($pbody);
        if ($squareDebug) {
            if ($useLogWrite) {
                logWrite(array ('payment: apiresponse' => $apiResponse));
            }
            else {
                web_error_log("payment: api respomnse");
                var_error_log($apiResponse);
            }
        }
        if ($errors = $apiResponse->getErrors()) {
            if ($useLogWrite) {
                logWrite('Payment returned non-success');
            }
            web_error_log("Payment returned non-success");
            foreach ($errors as $error) {
                $cat = $error->getCategory();
                $code = $error->getCode();
                $detail = $error->getDetail();
                $field = $error->getField();
                if ($useLogWrite) {
                    logWrite('Transid: ' . $results['transid'] . " Cat: $cat: Code $code, Detail: $detail [$field]");
                }
                web_error_log("Transid: " . $results['transid'] . " Cat: $cat: Code $code, Detail: $detail [$field]");

                switch ($code) {
                    case "GENERIC_DECLINE":
                        $msg = "Card Declined";
                        break;
                    case "CVV_FAILURE":
                        $msg = "Authorization error: Invalid CVV";
                        break;
                    case "ADDRESS_VERIFICATION_FAILURE":
                        $msg = "Address Verification Failure: Zip Code";
                        break;
                    case "INVALID_EXPIRATION":
                        $msg = "Authorization error: Invalid Expiration Date";
                        break;
                    default:
                        $msg = $code;
                }
                if ($useLogWrite) {
                    logWrite("Square card payment error for " . $results['transid'] . " of $msg");
                }
                web_error_log("Square card payment error for " . $results['transid'] . " of $msg");

                ajaxSuccess(array('status'=>'error','data'=>"Payment Error: $msg"));
                exit();
            }
            if ($useLogWrite) {
                logWrite('Square card payment error for ' . $results['transid'] . " of 'unknown'");
            }
            web_error_log("Square card payment error for " . $results['transid'] . " of 'unknown'");
            ajaxSuccess(array('status'=>'error','data'=>"Unknown Payment Error"));
            exit();
        }
    }
    catch (SquareApiException $e) {
        web_error_log('Order Square API Exception: ' . $e->getMessage());
        $ebody = json_decode($e->getBody(),true);
        $errors = $ebody['errors'];
        if ($errors) {
            if ($useLogWrite) {
                logWrite('Payment returned non-success');
            }
            web_error_log('Payment returned non-success');
            foreach ($errors as $error) {
                $cat = $error['category'];
                $code = $error['code'];
                $detail = $error['detail'];
                if ($useLogWrite) {
                    logWrite('Transid: ' . $results['transid'] . " Cat: $cat: Code $code, Detail: $detail");
                }
                web_error_log('Transid: ' . $results['transid'] . " Cat: $cat: Code $code, Detail: $detail");

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
                    logWrite('Square card payment error for ' . $results['transid'] . " of $msg");
                }
                web_error_log('Square card payment error for ' . $results['transid'] . " of $msg");

                ajaxSuccess(array ('status' => 'error', 'data' => "Payment Error: $msg"));
                exit();
            }
        }

        ajaxSuccess(array ('status' => 'error', 'data' => 'Error: Error connecting to Square'));
        exit();
    }
    catch (ApiException $e) {
        if ($useLogWrite) {
            logWrite('Payment received error while calling Square: ' . $e->getMessage());
        } else {
            web_error_log('Payment received error while calling Square: ' . $e->getMessage());
        }
        ajaxSuccess(array('status'=>'error','data'=>"Error: Error connecting to Square"));
        exit();
    }

    $payment = $apiResponse->getPayment();
    $approved_amt = ($payment->getApprovedMoney()->getAmount()) / 100;
    $status = $payment->getStatus();
    $last4 = $payment->getCardDetails()->getCard()->getLast4();
    $receipt_url = $payment->getReceiptUrl();
    $auth = $payment->getCardDetails()->getAuthResultCode();
    $desc = 'Square: ' . $payment->getApplicationDetails()->getSquareProduct();
    $txtime = $payment->getCreatedAt();
    $receipt_number = $payment->getReceiptNumber();

    // set category based on if exhibits is a portal type
    if (array_key_exists('exhibits', $results)) {
       $category =  $results['exhibits'];
    } else {
        $category = 'reg';
    }

    $rtn = array();
    $rtn['amount'] = $approved_amt;
    $rtn['txnfields'] = array('transid','type','category','description','source','pretax', 'tax', 'amount',
        'txn_time', 'cc','nonce','cc_txn_id','cc_approval_code','receipt_url','status','receipt_id', 'cashier');
    $rtn['tnxtypes'] = array('i', 's', 's', 's', 's', 'd', 'd', 'd',
            's', 's', 's', 's', 's', 's', 's', 's', 'i');
    $rtn['tnxdata'] = array($results['transid'],'credit',$category,$desc,'online',$results['pretax'], $results['tax'], $approved_amt,
        $txtime,$last4,$results['nonce'],$id,$auth,$receipt_url,$status,$receipt_number, $loginPerid);
    $rtn['url'] = $receipt_url;
    $rtn['rid'] = $receipt_number;
    $rtn['body'] = $body;
    $rtn['pbody'] = $pbody;
    return $rtn;
};
?>
