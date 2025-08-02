`<?php
// Plan Reminders - send reminder emails about payment plans needing payment
require_once('../lib/global.php');
require_once('../lib/db_functions.php');
require_once('../lib/paymentPlans.php');
require_once(_'../lib/email__load_methods.php');

loadConfFile();
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
// -i days between reminders (interval), default 7.  Note: will send on exact due date anyway.
// -k [p|c] kill plans past their p: pay by date, or c: within 10 days of con start date
// -l do not log emails sent to database table
// -q just show errors, be quiet about everything else
// -s suppress the past due portion of the note (used during the catch up phase)
// -t emailAddress - force all emails to go to this 'test' address
// -v verbose level (0 or missing, none, 1: progress messages, 2: progress + dumps
// -x delete expired 0 paid, unpaid memberships after as part of cancelling plan
// -h show help instructions

// get command line options
$options = getopt('c:d:hi:k:lqst:v:x');

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
$doNotLog = array_key_exists('l', $options);

$days = 7;
if (array_key_exists('d', $options))
    $days = $options['d'];

if (!is_numeric($days) || $days < 1 | $days > 90)
    calling_seq("Invalid number of days passed in -d $days, valid is 1-90");

$interval = 7;
if (array_key_exists('i', $options))
    $interval = $options['i'];

if (!is_numeric($interval) || $interval < 1 | $interval > 90)
    calling_seq("Invalid number of interval days passed in -i $interval, valid is 1-90");

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

$kill = 'n';
$killDate = '2099/12/31';
if (array_key_exists('k', $options)) {
    $kill = $options['k'];
    if ($kill == 'c') {
        $conEndQ = <<<EOS
SELECT DATE_ADD(startdate, INTERVAL -10 DAY) < NOW()
FROM conlist
WHERE id = ?;
EOS;
        $conR = dbSafeQuery($conEndQ, 'i', $conid);
        if ($conR === false || $conR->num_rows != 1) {
            calling_seq("error reading con end date for kill by con end - 10");
        }
        $killDate = $conR->fatch_row()[0];
        $conR->free();

        if ($killDate != 1) {
            // not yet kill date
            if ($verbose)
                echo "Disabling Kill as it's not within 10 days of con start\n";
            $kill = 'n';
        }
    }
}

$expire = array_key_exists('x', $options);

if ($verbose) {
    echo <<<EOS
Starting data fetch:
    days: $days
    cc: $cc
    to: $to
    kill: $kill
    expire: $expire
  

EOS;
}

$dolfmt = new NumberFormatter('', NumberFormatter::CURRENCY);
$emailsSent = 0;

// get all the plans
if ($verbose) echo "Getting Payment Plans\n";
$data = getPaymentPlans();
$plans = $data['plans'];
if ($verbose) echo count($plans) . " payment plans loaded\n";

if ($kill == 'c' || $kill == 'p') {
    // ok, check for plans past the due date and kill them
    $kQ = <<<EOS
SELECT pp.*, pln.name, p.first_name, p.last_name, p.email_addr,
    TRIM(REGEXP_REPLACE(CONCAT_WS(' ', first_name, middle_name, last_name, suffix), '  *', ' ')) AS fullName
FROM payorPlans pp
JOIN paymentPlans pln ON (pp.planId = pln.id)
JOIN perinfo p ON (p.id = pp.perid)
WHERE status = 'active'

EOS;
    if ($kill == 'p') {
        $kQ .= "AND NOW() > pp.payByDate";
    }
    $kQ .= ';' . PHP_EOL;
    $kR = dbQuery($kQ);
    if ($kR === false) {
        echo "Error reading in the payor plans to kill\n";
        exit(1);
    }
    while ($kP = $kR->fetch_assoc()){
        $emailsSent += killPlan($kP, $label, $dolfmt, $currency, $verbose, $portalSite, $regadminemail, $to, $cc);
    }
    if ($verbose) {
        echo $kR->num_rows . " plans cancelled for being past due date\n";
    }
    $kR->free();
}

if ($verbose) echo "Getting Payor Plans\n";
$payorPlans = [];
$payorPlanIdx = [];
// get all the payor plans that are not paid off
$ppQ = <<<EOS
SELECT pp.*, p.name
FROM payorPlans pp
JOIN paymentPlans p on (pp.planId = p.id)
WHERE status = 'active' /* and conid = ? */
ORDER BY perid, createDate;
EOS;
//$ppR = dbSafeQuery($ppQ, 'i', array($conid));
$ppR = dbQuery($ppQ);
if ($ppR === false) {
    echo "Error reading in the payor plans\n";
    exit(1);
}

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
}

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
SELECT p.*, TRIM(REGEXP_REPLACE(CONCAT_WS(' ', p.first_name, p.middle_name, p.last_name, p.suffix), '  *', ' ')) AS fullName
FROM pids pp
JOIN perinfo p ON pp.id = p.id;
EOS;

//$pR = dbSafeQuery($pQ, 'i', array($conid));
$pR = dbQuery($pQ);
if ($pR === false) {
    echo "Error reading in person records\n";
    exit(1);
}

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
$mailTrackLastQ = <<<EOS
SELECT DATEDIFF(NOW(), MAX(sentDate)) AS days
FROM payorPlanReminders
WHERE payorPlanId = ? AND conid = ? AND perid = ?;
EOS;


foreach ($payorPlans AS $payorPlan) {
    if ((!array_key_exists('perid', $payorPlan)) || $payorPlan['perid'] == null) {
        if ($verbose) echo "no perid for " . $payorPlan['id'] . ", skipping reminder until matched\n";
        continue;
    }
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
        if ($verbose) echo "Skipping because of being more than $days from due date of $nextPayDue\n\n";
        continue;
    }

    //TODO check days since last send, to determine if we need to send it again.
    // now check if we have sent them a reminder within the past i days.  If so, don't send another right now, but always send on due date.
    if ($daysPastDue != 0) {
        $mailTrackLastR = dbSafeQuery($mailTrackLastQ, 'iii', array($payorPlan['id'], $payorPlan['conid'], $payorPlan['perid']));
        if ($mailTrackLastR === false) {
            echo "Unable to check last sent date for " . $payorPlan['id'] . ':' . $payorPlan['conid'] . ":" . $payorPlan['perid'] . "\n";
        }
        if ($mailTrackLastR->num_rows == 1) {
            $lastDays = $mailTrackLastR->fetch_row()[0];
            if ($lastDays != null && $lastDays < $interval) {
                if ($verbose) echo "Skipping because $lastDays < $interval\n";
                $mailTrackLastR->free();
                continue;
            }
        }
        $mailTrackLastR->free();
    }

    if ($ignorePastDue) {
        $due = "Your payment plan payment is now due.";
        $duehtml = "Your payment plan payment is now due.";
    }
    else if ($daysPastDue <= 0) {
        $day = $daysPastDue == -1 ? 'day' : 'days';
        $due = "Your next payment plan payment is due in " . (-$daysPastDue) . " $day on $nextPayDue.";
        $duehtml = "Your next payment plan payment is due in " . (-$daysPastDue) . " $day on $nextPayDue.";
    }
    else {
        $day = $daysPastDue == 1 ? 'day' : 'days';
        $due = "Your payment plan payment is past due by $daysPastDue $day.";
        $duehtml = "<strong>Your payment plan payment is past due by $daysPastDue $day.</strong>";
    }
    $minAmt = $data['minAmt'];
    $minAmtNum = $data['minAmtNum'];

    if ($verbose) echo "Message will be:\n$due\nYour minimum amount due is $minAmt\n";

    // build the reminder email
    $createDate = $data['dateCreated'];
    $emailSubject = "Reminder about your Plan Payment For $label created $createDate - $due";
    $fullName = $person['fullName'];
    $balanceDue = $data['balanceDue'];
    $payByDate = $data['payByDate'];
    $nextPayDueDate = $data['nextPayDueDate'];
    $emailText = <<<EOS
$fullName has an active payment plan with $label created $createDate. $due

You may pay any amount up to the remaining balance of the plan of $balanceDue,  however the minimum amount due at this time is $minAmt.  
Please note that this plan must be paid in full by $payByDate.

To make your payment please visit the $label Membership Portal at $portalSite and click the "Make Pmt" button in the "Payment Plans for this account" section.

If you have any questions, please reach out to us at $regadminemail

$label Registration
EOS;

    $emailHTML = <<<EOS
<p>$fullName has an active payment plan with $label created $createDate. $duehtml</p>
<p>You may pay any amount up to the remaining balance of the plan of $balanceDue, however the minimum amount due at this time is $minAmt.  
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
        if ($verbose) echo "Reminder email sent to $sendTo\n";
        $emailsSent++;

        if (!$doNotLog) {
            $trackId = dbSafeInsert($mailTrackInsQ, 'iiissd', array ($payorPlan['perid'], $payorPlan['id'], $conid, $sendTo, $nextPayDue, $minAmtNum));
            if ($verbose) echo "Log key = $trackId\n";
            if ($trackId === false) {
                echo "unable to create tracking record for $person:$payorPlan:$conid:$sendTo:$nextPayDue:$minAmtNum\n";
            }
        }
    }
}

if ($expire) {
    // delete all 'expired' memberships that are unpaid and $0 paid
    $expiredU = <<<EOS
UPDATE reg
JOIN memList m ON reg.memId = m.id
SET reg.status = 'cancelled'
WHERE reg.status = 'unpaid' AND reg.paid = 0 AND reg.price > 0 AND m.enddate < NOW();
EOS;

    $numExpired = dbCmd($expiredU);
    if ($verbose)
        echo "Expired unpaid: $numExpired rows marked cancelled";

// and then delete them
    $expiredD = <<<EOS
DELETE reg
FROM reg
JOIN memList m ON reg.memId = m.id
WHERE reg.status = 'cancelled' AND reg.paid = 0 AND reg.price > 0 AND m.enddate < NOW();
EOS;

    $numexpired = dbCmd($expiredD);
    if ($verbose)
        echo "Expired unpaid: $numExpired rows marked deleted";
}

if ($verbose) echo "Reminders task completed\n";

echo "Send $emailsSent reminder emails out of " . count($payorPlans) . " payorPlans in " . count($plans) . " plans\n";
exit(0);

function killPlan($planInfo, $label, $dolfmt, $currency, $verbose, $portalSite, $regadminemail, $to, $cc) {
    // set the plan status to cancelled, and then clear all the memberships assocated with the plan, plus compute the final amount due on those memberships
    $amountDue = 0;
    $regQ = <<<EOS
SELECT id, price, paid
FROM reg
WHERE planId = ? AND status = 'plan';
EOS;
    $regU = <<<EOS
UPDATE reg
SET status = 'unpaid'
WHERE planId = ? AND status = 'plan';
EOS;

    $planU = <<<EOS
UPDATE payorPlans
SET status = 'cancelled'
WHERE id = ?;
EOS;


    $regR = dbSafeQuery($regQ, 'i', array($planInfo['id']));
    if ($regR === false) {
        echo "Error deleting plan " . $planInfo['id'] . "\n";
        return 0;
    }
    while ($regL = $regR->fetch_assoc()) {
        // mark them all unpaid if paid != price
        $amountDue += $regL['price'] - ($regL['paid'] + $regL['couponDiscount']);
    }
    $regR->free();

    // now mark them all unpaid, and the plan cancelled
    $numRegUpd = dbSafeCmd($regU, 'i', array($planInfo['id']));
    $numUpd = dbSafeCmd($planU, 'i', array($planInfo['id']));

    // now tell the owner it's cancelled
    if ($verbose) echo "Message will be:\n$amountDue\n";

    // build the reminder email
    $createDate = $planInfo['dateCreated'];
    $emailSubject = "Your Plan Payment For $label created $createDate has been cancelled for non payment";
    $fullName = $planInfo['fullName'];
    $balanceDue = $dolfmt->format_currency($amountDue, $currency);
    $payByDate = $planInfo['payByDate'];
    $sendTo = $to ? $to : $planInfo['email_addr'];
    $emailText = <<<EOS
$fullName has an active payment plan with $label created $createDate.

This plan was supposed have been paid by $payByDate, and is now cancelled.  All memberships under this plan that are full or partially paid remain so.
All memberships that have zero payment against them and are for memberships that are no longer available have been deleted.

You may use the portal to pay the remaining balance on the existing memberships and purchase replacement memberships for the expired ones.
At the time of this cancellation your balance due on those memberships was $balanceDue.

To make your payment please visit the $label Membership Portal at $portalSite.

If you have any questions, please reach out to us at $regadminemail

$label Registration
EOS;

    $emailHTML = <<<EOS
<p>$fullName has an active payment plan with $label created $createDate.</p>
<p>This plan was supposed have been paid by $payByDate, and is now cancelled.  All memberships under this plan that are full or partially paid remain so.
All memberships that have zero payment against them and are for memberships that are no longer available have been deleted.</p>
<p>You may use the portal to pay the remaining balance on the existing memberships and purchase replacement memberships for the expired ones.
At the time of this cancellation your balance due on those memberships was $balanceDue.</p>
<p>To make your payment please visit the $label Membership Portal at <a href="$portalSite">$portalSite</a>.</p>
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
    if (array_key_exists('email_error', $return_arr)) {
        echo "Unable to send receipt email to $sendTo, error: " . $return_arr['email_error'] . ", Code: $error_code\n";
        return 0;
    }
    if ($verbose) echo "Reminder email sent to $sendTo\n";
    return 1;
}

function calling_seq($msg) {
    echo <<<EOS
$msg

planreminders options:
    -c ccAddress - CC all emails to this address
    -d days before payment is due to send notice, default = 7
    -i days between reminders (interval), default 7.  Note: will send on exact due date anyway.
    -k [p|c] kill plans past their p: pay by date, or c: within 10 days of con start date
    -l do not log emails sent to database table
    -q just show errors, be quiet about everything else
    -s suppress the past due portion of the note (used during the catch up phase)
    -t emailAddress - force all emails to go to this 'test' address
    -v verbose level (0 or missing, none, 1: progress messages, 2: progress + dumps
    -x delete expired 0 paid, unpaid memberships after as part of cancelling plan
    -h show help instructions
   
Example:
    php planreminders -c reg@test.org -i 7 -v 2

EOS;
    exit(0);
}
