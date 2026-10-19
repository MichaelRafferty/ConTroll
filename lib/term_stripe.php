
<?php
//  term_stripe.php - library of modules to add talk to and control the Stripe Terminal
// uses config variables:
// [cc]
// type=stripe - selects that reg is to use stripe for credit cards
// needs to be developed for stripe terminals, not part of the original stripe release

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

function term_createDeviceCode($name, $locationId, $readerCode, $label, $useLogWrite = false) : array | null {
    $stripeDebug = getConfValue('debug', 'stripe', 0);
    $useLogWrite = $stripeDebug > 0;

    $readerCreate = [
        'location' => $locationId,
        'label' => $label,
        'registration_code' => $readerCode
    ];


    try {
        if ($stripeDebug & 32) stcc_logObject('cc_stripe/terminal->readers->create', $readerCreate, $useLogWrite);
        // get a client reference
        $client = new \Stripe\StripeClient(getConfValue('cc', 'key'));
        $reader = $client->terminal->readers->create($readerCreate);
        if ($stripeDebug & 32) stcc_logObject('cc_stripe/terminal->readers->create response', $reader, $useLogWrite);
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
    return $reader['id'];
}

function term_getDevice($name, $useLogWrite = false) : array | null {
    return array ('Error' => "Stripe Terminals not yet supported");
}

function term_getStatus($name, $useLogWrite = false) : array | null {
     return array ('Error' => 'Stripe Terminals not yet supported');
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
