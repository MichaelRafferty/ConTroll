<?php
function logEcho($string, $logonly=false) {
    global $logFile;

    if (!$logonly)
        echo $string . PHP_EOL;
    fwrite($logFile, date('Y-m-d H:i:s') . ': ' . $string . PHP_EOL);
}
