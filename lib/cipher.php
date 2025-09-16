<?php
// cipher.php - cipher key file

//

$cipherParams = null;
$attachParams = null;
$jwtSigningKey = null;
function getLoginCipher() : array {
    global $cipherParams;

    $con = get_conf('con');
    $db = get_conf('mysql');
    $conid = $con['id'];
    $label = $con['label'];
    $email = $con['regadminemail'];

    $ciphers = openssl_get_cipher_methods();
    $cipher = 'aes-128-cbc';
    $ivlen = openssl_cipher_iv_length($cipher);
    $configKey = $con['conname'] . '-' .  $conid . $db['db_name'] . '/' . $db['user'];
    $iv = substr($configKey . $configKey, 0, $ivlen);
    $key = $conid . $label . $email;
    $cipherParams = array('key' => $key, 'iv' => $iv, 'cipher' => $cipher);
    return $cipherParams;
}

function getAttachCipher() : array {
    global $attachParams;

    $attachParams = getLoginCipher();
    $attachParams['key'] .= '-attach';
    return $attachParams;
}

function decryptCipher($string, $doJson = false) {
    global $cipherParams;

    if ($cipherParams == null) {
        $cipherParams = getLoginCipher();
    }

    $decValue = openssl_decrypt($string, $cipherParams['cipher'], $cipherParams['key'], 0, $cipherParams['iv']);
    if ($doJson) {
        $decValue = json_decode($decValue, true);
    }

    return $decValue;
}

function encryptCipher($string, $doURLencode = false) : string {
    global $cipherParams;

    if ($cipherParams == null) {
        $cipherParams = getLoginCipher();
    }

    $string = openssl_encrypt($string, $cipherParams['cipher'], $cipherParams['key'], 0, $cipherParams['iv']);
    if ($doURLencode) {
        $string = urlencode($string);
    }
    return $string;
}

function decryptAttach($string, $doJson = false) {
    global $attachParams;

    if ($attachParams == null) {
        $attachParams = getAttachCipher();
    }

    $decValue = openssl_decrypt($string, $attachParams['cipher'], $attachParams['key'], 0, $attachParams['iv']);
    if ($doJson) {
        $decValue = json_decode($decValue, true);
    }

    return $decValue;
}

function encryptAttach($string, $doURLencode = false) : string {
    global $attachParams;

    if ($attachParams == null) {
        $attachParams = getAttachCipher();
    }

    $string = openssl_encrypt($string, $attachParams['cipher'], $attachParams['key'], 0, $attachParams['iv']);
    if ($doURLencode) {
        $string = urlencode($string);
    }
    return $string;
}

//  JWT related functions for cross system passing
function setJWTKey($key) : void {
    global $jwtSigningKey;

    if ($key == null || $key == '') {
        $con = get_conf('con');
        $jwtSigningKey = $con['label'] . '-' . $con['id'] . '-' . ((getConfValue('reg', 'test') == 1) ? 'Test' : 'Prod');
        return;
    }

    $jwtSigningKey = $key;
}

function genJWT($payload): string {
    global $jwtSigningKey;

    if ($jwtSigningKey == null) {
        setJWTKey(null);
    }

    $header = [
        'alg' => 'HS512',
        'typ' => 'JWT'
    ];
    $header = base64_encode_url(json_encode($header));
    $payload = base64_encode_url(json_encode($payload));
    $signature = base64_encode_url(hash_hmac('sha512', "$header.$payload", $jwtSigningKey, true));
    $jwt = "$header.$payload.$signature";
    return $jwt;
}

function checkJWT($jwt) : bool {
    global $jwtSigningKey;

    if ($jwtSigningKey == null) {
        setJWTKey(null);
    }

    // split into the three parts and then decode them back to structures
    [$header, $payload, $signature] = explode('.', $jwt);
    $checkSignature = base64_encode_url(hash_hmac('sha512', "$header.$payload", $jwtSigningKey, true));
    return $checkSignature == $signature;
}
