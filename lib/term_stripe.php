
<?php
//  term_stripe.php - library of modules to add talk to and control the Stripe Terminal
// uses config variables:
// [cc]
// type=stripe - selects that reg is to use stripe for credit cards

// Stripe uses a connection token to talk to the terminal, this can be scoped by location (??)
// Create a reader returns a registration code to attach a reader to the location and the account, this is similar a pair operation in square
//      The reader object can be used to retrueve a reader by id, and to update it, delete it, retrieve it (status), delete it,
//      cancel an action, collect information from the customer (unused by ConTroll), confirm a payment intent (pay an order),
//      hand off a payment intent to a reader to get card detils, hand off a payment intent to a reader, print to a reader (not all readers have printer),
//      set reader sidplay (show cart details)

require_once("global.php");

function term_createLocation($name, $address, $city, $state, $postalcode) {
    $country = strtoupper(getConfValue('cc', 'country', 'US'));
    $stripeDebug = getConfValue('debug', 'stripe', 0);
    $useLogWrite = $stripeDebug > 0;

    $locationReq = [
        'display_name' => $name,
        'address' => array_filter([
            'line1' => $address, 'city' => $city, 'state' => $state, 'country' => $country, 'postal_code' => $postalcode])
    ];


    try {
        if ($stripeDebug & 32) stcc_logObject('cc_stripe/terminal->locations->create', $locationReq, $useLogWrite);
        // get a client reference
        $client = new \Stripe\StripeClient(getConfValue('cc', 'key'));
        $location = $client->terminal->locations->create($locationReq);
        if ($stripeDebug & 32) stcc_logObject('cc_stripe/terminal->locations->create response', $location, $useLogWrite);
    }
    catch (\Stripe\Exception\InvalidRequestException $e) {
        stcc_logException('term_createLocation', $e, 'Invalid Request Exception', 'Invalid Location data for create location, seek assistance.',
            $useLogWrite);;
    }
    catch (\Stripe\Exception\ApiErrorException $e) {
        stcc_logException('term_createLocation', $e, 'other api error', 'Unable to create location, seek assistance.',
            $useLogWrite);
    }
    catch (Exception $e) {
        error_log('Another problem occurred, maybe unrelated to Stripe, seek assistance.');
    }

    return $location['id'];
}

function term_createDeviceCode($name, $locationId, $readerCode, $useLogWrite = false) : array | null {
    $stripeDebug = getConfValue('debug', 'stripe', 0);
    $useLogWrite = $stripeDebug > 0;

    // first try to see if it exists, if so, return it's data.
    // This is because if it goes into updates forever, you'll have to ask to re-create it to add it to our database.
    $readerList = [
        'limit' => 999,
        'location' => $locationId,
    ];

    try {
        if ($stripeDebug & 32) stcc_logObject('cc_stripe/terminal->readers->all', $readerList, $useLogWrite);
        // get a client reference
        $client = new \Stripe\StripeClient(getConfValue('cc', 'key'));
        $reader = $client->terminal->readers->all($readerList);
        $readerPHP = json_decode(json_encode($reader), true);
        if ($stripeDebug & 32) stcc_logObject('cc_stripe/terminal->readers->all response', $reader, $useLogWrite);
    }
    catch (\Stripe\Exception\InvalidRequestException $e) {
        stcc_logException('term_createLocation', $e, 'Invalid Request Exception',
            'Invalid data for list terminals, seek assistance.<br/>Stripe Error: ' . $e->getMessage(),
            $useLogWrite);
    }
    catch (\Stripe\Exception\ApiErrorException $e) {
        stcc_logException('term_createLocation', $e, 'other api error',
            'Unable to list terminals, seek assistance.<br/>Stripe Error: ' . $e->getMessage(),
            $useLogWrite);
    }
    catch (Exception $e) {
        error_log('Another problem occurred, maybe unrelated to Stripe, seek assistance.');
    }

    if (array_key_exists('data', $readerPHP)) {
        $readers = $readerPHP['data'];
        if (count($readers) > 0) {
            // search for a match
            foreach ($readers as $item) {
                if ($item['label'] == $name)
                    return $item;
            }
        }
    }

    // not found, try adding it.

    $readerCreate = [
        'location' => $locationId,
        'label' => $name,
        'registration_code' => $readerCode,
        'metadata' => array('Name' => $name, 'Label' => $name),
    ];

    try {
        if ($stripeDebug & 32) stcc_logObject('cc_stripe/terminal->readers->create', $readerCreate, $useLogWrite);
        // get a client reference
        $client = new \Stripe\StripeClient(getConfValue('cc', 'key'));
        $reader = $client->terminal->readers->create($readerCreate);
        $readerPHP = json_decode(json_encode($reader), true);
        if ($stripeDebug & 32) stcc_logObject('cc_stripe/terminal->readers->create response', $reader, $useLogWrite);
    }
    catch (\Stripe\Exception\InvalidRequestException $e) {
        stcc_logException('term_createLocation', $e, 'Invalid Request Exception',
            'Invalid data for create terminal, seek assistance.<br/>Stripe Error: ' . $e->getMessage(),
            $useLogWrite);
    }
    catch (\Stripe\Exception\ApiErrorException $e) {
        stcc_logException('term_createLocation', $e, 'other api error',
            'Unable to create terminal, seek assistance.<br/>Stripe Error: ' . $e->getMessage(),
            $useLogWrite);
    }
    catch (Exception $e) {
        error_log('Another problem occurred, maybe unrelated to Stripe, seek assistance.');
    }
    return $readerPHP;
}

