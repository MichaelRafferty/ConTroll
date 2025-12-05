<?php
// vendor approval, request, payment emails

// Psssword reset
function vendorReset($token, $email, $portalName, $reply) {
    $conName = getConfValue('con', 'conName');
    $body = "You have requested a password reset for $conName's $portalName\n\n" .
        "Please use this link to reset all passwords for $email.\n\n$token\n\n" .
        "If you continue to have problems please contact " . $reply . ".\n\nThank you for your interest in $conName.\n";
return $body;
}

// request for approval
function approval($vendorId, $regionName, $ownerName, $ownerEmail, $portalName, $portalType) {
    $conf = get_conf('con');
    $conid = $conf['id'];
    $vendorQ = <<<EOS
SELECT e.artistName, e.exhibitorName, e.exhibitorEmail, e.website, e.description, ey.contactName, ey.contactEmail
FROM exhibitors e
JOIN exhibitorYears ey ON (e.id = ey.exhibitorId)
WHERE e.id=? AND ey.conid = ?;
EOS;
    $vendorR = dbSafeQuery($vendorQ, 'ii', array($vendorId, $conid));
    $vendorL = $vendorR->fetch_assoc();
    $vendorR->free();

    $exhibitorName = $vendorL['exhibitorName'];
    $artistName = $vendorL['artistName'];
    if ($portalType == 'artist' && $artistName != null && $artistName != '' && $artistName != $exhibitorName) {
        $exhibitorName .= "($artistName)";
    }
    $exhibitorEmail = $vendorL['exhibitorEmail'];
    $website = $vendorL['website'];
    if ($website == null || trim($website) == '') {
        $website =  '<i>(None Entered)</i>';
        $websiteURL = $website;
    } else {
        $websiteURL = "<a href='$website' target='_blank'>$website</a>";
    }
    $websiteText = strip_tags($website);

    $description = $vendorL['description'];
    if ($description == null || trim($description) == '')
        $description = '<i>(None Entered)</i>';
    $descriptionText =strip_tags($description);

    $contactName = $vendorL['contactName'];
    $contactEmail = $vendorL['contactEmail'];
    if ($contactName == null || trim($contactName) == '') {
        $contactName = $exhibitorName;
        $contactEmail = $exhibitorEmail;
    }

    $body = <<<EOS
Dear $ownerName:
    $exhibitorName has requested permission to request space in $regionName.

Their business and contact info is:
Business name: $exhibitorName
Business email: $exhibitorEmail
Contact name: $contactName
Contact email: $contactEmail

They have provided the following description:

$descriptionText

Their website is $websiteText

Please followup with $contactName at $contactEmail if you have any further questions.

Respectfully submitted,
$portalName Portal
EOS;
    $bodyhtml = <<<EOS
<p>Dear $ownerName</p>
<p>$exhibitorName has requested permission to request space in $regionName.</p>
<p>
Their business and contact info is:<br/>
Business name: $exhibitorName<br/>
Business email: $exhibitorEmail<br/>
Contact name: $contactName<br/>
Contact email: $contactEmail
</p>
<p>They have provided the following description:</p>
<hr>
$description
<hr>
<p>Their website is $websiteURL.<p>
<p>Please followup with $contactName at <a href="mainto:$contactEmail">$contactEmail</a> if you have any further questions.</p>
<p>Respectfully submitted,<br/>$portalName Portal</p>
EOS;

    return array($contactName, $contactEmail, $body, $bodyhtml);
}

// request space
function request($exhibitorInfo, $regionInfo, $portalName, $portalType, $spaces) {
    $conf = get_conf("con");
    $conid = $conf['id'];

    $ownerName = $regionInfo['ownerName'];
    $exhibitorName = $exhibitorInfo['exhibitorName'];
    $artistName = $exhibitorInfo['artistName'];
    if ($portalType == 'artist' && $artistName != null && $artistName != '' && $artistName != $exhibitorName) {
        $exhibitorName .= "($artistName)";
    }

    $exhibitorEmail = $exhibitorInfo['exhibitorEmail'];
    $contactName = $exhibitorInfo['contactName'];
    $contactEmail = $exhibitorInfo['contactEmail'];
    if ($contactName == null || trim($contactName) == '') {
        $contactName = $exhibitorName;
        $contactEmail = $exhibitorEmail;
    }

    $description = $exhibitorInfo['description'];
    if ($description == null || trim($description) == '')
        $description = '<i>(None Entered)</i>';
    $descriptionText =strip_tags($description);

    $regionName = $regionInfo['name'];
    $ownerName = $regionInfo['ownerName'];

    $website = $exhibitorInfo['website'];
    if ($website == null || trim($website) == '') {
        $website =  '<i>(None Entered)</i>';
        $websiteURL = $website;
    } else {
        $websiteURL = "<a href='$website' target='_blank'>$website</a>";
    }
    $websiteText = strip_tags($website);

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
    
Their business and contact info is:
Business name: $exhibitorName
Business email: $exhibitorEmail
Contact name: $contactName
Contact email: $contactEmail

They have provided the following description:

$descriptionText

Their website is $websiteText

$spaces

Please followup with $contactName at $contactEmail if you have any further questions.

Respectfully submitted,
$portalName Portal
EOS;

    $spacesHtml = str_replace("\n", "<br>\n", $spaces);
    $bodyhtml = <<<EOS
<p>Dear $ownerName</p>
<p>$exhibitorName has $requestType space in $regionName.</p>
<p>
Their business and contact info is:<br/>
Business name: $exhibitorName<br/>
Business email: $exhibitorEmail<br/>
Contact name: $contactName<br/>
Contact email: $contactEmail
</p>
<p>They have provided the following description:</p>
<hr>
$description
<hr>
<p>Their website is $websiteURL.<p>
<p>$spacesHtml</p>
<p>Please followup with $contactName at <a href="mainto:$contactEmail">$contactEmail</a> if you have any further questions.</p>
<p>Respectfully submitted,<br/>$portalName Portal</p>
EOS;

    return array($body, $bodyhtml);
}

// space payment confirmation
function payment($results) {
    $receipts = trans_receipt($results['transid']);

    $currency = getConfValue('con', 'currency', 'USD');
    $buyer = $results['buyer'];
    $region = $results['region'];

    $label = getConfValue('con', 'label', 'Unknown Convention');
    $curLocale = locale_get_default();
    $dolfmt = new NumberFormatter($curLocale == 'en_US_POSIX' ? 'en-us' : $curLocale, NumberFormatter::CURRENCY);

    // plain text version
    $body = "Dear " . trim($buyer['fname'] . ' ' . $buyer['lname']) . ":\n\n" .
        "Here is your receipt for payment of " . $dolfmt->formatCurrency($results['approved_amt'], $currency) . ' for ' . $label .
            ' ' . $region['name'] . "\n\n" . $receipts['receipt'] . "\n\n" .
            "If you have any questions please contact the " . $region['name'] . ' staff at ' . $region['ownerEmail']  . ".\n\nThank you\n";

        // html version
    $bodyHtml = '<p>Dear ' . trim($buyer['fname'] . ' ' . $buyer['lname']) . ":</p>\n" .
        '<p>Here is your receipt for payment of ' . $dolfmt->formatCurrency($results['approved_amt'], $currency) . ' for ' . $label .
            ' ' . $region['name'] . "</p>\n" . $receipts['receipt_tables'] .
        '<p>If you have any questions please contact the ' . $region['name'] . ' staff at ' . $region['ownerEmail'] . ".</p>\n<p>Thank you</p>\n";

    return array($body, $bodyHtml);
}
