<?php
## Pull INI for variables
global $db_ini;
if (!$db_ini) {    
    $db_ini = parse_ini_file(__DIR__ . "/../../../config/reg_conf.ini", true);
}

if ($db_ini['reg']['https'] <> 0) {
    if (!isset($_SERVER['HTTPS']) OR $_SERVER["HTTPS"] != "on") {
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: https://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]);
        exit();
    }
}

require_once(__DIR__ . "/../../../lib/db_functions.php");
require_once(__DIR__ . "/../../../lib/ajax_functions.php");
db_connect();

function callOut($url, $data) {
   $ch = curl_init($url);
   curl_setopt($ch, CURLOPT_POST, TRUE);
   curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
   curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Length: ' . strlen($data)
   ));
   curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
   $result = curl_exec($ch);
   curl_close($ch);
}

function nullifnotsetempty($item) {
    if (isset($item)) {
        if ($item != '')
            return $item;
    }
    return null;
}

function blankifnotset($item) {
    if (isset($item))
        return $item;
    return '';
}
?>