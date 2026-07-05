<?php
// downloadCSV - take an associative array passed in and a file name, and output that
require_once "../lib/base.php";
require_once "../../lib/outputFile.php";

// ConTroll Registration System, Copyright 2015-2026, Michael Rafferty, Licensed under the GNU Affero General Public License, Version 3.
// library AJAX Processor: downloadFile.php
// Author: Syd Weinstein
// download a tabulator data array

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

if (!isSessionVar('user')) {
    header('Location: /index.php');
    exit(0);
}

$conid = getConfValue('con', 'id', '-1');

if ((!array_key_exists('format', $_POST)) || (!array_key_exists('table', $_POST)) || (!array_key_exists('filename', $_POST))) {
    $response['error'] = 'Invalid Arguments';
    ajaxSuccess($response);
    exit();
}

$format = $_POST['format'];
$sheetname = $_POST['filename'];
$filename = $sheetname . '-' . date("Y-m-d_H-i-s");

try {
    $tableData = json_decode($_POST['table'], true, 512, JSON_THROW_ON_ERROR);
}
catch (Exception $e) {
    $msg = 'Caught exception on json_decode of data: ' . $e->getMessage() . PHP_EOL . 'JSON error: ' . json_last_error_msg() . PHP_EOL;
    $response['error'] = $msg;
    error_log($msg);
    ajaxSuccess($response);
    exit();
}

if (count($tableData) == 0) {
  $response['error'] = 'Empty table to export as CSV';
  ajaxSuccess($response);
  exit();
}

$excludeList = null;
if (array_key_exists('excludeList', $_POST)) {
  try {
    $excludeList = json_decode($_POST['excludeList'], true, 512, JSON_THROW_ON_ERROR);
  }
  catch (Exception $e) {
    $msg = 'Caught exception on json_decode of exclude list: ' . $e->getMessage() . PHP_EOL . 'JSON error: ' . json_last_error_msg() . PHP_EOL;
    $response['error'] = $msg;
    error_log($msg);
    ajaxSuccess($response);
    exit;
  }
}

$fieldList = null;
if (array_key_exists('fieldList', $_POST)) {
  try {
    $fieldList = json_decode($_POST['fieldList'], true, 512, JSON_THROW_ON_ERROR);
  }
  catch (Exception $e) {
    $msg = 'Caught exception on json_decode of field list: ' . $e->getMessage() . PHP_EOL . 'JSON error: ' . json_last_error_msg() . PHP_EOL;
    $response['error'] = $msg;
    error_log($msg);
    ajaxSuccess($response);
    exit;
  }
}

outputFile($format, $sheetname, $filename, $tableData, $excludeList, $fieldList);
