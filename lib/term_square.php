
<?php
//  term_square.php - library of modules to add talk to and control the Square Terminal
// uses config variables:
// [cc]
// type=square - selects that reg is to use square for credit cards
// appid=[APPID] - appliction ID from the square developer portal, be it sandbox or production
// token=[TOKEN] - auth token from the square developer portal
// locationName=[LOCATION] - location id from the square developer portal for this terminal name
// does not currently use any other config sections for credit card other than [cc]

require_once("global.php");

use Square\Environments;
use Square\SquareClient;
use Square\Devices;
use Square\Exceptions\SquareApiException;
use Square\Devices\Codes\Requests;
use Square\Types\DeviceCode;
use Square\Types\Money;
use Square\Types\CreateTerminalCheckoutResponse;

function term_createDeviceCode($name, $locationId, $useLogWrite = false) : array | null {
    $cc = get_conf('cc');
    $debug = get_conf('debug');
    if (array_key_exists('square', $debug))
        $squareDebug = $debug['square'];
    else
        $squareDebug = 0;

    // get a client
    $client = new SquareClient(
        token: $cc['token'],
        options: [
                   'baseUrl' => $cc['env'] == 'production' ? Environments::Production->value : Environments::Sandbox->value,
               ]);

    // pass create to square
    $body = new Requests\GetCodesRequest([
        'deviceCode' => new DeviceCode([
            'name' => $name,
            'locationId' => $locationId,
            'productType' => 'TERMINAL_API',
        ]),
    ]);

    try {
        if ($squareDebug) sqterm_logObject(array ('Terminal API create device', $body), $useLogWrite);
        $apiResponse = $client->devices->codes->get($body);
        if ($squareDebug) sqterm_logObject(array ('Terminal API create device: apiResponse', $apiResponse), $useLogWrite);

        // convert the object into an associative array
        $terminal = json_decode(json_encode($apiResponse->getDeviceCode()), true);
        return $terminal;
    }
    catch (SquareApiException $e) {
        sqterm_logException($name, $e, 'Terminal Square API create device Exception', 'Terminal API create device failed', $useLogWrite);
    }
    catch (Exception $e) {
        sqterm_logException($name, $e, 'Terminal received error while calling Square', 'Error connecting to Square', $useLogWrite);
    }

    return null;
}

function term_getDevice($name, $useLogWrite = false) : array | null {
    $cc = get_conf('cc');
    $debug = get_conf('debug');
    if (array_key_exists('square', $debug))
        $squareDebug = $debug['square'];
    else
        $squareDebug = 0;

    // get the device name
    $terminal = getTerminal($name);
    if (!$terminal)
        return array("Error" => "Device $name not found");

    // get a client
    $client = new SquareClient(
        token: $cc['token'],
        options: [
                   'baseUrl' => $cc['env'] == 'production' ? Environments::Production->value : Environments::Sandbox->value,
               ]);

    $body = new Requests\GetCodesRequest(['id' => $terminal['squareId'], ]);

    try {
        if ($squareDebug) sqterm_logObject(array ('Terminal API get device code', $body), $useLogWrite);
        $apiResponse = $client->devices->codes->get($body);
        if ($squareDebug) sqterm_logObject(array ('Terminal API get device code: apiResponse', $apiResponse), $useLogWrite);

        // convert the object into an associative array
        $terminal = json_decode(json_encode($apiResponse->getDeviceCode()), true);
        return $terminal;
    }
    catch (SquareApiException $e) {
        sqterm_logException($name, $e, 'Terminal Square API get device Exception', 'Terminal API get device code failed', $useLogWrite);
    }
    catch (Exception $e) {
        sqterm_logException($name, $e, 'Terminal received error while calling Square', 'Error connecting to Square', $useLogWrite);
    }

    return null;
}

function term_getStatus($name, $useLogWrite = false) : array | null {
    $cc = get_conf('cc');
    $debug = get_conf('debug');
    if (array_key_exists('square', $debug))
        $squareDebug = $debug['square'];
    else
        $squareDebug = 0;

    // get the device name
    $terminal = getTerminal($name);
    // get a client
    $client = new SquareClient(
        token: $cc['token'],
        options: [
                   'baseUrl' => $cc['env'] == 'production' ? Environments::Production->value : Environments::Sandbox->value,
               ]);

    // pass get device status to square
    $body = new Devices\Requests\GetDevicesRequest([
        'deviceId' => 'device:' . $terminal['deviceId'],
    ]);

    try {
        if ($squareDebug) sqterm_logObject(array ('Terminal API get device by id', $body), $useLogWrite);
        $apiResponse = $client->devices->get($body);
        if ($squareDebug) sqterm_logObject(array ('Terminal API get device by id: apiResponse', $apiResponse), $useLogWrite);

        // convert the object into an associative array
        $terminal = json_decode(json_encode($apiResponse->getDevice()), true);
        return $terminal;
    }
    catch (SquareApiException $e) {
        sqterm_logException($name, $e, 'Terminal Square API get device by id Exception', 'Terminal API get device by id failed', $useLogWrite);
    }
    catch (Exception $e) {
        sqterm_logException($name, $e, 'Terminal received error while calling Square', 'Error connecting to Square', $useLogWrite);
    }

    return null;
}

