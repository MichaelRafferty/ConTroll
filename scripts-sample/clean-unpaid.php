<?php
// clean unpaid - delete unpaid web registrations that are complete in a subsequent transaction
//  notes: must have a perid assigned and match the perid of the paid transaction
// currently only looks for payments within one week of original transaction

global $db_ini;
if (!$db_ini) {
    $db_ini = parse_ini_file(__DIR__ . '/../config/reg_conf.ini', true);
}
require_once(__DIR__ . '/../lib/db_functions.php');
require_once(__DIR__ . '/../lib/log.php');
db_connect();
$con = get_conf('con');
$log = get_conf('log');
$id = $con['id'];
$maxDiff = 7*24*60*60;

// first find the unpaid registrations and log those
$regQ = <<<EOS
WITH UNPAID AS (
    SELECT *
    FROM reg 
    WHERE complete_trans IS NULL AND perid IS NOT NULL AND conid = ? AND IFNULL(paid, 0) = 0
)
SELECT DISTINCT u.*
FROM UNPAID u
JOIN memList mu ON (u.memId = mu.id)
JOIN reg r ON (r.perid = u.perid and r.memId = u.memId and r.complete_trans is not null AND r.conid = ?
    AND TIMESTAMPDIFF(SECOND,u.create_date,r.create_date) > 0 AND TIMESTAMPDIFF(SECOND,u.create_date,r.create_date) < ?)
EOS;
$regR = dbSafeQuery($regQ, 'iii', array($id, $id, $maxDiff));
if ($regR === false)
    exit(1);
if ($regR->num_rows == 0) {
    echo "No records to process\n";
    exit(0);
}

$unpaidsReg = [];
$regRollback = [];
while ($reqL = $regR->fetch_assoc()) {
    $unpaidsReg[] = $reqL;
    $keys = array_keys($reqL);
    $values = [];
    $ikeys = [];
    foreach ($keys as $key) {
        $ikeys[] = $key;
        $values[] = $reqL[$key];
    }
    $stmt = 'INSERT INTO reg(' . join(',', $ikeys) . ") VALUES ('" . join("','", $values) . "');";
    //echo $stmt . PHP_EOL;
    $regRollback[] = $stmt;
}
$regR->free();

// now all the unpaid transactions that match those
$transQ = <<<EOS
WITH UNPAID AS (
    SELECT *
    FROM reg 
    WHERE complete_trans IS NULL AND perid IS NOT NULL AND conid = ? AND IFNULL(paid, 0) = 0
), PAID AS (
    SELECT DISTINCT u.create_trans, r.create_trans as PaidTrans
    FROM UNPAID u
    JOIN reg r ON (r.perid = u.perid and r.memId = u.memId and r.complete_trans is not null AND r.conid = ?
        AND TIMESTAMPDIFF(SECOND,u.create_date,r.create_date) > 0 AND TIMESTAMPDIFF(SECOND,u.create_date,r.create_date) < ?)
)
SELECT DISTINCT t.*
FROM PAID p
JOIN transaction t on (p.create_trans = t.id)
EOS;
$transR = dbSafeQuery($transQ, 'iii', array($id, $id, $maxDiff));
if ($transR === false)
    exit(1);
if ($transR->num_rows == 0) {
    echo "No records to process\n";
    exit(0);
}

$unpaidsTrans = [];
$transRollback = [];
while ($transL = $transR->fetch_assoc()) {
    $unpaidsTrans[] = $transL;
    $keys = array_keys($transL);
    $values = [];
    $ikeys = [];
    foreach ($keys as $key) {
        $ikeys[] = $key;
        $values[] = $transL[$key];
    }
    $stmt = 'INSERT INTO transaction(' . join(',', $ikeys) . ") VALUES ('" . join("','", $values) . "');";
    //echo $stmt . PHP_EOL;
    $transRollback[] = $stmt;
}
$transR->free();
logInit($log['db']);
logWrite(array('type' => 'cleanUnpaid', 'reg' => $regRollback, 'trans' => $transRollback));

// now delete all the reg records attached to those transactions
$num_reg = 0;
foreach ($unpaidsReg AS $reg) {
    //echo "DELETE FROM reg WHERE id = ?;', 'i', " . $reg['id'] . ")\n";
    $num_reg += dbSafeCmd('DELETE FROM reg WHERE id = ?;', 'i', array($reg['id']));
}

// now delete all the transaction records attached to those registrations
$num_trans = 0;
foreach ($unpaidsTrans AS $trans) {
    //echo "DELETE FROM transaction WHERE id = ?;', 'i', " . $trans['id'] . ")\n";
    $num_trans = dbSafeCmd('DELETE FROM transaction WHERE id = ?;', 'i', array($trans['id']));
}
echo "$num_reg registrations deleted, $num_trans transactions deleted\n";

exit(0);
