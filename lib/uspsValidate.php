<?php
require_once("ajax_functions.php");
$uspsAPIToken = null;
$uspsAuthorization = null;
$uspsKey = null;
global $db_ini;

if (!$db_ini) {
    $db_ini = parse_ini_file(__DIR__ . '/../config/reg_conf.ini', true);
}

function getUSPSV3Token() {
    global $uspsAPIToken, $uspsAuthorization, $uspsKey;

    $usps = get_conf('usps');
    $key = $usps['clientId'];
    $secret = $usps['secret'];

    $tokenReq = array(
        'client_id' => $key,
        'client_secret' => $secret,
        'grant_type' => 'client_credentials'
    );

    $tokenCURL = curl_init();
    curl_setopt($tokenCURL, CURLOPT_URL, 'https://api.usps.com/oauth2/v3/token');
    curl_setopt($tokenCURL, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($tokenCURL, CURLOPT_CONNECTTIMEOUT, 300);
    curl_setopt($tokenCURL, CURLOPT_POST, true);
    curl_setopt($tokenCURL, CURLOPT_POSTFIELDS, http_build_query($tokenReq));
    $response = json_decode(curl_exec($tokenCURL), true);
    curl_close($tokenCURL);

    if ($response == false) {
        ajaxError('unable to get token');
    }

    $uspsAPIToken = $response['access_token'];
    $uspsAuthorization = 'Authorization: Bearer ' . $uspsAPIToken;
    $uspsKey = "x-user-id: " . $key;
}

function getUSPSNormalizedAddress($address, $address2, $city, $state, $zip) {
    global $uspsAPIToken, $uspsKey, $uspsAuthorization;
    
    if ($state == null || strlen($state) != 2)
        ajaxError('State must be 2 character USPS State code');
    $state = strtoupper($state);

    $validate = array('state' => $state );
    if ($address !== null && $address != '') {
        $validate['streetAddress'] = $address;
    } else {
        ajaxError('address required');
    }
    if ($address2 !== null && $address2 != '')
        $validate['secondaryAddress'] = $address2;
    if ($city !== null && $city != '')
        $validate['city'] = $city;
    if ($zip !== null && $zip != '') {
        if (strlen($zip) == 5)
            $validate['ZIPCode'] = $zip;
        else if (strlen($zip) == 9) {
            $validate['ZIPCode'] = substr($zip, 0, 5);
            $validate['ZIPPlus4'] = substr($zip, 5, 4);
        } else if (strlen($zip) == 10) {
            $validate['ZIPCode'] = substr($zip, 0, 5);
            $validate['ZIPPlus4'] = substr($zip, 6, 4);
        } else
            $validate['ZIPCode'] = substr($zip, 0, 5);
    }
    
    if ($uspsAPIToken == null)
        $uspsAPIToken = getUSPSV3Token();
    
    $valCURL = curl_init();
    curl_setopt($valCURL, CURLOPT_URL, 'https://api.usps.com/addresses/v3/address?' . http_build_query($validate));
    curl_setopt($valCURL, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($valCURL, CURLOPT_CONNECTTIMEOUT, 300);
    curl_setopt($valCURL, CURLOPT_HTTPHEADER, array('Content-Type: application/json', $uspsKey, $uspsAuthorization));
    $validated = json_decode(curl_exec($valCURL), true);
    curl_close($valCURL);

    return $validated;
}


function getUSPSZipCode($address, $address2, $city, $state) {
    global $uspsAPIToken, $uspsKey, $uspsAuthorization;

    if ($state == null || strlen($state) != 2)
        ajaxError('State must be 2 character USPS State code');
    $state = strtoupper($state);

    $query = array('state' => $state );
    if ($address !== null && $address != '') {
        $query['streetAddress'] = $address;
    } else {
        ajaxError('address required');
    }
    if ($address2 !== null && $address2 != '')
        $query['secondaryAddress'] = $address2;
    if ($city !== null && $city != '')
        $query['city'] = $city;

    if ($uspsAPIToken == null)
        $uspsAPIToken = getUSPSV3Token();

    $zipCURL = curl_init();
    curl_setopt($zipCURL, CURLOPT_URL, 'https://api.usps.com/addresses/v3/zipcode?' . http_build_query($query));
    curl_setopt($zipCURL, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($zipCURL, CURLOPT_CONNECTTIMEOUT, 300);
    curl_setopt($zipCURL, CURLOPT_HTTPHEADER, array('Content-Type: application/json', $uspsKey, $uspsAuthorization));
    $zipcode = json_decode(curl_exec($zipCURL), true);
    curl_close($zipCURL);

    return $zipcode;
}
