<?php
global $db_ini;

require_once '../lib/base.php';
require_once('../../lib/uspsValidate.php');

$check_auth = google_init('ajax');
$perm = 'overview';

$response = array ('post' => $_POST, 'get' => $_GET, 'perm' => $perm);

if ($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = 'Authentication Failed';
    ajaxSuccess($response);
    exit();
}

$response['post'] = $_POST;
if (array_key_exists('addr', $_POST))
    $address = $_POST['addr'];
else
    $address = null;

if (array_key_exists('addr2', $_POST))
    $address2 = $_POST['addr2'];
else
    $address2 = null;

if (array_key_exists('city', $_POST))
    $city = $_POST['city'];
else
    $city = null;

if (array_key_exists('state', $_POST))
    $state = $_POST['state'];
else
    $state = null;

if (array_key_exists('zip', $_POST))
    $zip = $_POST['zip'];
else
    $zip = null;

$validated = getUSPSNormalizedAddress($address, $address2, $city, $state, $zip);
$response['usps'] = $validated;

if (!is_array($validated)) {
    $response['error'] = $validated;
    ajaxSuccess($response);
    exit();
}

if (array_key_exists('error', $validated)) {
    $response['error'] = $validated['error']['code'] . ': ' . $validated['error']['message'];
    ajaxSuccess($response);
    exit();
}


// usps returns an array of
// firm - company name or null
// address = array of address items
// additionalInfo = array of more data
// corrections = any corrections made to the address
// matches = any matches made to the requested address

$validAddr = array();
if ($validated['firm'])
    $validAddr['company'] = $validated['firm'];

if ($validated['address']) {
    $address = $validated['address'];
    if ($address['streetAddress'])
        $validAddr['address'] = $address['streetAddress'];
    if ($address['secondaryAddress'])
        $validAddr['address2'] = $address['secondaryAddress'];
    if ($address['city'])
        $validAddr['city'] = $address['city'];
    if ($address['state'])
        $validAddr['state'] = $address['state'];
    if ($address['postalCode'])
        $validAddr['zip'] = $address['postalCode'];
    if ($address['province'])
        $validAddr['state'] = $address['province'];
    if ($address['ZIPCode']) {
        $zip = $address['ZIPCode'];
        if ($address['ZIPPlus4'])
            $zip .= '-' . $address['ZIPPlus4'];
        $validAddr['zip'] = $zip;
    }
    if ($address['countryISOCode'])
        $validAddr['country'] = $address['countryISOCode'];

    if ($validated['additionalInfo']) {
        $addInfo = $validated['additionalInfo'];
        if ($addInfo['vacant'])
            $validAddr['vacant'] = $addInfo['vacant'];

        if ($addInfo['DPVConfirmation']) {
            switch ($addInfo['DPVConfirmation']) {
                case 'Y':
                    $validAddr['valid'] = 'Valid';
                    break;
                case 'D':
                    $validAddr['valid'] = 'Missing Suite/Apt';
                    break;
                case 'S':
                    $validAddr['valid'] = 'Invalid Suite/Apt';
                    break;
                case 'N':
                    $validAddr['valid'] = 'Undeliverable';
                    break;
            }
        } else
            $validAddr['valid'] = 'Invalid';
    }
}

$response['success'] = true;
$response['address'] = $validAddr;
ajaxSuccess($response);
