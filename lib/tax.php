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
        $row['taxItems'] = array();
        $taxRates[$row['taxField']] = $row;
    }
    $QR->free();

    if (count($taxRates) == 0) {
        // default to the older configuration file based tax rates
        $taxRate = getConfValue('con', 'taxRate', 0);
        if ($taxRate > 0) {
            $taxLabel = getConfValue('con', 'taxLabel');
            $taxRates[] = array('taxField' => 'tax', 'rate' => $taxRate, 'label' => $taxLabel, 'taxItems' => array());
        }
    } else {
        // get the tax items for each tax rate
        $QQ = <<<EOS
SELECT *
FROM taxItems
WHERE conid = ?
ORDER BY sortOrder;
EOS;
        $QR = dbSafeQuery($QQ, 'i', array($conid));
        while ($row = $QR->fetch_assoc()) {
            $taxField = $row['taxField'];
            $taxRates[$taxField]['taxItems'][] = $row;
        }
        $QR->free();
    }

    return $taxRates;
}

// are there non zero rates in taxList?
function hasTaxRates() {
    global $taxRates;

    if ($taxRates == null) {
        getTaxRates();
    }
    foreach ($taxRates as $tax) {
        if ($tax['rate'] > 0)
            return true;
    }
    return false;
}

// build payment and transaction update tax sections, unused fields default to null
function buildTaxUpdate($taxes) : array {
    global $taxRates;

    if ($taxRates == null) {
        getTaxRates();
    }

    $taxFields = array('tax1','tax2','tax3','tax4','tax5');
    $valStr = 'ddddd';
    $sqlStr = [];
    $values = [];
    foreach ($taxFields as $taxField) {
        $sqlStr[] = "$taxField = ?";
        if (array_key_exists($taxField, $taxRates) && array_key_exists($taxField, $taxes)) {
            $values[] = $taxes[$taxField];
        } else {
            $values[] = null;
        }
    }
    return array(implode(',', $sqlStr), $valStr, $values);
}

// build square tax arrays
// item applied tax
function buildSquareAppliedTaxArray($prefix = '', $lineid = 0) : array {
    global $taxRates;
    $taxArray = array();
    if ($prefix != '')
        $prefix .= '-';

    foreach ($taxRates as $tax) {
        if ($tax['rate'] > 0) {
            $taxArray[] = new Square\Types\OrderLineItemAppliedTax([
                'uid' => $prefix . $tax['taxField'] . '-' . ($lineid + 1),
                'taxUid' => $tax['taxField']
            ]);
        }
    }

    return $taxArray;
}

function buildSquareOrderTaxArray() : array {
    global $taxRates;
    $taxArray = array();

    foreach ($taxRates as $tax) {
        if ($tax['rate'] > 0) {

            $taxArray[] = new Square\Types\OrderLineItemTax([
                'uid' => $tax['taxField'],
                'name' => $tax['label'],
                'type' => Square\Types\OrderLineItemTaxType::Additive->value,
                'percentage' => $tax['rate'],
                'scope' => Square\Types\OrderLineItemTaxScope::LineItem->value,
            ]);
        }
    }

    return $taxArray;
}


// build payment and transaction insert sections
function buildTaxInsert($taxes) : array {
    global $taxRates;

    if ($taxRates == null) {
        getTaxRates();
    }

    $taxFields = array('tax1','tax2','tax3','tax4','tax5');
    $valStr = 'ddddd';
    $sqlStr = [];
    $values = [];
    foreach ($taxFields as $taxField) {
        $sqlStr[] = "?";
        if (array_key_exists($taxField, $taxRates) && array_key_exists($taxField, $taxes)) {
            $values[] = $taxes[$taxField];
        } else {
            $values[] = null;
        }
    }
    return array(implode(',', $taxFields), implode(',', $sqlStr), $valStr, $values);
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
        $row['taxItems'] = [];
        $row['taxItemsDisplay'] = '';
        $taxField = $row['taxField'];
        $taxConfig[$taxField] = $row;
    }
    $QR->free();

    // now add tax items to taxConfig
    $QQ = <<<EOS
SELECT *
FROM taxItems
WHERE conid = ?
ORDER BY sortOrder;
EOS;
    $QR = dbSafeQuery($QQ, 'i', array($conid));
    while ($row = $QR->fetch_assoc()) {
        $taxField = $row['taxField'];
        $taxConfig[$taxField]['taxItems'][] = $row;
        $taxConfig[$taxField]['taxItemsDisplay'] .= ',' . $row['taxItem'] . '=' . $row['taxable'];
    }
    $QR->free();

    // strip the leading comma from each taxItemsDisplay while copying over the array
    $taxConfigArray = array();
    foreach ($taxConfig as $taxField => $tax) {
        if ($tax['taxItemsDisplay'] != '')
            $tax['taxItemsDisplay'] = substr($tax['taxItemsDisplay'], 1);
        $taxConfigArray[] = $tax;
    }

    $QQ = <<<EOS
SELECT item, label, defaultValue
FROM taxable
ORDER BY sortOrder;
EOS;
    $QR = dbQuery($QQ);
    $taxable = array();
    while ($row = $QR->fetch_assoc()) {
        $taxable[] = $row;
    }
    $QR->free();

    return array($taxConfigArray, $taxable);
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
