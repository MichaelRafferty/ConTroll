<?php

// uspsCheck use the usps address validation API to get a valid address back.  Limited to USA addresses only

require_once('../lib/base.php');
require_once('../../lib/uspsValidate.php');
require_once('../../lib/uspsValidate.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$response = array('post' => $_POST, 'get' => $_GET);
// check for source, login source does not need id and idtype
if (!array_key_exists('source', $_POST) || $_POST['source'] != 'login') {
    if (!(isSessionVar('id') && isSessionVar('idType'))) {
        ajaxSuccess(array('status' => 'error', 'message' => 'Not logged in.'));
        exit();
    }
}

$con = get_con();
$conid=$con['id'];
$conf = get_conf('con');
$portal_conf = get_conf('portal');

$response['conid'] = $conid;

// take either database names or edit form names
if (array_key_exists('addr', $_POST))
    $address = $_POST['addr'];
else if (array_key_exists('address', $_POST))
    $address = $_POST['address'];
else
    $address = null;

if (array_key_exists('addr2', $_POST))
    $address2 = $_POST['addr2'];
else if (array_key_exists('addr_2', $_POST))
    $address2 = $_POST['addr_2'];
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
