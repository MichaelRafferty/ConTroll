<?php
// items related to configuring and computing sales tax

global $taxRates;
$taxRates = null;
function getTaxRates() : array {
    global $taxRates;

    $conid = getConfValue('con', 'id');
// get tax rates
    $taxRates = array();
    $QQ = <<<EOS
SELECT taxField, rate, label
FROM taxList
WHERE active = 'Y' AND conid = ?
ORDER BY taxField;
EOS;
    $QR = dbSafeQuery($QQ, 'i', array($conid));
    while ($row = $QR->fetch_assoc()) {
        $taxRates[$row['taxField']] = $row;
    }
    $QR->free();

    return $taxRates;
}

function getTaxConfig() : array {
    $conid = getConfValue('con', 'id');
    // get tax rate configuration info

    $taxConfig = array();
    $QQ = <<<EOS
SELECT *
FROM taxList
WHERE conid = ?
ORDER BY taxField;
EOS;
    $QR = dbSafeQuery($QQ, 'i', array($conid));
    while ($row = $QR->fetch_assoc()) {
        $taxConfig[$row['taxField']] = $row;
    }
    $QR->free();

    return $taxConfig;
}

function computeTax($taxableAmt) : array {
    global $taxRates;

    if ($taxRates == null) {
        getTaxRates();
    }

    $taxes = array();
    foreach ($taxRates as $taxField => $tax) {
        $taxes[$taxField] = round($taxableAmt * $tax['rate'] / 100.0, 2);
    }

    return $taxes;
}
