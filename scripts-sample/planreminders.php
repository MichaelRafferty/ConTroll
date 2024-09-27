<?php
// Plan Reminders - send reminder emails about payment plans needing payment
global $db_ini;
if (!$db_ini) {
    $db_ini = parse_ini_file(__DIR__ . '/../config/reg_conf.ini', true);
}
require_once(__DIR__ . '/../lib/db_functions.php');
require_once(__DIR__ . '/../lib/global.php');
require_once(__DIR__ . '/../lib/paymentPlans.php');
require_once(__DIR__ . '/../lib/email__load_methods.php');
db_connect();
$con = get_conf('con');
$portal = get_conf('portal');
$conid = $con['id'];
$label = $con['label'];
$regadminemail = $con['regadminemail'];
$portalSite = $portal['portalsite'];
if (array_key_exists('currency', $con)) {
    $currency = $con['currency'];
} else {
    $currency = 'USD';
}

// parameters -
// -c ccAddress - CC all emails to this address
// -d days before payment is due to send notice, default = 7
// -l log emails sent to database table
// -q just show errors, be quiet about everything else
// -s suppress the past due portion of the note (used during the catch up phase)
// -t emailAddress - force all emails to go to this 'test' address
// -v verbose level (0 or missing, none, 1: progress messages, 2: progress + dumps
// -h show help instructions

// get command line options
$options = getopt('c:d:hlqst:v:');

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

$ignorePastDue = array_key_exists('s', $options);

$days = 7;
if (array_key_exists('d', $options))
    $days = $options['d'];

if (!is_numeric($days) || $days < 1 | $days > 90)
    calling_seq("Invalid number of days passed in -d $days, valid is 1-90");

$cc = null;
$to = null;
if (array_key_exists('c', $options)) {
    $cc = $options['c'];
    if (!filter_var($cc, FILTER_VALIDATE_EMAIL)) {
        calling_seq("-c $cc is not a valid email address");
    }
}
if (array_key_exists('t', $options)) {
    $to = $options['t'];
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        calling_seq("-t $to is not a valid email address");
    }
}

if ($verbose) {
    echo <<<EOS
Starting data fetch:
    days: $days
    cc: $cc
    to: $to


EOS;
}

$dolfmt = new NumberFormatter('', NumberFormatter::CURRENCY);

// get all the plans
if ($verbose) echo "Getting Payment Plans\n";
$data = getPaymentPlans();
$plans = $data['plans'];
if ($verbose) echo count($plans) . " payment plans loaded\n";

if ($verbose) echo "Getting Payor Plans\n";
$payorPlans = [];
$payorPlanIdx = [];
// get all the payor plans that are not paid off
$ppQ = <<<EOS
SELECT pp.*, p.name FROM payorPlans pp
JOIN paymentPlans p on (pp.planId = p.id)
WHERE status = 'active' /* and conid = ? */
ORDER BY perid, createDate;
EOS;
//$ppR = dbSafeQuery($ppQ, 'i', array($conid));
$ppR = dbQuery($ppQ);
if ($ppR === false) {
    echo "Error reading in the payor plans\n";
    exit(1);
};

$index = 0;
while ($row = $ppR->fetch_assoc()) {
    $payorPlans[] = $row;
    $payorPlanIdx[$row['id']] = $index;
    $index++;
}
$ppR->free();
if ($verbose) echo count($payorPlans) . " payor plans loaded\n";

// now get the payments for these plans
if ($verbose) echo "Getting Payor Plan Payments\n";
$ppQ = <<<EOS
SELECT pp.*, p.perid
FROM payorPlanPayments pp
JOIN payorPlans p ON pp.payorPlanId = p.id
WHERE p.status = 'active' /* and p.conid = ? */
ORDER BY p.perid, p.createDate, pp.payorPlanId;
EOS;
//$ppR = dbSafeQuery($ppQ, 'i', array($conid));
    $ppR = dbQuery($ppQ);
if ($ppR === false) {
    echo "Error reading in the payor plan payments\n";
    exit(1);
};

$priorid = -1;
$payments = [];
$index = 0;

while ($row = $ppR->fetch_assoc()) {
    if ($priorid != $row['payorPlanId']) {
        if ($priorid >= 0) {
            $prow = $payorPlanIdx[$priorid];
            $payorPlans[$prow]['payments'] = $payments;
        }
        $priorid = $row['payorPlanId'];
        $payments = [];
    }
    $payments[$row['paymentNbr']] = $row;
    $index++;
}
$ppR->free();
if ($priorid >= 0) {
    $prow = $payorPlanIdx[$priorid];
    $payorPlans[$prow]['payments'] = $payments;
}
if ($verbose) echo "$index payor plan payments loaded\n";

// now load the perid info
if ($verbose) echo "Getting person information mentioned in plans\n";
$people = [];
$pQ = <<<EOS
WITH pids AS (
    SELECT DISTINCT p.id
    FROM payorPlans pp
    JOIN perinfo p ON pp.perid = p.id
    WHERE pp.status = 'active' /* and pp.conid = ? */
)
SELECT p.*, TRIM(REGEXP_REPLACE(CONCAT(IFNULL(p.first_name, ''),' ', IFNULL(p.middle_name, ''), ' ', IFNULL(p.last_name, ''), ' ',  
        IFNULL(p.suffix, '')), '  *', ' ')) AS fullName
FROM pids pp
JOIN perinfo p ON pp.id = p.id;
EOS;

//$pR = dbSafeQuery($pQ, 'i', array($conid));
$pR = dbQuery($pQ);
if ($pR === false) {
    echo "Error reading in person records\n";
    exit(1);
};

