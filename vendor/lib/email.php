<?php
  require_once("artshow.php");
function vendorReset($passwd, $dest) {
    $conf = get_conf('con');
    $vendor_conf = get_conf('vendor');
    $body = "The password to you " . $conf['conname'] . " Vendor Portal account has been reset.\nThe new password is:\n\n\t$passwd\n\n" .
        "Please login to the " . $conf['conname'] . " vendor site at " . $vendor_conf['vendorsite'] .
        " to change your password.\n\n" .
        "If you continue to have problems please contact " . $vendor_conf['vendors'] . ".\n\nThank you for your interest in " . $conf['conname'] . ".\n";

return $body;
}

function request($access,$price, $vendorId, $address) {
    $conf = get_conf("con");
    $dolfmt = new NumberFormatter('', NumberFormatter::CURRENCY);

    $vendorQ = "SELECT name, website, description FROM vendors WHERE id=?";
    $vendor = fetch_safe_assoc(dbSafeQuery($vendorQ, 'i', array($vendorId)));

    if (array_key_exists('price', $price)) {
        $body = $vendor['name'] . ",\n" .
            "Thank you for your interest in the " . $conf['conname'] . " $access.\n" .
            "You provided the description:\n" . $vendor['description'] . "\n" .
            "And the website " . $vendor['website'] . "\n" .
            "\nYou have requested " . $price['description'] . ' in the ' . $access . ' for ' . $dolfmt->formatCurrency($price['price'], 'USD') . "\n" .
            "\nIf you have any questions please contact the $access staff at $address.\n\nThank you\n";
        //$body = "id: $vendorId access: $access info: " . json_encode($vendor) . "\n";
    } else {
        $body = $vendor['name'] . ",\n" .
        'Thank you for your interest in the ' . $conf['conname'] . " $access.\n" .
        "We are sorry you had to cancel your space request in the $access.\n" .
        "\nIf you have any questions please contact the $access staff at $address.\n\nThank you\n";
    }

    return $body;
}

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

    return $body;
}
