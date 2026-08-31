
<?php
//  term_stripe.php - library of modules to add talk to and control the Stripe Terminal
// uses config variables:
// [cc]
// type=stripe - selects that reg is to use stripe for credit cards
// needs to be developed for stripe terminals, not part of the original stripe release

require_once("global.php");

function term_createDeviceCode($name, $locationId, $useLogWrite = false) : array | null {
    return array ('Error' => "Stripe Terminals not yet supported");
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
     return array ('Error' => 'Stripe Terminals not yet supported');
}
