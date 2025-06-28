<?php
// make the new memberships for this year for life and goh's.
// this script will just add to those already make if they exists, so new life members or GoH's can get memberships.

global $db_ini;

if (!$db_ini) {
    $db_ini = parse_ini_file(__DIR__ . '/../config/reg_conf.ini', true);
}
require_once(__DIR__ . '/../lib/db_functions.php');
require_once(__DIR__ . '/../lib/global.php');
db_connect();
$con = get_conf('con');
$conid = $con['id'];

// parameters -
// -t test mode, just show what it would do, not make any database changes
// -l life membership memId
// -l goh membership memId
// -L life perid range as start-end inclusive
// -G goh perid range as start-end inclusive
// -v verbose
// -h show help instructions

// get command line options
$options = getopt('g:G:hl:L:tv');

if ($options === false)
    calling_seq("options did not parse correctly");

if (array_key_exists('h', $options)) {
    calling_seq("Calling Sequence");
}

$testsite = array_key_exists('t', $options);
$verboseMode = array_key_exists('v', $options);
$gohMemId = array_key_exists('g', $options) ? $options['g'] : 986;
$lifeMemId = array_key_exists('l', $options) ? $options['l'] : 987;

if (array_key_exists('G', $options)) {
    $vals = explode('-', $options['G']);
    $guest_start = (int) $vals[0];
    $guest_end = (int) $vals[1];
} else {
    $guest_start = 1001;
    $guest_end = 9999;
}

if (array_key_exists('L', $options)) {
    $vals = explode('-', $options['G']);
    $life_start = (int) $vals[0];
    $life_end = (int) $vals[1];
} else {
    $life_start = 1;
    $life_end = 1000;
}

// check for overlaps
    if (
        ($life_start >= $guest_start && $life_start <= $guest_end)
        || ($life_end <= $guest_end && $life_end >= $guest_start)
        || ($guest_start >= $life_start && $guest_start <= $life_end)
        || ($guest_end <= $life_end && $guest_end >= $life_start)
    ) {
    echo "Life range of $life_start-$life_end overlaps goh range of $guest_start-$guest_end\n";
    exit(1);
}

if ($verboseMode) {
    echo <<<EOS
Parameter Summary:
Convention id: $conid
Life Members: $life_start through $life_end of memory type $lifeMemId
Former GoH: $guest_start through $guest_end of memory type $gohMemId
Test Mode: $testsite

EOS;
}
// validate memId and make sure it is for this year
$memCheckQ = <<<EOS
SELECT id, conid, price, label
FROM memList
WHERE id in (?, ?);
EOS;
$numRowsExpected = $gohMemId == $lifeMemId ? 1 : 2;
$numRows = 0;
$memFound = '';
$memCheckR = dbSafeQuery($memCheckQ, 'ii', array($gohMemId, $lifeMemId));
if ($memCheckR === false) {
    echo "Invalid memlist check query\n";
    exit(1);
}
while ($line = $memCheckR->fetch_assoc()) {
    if ($line['conid'] != $conid) {
        echo "MemId " . $line['id'] . ", " . $line['label'] . " is not for this conid of $conid\n";
        exit(1);
    }
    if ($line['price'] != 0) {
        echo 'MemId ' . $line['id'] .  ", " . $line['label'] . " is not for this a free membership\n";
        exit(1);
    }
    $memFound .= $line['id'] . ': ' . $line['label'] . PHP_EOL;
    $numRows++;
}
$memCheckR->free();
if ($numRows != $numRowsExpected) {
    echo "Looking for $numRowsExpected memList entries but only found $numRows\n $memFound\n";
    exit(1);
}

// all the arguments are now validated

// lifeMembers
$numRows = addMemberships('Life', $life_start, $life_end, $lifeMemId, $testsite, $verboseMode, $conid);
echo "$numRows life memberships added\n";
// goh's
$numRows = addMemberships('GoH', $guest_start, $guest_end, $gohMemId, $testsite, $verboseMode, $conid);
echo "$numRows GoH memberships added\n";

exit(0);

function addMemberships($type, $start, $end, $memId, $testsite, $verboseMode, $conid): int {
    if ($verboseMode) {
        echo "Starting check for how many new $type Memberships are needed in the range of $start-$end using $conid:$memId\n";
    }

    $memQ = <<<EOS
SELECT p.id, 
    REPLACE(REPLACE(REPLACE(REPLACE(LOWER(TRIM(IFNULL(p.phone, ''))), ')', ''), '(', ''), '-', ''), ' ', '') AS phoneCheck,
    TRIM(REGEXP_REPLACE(CONCAT_WS(' ', first_name, middle_name, last_name, suffix), '  *', ' ')) AS fullName, r.create_date
FROM perinfo p
LEFT OUTER JOIN reg r ON r.perid = p.id AND r.conid = ? AND r.memId = ?
WHERE p.id BETWEEN ? AND ?;
EOS;

    $memR = dbSafeQuery($memQ, 'iiii', array ($conid, $memId, $start, $end));
    $numAdded = 0;
    $numExist = 0;
    if ($memR === false) {
        echo "Error in member select query of type $type\n";
        exit(1);
    }
    $people = [];
    while ($person = $memR->fetch_assoc()) {
        if ($person['create_date'] != null) {
            if ($verboseMode) {
                echo $person['id'] . ": " . $person['fullName'] . " already has a membership of type $type/id $memId\n";
            }
            $numExist++;
        } else
            $people[] = $person;
    }
    $memR->free();

    if ($verboseMode) {
        echo "$numExist already have memberships, " . count($people) . " need memberships\n";
    }

    // now more to add
    if (count($people) == 0) {
        return 0;
    }

    $insTranQ = <<<EOS
INSERT INTO transaction(conid, perid, userid, create_date, complete_date, price, couponDiscount, paid, withtax, tax, type)
VALUES (?,?,1,NOW(),NOW(),0,0,0,0,0,?);
EOS;
    $insRegQ = <<<EOS
INSERT INTO reg(conid, perid, create_date, price, couponDiscount, paid, create_trans, complete_trans, create_user, memId)
VALUES (?, ?, NOW(), 0, 0, 0, ?, ?, 1, ?);
EOS;


    foreach ($people as $person) {
        if ($testsite) {
            echo "Would: insert tranaction for $conid:" . $person['id'] . " and registration of id $memId for $type\n";
            continue;
        }
        $tid = dbSafeInsert($insTranQ, 'iis', array($conid, $person['id'], $type . '/free'));
        if ($tid === false) {
            echo "Unable to insert transaction for " . $person['id'] . ", continuing with remainder of list\n";
            continue;
        }
        $regid = dbSafeInsert($insRegQ, 'iiiii', array($conid, $person['id'], $tid, $tid, $memId));
        if ($regid === false) {
            echo 'Unable to insert registration for ' . $person['id'] . ", continuing with remainder of list\n";
            continue;
        }
        if ($verboseMode) {
            echo "Inserted transaction $tid and registration $regid for " . $person['id'] . ': ' . $person['fullName'] . PHP_EOL;
        }
        $numAdded++;
    }

    return $numAdded;
}

function calling_seq($msg): void {
    echo <<<EOS
$msg

boskone_lifegoh options:
    -g goh membership memId
    -l life membership memId
    -G start-end, the start and end range of perid's for former GoH, inclusive, defaults to 1001-9999
    -L start-end, the start and end range of perid's for life members, inclusive, defaults to 1-1000
    -t test mode, just show what it would do, not make any database changes
    -v verbose
    -h show calling sequence instructions
   
Example:
    php boskone_lifegoh -g 1234 -l 1235 -v -t

EOS;
    exit(0);
}