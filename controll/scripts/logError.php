<?php
// logError - log a javascript error to the javascript error log
require_once '../../lib/global.php';
require_once '../../lib/log.php';
require_once '../../lib/logError.php';

global $appSessionPrefix;
$appSessionPrefix = 'Ctrl/ConTroll/';
    if (!session_start()) {
        return;
    }
$user = getSessionVar('user_perid');

logError("controll: user $user");
