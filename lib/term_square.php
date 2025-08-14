
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
    $squareDebug = getConfValue('debug', 'square', 0);

    // get a client
    $client = new SquareClient(
        token: $cc['token'],
        options: [
                   'baseUrl' => $cc['env'] == 'production' ? Environments::Production->value : Environments::Sandbox->value,
               ]);

    // pass create to square
    $body = new Requests\CreateDeviceCodeRequest([
        'idempotencyKey' => guidv4(),
        'deviceCode' => new DeviceCode([
            'name' => $name,
            'locationId' => $locationId,
            'productType' => 'TERMINAL_API',
        ]),
    ]);

    try {
        if ($squareDebug & 6) sqterm_logObject($squareDebug, array ('Terminal API create device', $body), $useLogWrite);
        $apiResponse = $client->devices->codes->create($body);
        if ($squareDebug & 6) sqterm_logObject($squareDebug, array ('Terminal API create device: apiResponse', $apiResponse), $useLogWrite);

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
    $squareDebug = getConfValue('debug', 'square', 0);

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
        if ($squareDebug & 6) sqterm_logObject($squareDebug, array ('Terminal API get device code', $body), $useLogWrite);
        $apiResponse = $client->devices->codes->get($body);
        if ($squareDebug & 6) sqterm_logObject($squareDebug, array ('Terminal API get device code: apiResponse', $apiResponse), $useLogWrite);

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
    $squareDebug = getConfValue('debug', 'square', 0);

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
        if ($squareDebug & 6) sqterm_logObject($squareDebug, array ('Terminal API get device by id for ' . $terminal['deviceId'], $body), $useLogWrite);
        $apiResponse = $client->devices->get($body);
        if ($squareDebug & 6) sqterm_logObject($squareDebug, array ('Terminal API get device by id: apiResponse', $apiResponse), $useLogWrite);

        // convert the object into an associative array
        $apiResult = json_decode(json_encode($apiResponse->getDevice()), true);

        // update the database
        $upSQL = <<<EOS
UPDATE terminals
SET
    productType = ?,
    squareName = ?,
    squareModel = ?,
    version = ?,
    terminalAPIVersion = ?,
    batteryLevel = ?,
    externalPower = ?,
    wifiActive = ?,
    wifiSSID = ?,
    wifiIPAddressV4 = ?,
    wifiIPAddressV6 = ?,
    signalStrength = ?,
    ethernetActive = ?,
    ethernetIPAddressV4 = ?,
    ethernetIPAddressV6 = ?,
    status = ?,
    statusChanged = now()
WHERE name = ?;
EOS;
        $attributes = $apiResult['attributes'];
        $components = $apiResult['components'];
        $application = null;
        $battery = null;
        $wifi = null;
        $ethernet = null;
        foreach ($components as $component) {
            switch ($component['type']) {
                case 'APPLICATION':
                    $application = $component;
                    break;
                case 'BATTERY':
                    $battery = $component;
                    break;
                case 'WIFI':
                    $wifi = $component;
                    break;
                case 'ETHERNET':
                    $ethernet = $component;
                    break;
            }
        }
        $status = $apiResult['status'];

        // now the fields

        $version = $attributes['version'];
        if ($application) {
            $productType = $application['application_details']['application_type'];
            $terminalAPIVersion = $application['application_details']['version'];
        } else {
            $productType = null;
            $terminalAPIVersion = null;
        }

        $squareName = $attributes['name'];
        $squareModel = $attributes['model'];

        if ($battery) {
            $batteryLevel = $battery['battery_details']['visible_percent'];
            $externalPower = $battery['battery_details']['external_power'];
        } else {
            $batteryLevel = null;
            $externalPower = null;
        }

        if ($wifi) {
            $wifiActive = $wifi['wifi_details']['active'] ? true : false;
            $wifiSSID = $wifi['wifi_details']['ssid'];
            if (array_key_exists('signal_strength', $wifi['wifi_details']))
                $signalStrength = $wifi['wifi_details']['signal_strength']['value'];
            else
                $signalStrength = null;
            if (array_key_exists('ip_address_v4', $wifi['wifi_details']))
                $wifiIPAddressV4 = $wifi['wifi_details']['ip_address_v4'];
            else
                $wifiIPAddressV4 = null;
            if (array_key_exists('ip_address_v6', $wifi['wifi_details']))
                $wifiIPAddressV6 = $wifi['wifi_details']['ip_address_v6'];
            else
                $wifiIPAddressV6 = null;
        } else {
            $wifiActive = null;
            $wifiSSID = null;
            $signalStrength = null;
            $wifiIPAddressV4 = null;
            $wifiIPAddressV6 = null;
        }
        if ($ethernet) {
            $ethernetActive = $ethernet['ethernet_details']['active'] ? true : false;
            if (array_key_exists('ip_address_v4', $ethernet['ethernet_details']))
                $ethernetIPAddressV4 = $ethernet['ethernet_details']['ip_address_v4'];
            else
                $ethernetIPAddressV4 = null;
            if (array_key_exists('ip_address_v6', $ethernet['ethernet_details']))
                $ethernetIPAddressV6 = $ethernet['ethernet_details']['ip_address_v6'];
            else
                $ethernetIPAddressV6 = null;
        } else {
            $ethernetActive = null;
            $ethernetIPAddressV4 = null;
            $ethernetIPAddressV6 = null;
        }

        $statusCat = $status['category'];

        $arrVals = array($productType, $squareName, $squareModel, $version, $terminalAPIVersion, $batteryLevel, $externalPower,
            $wifiActive, $wifiSSID, $wifiIPAddressV4, $wifiIPAddressV6, $signalStrength,
            $ethernetActive, $ethernetIPAddressV4, $ethernetIPAddressV6, $statusCat, $name);

        $datatypes = 'sssssisisssiissss';
        $response = [];
        $response['updCnt'] = dbSafeCmd($upSQL, $datatypes, $arrVals);


// fetch the updated terminal record
        $terminalSQL = <<<EOS
SELECT *
FROM terminals
WHERE name = ?;
EOS;
        $terminalQ = dbSafeQuery($terminalSQL, 's', array($name));
        if ($terminalQ === false || $terminalQ->num_rows != 1) {
            RenderErrorAjax("Cannot fetch terminal $name status.");
            exit();
        }
        $updatedRow = $terminalQ->fetch_assoc();
        $response['updatedRow'] = $updatedRow;
        $terminalQ->free();
        return $response;
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
    $squareDebug = getConfValue('debug', 'square', 0);

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
                'amount' => round($amount * 100),
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
        if ($squareDebug & 6) sqterm_logObject($squareDebug, array ('Terminal API pay request', $payRequest), $useLogWrite);
        $apiResponse = $client->terminal->checkouts->create($payRequest);
        if ($squareDebug & 6) sqterm_logObject($squareDebug, array ('Terminal API pay request: apiResponse', $apiResponse), $useLogWrite);

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

function term_cancelPayment($name, $payRef, $useLogWrite = false) : array | null {
    $cc = get_conf('cc');
    $squareDebug = getConfValue('debug', 'square', 0);

    // get the device name
    $terminal = getTerminal($name);
    // get a client
    $client = new SquareClient(
        token: $cc['token'],
        options: [
                   'baseUrl' => $cc['env'] == 'production' ? Environments::Production->value : Environments::Sandbox->value,
               ]);

    $cancelRequest = new Square\Terminal\Checkouts\Requests\CancelCheckoutsRequest([
    'checkoutId' => $payRef,
    ]);

    try {
        if ($squareDebug & 6) sqterm_logObject($squareDebug, array ('Terminal API cancel checkout request', $cancelRequest), $useLogWrite);
        $apiResponse = $client->terminal->checkouts->cancel($cancelRequest);
        if ($squareDebug & 6) sqterm_logObject($squareDebug, array ('Terminal API cancel checkout request: apiResponse', $apiResponse), $useLogWrite);

        // convert the object into an associative array
        $checkout = json_decode(json_encode($apiResponse->getCheckout()), true);
        return $checkout;
    }
    catch (SquareApiException $e) {
        sqterm_logException($name, $e, 'Terminal Square API cancel checkout request Exception', 'Terminal API cancel checkout request failed', $useLogWrite,
            false);
    }
    catch (Exception $e) {
        sqterm_logException($name, $e, 'Terminal received error while calling Square', 'Error connecting to Square', $useLogWrite, false);
    }

    return null;
}

function term_getPayStatus($name, $payRef, $useLogWrite = false) : array | null {
    $cc = get_conf('cc');
    $squareDebug = getConfValue('debug', 'square', 0);

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
        if ($squareDebug & 6) sqterm_logObject($squareDebug, array ('Terminal API pay status', $statusRequest), $useLogWrite);
        $apiResponse = $client->terminal->checkouts->get($statusRequest);
        if ($squareDebug & 6) sqterm_logObject($squareDebug, array ('Terminal API pay status: apiResponse', $apiResponse), $useLogWrite);

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

function term_printReceipt($name, $paymentId, $useLogWrite = false) : null | array {
    $cc = get_conf('cc');
    $squareDebug = getConfValue('debug', 'square', 0);

    // get the device name
    $terminal = getTerminal($name);
    // get a client
    $client = new SquareClient(
        token: $cc['token'],
        options: [
            'baseUrl' => $cc['env'] == 'production' ? Environments::Production->value : Environments::Sandbox->value,
        ]);

    $receiptRequest = new Square\Terminal\Actions\Requests\CreateTerminalActionRequest([
        'idempotencyKey' => guidv4(),
        'action' => new Square\Types\TerminalAction([
            'deviceId' => $terminal['deviceId'],
            'type' => 'RECEIPT',
            'receiptOptions' => new Square\Types\ReceiptOptions([
                'paymentId' => $paymentId,
                'printOnly' => true,
            ]),
        ]),
    ]);

    try {
        if ($squareDebug & 6) sqterm_logObject($squareDebug, array ('Terminal API receipt', $receiptRequest), $useLogWrite);
        $apiResponse = $client->terminal->actions->create($receiptRequest);
        if ($squareDebug & 6) sqterm_logObject($squareDebug, array ('Terminal API receipt: apiResponse', $apiResponse), $useLogWrite);

        // convert the object into an associative array
        $receipt = json_decode(json_encode($apiResponse->getAction()), true);
        return $receipt;
    }
    catch (SquareApiException $e) {
        sqterm_logException($name, $e, 'Terminal Square API pay request Exception', 'Terminal API rceipt failed', $useLogWrite);
    }
    catch (Exception $e) {
        sqterm_logException($name, $e, 'Terminal received error while calling Square', 'Error connecting to Square', $useLogWrite);
    }

    return null;
}


function sqterm_logObject($squareDebug, $objArray, $useLogWrite = false) : void {
    if ($useLogWrite) {
        logWrite($objArray);
    } else if ($squareDebug & 4) {
        web_error_log($objArray[0]);
        var_error_log(json_decode(json_encode( $objArray[1]), true));
    }
}

function sqterm_logException($name, $e, $message, $ajaxMessage, $useLogWrite = false, $doExit = true) : void {
    error_log("$message:" . $e->getMessage());
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
        }
    }
    if ($doExit) {
        ajaxSuccess(array ('status' => 'error', 'data' => "Error: $ajaxMessage, see logs."));
        exit();
    }
}
