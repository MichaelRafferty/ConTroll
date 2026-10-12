<?php
// ConTroll - Log a javascript error to the javascript error log file
// Tis is the common code for all apps calling logerror

function logError($app) : void {
    loadConfFile();
    if (!array_key_exists('type', $_POST) || !array_key_exists('message', $_POST))
        return;

    $log = getConfValue('log', 'javascript', null);
    if ($log == null) {
        $log = getConfValue('log', 'logins', null);
        if ($log == null)
            return;

        $log = str_replace('/logins', '/javascript', $log);
    }

    logInit($log);
    labeled_logWrite("Javascript error from $app", $_POST);
}
