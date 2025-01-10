<?php
// send interests - send csv file and direct emails for those receiving interest notifications for new entries
global $db_ini;
if (!$db_ini) {
    $db_ini = parse_ini_file(__DIR__ . '/../config/reg_conf.ini', true);
}
require_once(__DIR__ . '/../lib/db_functions.php');
require_once(__DIR__ . '/../lib/global.php');
require_once(__DIR__ . '/../lib/email__load_methods.php');
db_connect();
$con = get_conf('con');
$local = get_conf('local');
$conid = $con['id'];
$regadminemail = $con['regadminemail'];

if (array_key_exists('csvto', $local))
    $csvTo = $local['csvto'];
else
    $csvTo = null;

if (array_key_exists('csvcc', $local))
    $csvCc = $local['csvcc'];
else
    $csvCC = null;

if (array_key_exists('csvsavedir', $local))
    $csvSaveDir = $local['csvsavedir'];
else
    $csvSaveDir = '.';

// parameters -
// -c ccAddress - CC all emails to this address
// -n - no send,update, do not mark the records as updated, just echo what you would be doing
// -q just show errors, be quiet about everything else
// -t emailAddress - force all emails to go to this 'test' address
// -v verbose level (0 or missing, none, 1: progress messages, 2: progress + dumps
// -h show help instructions

// get command line options
$options = getopt('c:nqt:v:h');

if ($options === false)
    calling_seq("options did not parse correctly");

if (array_key_exists('h', $options)) {
    calling_seq("Calling Sequence");
}

$verbose = 0;
if (array_key_exists('v', $options)) {
    $verbose = $options['v'];
    if ($verbose == null || $verbose == '')
        $verbose = 0;
}

$noSendUpdate = array_key_exists('n', $options);
$quiet = array_key_exists('q', $options);
$testEmailAddress = null;
if (array_key_exists('t', $options)) {
    $testEmailAddress = $options['t'];
    if ($testEmailAddress == '')
        $testEmailAddress = null;
    else if (!filter_var($testEmailAddress, FILTER_VALIDATE_EMAIL)) {
        calling_seq("-t $testEmailAddress is not a valid email address");
    }
    if ($csvTo != null)
        $csvTo = $testEmailAddress;
    $csvCc = null;
}
$ccEmailAddress = null;
if (array_key_exists('c', $options)) {
    $ccEmailAddress = $options['c'];
    if ($ccEmailAddress == '')
        $ccEmailAddress = null;
    else if (!filter_var($ccEmailAddress, FILTER_VALIDATE_EMAIL)) {
        calling_seq("-c $ccEmailAddress is not a valid email address");
    }
}
$runDate = date(DATE_RFC2822);
if ($verbose) {
    echo <<<EOS
Starting data fetch:
    noSendUpdate: $noSendUpdate
    cc: $ccEmailAddress
    to: $testEmailAddress
    rundate: $runDate

EOS;
}

load_email_procs();
$emailsSent = 0;
// get all the plans
if ($verbose) echo "Getting interests\n";
$interests = getInterests();

// get last csv date and last notify date
$lastDatesQ = <<<EOS
SELECT MAX(notifyDate) As notifyDate, max(csvDate) AS csvDate
FROM memberInterests
WHERE conid = ?;
EOS;

$lastDatesR = dbSafeQuery($lastDatesQ, 'i', array($conid));
if ($lastDatesR === false) {
    echo "Error retrieving last send dates\n";
    exit();
}
$row = $lastDatesR->fetch_assoc();
$lastDatesR->free();
$lastCSVDate = $row['csvDate'];
$lastNotifyDate = $row['notifyDate'];
if ($lastCSVDate == null)
    $lastCSVDate = 'Never';
if ($lastNotifyDate == null)
    $lastNotifyDate = 'Never';

if ($verbose) echo "Getting People to check\n";
$people = [];

$getP = <<<EOS
SELECT m.id, m.perid, m.conid, m.newperid, m.interested,
    CASE 
        WHEN m.perid IS NOT NULL THEN p.email_addr
        ELSE n.email_addr
    END AS email_addr,
    CASE 
        WHEN m.perid IS NOT NULL THEN p.first_name
        ELSE n.first_name
    END AS first_name,
    CASE 
        WHEN m.perid IS NOT NULL THEN p.last_name
        ELSE n.last_name
    END AS last_name,
    CASE 
        WHEN m.perid IS NOT NULL THEN TRIM(REGEXP_REPLACE(CONCAT(IFNULL(p.first_name, ''),' ', IFNULL(p.middle_name, ''), ' ', 
            IFNULL(p.last_name, ''), ' ', IFNULL(p.suffix, '')), '  *', ' '))
        ELSE TRIM(REGEXP_REPLACE(CONCAT(IFNULL(n.first_name, ''),' ', IFNULL(n.middle_name, ''), ' ',
            IFNULL(n.last_name, ''), ' ', IFNULL(n.suffix, '')), '  *', ' '))
    END AS fullName
