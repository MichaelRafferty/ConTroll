<?php
// vendor approval, request, payment emails

// Psssword reset
function vendorReset($passwd, $dest, $portalName, $reply) {
    $conf = get_conf('con');
    $vendor_conf = get_conf('vendor');
    $body = "The password to you " . $conf['conname'] . " " . $portalName . " Portal account has been reset.\nThe new password is:\n\n\t$passwd\n\n" .
        "Please login to the " . $conf['conname'] . " " . $portalName . " Portal site at " . $dest .
        " to change your password.\n\n" .
        "If you continue to have problems please contact " . $reply . ".\n\nThank you for your interest in " . $conf['conname'] . ".\n";

return $body;
}

// request for approval
function approval($vendorId, $regionName, $ownerName, $ownerEmail, $portalName) {
    $conf = get_conf('con');
    $conid = $conf['id'];
    $vendorQ = <<<EOS
SELECT e.exhibitorName, e.exhibitorEmail, e.website, e.description, ey.contactName, ey.contactEmail
FROM exhibitors e
JOIN exhibitorYears ey ON (e.id = ey.exhibitorId)
WHERE e.id=? AND ey.conid = ?;
EOS;
    $vendorR = dbSafeQuery($vendorQ, 'ii', array($vendorId, $conid));
    $vendorL = $vendorR->fetch_assoc();
    $exhibitorName = $vendorL['exhibitorName'];
    $exhibitorEmail = $vendorL['exhibitorEmail'];
    $website = $vendorL['website'];
    $description = $vendorL['description'];
    $descriptionText =strip_tags($description);
    $contactName = $vendorL['contactName'];
    $contactEmail = $vendorL['contactEmail'];
    $vendorR->free();

    $body = <<<EOS
Dear $ownerName:
    $exhibitorName has requested permission to request space in $regionName.
    
They have provided the following description:

$descriptionText

Their website is $website

Please followup with $contactName at $contactEmail if you have any further questions.

Respectfully submitted,
$portalName Portal
EOS;
    $bodyhtml = <<<EOS
<p>Dear $ownerName</p>
<p>$exhibitorName has requested permission to request space in $regionName.</p>
<p>They have provided the following description:</p>
<hr>
$description
<hr>
<p>Their website is <a href="$website" target="_blank">$website</a>.<p>
<p>Please followup with $contactName at <a href="mainto:$contactEmail">$contactEmail</a> if you have any further questions.</p>
<p>Respectfully submitted,<br/>$portalName Portal</p>
EOS;

    return array($contactName, $contactEmail, $body, $bodyhtml);
}

// request space
function request($exhibitorInfo, $regionInfo, $portalName, $spaces) {
    $conf = get_conf("con");
    $conid = $conf['id'];

    $ownerName = $regionInfo['ownerName'];
    $exhibitorName = $exhibitorInfo['exhibitorName'];
    $contactName = $exhibitorInfo['contactName'];
    $contactEmail = $exhibitorInfo['contactEmail'];
    $description = $exhibitorInfo['description'];
    $descriptionText =strip_tags($description);
    $regionName = $regionInfo['name'];
    $ownerName = $regionInfo['ownerName'];
    $website = $exhibitorInfo['website'];
    if ($spaces == '') {
        $requestType = 'cancelled their';
        $spaces =  "We are sorry $exhibitorName has had to cancel their space request in the $regionName.";
    } else {
        $requestType = 'requested';
        $spaces = "They have requested:\n" . $spaces;
    }
    $body = <<<EOS
Dear $ownerName:
    $exhibitorName has $requestType space in $regionName.
    
They have provided the following description:

$descriptionText

Their website is $website

$spaces

Please followup with $contactName at $contactEmail if you have any further questions.

Respectfully submitted,
$portalName Portal
EOS;

    $spacesHtml = str_replace("\n", "<br>\n", $spaces);
    $bodyhtml = <<<EOS
<p>Dear $ownerName</p>
<p>$exhibitorName has $requestType space in $regionName.</p>
<p>They have provided the following description:</p>
<hr>
$description
<hr>
<p>Their website is <a href="$website" target="_blank">$website</a>.<p>
<p>$spacesHtml</p>
<p>Please followup with $contactName at <a href="mainto:$contactEmail">$contactEmail</a> if you have any further questions.</p>
<p>Respectfully submitted,<br/>$portalName Portal</p>
EOS;

    return array($body, $bodyhtml);
}

// space payment confirmation
function payment($results) {
    $buyer = $results['buyer'];
    $vendor = $results['vendor'];
    $space = $results['space'];

    $conf = get_conf('con');
    $vendor_conf = get_conf('vendor');
    $dolfmt = new NumberFormatter('', NumberFormatter::CURRENCY);

    $body = "Dear " . trim($buyer['fname'] . ' ' . $buyer['lname']) . ":\n\n" .
        "Here is your receipt for payment of " . $dolfmt->formatCurrency($results['approved_amt'], 'USD') . ' for ' . $conf['label'] . ' ' . $space['name'] . "\n\n" .
        "RECEIPT FOR PAYMENT TO: " . $conf['label'] . ' on ' . date('m/d/Y h:i:s A', time()) . "\n\n" .
        "Vendor: \n" .
        $vendor['name'] . "\n" .
        $vendor['addr'] . "\n";
        if ($vendor['addr2'] && $vendor['addr2'] != '')
            $body .= $vendor['addr2'] . "\n";
        $body .= $vendor['city'] . ', ' . $vendor['state'] . ' ' . $vendor['zip'] . "\n\n" .
            "Space: " . $space['name'] . ' (' . $space['description'] . ') with up to ' . $space['includedMemberships'] . ' included memberships and up to ' . $space['additionalMemberships'] . " additional memberships\n" .
            $vendor_conf['taxidlabel'] . ': ' . $results['taxid'] . "\n\n" .
            "Price for Space: " . $dolfmt->formatCurrency($space['price'], 'USD') . "\n\n" .
            "Special Requests:\n" . $results['specialrequests'] . "\n\n";

        $body .= "Memberships purchased at this time:\n\n";
        foreach ($results['formbadges'] as $badge) {
            if ($badge['type'] == 'i')
                $body .= "Included membership " . $badge['index'] . ":\n     ";
            else
                $body .= "Additional membership ". $badge['index'] . ": for " . $dolfmt->formatCurrency($badge['price'], 'USD') .  "\n     ";
            $body .= $badge['fname'] . ' ' . ltrim($badge['mname'] . ' ') . $badge['lname'] . ' ' . $badge['suffix'] . "\n     " .
                $badge['addr'] . "\n     ";
            if ($badge['addr2'] && $badge['addr2'] != '')
                $body .= $badge['addr2'] . "\n     ";
            $body .= $badge['city'] . ', ' . $badge['state'] . ' ' . $badge['zip'] . ' ' . $badge['country'] . "\n     " .
                'Badgename: ' . $badge['badgename'] . "\n\n";
        }

        $body .= "Total amount: " . $dolfmt->formatCurrency($results['total'], 'USD') . "\n\n" .
            "If you have any questions please contact the " . $space['name'] . ' staff at ' . $vendor_conf[$space['shortname']]  . ".\n\nThank you\n";

    return array($body);
}