function term_payOrder($name, $orderId, $amount, $useLogWrite = false) : array | null {
    $cc = get_conf('cc');
    $con = get_conf('con');
    $debug = get_conf('debug');
    if (array_key_exists('square', $debug))
        $squareDebug = $debug['square'];
    else
        $squareDebug = 0;

    $currency = cc_getCurrency($con);

    // get the device name
    $terminal = getTerminal($name);
    // get a client
    $client = new SquareClient(
        token: $cc['token'],
        options: [
                   'baseUrl' => $cc['env'] == 'production' ? Environments::Production->value : Environments::Sandbox->value,
               ]);

    $payRequest = new Square\Terminal\Checkouts\Requests\CreateTerminalCheckoutRequest([
        'idempotencyKey' => guidv4(),
        'checkout' => new Square\Types\TerminalCheckout([
            'amountMoney' => new Money([
                'amount' => $amount,
                'currency' => $currency,
            ]),
            'note' => 'Payment Note for ' . time(),
            'orderId' => $orderId,
            'referenceId' => 'testOrder',
            'deviceOptions' => new Square\Types\DeviceCheckoutOptions([
                'deviceId' => $terminal['deviceId'],
                'showItemizedCart' => true,
            ]),
        ]),
    ]);

    try {
        if ($squareDebug) sqterm_logObject(array ('Terminal API pay request', $payRequest), $useLogWrite);
        $apiResponse = $client->terminal->checkouts->create($payRequest);
        if ($squareDebug) sqterm_logObject(array ('Terminal API pay request: apiResponse', $apiResponse), $useLogWrite);

        // convert the object into an associative array
        $checkout = json_decode(json_encode($apiResponse->getCheckout()), true);
        return $checkout;
    }
    catch (SquareApiException $e) {
        sqterm_logException($name, $e, 'Terminal Square API pay request Exception', 'Terminal API pay order failed', $useLogWrite);
    }
    catch (Exception $e) {
        sqterm_logException($name, $e, 'Terminal received error while calling Square', 'Error connecting to Square', $useLogWrite);
    }

    return null;
}

function term_getPayStatus($name, $payRef, $useLogWrite = false) : array | null {
    $cc = get_conf('cc');
    $debug = get_conf('debug');
    if (array_key_exists('square', $debug))
        $squareDebug = $debug['square'];
    else
        $squareDebug = 0;

    // get the device name
    $terminal = getTerminal($name);
    // get a client
    $client = new SquareClient(
        token: $cc['token'],
        options: [
                   'baseUrl' => $cc['env'] == 'production' ? Environments::Production->value : Environments::Sandbox->value,
               ]);

    $statusRequest = new Square\Terminal\Checkouts\Requests\GetCheckoutsRequest([
    'checkoutId' => $payRef,
    ]);

    try {
        if ($squareDebug) sqterm_logObject(array ('Terminal API pay status', $statusRequest), $useLogWrite);
        $apiResponse = $client->terminal->checkouts->get($statusRequest);
        if ($squareDebug) sqterm_logObject(array ('Terminal API pay status: apiResponse', $apiResponse), $useLogWrite);

        // convert the object into an associative array
        $checkout = json_decode(json_encode($apiResponse->getCheckout()), true);
        return $checkout;
    }
    catch (SquareApiException $e) {
        sqterm_logException($name, $e, 'Terminal Square API pay request Exception', 'Terminal API pay status failed', $useLogWrite);
    }
    catch (Exception $e) {
        sqterm_logException($name, $e, 'Terminal received error while calling Square', 'Error connecting to Square', $useLogWrite);
    }


    return null;
}

function sqterm_logObject($objArray, $useLogWrite = false) : void {
    if ($useLogWrite) {
        logWrite($objArray);
    } else {
        web_error_log($objArray[0]);
        var_error_log($objArray[1]);
    }
}

function sqterm_logException($name, $e, $message, $ajaxMessage, $useLogWrite = false) : void {
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