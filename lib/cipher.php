<?php
// cipher.php - cipher key file

//

$cipherParams = null;
$attachParams = null;
function getLoginCipher() {
    global $cipherParams;

    $con = get_conf('con');
    $conid = $con['id'];
    $label = $con['label'];
    $email = $con['regadminemail'];

    $ciphers = openssl_get_cipher_methods();
    $cipher = 'aes-128-cbc';
    $ivlen = openssl_cipher_iv_length($cipher);
    $ivdate = date_create('now');
    $iv = substr(date_format($ivdate, 'YmdzwLLwzdmY'), 0, $ivlen);
    $key = $conid . $label . $email;
    $cipherParams = array('key' => $key, 'iv' => $iv, 'cipher' => $cipher);
    return $cipherParams;
}

function getAttachCipher() {
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

function encryptCipher($string, $doURLencode = false) {
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

function encryptAttach($string, $doURLencode = false) {
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