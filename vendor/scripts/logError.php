<?php
// logError - log a javascript error to the javascript error log
require_once '../../lib/global.php';
require_once '../../lib/log.php';
require_once '../../lib/logError.php';

global $appSessionPrefix;
$appSessionPrefix = 'Ctrl/Exhibitor/';
    if (!session_start()) {
        return;
    }

$eyID = getSessionVar('eyID');
$id = getSessionVar('id');
$idType = getSessionVar('login_type');
$email = getSessionVar('loginEmail');

logError("exhibitor: user $idType:$id, email: $email, eyID: $eyID");
