<?php
// portal auth - base.php - base functions for membership portal auth testing
global $db_ini;
global $appSessionPrefix;

if (!$db_ini) {
    $db_ini = parse_ini_file(__DIR__ . '/../../../config/reg_conf.ini', true);
}

if ($db_ini['reg']['https'] <> 0) {
    if (!isset($_SERVER['HTTPS']) or $_SERVER['HTTPS'] != 'on') {
        header('HTTP/1.1 301 Moved Permanently');
        header('Location: https://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']);
        exit();
    }
}

require_once(__DIR__ . "/../../../lib/db_functions.php");
require_once(__DIR__ . '/../../../lib/global.php');
require_once(__DIR__ . '/../../../lib/cipher.php');

db_connect();
$appSessionPrefix = 'Ctrl/Portal/';
session_start();

function index_page_init($title) {
    $portal_conf = get_conf('portal');
    echo <<<EOF
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>$title</title>
</head>
EOF;
}