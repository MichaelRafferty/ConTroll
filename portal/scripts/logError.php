<?php
// logError - log a javascript error to the javascript error log
require_once '../../lib/global.php';
require_once '../../lib/log.php';
require_once '../../lib/logError.php';

global $appSessionPrefix;
$appSessionPrefix = 'Ctrl/Portal/';
    if (!session_start()) {
        return;
    }

$id = getSessionVar('id');
$idType = getSessionVar('idType');
$age = getSessionVar('idAge');
$source = getSessionVar('idSource');
$multiple = getSessionVar('multiple');
$email = getSessionVar('email');

logError("portal: User $idType:$id, Age: $age, email: $email, source: $source, multiple: $multiple");
