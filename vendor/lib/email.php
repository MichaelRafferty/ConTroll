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

    $vendorQ = "SELECT name, website, description FROM vendors WHERE id=$vendorId";
    $vendor = fetch_safe_assoc(dbQuery($vendorQ));

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
