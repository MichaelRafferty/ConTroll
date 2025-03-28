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
    $exhibitorName = $vendorL['exhibitorName'];
    $artistName = $vendorL['artistName'];
    if ($portalType == 'artist' && $artistName != null && $artistName != '' && $artistName != $exhibitorName) {
        $exhibitorName .= "($artistName)";
    }
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
function request($exhibitorInfo, $regionInfo, $portalName, $portalType, $spaces) {
    $conf = get_conf("con");
    $conid = $conf['id'];

    $ownerName = $regionInfo['ownerName'];
    $exhibitorName = $exhibitorInfo['exhibitorName'];
    $artistName = $exhibitorInfo['artistName'];
    if ($portalType == 'artist' && $artistName != null && $artistName != '' && $artistName != $exhibitorName) {
        $exhibitorName .= "($artistName)";
    }
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
    $con = get_conf('con');
    if (array_key_exists('currency', $con)) {
        $currency = $con['currency'];
    } else {
        $currency = 'USD';
    }
    $buyer = $results['buyer'];
    $vendor = $results['vendor'];
    $region = $results['region'];
    $portalType = $results['exhibits'];
    $exhibitorName = $vendor['exhibitorName'];
    if (array_key_exists('artistName', $vendor)) {
        if ($portalType == 'artist' && $vendor['artistName'] != null && $vendor['artistName'] != '' && $vendor['artistName'] != $exhibitorName) {
            $exhibitorName .= "(" . $vendor['artistName'] . ")";
        }
    }
    switch ($portalType) {
        case 'artist':
            $portalName = 'Artist';
            break;
        case 'exhibitor':
            $portalName = 'Exhibitor';
            break;
        case 'fan':
            $portalName = 'Fan';
            break;
        default:
            $portalName = 'Vendor';
            break;
    }

    loadCustomText('exhibitor', 'index', null, true);
    $textDisclaimer = '';
    $htmlDisclaimer = '';
    $disclaimer1 = returnCustomText('invoice/payDisclaimer', 'exhibitor/index/');
    $disclaimer2 = returnCustomText('invoice/payDisclaimer' . $portalName,'exhibitor/index/');
    if ($disclaimer1 != '' || $disclaimer2 != '') {
        $textDisclaimer = $disclaimer1;
        $htmlDisclaimer = $disclaimer1;
        if ($disclaimer1 != '' && $disclaimer2 != '') {
            $textDisclaimer .= PHP_EOL;
            $htmlDisclaimer .= "<br/>\n";
        }
        $textDisclaimer .= $disclaimer2;
        $htmlDisclaimer .= $disclaimer2;
        $textDisclaimer = "\n\n$textDisclaimer\n";
        $htmlDisclaimer = <<<EOS
    <div class='row mt-4'>
        <div class='col-sm-12'>
           $htmlDisclaimer
        </div>
    </div>
EOS;
    }



    $conf = get_conf('con');
    $vendor_conf = get_conf('vendor');
    $curLocale = locale_get_default();
    $dolfmt = new NumberFormatter($curLocale == 'en_US_POSIX' ? 'en-us' : $curLocale, NumberFormatter::CURRENCY);

    // plain text version
    $body = "Dear " . trim($buyer['fname'] . ' ' . $buyer['lname']) . ":\n\n" .
        "Here is your receipt for payment of " . $dolfmt->formatCurrency($results['approved_amt'], $currency) . ' for ' . $conf['label'] .
            ' ' . $region['name'] . "\n\n" .
        "RECEIPT FOR PAYMENT TO: " . $conf['label'] . ' on ' . date('m/d/Y h:i:s A', time()) . "\n\n" .
        "$portalName: $exhibitorName\n" .
        $vendor['addr'] . "\n";
        if ($vendor['addr2'] && $vendor['addr2'] != '')
            $body .= $vendor['addr2'] . "\n";
        $body .= $vendor['city'] . ', ' . $vendor['state'] . ' ' . $vendor['zip'] . "\n\n" .
            "Space: " . $region['name'] . ' (' . $region['description'] . ') with up to ' . $region['includedMemberships'] .
                ' included memberships and up to ' .
            $region['additionalMemberships'] . " additional memberships\n" .
            $vendor_conf['taxidlabel'] . ': ' . $results['salesTaxId'] . "\n\n" .
            "Price for Space: " . $dolfmt->formatCurrency($region['price'], $currency) . "\n\n" .
            "Special Requests:\n" . $results['specialrequests'] . "\n\n";

        if (array_key_exists('spaces', $results)) {
            $body .= "Spaces purchased:\n";
            foreach ($results['spaces'] as $space) {
                $body .= $space['description'] . " of type " . $space['name'] . ' for ' .
                    $dolfmt->formatCurrency($space['approved_price'], $currency) . "\n";
            }
        }

        $body .= "Memberships purchased at this time:\n\n";
        if (array_key_exists('formbadges', $results) && is_array($results['formbadges'])) {
            foreach ($results['formbadges'] as $badge) {
                if ($badge['type'] == 'i')
                    $body .= "Included membership " . $badge['index'] . ":\n     ";
                else
                    $body .= "Additional membership " . $badge['index'] . ": for " . $dolfmt->formatCurrency($badge['price'], $currency) .
                        "\n     ";
                $body .= $badge['fname'] . ' ' . ltrim($badge['mname'] . ' ') . $badge['lname'] . ' ' . $badge['suffix'] . "\n     " .
                    $badge['addr'] . "\n     ";
                if ($badge['addr2'] && $badge['addr2'] != '')
                    $body .= $badge['addr2'] . "\n     ";
                $body .= $badge['city'] . ', ' . $badge['state'] . ' ' . $badge['zip'] . ' ' . $badge['country'] . "\n     " .
                    'Badgename: ' . $badge['badgename'] . "\n\n";
            }
        }

        $body .= "Total amount: " . $dolfmt->formatCurrency($results['total'], $currency) . "\n\n" . $textDisclaimer .
            "If you have any questions please contact the " . $region['name'] . ' staff at ' . $region['ownerEmail']  . ".\n\nThank you\n";

        // html version
    $bodyHtml = '<p>Dear ' . trim($buyer['fname'] . ' ' . $buyer['lname']) . ":</p>\n" .
        '<p>Here is your receipt for payment of ' . $dolfmt->formatCurrency($results['approved_amt'], $currency) . ' for ' . $conf['label'] .
            ' ' . $region['name'] . "</p>\n" .
        '<p>RECEIPT FOR PAYMENT TO: ' . $conf['label'] . ' on ' . date('m/d/Y h:i:s A', time()) . "</p>\n" .
        "<p>$portalName: <br/>\n" .
        "$exhibitorName<br/>\n" .
        $vendor['addr'] . "<br/>\n";
    if ($vendor['addr2'] && $vendor['addr2'] != '')
        $bodyHtml .= $vendor['addr2'] . "<br/>\n";
    $bodyHtml .= $vendor['city'] . ', ' . $vendor['state'] . ' ' . $vendor['zip'] . "</p>\n" .
        '<p>Space: ' . $region['name'] . ' (' . $region['description'] . ') with up to ' . $region['includedMemberships'] .
            ' included memberships and up to ' . $region['additionalMemberships'] . " additional memberships</p>\n" .
        '<p>' . $vendor_conf['taxidlabel'] . ': ' . $results['salesTaxId'] . "</p>\n" .
        '<p>Price for Space: ' . $dolfmt->formatCurrency($region['price'], $currency) . "</p>\n" .
        "<p>Special Requests:<br/>\n" . $results['specialrequests'] . "</p>\n";

    if (array_key_exists('spaces', $results)) {
        $bodyHtml .= "<p>Spaces purchased:</p>\n";
        foreach ($results['spaces'] as $space) {
            $bodyHtml .= "<p>" . $space['description'] . ' of type ' . $space['name'] . ' for ' .
                $dolfmt->formatCurrency($space['approved_price'], $currency) . "</p>\n";
        }
    }

    if (array_key_exists('formbadges', $results) && is_array($results['formbadges'])) {
        $bodyHtml .= "<p>Memberships purchased at this time:</p>\n";
        foreach ($results['formbadges'] as $badge) {
            if ($badge['type'] == 'i')
                $bodyHtml .= '<p style="margin-left: 40px;">Included membership ' . $badge['index'] . ":<br/>\n";
            else
                $bodyHtml .= '<p style="margin-left: 40px;">Additional membership ' . $badge['index'] . ': for ' . $dolfmt->formatCurrency($badge['price'], $currency) . "<br/>\n";
            $bodyHtml .= $badge['fname'] . ' ' . ltrim($badge['mname'] . ' ') . $badge['lname'] . ' ' . $badge['suffix'] . ",<br/>\n" .
                $badge['addr'] . "<br/>\n";
            if ($badge['addr2'] && $badge['addr2'] != '')
                $bodyHtml .= $badge['addr2'] . "<br/>\n";
            $bodyHtml .= $badge['city'] . ', ' . $badge['state'] . ' ' . $badge['zip'] . ' ' . $badge['country'] . "<br/>\n" .
                'Badgename: ' . $badge['badgename'] . "</p>\n";
        }
    }

    $bodyHtml .= '<p>Total amount: ' . $dolfmt->formatCurrency($results['total'], $currency) . "</p>\n" . $htmlDisclaimer .
        '<p>If you have any questions please contact the ' . $region['name'] . ' staff at ' . $region['ownerEmail'] . ".</p>\n<p>Thank you</p>\n";

    return array($body, $bodyHtml);
}
