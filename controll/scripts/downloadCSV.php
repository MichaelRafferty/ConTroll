<?php
// downloadCSV - take an associative array passed in and a file name, and output that
global $db_ini;
require_once "../lib/base.php";

$check_auth = google_init("ajax");
$perm = "overview";

$response = array("perm"=>$perm);

if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

if ((!array_key_exists('table', $_POST)) || (!array_key_exists('filename', $_POST))) {
  $response['error'] = 'Invalid Arguments';
  ajaxSuccess($response);
  exit();
}

$fileName = $_POST['filename'];

try {
  $tableData = json_decode($_POST['table'], true, 512, JSON_THROW_ON_ERROR);
}
catch (Exception $e) {
  $msg = 'Caught exception on json_decode: ' . $e->getMessage() . PHP_EOL . 'JSON error: ' . json_last_error_msg() . PHP_EOL;
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

header('Cache-Control: max-age=0');
header('Expires: 0');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
header('Pragma: public'); // HTTP/1.0
header('Content-Type: application/force-download');
header('Content-Type: application/octet-stream');
header('Content-Type: application/download');
header('Content-type: application/csv');
header('Content-Disposition: attachment;filename="' . $fileName . '.csv"');
header('Content-Transfer-Encoding: binary');
$csv = fopen('php://output', 'w');
//add BOM to fix UTF-8 in Excel
fputs($csv, $bom =( chr(0xEF) . chr(0xBB) . chr(0xBF) ));

// get the header line
$keys = array_keys($tableData[0]);
fputcsv($csv, $keys, ",", "\"", "\"", PHP_EOL);
foreach ($tableData as $row) {
  fputcsv($csv, $row, ",", "\"", "\"", PHP_EOL);
}

fclose($csv);
?>
