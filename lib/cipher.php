<?php
// cipher.php - cipher key file

function getLoginCipher() {
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

    return (array('key' => $key, 'iv' => $iv, 'cipher' => $cipher));
}

function getAttachCipher() {
    $con = get_conf('con');
    $conid = $con['id'];
    $label = $con['label'];
    $email = $con['regadminemail'];

    // encrypt/decrypt stuff
    $ciphers = openssl_get_cipher_methods();
    $cipher = 'aes-128-cbc';
    $ivlen = openssl_cipher_iv_length($cipher);
    $ivdate = date_create('now');
    $iv = substr(date_format($ivdate, 'YmdzwLLwzdmY'), 0, $ivlen);
    $key = $conid . $label . $email . '-attach';
    return (array('key' => $key, 'iv' => $iv, 'cipher' => $cipher));
}
