<?php
require_once("ajax_functions.php");
require_once('../../lib/global.php');

$uspsAPIToken = null;
$uspsAuthorization = null;
$uspsKey = null;
$validstate = ['AA','AE','AL','AK','AP','AS','AZ','AR','CA','CO','CT','DE','DC','FM','FL','GA','GU','HI','ID','IL','IN','IA','KS','KY','LA',
               'ME','MH','MD','MA','MI','MN','MS','MO','MP','MT','NE','NV','NH','NJ','NM','NY','NC','ND','OH','OK','OR','PW','PA','PR','RI',
               'SC','SD','TN','TX','UT','VT','VI','VA','WA','WV','WI','WY'];

loadConfFile();

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
        ajaxSuccess(array('status'=>'error', 'message'=>'unable to get token'));
        exit();
    }

    $uspsAPIToken = $response['access_token'];
    $uspsAuthorization = 'Authorization: Bearer ' . $uspsAPIToken;
    $uspsKey = "x-user-id: " . $key;
}

function getUSPSNormalizedAddress($address, $address2, $city, $state, $zip) {
    global $uspsAPIToken, $uspsKey, $uspsAuthorization, $validstate;
    
    if ($state == null || strlen($state) != 2) {
        ajaxSuccess(array ('status' => 'error', 'message' => 'State must be 2 character USPS State code'));
        exit();
    }
    $state = strtoupper($state);
    if (!in_array($state, $validstate)) {
        ajaxSuccess(array('status'=>'error', 'message'=>'Invalid state, must be one of the valid USPS state codes'));
        exit();
    }
    $validate = array('state' => $state );

    if ($address !== null && $address != '') {
        $validate['streetAddress'] = $address;
    } else {
        ajaxSuccess(array('status'=>'error', 'message'=>'address required'));
        exit();
    }

    if ($address2 !== null && $address2 != '')
        $validate['secondaryAddress'] = $address2;

    if ($city !== null && $city != '')
        $validate['city'] = $city;

    if ($zip !== null && $zip != '') {
        if (strlen($zip) == 5) {
            if (!is_numeric($zip)) {
                ajaxSuccess(array('status'=>'error', 'message'=>'Zip code must be a numeric value'));
                exit();
            }
            $validate['ZIPCode'] = $zip;
        } else if (strlen($zip) == 9) {
            $zc = substr($zip, 0, 5);
            if (!is_numeric($zc)) {
                ajaxSuccess(array('status'=>'error', 'message'=>'Five digit portion of the Zip code must be a numeric value'));
                exit();
            }
            $validate['ZIPCode'] = $zc;
            $zc = substr($zip, 5, 4);
            if (!is_numeric($zc)) {
                ajaxSuccess(array('status'=>'error', 'message'=>'Zip+4 portion must be a numeric value'));
                exit();
            }
            $validate['ZIPPlus4'] = $zc;
        } else if (strlen($zip) == 10) {
            $zc = substr($zip, 0, 5);
            if (!is_numeric($zc)) {
                ajaxSuccess(array('status'=>'error', 'message'=>'Five digit portion of the Zip code must be a numeric value'));
                exit();
            }
            $validate['ZIPCode'] = $zc;
            $zc = substr($zip, 6, 4);
            if (!is_numeric($zc)) {
                ajaxSuccess(array('status'=>'error', 'message'=>'Zip+4 portion must be a numeric value'));
                exit();
            }
            $validate['ZIPPlus4'] = $zc;
        } else {
            $zc = substr($zip, 0, 5);
            if (!is_numeric($zc)) {
                ajaxSuccess(array('status'=>'error', 'message'=>'Zip code must be a numeric value'));
                exit();
            }
            $validate['ZIPCode'] = $zc;
        }
    }
    
    if ($uspsAPIToken == null)
        $uspsAPIToken = getUSPSV3Token();
    
    $valCURL = curl_init();
    curl_setopt($valCURL, CURLOPT_URL, 'https://api.usps.com/addresses/v3/address?' . http_build_query($validate));
    curl_setopt($valCURL, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($valCURL, CURLOPT_CONNECTTIMEOUT, 300);
    curl_setopt($valCURL, CURLOPT_HTTPHEADER, array('Content-Type: application/json', $uspsKey, $uspsAuthorization));
    $jsonData = curl_exec($valCURL);
    $validated = json_decode($jsonData, true);
    curl_close($valCURL);
    if ($validated == null) {
        // deal with errors causing invalid json to decode
        $validated = $jsonData;
    }

    return $validated;
}


function getUSPSZipCode($address, $address2, $city, $state) {
    global $uspsAPIToken, $uspsKey, $uspsAuthorization, $validstate;

    if ($state == null || strlen($state) != 2) {
        ajaxSuccess(array ('status' => 'error', 'message' => 'State must be 2 character USPS State code'));
        exit();
    }
    $state = strtoupper($state);
    if (!in_array($state, $validstate)) {
        ajaxSuccess(array('status'=>'error', 'message'=>'Invalid state, must be one of the valid USPS state codes'));
        exit();
    }
    $query = array('state' => $state );

    if ($address !== null && $address != '') {
        $query['streetAddress'] = $address;
    } else {
        ajaxSuccess(array('status'=>'error', 'message'=>'address required'));
        exit();
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