FROM memberInterests m
LEFT OUTER JOIN perinfo p ON (p.id = m.perid)
LEFT OUTER JOIN newperson n ON (n.id = m.newperid)
WHERE m.conid = ? and m.interest = ? AND m.notifyDate IS NULL AND (DATEDIFF(m.updateDate, m.createDate) > 0 || m.interested = 'Y')
ORDER BY last_name, first_name, perid, newperid;
EOS;

$updP = <<<EOS
UPDATE memberInterests m
SET notifyDate = NOW()
WHERE m.conid = ? and m.interest = ? AND m.notifyDate IS NULL
EOS;


// we want all the interest entries for an interest that need notifying, for direct notifications
foreach ($interests as $interestRow) {
    if ($verbose > 2) {
        echo "Starting new interest:\n";
        var_dump($interestRow);
    }
    $interest = $interestRow['interest'];
    // skip over those that are not to be notified, for now don't update the date
    //TODO decide if the list is empty now, then when filled update all people or just since change date
    $notifyAddrs = trim($interestRow['notifyList']);
    if ($notifyAddrs == null || $notifyAddrs == '') {
        if ($verbose > 1)
            echo "Skipping interest $interest, notify list is empty\n";
        continue;
    }

    if ($testEmailAddress != null) {
        $notifyArr = explode('l', $testEmailAddress);
    } else {
        $notifyArr = explode('l', $notifyAddrs);
    }
    for ($index = 0; $index < count($notifyArr); $index++) {
        $notifyArr[$index] = trim($notifyArr[$index]);
    }

    $notifyR = dbSafeQuery($getP, 'is', array($conid, $interest));
    if ($notifyR === false) {
        echo "Error in notify query for $interest\n";
        exit();
    }
    if ($notifyR->num_rows == 0) {
        $notifyR->free();
        if (!$quiet)
            echo "No unset notifications for $interest\n";
        continue;
    }
    $notifyList = [];
    while ($row = $notifyR->fetch_assoc()) {
        $notifyList[] = $row;
    }
    $notifyR->free();

    // now we have a list of people to send for this interest
    if ($verbose > 2) {
        echo "List to notify $interest to $notifyAddrs:\n";
        var_dump($notifyList);
    }

    // build the email body
    $emailText = <<<EOS
To: $notifyAddrs

You have been configured by $regadminemail to receive notifications of changea to the interest '$interest' by members.
This is the list of updates as of $runDate

EOS;
    $csv = "lastName, firstName, fullName, emailAddr, interested\n";
    foreach ($notifyList as $row) {
        $csvRow = array($row['last_name'], $row['first_name'], $row['fullName'], $row['email_addr'], $row['interested']);
        $csv .= '"' . join('","', $csvRow) . '"' . PHP_EOL;
    }

    $fname = date_format(date_create(), 'Y-m-d-H-i-s') . "-$interest.csv";
    $csvF = fopen("$csvSaveDir/$fname", 'w');
    fwrite($csvF, $csv);
    fclose($csvF);

    if ($verbose > 1) {
        echo "email text:\n$emailText\n\n";
    }

    if (!$noSendUpdate) {
        $return_arr = send_email($regadminemail, $notifyAddrs, $ccEmailAddress, "Interest $interest Change Notifications since $lastNotifyDate",
                                 $emailText, null, array(array("$csvSaveDir/$fname", $fname, 'application/csv')));

        if (array_key_exists('error_code', $return_arr)) {
            $error_code = $return_arr['error_code'];
        }
        else {
            $error_code = null;
        }
        if (array_key_exists('email_error', $return_arr))
            echo "Unable to send interests email to $notifyAddrs, error: " . $return_arr['email_error'] . ", Code: $error_code\n";
        else {
            if ($verbose) echo "Notification email sent to $notifyAddrs\n";
            $emailsSent++;

            $numRows = dbSafeCmd($updP, 'is', array($conid, $interest));
            if ($verbose) {
                echo "$numRows updated to current date for $interest";
            }
        }
    }
}

if ($verbose) echo "sendinterests individual notifies completed\n";