function term_getDevice($name, $id, $useLogWrite = false) : array | null {
    $stripeDebug = getConfValue('debug', 'stripe', 0);
    try {
        if ($stripeDebug & 32) stcc_logObject('cc_stripe/terminal->readers->retrieve', $id, $useLogWrite);
        // get a client reference
        $client = new \Stripe\StripeClient(getConfValue('cc', 'key'));
        $reader = $client->terminal->readers->retrieve($id);
        $data = json_decode(json_encode($reader), true);
        if ($stripeDebug & 32) stcc_logObject('cc_stripe/terminal->readers->retrieve response', $reader, $useLogWrite);
        return $data;
    }
    catch (\Stripe\Exception\InvalidRequestException $e) {
        stcc_logException('term_retrieve', $e, 'Invalid Request Exception',
            'Invalid data for retrieve terminal, seek assistance.<br/>Stripe Error: ' . $e->getMessage(),
            $useLogWrite);
    }
    catch (\Stripe\Exception\ApiErrorException $e) {
        stcc_logException('term_retrieve', $e, 'other api error',
            'Unable to retrieve terminal, seek assistance.<br/>Stripe Error: ' . $e->getMessage(),
            $useLogWrite);
    }
    catch (Exception $e) {
        error_log('Another problem occurred, maybe unrelated to Stripe, seek assistance.');
    }
    return null;
}

function term_getStatus($name, $id, $useLogWrite = false) : array | null {
     $apiResult = term_getDevice($name, $id, $useLogWrite);
     if ($apiResult == null)
         return null;

    // update the database
    $upSQL = <<<EOS
UPDATE terminals
SET
    productType = ?,
    squareName = ?,
    squareModel = ?,
    version = ?,
    terminalAPIVersion = ?,
    wifiIPAddressV4 = ?,
    status = ?,
    statusChanged = now()
WHERE name = ?;
EOS;
    $productType = $apiResult['object'];
    $squareName = $apiResult['label'];
    $squareModel = $apiResult['device_type'];
    $version = $apiResult['device_sw_version'];
    $wifiIPAddressV4 = $apiResult['ip_address'];
    $terminalAPIVersion = $apiResult['device_sw_version'];
    $status = $apiResult['status'];

    $arrVals = array($productType, $squareName, $squareModel, $version, $terminalAPIVersion, $wifiIPAddressV4, $status, $name);
    $datatypes = 'ssssssss';
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


function term_payOrder($name, $orderId, $tid, $amount, $useLogWrite = false) : array | null {
     return array ('Error' => 'Stripe Terminals not yet supported');
}

function term_cancelPayment($name, $payRef, $useLogWrite = false) : array | null {
     return array ('Error' => 'Stripe Terminals not yet supported');
}

function term_getPayStatus($name, $payRef, $useLogWrite = false) : array | null {
     return array ('Error' => 'Stripe Terminals not yet supported');
}

function term_printReceipt($name, $paymentId, $useLogWrite = false) : null | array {
     return array ('Error' => 'Stripe Terminals do not have integral printers, this feature is not supported');
}