$index = 0;
$people = [];
while ($row = $pR->fetch_assoc()) {
    $people[$row['id']] = $row;
    $index++;
}
$pR->free();
if ($verbose) echo "$index people loaded\n";

// data is now loaded, loop over the plans, and check if a payment is due
load_email_procs();

$mailTrackInsQ = <<<EOS
INSERT INTO payorPlanReminders(perid, payorPlanId, conid, emailAddr, dueDate, minAmt)
VALUES (?, ?, ?, ?, ?, ?);
EOS;

foreach ($payorPlans AS $payorPlan) {
    $person = $people[$payorPlan['perid']];
    $sendTo = $to ? $to : $person['email_addr'];
    if ($verbose)
        echo "\n\nStarting to check plan: " . $payorPlan['id'] . " for " . $payorPlan['perid'] . ": " . $person['fullName'] . ' at ' . $person['email_addr'] .
            " sent to $to\n\n";

    $data = computeNextPaymentDue($payorPlan, $plans, $dolfmt, $currency);
    if ($verbose > 1)
        var_dump($data);

    // determine if a payment is due
    $daysPastDue = round($data['daysPastDue']);
    $nextPayDue = $data['nextPayDue'];

    if ((-$daysPastDue) > $days) {
        if ($verbose)
            echo "Skiping becauase of being more than $days from due date of $nextPayDue\n\n";
        continue;
    }

    if ($ignorePastDue) {
        $due = "Your payment plan payment is now due.";
        $duehtml = "Your payment plan payment is now due.";
    }
    else if ($daysPastDue <= 0) {
        $day = $daysPastDue == -1 ? 'day' : 'days';
        $due = "Your next payment plan payment is due in " . (-$daysPastDue) . " $day, on $nextPayDue.";
        $duehtml = "Your next payment plan payment is due in " . (-$daysPastDue) . " $day, on $nextPayDue.";
    }
    else {
        $day = $daysPastDue == 1 ? 'day' : 'days';
        $due = "Your payment plan payment is past due by $daysPastDue $day.";
        $duehtml = "<strong>Your payment plan payment is past due by $daysPastDue $day.</strong>";
    }
    $minAmt = $data['minAmt'];
    $minAmtNum = $data['minAmtNum'];

    if ($verbose) {
        echo "Message will be:\n$due\nYour minimum amount due is $minAmt\n";
    }

    // build the reminder email
    $emailSubject = "Reminder about your Plan Payment For $label - $due";
    $fullName = $person['fullName'];
    $balanceDue = $data['balanceDue'];
    $payByDate = $data['payByDate'];
    $nextPayDueDate = $data['nextPayDueDate'];
    $emailText = <<<EOS
Dear $fullName,

You have an active payment plan with $label. $due

You may pay any amount up to the remaining balance of the plan of $balanceDue.  But the minimum amount due at this time is $minAmt.  
Please note that this plan must be paid in full by $payByDate.

To make your payment please visit the $label Membership Portal at $portalSite and click the "Make Pmt" button in the "Payment Plans for this account" section.

If you have any questions, please reach out to us at $regadminemail

$label Registration
EOS;

    $emailHTML = <<<EOS
<p>Dear $fullName,</p>
<p>You have an active payment plan with $label. $duehtml</p>
<p>You may pay any amount up to the remaining balance of the plan of $balanceDue.  But the minimum amount due at this time is $minAmt.  
Please note that this plan must be paid in full by $payByDate.</p>
<p>To make your payment please visit the $label Membership Portal at <a href="$portalSite">$portalSite</a>
and click the "Make Pmt" button in the "Payment Plans for this account" section.</p>
<p>If you have any questions, please reach out to us at <a href="mailto:$regadminemail?subject=Payment%20Plan%20Question"$regadminemail</a>.</p>
<p>&nbsp;</p>
<p>$label Registration</p>
EOS;

    $return_arr = send_email($regadminemail, $sendTo, $cc, $emailSubject, $emailText, $emailHTML);

    if (array_key_exists('error_code', $return_arr)) {
        $error_code = $return_arr['error_code'];
    }
    else {
        $error_code = null;
    }
    if (array_key_exists('email_error', $return_arr))
        echo "Unable to send receipt email to $sendTo, error: " . $return_arr['email_error'] . ", Code: $error_code\n";
    else {
        if ($verbose)
            echo "Reminder email sent to $sendTo\n";

        // (perid, payorPlanId, conid, emailAddr, dueDate, minAmt)
        $trackId = dbSafeInsert($mailTrackInsQ, 'iiissd', array ($person, $payorPlan, $conid, $sendTo, $nextPayDueDate, $minAmtNum));
        if ($trackId === false) {
            echo "unable to create tracking record for $person:$payorPlan:$conid:$sendTo:$nextPayDueDate:$minAmtNum\n";
        }
    }
}

if ($verbose)
    echo "Reminders task completed\n";

exit(0);

function calling_seq($msg) {
    echo <<<EOS
$msg

planreminders options:
    -c ccAddress - CC all emails to this address
    -d days before payment is due to send notice, default = 7
    -l log emails sent to database table
    -q just show errors, be quiet about everything else
    -s suppress the past due portion of the note (used during the catch up phase)
    -t emailAddress - force all emails to go to this 'test' address
    -v verbose level (0 or missing, none, 1: progress messages, 2: progress + dumps
    -h show help instructions
   
Example:
    php makekeys -c 4 -n "Override Note" -i perid_file.txt -o links_file.csv

EOS;
    exit(0);
}