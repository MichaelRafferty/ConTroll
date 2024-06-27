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

use Square\SquareClient;
use Square\Exceptions\ApiException;
use Square\Http\ApiResponse;
use Square\Models\CreateOrderRequest;
use Square\Models\CreateOrderResponse;
use Square\Models\Order;
use Square\Models\OrderSource;
use Square\Models\OrderLineItem;
use Square\Models\OrderLineItemDiscount;
use Square\Models\OrderLineItemDiscountType;
use Square\Models\Currency;
use Square\Models\Money;
use Square\Models\CreatePaymentRequest;
use Square\Models\CreatePaymentResponse;


function cc_charge_purchase($results, $ccauth, $useLogWrite=false) {
    $cc = get_conf('cc');
    $con = get_conf('con');
    $client = new SquareClient([
        'accessToken' => $cc['token'],
        'squareVersion' => $cc['apiversion'],
        'environment' => $cc['env'],
    ]);

    if (isset($_SESSION)) {
        if (array_key_exists('user_perid', $_SESSION)) {
            $user_perid = $_SESSION['user_perid'];
        } else {
            $user_perid = null;
        }
    } else {
        $user_perid = null;
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
    // failure fall throughx

    // base order
    $body = new CreateOrderRequest;
    $body->setIdempotencyKey(guidv4());
    $body->setOrder(new Order($cc['location']));
    $order = $body->getOrder();
    $order->setLocationId( $cc['location']);
    $order->setReferenceId($con['id'] . '-' . $results['transid']);
    $order->setSource(new OrderSource);
    $order->getSource()->setName($con['conname'] . 'OnLineReg');

    if (array_key_exists('custid', $results)) {
        $custid = $results['custid'];
    } else if (array_key_exists('badges', $results) && is_array($results['badges']) && count($results['badges']) > 0) {
        $custid = 'r-' . $results['badges'][0]['badge'];
    } else if (array_key_exists('spaceName', $results) && array_key_exists('vendorId', $results)) {
        $custid = 'e-' . $results['vendorId'];
    } else {
        $custid = 't-' + $results['transid'];
    }
    $order->setCustomerId($con['id'] . '-' . $custid);
    $order_lineitems = [];
    $lineid = 0;

    // add order lines

    if (array_key_exists('badges', $results) && is_array($results['badges']) && count($results['badges']) > 0) {
        foreach ($results['badges'] as $badge) {
            if (array_key_exists('fullname', $badge))
                $fullname = $badge['fullname'];
            else
                $fullname = trim(trim($badge['fname'] . ' ' . $badge['mname']) . ' ' . $badge['lname']);
            $item = new OrderLineItem ('1');
            $item->setUid('badge' . ($lineid + 1));
            $item->setName($badge['age'] . ' Membership for ' . $fullname);
            $item->setNote($badge['memId'] . ': Membership Type Code');
            $item->setBasePriceMoney(new Money);
            $item->getBasePriceMoney()->setAmount($badge['price'] * 100);
            $item->getBasePriceMoney()->setCurrency(Currency::USD);
            $order_lineitems[$lineid] = $item;
            $lineid++;
        }
    }
    if (array_key_exists('spaceName', $results)) {
        $item = new OrderLineItem ('1');
        $item->setUid('exhibits-space');
        $item->setName($results['spaceName'] . ':' . mb_substr($results['spaceDescription'], 0, 128));
        $item->setBasePriceMoney(new Money);
        $item->getBasePriceMoney()->setAmount($results['spacePrice'] * 100);
        $item->getBasePriceMoney()->setCurrency(Currency::USD);
        $order_lineitems[$lineid] = $item;
        $lineid++;
    }

    $order->setLineItems($order_lineitems);

    // now apply the coupon
    if (array_key_exists('discount', $results) && $results['discount'] > 0) {
        $item = new OrderLineItemDiscount ();
        $item->setUid('couponDiscount');
        if (array_key_exists('coupon', $results) && $results['coupon'] != null) {
            $coupon = $results['coupon'];
            $couponName = 'Coupon: ' . $coupon['code'] . ' (' . $coupon['name'] . ')';
        } else {
            $couponName = 'Coupon Applied';
        }
        $item->setName($couponName);
        $item->setType(OrderLineItemDiscountType::FIXED_AMOUNT);
        $money = new Money;
        $money->setAmount($results['discount'] * 100);
        $money->setCurrency(Currency::USD);
        $item->setAmountMoney($money);
        $item->setScope(\Square\Models\OrderLineItemDiscountScope::ORDER);
        $order->setDiscounts(array($item));
    }

    // pass order to square and get order id

    try {
        $ordersApi = $client->getOrdersApi();
//        if ($useLogWrite) {
//            logWrite(array('ordersApi'=>$ordersApi, 'body'=>$body));
//        } else {
//            web_error_log("ordersApi"); var_error_log($ordersApi);
//            web_error_log("body"); var_error_log($body);
//        }
        
        $apiResponse = $ordersApi->createOrder($body);

//        if ($useLogWrite) {
//            logWrite(array('apiResponse'=>$apiResponse));
//        } else {
//            web_error_log("apiResponse");
//            var_error_log($apiResponse);
//        }
        
        if ($apiResponse->isSuccess()) {
            $crerateorderresponse = $apiResponse->getResult();

//            if ($useLogWrite) {
//                logWrite(array('order: success' => $crerateorderresponse));
//            } else {
//                web_error_log("order: success");
//                var_error_log($crerateorderresponse);
//            }
        } else {
            $errors = $apiResponse->getErrors();
            if ($useLogWrite) {
                logWrite(array('ordersApi' => 'Order returned non-success'));
            } else {
                web_error_log('Order returned non-success');
            }
            
            $errorreturn = null;
            foreach ($errors as $error) {
                if ($useLogWrite) {
                    logWrite(array('Category' => $error->getCategory(), 'Code' => $error->getCode(), 'Detail' => $error->getDetail(), 'Field' => $error->getField()));
                } else {
                    var_error_log("Cat: " . $error->getCategory() . ": Code " . $error->getCode() . ". Detail: " . $error->getDetail() . ", [" . $error->getField() . "]");
                }
                if ($errorreturn == null)
                    $errorreturn = array('status'=>'error','data'=>"Order Error: " . $error->getCategory() . "/" . $error->getCode() . ": " . $error->getDetail() . "[" . $error->getField() . "]");
            }
            if ($errorreturn == null)
                $errorreturn = array('status' => 'error', 'data' => 'UnknownOrder Error');
            ajaxSuccess($errorreturn);
            exit();
        }
    } catch (ApiException $e) {
        if ($useLogWrite) {
            logWrite('Order received error while calling Square: ' . $e->getMessage());
        } else {
            web_error_log("Order received error while calling Square: " . $e->getMessage());
        }
        ajaxSuccess(array('status'=>'error','data'=>"Error: Error connecting to Square"));
        exit();
    }

    $corder = $crerateorderresponse->getOrder();

    $payuuid = guidv4();
    $pay_money = new Money;
    $pay_money->setAmount($results['total'] * 100);
    $pay_money->setCurrency(Currency::USD);

//    if ($useLogWrite) {
//        logWrite(array('CALLED WITH' => $results['total'], 'pay_money' => $pay_money));
//    } else {
//        web_error_log("CALLED WITH " . $results['total']);
//        var_error_log($pay_money);
//    }
        
    $pbody = new CreatePaymentRequest($results['nonce'], $payuuid);
    $pbody->setAmountMoney($pay_money);
    $pbody->setAutocomplete(true);
    $pbody->setOrderID($corder->getId());
    $pbody->setCustomerId($con['id'] . '-' . $custid);
    $pbody->setLocationId($cc['location']);
    $pbody->setReferenceId($con['id'] . '-' . $results['transid']);
    $pbody->setNote('On-Line Registration');

//var_error_log($pbody);

    try {
        $paymentsApi = $client->getPaymentsApi();
        $apiResponse = $paymentsApi->createPayment($pbody);

        if ($apiResponse->isSuccess()) {
            $createPaymentResponse = $apiResponse->getResult();
//            if ($useLogWrite) {
//                logWrite(array('payment: success' => $createPaymentResponse));
//            } else {
//                web_error_log("payment: success");
//                var_error_log($createPaymentResponse);
//            }
        } else {
            $errors = $apiResponse->getErrors();
            if ($useLogWrite) {
                logWrite('Payment returned non-success');
            } else {
                web_error_log("Payment returned non-success");
            }
            foreach ($errors as $error) {
                $cat = $error->getCategory();
                $code = $error->getCode();
                $detail = $error->getDetail();
                $field = $error->getField();
                if ($useLogWrite) {
                    logWrite('Transid: ' . $results['transid'] . " Cat: $cat: Code $code, Detail: $detail [$field]");
                } else {
                    web_error_log("Transid: " . $results['transid'] . " Cat: $cat: Code $code, Detail: $detail [$field]");
                }
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
                } else {
                    web_error_log("Square card payment error for " . $results['transid'] . " of $msg");
                }
                ajaxSuccess(array('status'=>'error','data'=>"Payment Error: $msg"));
                exit();
            }
            if ($useLogWrite) {
                logWrite('Square card payment error for ' . $results['transid'] . " of 'unknown'");
            } else {
                web_error_log("Square card payment error for " . $results['transid'] . " of 'unknown'");
            }
            ajaxSuccess(array('status'=>'error','data'=>"Unknown Payment Error"));
            exit();
        }
    } catch (ApiException $e) {
        if ($useLogWrite) {
            logWrite('Payment received error while calling Square: ' . $e->getMessage());
        } else {
            web_error_log('Payment received error while calling Square: ' . $e->getMessage());
        }
        ajaxSuccess(array('status'=>'error','data'=>"Error: Error connecting to Square"));
        exit();
    }

    $payment = $createPaymentResponse->getPayment();
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
    if (array_key_exists('exhibits', $results)) {
        if ($results['exhibits'] == 'vendor')
            $category = 'vendor';
        else
            $category = 'artshow';
    } else {
        $category = 'reg';
    }

    $rtn = array();
    $rtn['amount'] = $approved_amt;
    $rtn['txnfields'] = array('transid','type','category','description','source','amount',
        'txn_time', 'cc','nonce','cc_txn_id','cc_approval_code','receipt_url','status','receipt_id', 'cashier');
    $rtn['tnxtypes'] = array('i', 's', 's', 's', 's', 'd',
            's', 's', 's', 's', 's', 's', 's', 's', 'i');
    $rtn['tnxdata'] = array($results['transid'],'credit',$category,$desc,'online',$approved_amt,
        $txtime,$last4,$results['nonce'],$id,$auth,$receipt_url,$status,$receipt_number, $user_perid);
    $rtn['url'] = $receipt_url;
    $rtn['rid'] = $receipt_number;
    return $rtn;
};
?>