if ($csvTo != null) {
// send the combinded CSV file
    $interestFields = '';
    $joins = '';
    foreach ($interests as $interestRow)  {
        $interest = $interestRow['interest'];
        $interestFields .= ",$interest.interested AS $interest";
        $joins .= <<<EOS
LEFT OUTER JOIN nonCSV $interest ON ($interest.conid = m.conid AND $interest.interest = '$interest'
    AND IFNULL(m.perid, -1) = IFNULL($interest.perid, -1)
    AND IFNULL(m.newperid, -1) = IFNULL($interest.newperid, -1))

EOS;
    }

    $csvGetQ = <<<EOS
WITH nonCSV AS (
SELECT DISTINCT *
    FROM memberInterests
    WHERE conid = ? AND csvDate IS NULL
), perids AS (
	SELECT DISTINCT perid, conid, newperid
    FROM nonCSV
)
SELECT m.perid, m.newperid,
    CASE 
        WHEN m.perid IS NOT NULL THEN p.email_addr
        ELSE n.email_addr
    END AS email_addr,
    CASE 
        WHEN m.perid IS NOT NULL THEN p.first_name
        ELSE n.first_name
    END AS first_name,
    CASE 
        WHEN m.perid IS NOT NULL THEN p.last_name
        ELSE n.last_name
    END AS last_name,
    CASE 
        WHEN m.perid IS NOT NULL THEN TRIM(REGEXP_REPLACE(CONCAT(IFNULL(p.first_name, ''),' ', IFNULL(p.middle_name, ''), ' ', 
            IFNULL(p.last_name, ''), ' ', IFNULL(p.suffix, '')), '  *', ' '))
        ELSE TRIM(REGEXP_REPLACE(CONCAT(IFNULL(n.first_name, ''),' ', IFNULL(n.middle_name, ''), ' ',
            IFNULL(n.last_name, ''), ' ', IFNULL(n.suffix, '')), '  *', ' '))
    END AS fullName $interestFields
FROM perids m
LEFT OUTER JOIN perinfo p ON (p.id = m.perid)
LEFT OUTER JOIN newperson n ON (n.id = m.newperid)
$joins;
EOS;

    $updCSVP = <<<EOS
UPDATE memberInterests
SET csvDate = NOW()
WHERE csvDate IS NULL AND conid = ?;
EOS;


    $csvGetR = dbSafeQuery($csvGetQ, 'i', array ($conid));
    if ($csvGetR === false) {
        echo "Error in csv query for $conid\n";
        exit();
    }

    if ($csvGetR->num_rows == 0) {
        if (!$quiet)
            echo "No new csv rows to report on since $lastCSVDate\n";
        $msgTxt = <<<EOM
There is nothing new to send since $lastCSVDate.

EOM;
    }
    else {
        $first = true;
        $csv = '';
        while ($row = $csvGetR->fetch_assoc()) {
            if ($first) {
                $csv = implode(',', array_keys($row)) . PHP_EOL;
                $first = false;
            }
            $csv .= '"' . implode('","', $row) . '"' . PHP_EOL;
        }
        if ($verbose > 1) {
            echo "csv:" . PHP_EOL . $csv . PHP_EOL;
        }

        if (!$noSendUpdate) {
            if ($csvTo != null) {
                $fname = date_format(date_create(), 'Y-m-d-H-i-s') . "-allInterests.csv";
                $csvF = fopen("$csvSaveDir/$fname", 'w');
                fwrite($csvF, $csv);
                fclose($csvF);
                $emailText = <<<EOM
Interested records since $lastCSVDate.

EOM;

                $return_arr = send_email($regadminemail, $csvTo, $csvCc, "Periodic Interests CSV Send",
                                         $emailText, null, array (array ("$csvSaveDir/$fname", $fname, 'application/csv')));

                if (array_key_exists('error_code', $return_arr)) {
                    $error_code = $return_arr['error_code'];
                }
                else {
                    $error_code = null;
                }
                if (array_key_exists('email_error', $return_arr))
                    echo "Unable to send csv change email to $csvTo, error: " . $return_arr['email_error'] . ", Code: $error_code\n";
                else {
                    if ($verbose) echo "CSV Change email sent to $csvTo\n";
                    $emailsSent++;

                    $numRows = dbSafeCmd($updCSVP, 'i', array ($conid));
                    if ($verbose)
                        echo "$numRows updated to current date for $interest";
                }
            }
        }
    }
    $csvGetR->free();
}

if ($verbose) echo "sendinterests task completed\n";

if (!$quiet)
    echo "$emailsSent notification emails sent.\n";
exit(0);

function calling_seq($msg) {
    echo <<<EOS
$msg

sendinterests options:
    -c ccAddress - CC all emails to this address
    -n - no send,update, do not mark the records as updated, just echo what you would be doing
    -q just show errors, be quiet about everything else
    -t emailAddress - force all emails to go to this 'test' address
    -v verbose level (0 or missing, none, 1: progress messages, 2: progress + dumps
    -h show help instructions
   
Example:
    php sendinterests -c reg@test.org  -v 2

EOS;
    exit(0);
}

    function getInterests() {
        $interests = null;
        $iQ = <<<EOS
SELECT *
FROM interests
WHERE active = 'Y'
ORDER BY sortOrder ASC;
EOS;
        $iR = dbQuery($iQ);
        if ($iQ !== false) {
            $interests = [];
            while ($row = $iR->fetch_assoc()) {
                $interests[] = $row;
            }
            $iR->free();
            if (count($interests) == 0) {
                $interests = null;
            }
        }
        return $interests;
    }