<?php
// clean unpaid - delete unpaid web registrations that are complete in a subsequent transaction
//      or
//  delete expired $0 paid unpaid registrations
//  notes: must have a perid assigned and match the perid of the paid transaction
// currently only looks for payments within one week of original transaction

require_once('../lib/global.php');
require_once('../lib/db_functions.php');
require_once('../lib/log.php');

loadConfFile();
db_connect();

$con = get_conf('con');
$log = get_conf('log');
$controll = get_conf('controll');
$useportal = $controll['useportal'];
$id = $con['id'];

// clean up all unpaid memberships that are expired, leave the transactions alone
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

$numexpired = dbCmd($expiredD);
echo "Expired unpaid: $numExpired rows marked deleted";
exit(0);
