9<?php
global $db_ini;
require_once "lib/base.php";
require_once "../lib/term__load_methods.php";
require_once('../lib/log.php');
//initialize google session
$need_login = google_init("page");

$page = "admin";
if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page("index.php");
}

if (array_key_exists('user_id', $_SESSION)) {
    $user_id = $_SESSION['user_id'];
} else {
    bounce_page('index.php');
    return;
}

$cdn = getTabulatorIncludes();
page_init($page,
    /* css */ array($cdn['tabcss'],
                    //$cdn['tabbs5'],
                    'css/base.css',
                   ),
    /* js  */ array( //$cdn['luxon'],
                    $cdn['tabjs'],
                   ),
              $need_login);
$con = get_conf("con");
$conid=$con['id'];
$debug = get_conf('debug');
$cc = get_conf('cc');

$log = get_conf('log');
logInit($log['term']);

if (!array_key_exists('action', $_REQUEST)) {
    echo "No action provided.\n";
    page_foot($page);
    exit();
}

if (array_key_exists('name', $_REQUEST)) {
    $name = $_REQUEST['name'];
} else {
    $name = 'test';
}

load_term_procs();

switch ($_REQUEST['action']) {
    case 'echo':
        echo "test harness startup\n";
        break;

    case 'list':
        echo "contents of terminals table\n<PRE>\n";
        var_dump(listTerminals());
        echo "</pre>\n";
        break;

    case 'create':
        if (array_key_exists('location' . $name, $cc))
            $location = $cc['location' . $name];
        else
            $location = $cc['location'];

        echo "<pre>calling createDeviceCode($name, $location)\n";
        $terminal = term_createDeviceCode($name, $location, true);
        var_dump($terminal);
        echo addTerminal($terminal);
        echo "\nnew contents of terminals table\n";
        var_dump(listTerminals());
        echo "</pre>\n";
        break;

    case 'get':
        $terminal = term_getDevice($name, true);
        echo "\nTerminal code status:\n<pre>";
        var_dump($terminal);
        echo "</pre>\n";
        echo updateTerminal($terminal);
        break;

    case 'status':
        $terminal = term_getStatus($name, true);
        echo "\nTerminal full status:\n<pre>";
        var_dump($terminal);
        echo "</pre>\n";
        break;

    case 'pay':
        if (array_key_exists('order', $_REQUEST)) {
            $order = $_REQUEST['order'];
        } else
            $order = null;
        if (array_key_exists('amount', $_REQUEST)) {
            $amount = $_REQUEST['amount'];
        } else
            $amount = null;

        if ($order == null || $amount == null) {
            $order = buildOrder($name);
        }
        $response = term_payOrder($name, $order, $amount,true);
        echo "\npay request status:\n<pre>";
        var_dump($response);
        echo "</pre>\n";
        break;

    case 'paystatus':
        if (array_key_exists('pay', $_REQUEST))
            $payRef = $_REQUEST['pay'];
        else {
            echo "pay required, missing\n";
            break;
        }
        $response = term_getPayStatus($name, $payRef, true);
        echo "\npay status:\n<pre>";
        var_dump($response);
        echo "</pre>\n";
        break;

    default:
        echo "Unknown action requested.\n";
}
?>
<div id='result_message' class='mt-4 p-2'></div>
<pre id='test'></pre>
<?php
page_foot($page);


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

function buildOrder($name) {
    $terminal = getTerminal($name);
    if (!$terminal) {
        echo "terminal $name not found.\n";
        return null;
    }

    $orderLineItems = [];
    $order_value = 0;
    $lineid = 1;
    $item = new OrderLineItem ([
           'itemType' => OrderLineItemItemType::Item->value,
           'uid' =>  "test.$lineid",
           'name' => "Test Item $lineid Membership for Test User",
           'quantity' => 1,
           'note' => "This is the note field for $lineid",
           'basePriceMoney' => new Money([
                 'amount' => floor($lineid * 7.55 * 100),
                 'currency' => 'USD',
             ]),
       ]);
    $orderLineItems[] = $item;
    $order_value += floor($lineid * 7.55 * 100);
    $lineid++;
    $item = new OrderLineItem ([
       'itemType' => OrderLineItemItemType::Item->value,
       'uid' =>  "test.$lineid",
       'name' => "Test Item $lineid Donation for Test User",
       'quantity' => 1,
       'note' => "This is the note field for $lineid",
       'basePriceMoney' => new Money([
             'amount' => floor($lineid * 7.55 * 100),
             'currency' => 'USD',
         ]),
   ]);
    $orderLineItems[] = $item;
    $order_value += floor($lineid * 7.55 * 100);

    $order = new Order([
       'locationId' => $terminal['locationId'],
       'referenceId' => "testOrder " . time(),
       'source' => new OrderSource([
               'name' => 'Test Order from TermTest',
           ]),
       'customerId' => 'Test1234',
       'lineItems' => $orderLineItems,
    ]);

    // build the order request from it's parts
    $body = new CreateOrderRequest([
       'idempotencyKey' => guidv4(),
       'order' => $order,
    ]);

    $cc = get_conf('cc');
    $client = new SquareClient(
    token: $cc['token'],
    options: [
               'baseUrl' => $cc['env'] == 'production' ? Environments::Production->value : Environments::Sandbox->value,
           ]);
    try {
        $apiResponse = $client->orders->create($body);
        // convert the object into an associative array
        $orderReturn = $apiResponse->getOrder();
    }
    catch (SquareApiException $e) {
        sqterm_logException($name, $e, 'create order Exception', 'Terminal API get device by id failed', true);
    }
    catch (Exception $e) {
        sqterm_logException($name, $e, 'create order received error while calling Square', 'Error connecting to Square', true);
    }
    echo "\n<pre>";
    var_dump($orderReturn);
    echo "</pre>\n";
    return $orderReturn;
}
?>
