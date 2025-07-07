<?php
// make coupon keys file from a file of perid's, one per line,
require_once('../lib/global.php');
require_once('../lib/db_functions.php');
db_connect();
$server = getConfValue('reg','server');

// parameters -
// -c coupon id (number of that coupon)
// -n notes field (if you don't want the coupon name from the coupon table)  This field must be enclosed in "'s if it contains more than one word.
// -i inputfile of perid's one per line, if -i is omitted, it reads from stdin
// -o output file of keys and links, one per line, if -o is omitted it writes to stdout
// -q don't show confirmations and just insert the keys and write the output file
// -h show help instructions

// get command line options
$options = getopt('c:n:i:o:qh');

if ($options === false)
    calling_seq("options did not parse correctly");

if (array_key_exists('h', $options)) {
    calling_seq("Calling Sequence");
}

if (!array_key_exists('c', $options))
    calling_seq("required argument '-c coupon' missing");

// first get the coupon information
$couponQ = <<<EOS
SELECT id, code, name, oneUse, startDate, endDate
FROM coupon
WHERE id = ?;
EOS;
$couponR = dbSafeQuery($couponQ, 'i', array($options['c']));
if ($couponR === false)
    die(mysqli_error());

if ($couponR->num_rows != 1) {
    fwrite(STDERR, "Cannot retrieve coupon data for coupon " . $options['c'] . PHP_EOL);
    exit(1);
}
$coupon = $couponR->fetch_assoc();
$couponR->free();

if ($coupon['oneUse'] != 1) {
    fwrite(STDERR, "Error: coupon " . $options['c'] . "(" . $coupon['code'] . ") is not a one use coupon and can not be used to make keys." . PHP_EOL);
    exit(1);
}

// now load the list of perids
$pids = [];
if (array_key_exists('i', $options)) {
    $file = fopen($options['i'], 'r');
    if ($file === false) {
        fwrite(STDERR, "Cannot open PID file (" . $options['i'] . ") for reading (-i arguement)" . PHP_EOL);
        exit(2);
    }
} else {
    $file = STDIN;
}

$ok = true;
while ($line = fgets($file)) {
    $line = trim($line, " \t\r\n");
    if (!is_numeric($line)) {
        fwrite(STDERR, "Error: non numreric characters found in PID line: '" . $line . "', processing will not continue" . PHP_EOL);
        $ok = false;
    } else {
        $pids[] = trim($line, " \t\r\n,.:");
    }
}

if (!$ok)
    exit(3);

if (count($pids) == 0) {
    fwrite(STDERR, "Error: no perids were found in the input file/stream" . PHP_EOL);
    exit(4);
}

$pidlist = implode(',', $pids);
$pidQ = <<<EOS
SELECT id, first_name, middle_name, last_name, suffix, email_addr
FROM perinfo
WHERE id in ($pidlist);
EOS;
$pidR = dbQuery($pidQ);
$rows = $pidR->num_rows;
if ($rows === false) {
    fwrite(STDERR, "Error: error in format of pid list '$pidlist'" . PHP_EOL);
    exit(5);
}
if ($rows != count($pids)) {
    fwrite(STDERR, "Error: " . count($pids) - $rows . " PIDs were not found in '$pidlist'" . PHP_EOL);
    exit(6);
}

$people = [];
while ($person = $pidR->fetch_assoc()) {
    $people[] = $person;
}
$pidR->free();

if (array_key_exists('n', $options))
    $note = $options['n'];
else
    $note = $coupon['name'];

// if no -q, preview list of keys to insert
if (!array_key_exists('q', $options)) {
    fwrite(STDOUT, "Preview of keys to create:" . PHP_EOL . PHP_EOL);
    foreach ($people as $person) {
        fwrite(STDOUT, implode(",", $person) . ":" . implode(',', array($coupon['id'], $note)) . PHP_EOL);
    }

    fwrite(STDOUT, PHP_EOL . PHP_EOL . "Continue (y/n)?");
    fflush(STDOUT);
    $ans = fgets(STDIN);
    $ans = strtolower(trim($ans, " \t\r\n"));
    if ($ans != 'y') {
        fwrite(STDERR, "Aborting makekeys on non 'y' answer" . PHP_EOL);
        exit(0);
    }
}
// open output file
if (array_key_exists('o', $options)) {
    $file = fopen($options['o'], 'w');
    if ($file === false) {
        fwrite(STDERR, 'Cannot open output file (' . $options['o'] . ') for writing (-o arguement)' . PHP_EOL);
        exit(2);
    }
} else {
    $file = STDOUT;
}

$inskey = <<<EOS
INSERT INTO couponKeys(couponId, guid, perid, notes)
VALUES (?, ?, ?, ?);
EOS;
foreach ($people as $person) {
    $guid = guidv4();
    $perid = $person['id'];
    $notes = $note . ": " . $person['email_addr'];
    $fields = '"' .  join ('","', $person) . '"';
    $newid = dbSafeInsert($inskey, 'isis', array($coupon['id'], $guid, $perid, $notes));
    if ($newid) {
        fwrite($file, "$fields, " . $server . '?offer=' . base64_encode_url($coupon['code'] . '~!~' . $guid) . "\n");
    } else {
        fwrite(STDERR, "Insert failed for $perid\n");
    }
}

exit(0);

function calling_seq($msg) {
    echo <<<EOS
$msg

makekeys options:
    -c coupon numeric id of the coupon to use to create the keys (required)
    -n notes field (if you don't want the coupon name from the coupon table)  This field must be enclosed in "'s if it contains more than one word. (optional)
    -i inputfile of perid's, one per line, if -i is omitted, it reads from stdin.  This is not a CSV file, there is no header line, just one perid per line.
    -o output file of keys and links, one per line, if -o is omitted it writes to stdout
    -q don't show confirmations and just insert the keys and write the output file (quiet or quick mode)
    -h show calling sequence instructions
   
Example:
    php makekeys -c 4 -n "Override Note" -i perid_file.txt -o links_file.csv

EOS;
    exit(0);
}
