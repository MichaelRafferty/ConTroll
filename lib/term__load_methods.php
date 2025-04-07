<?php

// load the appropriate methods for processing credit cards based on the config file
function load_term_procs() : void {
    $reg = get_conf('reg');
    $cc = get_conf('cc');
    $con = get_conf('con');

    switch ($cc['type']) {
        case 'square':
            require_once (__DIR__ . "/../Composer/vendor/autoload.php");
            require_once("term_square.php");
            break;
        case 'test':
            if ((!array_key_exists('demo', $cc)) || $cc['demo'] != 1) { // allow demo override on test for cc
                if (($cc['env'] != 'sandbox') || $reg['test'] != 1) {
                    ajaxSuccess(array ('status' => 'error', 'data' => 'Something thinks this is a real terminal method'));
                    exit();
                }
            }
            require_once("term_test.php");
            break;
        case 'bypass':
            if (isDirectAllowed()) {
                require_once("term_bypass.php");
                break;
            } else {
                echo "Bypass is not a valid credit card terminal provider for this configuration\n";
                exit();
            }
        default:
            echo "No valid credit card terminal provider defined\n";
            exit();
    }
}

// database access functions in common
function getTerminal($name): array|null {
    $getQ = <<<EOS
SELECT *
FROM terminals
WHERE name = ?;
EOS;
    $getR = dbSafeQuery($getQ, 's', array ($name));
    if ($getR === false || $getR->num_rows != 1)
        return null;

    $term = $getR->fetch_assoc();
    $getR->free();
    return $term;
}

function addTerminal($terminal): string {
    $name = $terminal['name'];
    $createAt = date('Y-m-d H:i:s', strtotime($terminal['created_at']));
    $pairBy = date('Y-m-d H:i:s', strtotime($terminal['pair_by']));
    $changedAt = date('Y-m-d H:i:s', strtotime($terminal['status_changed_at']));
    if (getTerminal($name)) {

// update the existing record as a new pairing code was requested
        $updSQL = <<<EOS
UPDATE terminals
SET productType = ?, locationId = ?, squareId = ?, squareCode = ?, pairBy = ?, createDate = ?, status = ?, statusChanged = ?
WHERE name = ?;
EOS;
        $upd = dbSafeCmd($updSQL, 'sssssssss', array ($terminal['product_type'], $terminal['location_id'], $terminal['id'],
            $terminal['code'], $pairBy, $createAt, $terminal['status'], $changedAt, $name));
        if ($upd === false)
            return "Error: Updating terminal $name returned an error, seek assistance";

        if ($upd === 0)
            return 'Nothing to change';

        return "Terminal $name updated successfully";
    }

    $insSQL = <<<EOS
INSERT INTO terminals(name, productType, locationId, squareId, squareCode, pairBy, createDate, status, statusChanged)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?);
EOS;
    $ins = dbSafeInsert($insSQL, 'sssssssss', array ($name, $terminal['product_type'], $terminal['location_id'], $terminal['id'],
        $terminal['code'], $pairBy, $createAt, $terminal['status'], $changedAt));

    if ($ins === false)
        return "Error: Inserting terminal $name returned an error, seek assistance";

    return "Terminal $name added successfully";
}

function listTerminals() {
    $sql = "SELECT * FROM terminals";
    $terminals = [];
    $sqlR = dbQuery($sql);
    while ($terminal = $sqlR->fetch_assoc()) {
        $terminals[] = $terminal;
    }
    $sqlR->free();
    return $terminals;
}

function updateTerminal($terminal): string {
    $name = $terminal['name'];
    $createAt = date('Y-m-d H:i:s', strtotime($terminal['created_at']));
    $changedAt = date('Y-m-d H:i:s', strtotime($terminal['status_changed_at']));
    if (array_key_exists('pairBy', $terminal)) {
        $pairBy = date('Y-m-d H:i:s', strtotime($terminal['pair_by']));
    } else {
        $pairBy = null;
    }
    if (array_key_exists('paired_at', $terminal)) {
        $pairedAt = date('Y-m-d H:i:s', strtotime($terminal['paired_at']));
    } else {
        $pairedAt = null;
    }

// update the existing record as a new pairing code was requested
    $updSQL = <<<EOS
UPDATE terminals
SET productType = ?, locationId = ?, squareId = ?, squareCode = ?, pairBy = ?, pairedAt = ?, createDate = ?, status = ?, statusChanged = ?, deviceId = ?
WHERE name = ?;
EOS;
    $upd = dbSafeCmd($updSQL, 'sssssssssss', array ($terminal['product_type'], $terminal['location_id'], $terminal['id'],
        $terminal['code'], $pairBy, $pairedAt, $createAt, $terminal['status'], $changedAt, $terminal['device_id'], $name));
    if ($upd === false)
        return "Error: Updating terminal $name returned an error, seek assistance";

    if ($upd === 0)
        return 'Nothing to change';

    return "Terminal $name updated successfully";
}
