<?php
## Pull INI for variables
global $db_ini;
if (!$db_ini) {    
    $db_ini = parse_ini_file(__DIR__ . "/../../config/reg_conf.ini", true);
    $include_path_additions = PATH_SEPARATOR . $db_ini['api']['path'] . "/../Composer";
}

if ($db_ini['reg']['https'] <> 0) {
    if(!isset($_SERVER['HTTPS']) or $_SERVER["HTTPS"] != "on") {
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: https://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]);
        exit();
    }
}
set_include_path(get_include_path(). $include_path_additions);

require_once("vendor/autoload.php");
require_once(__DIR__ . '/../../lib/db_functions.php');
require_once(__DIR__ . '/../../lib/global.php');
require_once(__DIR__ . '/../../lib/ajax_functions.php');
db_connect();

function get_oauthkey() {
    $api = get_conf("api");
    if ($api && array_key_exists('oauthEncryptionKey', $api)) {
        return $api['oauthEncryptionKey'];
    }
    return 'def00000a40f753f22d669707fd0adf040bf3bac8b56decd939eea3761f65e00c3b4a545009ba030019b0778e83e9966a15f008d1438c11e820ed2a297ad2ce4d5660c0d';
}

function get_debug($type) {
    $debug = get_conf("debug");
    if ($debug && array_key_exists($type, $debug)) {
        return $debug[$type];
    }
    return (0);
}

// reg_ uses the atcon ajax renders
function RenderErrorAjax($message_error)
{
    global $return500errors;
    if (isset($return500errors) && $return500errors) {
        Render500ErrorAjax($message_error);
    } else {
        echo "<div class=\"error-container alert\"><span>$message_error</span></div>\n";
    }
}

function Render500ErrorAjax($message_error)
{
    // pages which know how to handle 500 errors are expected to format the error message appropriately.
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
    echo "$message_error";
}
?>
