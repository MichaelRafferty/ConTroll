<?php
// clean unpaid - delete unpaid web registrations that are complete in a subsequent transaction
//      or
//  delete expired $0 paid unpaid registrations
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
$controll = get_conf('controll');
$useportal = $controll['useportal'];
$id = $con['id'];
$maxDiff = 7*24*60*60;

if ($useportal == 0) {
    // only for non portal sites

    // first find the unpaid registrations and log those
    $regQ = <<<EOS
WITH UNPAID AS (
    SELECT *
    FROM reg 
    WHERE complete_trans IS NULL AND perid IS NOT NULL AND conid in (?, ?) 
        AND (IFNULL(paid, 0) = 0 AND IFNULL(price, 0) > 0) AND status = 'unpaid'
)
SELECT DISTINCT u.*
FROM UNPAID u
JOIN memList mu ON (u.memId = mu.id)
JOIN reg r ON (r.perid = u.perid and r.memId = u.memId and r.complete_trans is not null AND r.conid = u.conid)
    AND TIMESTAMPDIFF(SECOND,u.create_date,r.create_date) > 0 AND TIMESTAMPDIFF(SECOND,u.create_date,r.create_date) < ?;
EOS;
    $regR = dbSafeQuery($regQ, 'iii', array ($id, $id + 1, $maxDiff));
    if ($regR === false) {
        echo "Phase 1: Error with find unpaid query for non portal\n";
        exit(1);
    }
    if ($regR->num_rows == 0) {
        echo "Non Portal Phase 1: No unpaid -> paid records to process\n";
    }
    else {
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
    WHERE complete_trans IS NULL AND perid IS NOT NULL AND conid IN (?, ?) AND IFNULL(paid, 0) = 0 AND status = 'unpaid'
), PAID AS (
    SELECT DISTINCT u.create_trans, r.create_trans as PaidTrans
    FROM UNPAID u
    JOIN reg r ON (r.perid = u.perid and r.memId = u.memId and r.complete_trans is not null AND r.conid = u.conid
        AND TIMESTAMPDIFF(SECOND,u.create_date,r.create_date) > 0 AND TIMESTAMPDIFF(SECOND,u.create_date,r.create_date) < ?)
)
SELECT DISTINCT t.*
FROM PAID p
JOIN transaction t on (p.create_trans = t.id)
EOS;
        $transR = dbSafeQuery($transQ, 'iii', array ($id, $id + 1, $maxDiff));
        if ($transR === false)
            exit(1);
        if ($transR->num_rows == 0) {
            echo "Phase 1: No unpaid -> paid transactions to process\n";
        }
        else {
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
            logWrite(array ('type' => 'cleanUnpaid', 'reg' => $regRollback, 'trans' => $transRollback));

            // now delete all the reg records attached to those transactions
            $num_reg = 0;
            foreach ($unpaidsReg as $reg) {
                //echo "DELETE FROM reg WHERE id = ?;', 'i', " . $reg['id'] . ")\n";
                $num_reg += dbSafeCmd('DELETE FROM reg WHERE id = ?;', 'i', array ($reg['id']));
            }

            // now delete all the transaction records attached to those registrations
            $num_trans = 0;
            foreach ($unpaidsTrans as $trans) {
                $num_rows = dbSafeCmd('UPDATE newperson SET transid = NULL WHERE transid = ?;', 'i', array ($trans['id']));
                //echo "DELETE FROM transaction WHERE id = ?;', 'i', " . $trans['id'] . ")\n";
                $num_trans = dbSafeCmd('DELETE FROM transaction WHERE id = ?;', 'i', array ($trans['id']));
            }
            echo "Non Portla Phase 1: $num_reg registrations deleted from unpaid attempts becoming paid with a later transaction, $num_trans transactions deleted\n";
        }
    }

    //  now for just unpaid ones
    $maxDiff = 30 * 24 * 60 * 60;
    $regQ = <<<EOS
SELECT r.*
FROM reg r
JOIN transaction t ON (r.create_trans = t.id)
WHERE r.complete_trans IS NULL AND r.perid IS NOT NULL AND r.conid IN (?, ?) AND (IFNULL(r.paid, 0) = 0 AND IFNULL(r.price, 0) > 0)
    AND r.status = 'unpaid' AND TIMESTAMPDIFF(SECOND,r.create_date,NOW()) > ?;
EOS;
    $regR = dbSafeQuery($regQ, 'iii', array ($id, $id + 1, $maxDiff));
    if ($regR === false) {
        echo "Non Portal Phase 2: all unpaids: error in unpaid query\n";
        exit(1);
    }
    if ($regR->num_rows == 0) {
        echo "Phase 2: No old unpaid records to process\n";
    }
    else {
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
    SELECT DISTINCT r.create_trans
    FROM reg r
    JOIN transaction t ON (r.create_trans = t.id)
    WHERE r.complete_trans IS NULL AND r.perid IS NOT NULL 
        AND r.conid IN (?, ?) AND IFNULL(r.paid, 0) = 0 AND IFNULL(r.price, 0) > 0 AND r.status = 'unpaid'
        AND TIMESTAMPDIFF(SECOND,r.create_date,NOW()) > ?
)
SELECT DISTINCT t.*
FROM UNPAID u
JOIN transaction t on (u.create_trans = t.id)
EOS;
        $transR = dbSafeQuery($transQ, 'iii', array ($id, $id + 1, $maxDiff));
        if ($transR === false) {
            echo "Non Portal Phase 2: Transaction query error\n";
            exit(1);
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
        logWrite(array ('type' => 'cleanUnpaidExpired', 'reg' => $regRollback, 'trans' => $transRollback));

        // now delete all the reg records attached to those transactions
        $num_reg = 0;
        foreach ($unpaidsReg as $reg) {
            //echo "DELETE FROM reg WHERE id = ?;', 'i', " . $reg['id'] . ")\n";
            $num_reg += dbSafeCmd('DELETE FROM reg WHERE id = ?;', 'i', array ($reg['id']));
        }

        // now delete all the transaction records attached to those registrations
        $num_trans = 0;
        foreach ($unpaidsTrans as $trans) {
            $num_rows = dbSafeCmd('UPDATE newperson SET transid = NULL WHERE transid = ?;', 'i', array ($trans['id']));
            //echo "DELETE FROM transaction WHERE id = ?;', 'i', " . $trans['id'] . ")\n";
            $num_trans = dbSafeCmd('DELETE FROM transaction WHERE id = ?;', 'i', array ($trans['id']));
        }
        echo "Non PortalPhase 2: $num_reg registrations deleted from expired unpaid, $num_trans transactions deleted\n";
    }
}

// now clean up all unpaid memberships that are expired, leave the transactions alone
// first make them cancelled to add the latest value to regHistory
$expiredU = <<<EOS
UPDATE reg
JOIN memList m ON reg.memId = m.id
SET reg.status = 'cancelled'
WHERE reg.status = 'unpaid' AND reg.paid = 0 AND reg.price > 0 AND m.enddate < NOW();
EOS;

$numExpired = dbCmd($expiredU);
echo "Expired unpaid: $numExpired rows marked cancelled";

// and then delete them
$expiredD = <<<EOS
DELETE reg
FROM reg
JOIN memList m ON reg.memId = m.id
WHERE reg.status = 'cancelled' AND reg.paid = 0 AND reg.price > 0 AND m.enddate < NOW();
EOS;

$numexpired = dbCmd($expireD);
echo "Expired unpaid: $numExpired rows marked deleted";
exit(0);
