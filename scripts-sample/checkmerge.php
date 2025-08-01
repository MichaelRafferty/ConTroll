<?php
// check merge, check the perinfo table for potential merge records
require_once('../../lib/global.php');
require_once(__DIR__ . '/../lib/db_functions.php');
require_once(__DIR__ . '/../lib/checkmerge.php');

loadConfFile();
db_connect();

$data = checkmerge(0, 5);
$values = $data['values'];
foreach ($values as $key => $rows) {
    echo "$key:\n";
    foreach ($rows as $row)
        echo "~" . implode('~,~', $row) . "~\n";
    echo "\n";
}
