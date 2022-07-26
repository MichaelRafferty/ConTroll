<?php
require_once("db_functions.php");

function getEmailBody($transid) {
  $condata = get_con();
  $ini = get_conf('reg');
  $con = get_conf('con');

$ownerQ = <<<EOS
SELECT NP.first_name, NP.last_name, P.receipt_id as payid, T.complete_date, P.receipt_url AS url
FROM transaction T
JOIN newperson NP ON (NP.id=T.newperid)
JOIN payments P ON (P.transid=T.id)
WHERE T.id=?
;
EOS;
$owner = fetch_safe_assoc(dbSafeQuery($ownerQ, 'i', array($transid)));


$body = trim($owner['first_name'] . " " . $owner['last_name']) .",\n\n";
$body .= "thank you for registering for ". $condata['label'] . "\n\n";

if($ini['test']==1) {
  $body .= "This Page is for test purposes only\n\n";
}

$body .= "Your Transaction number is $transid and Receipt number is " . $owner['payid'] . "\n\nIn response to your request Badges have been created for:\n\n";

$badgeQ = <<<EOS
SELECT NP.first_name, NP.last_name, A.label
FROM transaction T
JOIN reg R ON  (R.create_trans=T.id)
JOIN newperson NP ON (NP.id = R.newperid)
JOIN memList M ON (R.memID = M.id)
JOIN ageList A ON (M.memAge = A.ageType AND M.conid = A.conid)
WHERE  T.id= ?
EOS;

$badgeR = dbSafeQuery($badgeQ, 'i', array($transid));

while($badge = fetch_safe_assoc($badgeR)) {
  $body.= "     * ". $badge['first_name'] . " " . $badge['last_name']
    . " (" . $badge['label'] . ")\n\n";
}

if ($owner['url'] != '') {
    $body .= "Your credit card receipt is available at " . $owner['url'] . "\n\n";
} else {
    $body .= "You will receive a separate email with credit card receipt details.\n\n";
}

$body .= "Please contact " . $con['regemail'] . " with any questions and we look forward to seeing you at " . $condata['label'] . ".\n";

$body .=
"For hotel information and directions please see " . $con['hotelwebsite'] . "\n" .
"Click " . $con['policy'] . " for the " . $con['policytext'] . ".\n" .
"For more information about " . $con['conname'] . " please email " . $con['infoemail'] . ".\n" .
"For questions about " . $con['conname'] . " Registration, email " . $con['regemail'] . ".\n" .
$con['conname'] . " memberships are not refundable, except in case of emergency. For details and questions about transfers and rollovers to future conventions, please see The Registration Policies Page.\n"
;

return $body;
}
