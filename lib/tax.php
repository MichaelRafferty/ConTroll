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

    // NOTE: the code to use the config variables is obsolete, the editing of the sales tax configuration will import it if the tax table is empty.
    if (count($taxRates) > 0) {
        // get the tax items for each tax rate
        $QQ = <<<EOS
SELECT l.taxField, t.item, IFNULL(i.taxable, t.defaultValue) AS taxable
FROM taxList l
JOIN taxable t
LEFT OUTER JOIN taxItems i ON i.taxfield = l.taxfield AND i.conid = l.conid AND i.item = t.item
WHERE i.conid = ? AND l.active = 'Y'
ORDER BY l.taxField, i.sortOrder;
EOS;
        $QR = dbSafeQuery($QQ, 'i', array($conid));
        while ($row = $QR->fetch_assoc()) {
            $taxField = $row['taxField'];
            $taxRates[$taxField]['taxItems'][$row['item']] = $row;
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
        if ($tax['rate'] > 0) {
            foreach ($tax['taxItems'] as $taxItemName => $taxItem) {
                // check if anything is taxable using this rate
                if ($taxItem['taxable'] == 'Y') {
                    return true;
                }
            }
        }
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
function buildSquareAppliedTaxArray($taxitem = '', $lineid = 0) : array {
    global $taxRates;
    $taxArray = array();
    $prefix = $taxitem;
    if ($prefix != '')
        $prefix .= '-';

    foreach ($taxRates as $tax) {
        if ($tax['rate'] > 0 && $tax['taxItems'][$taxitem]['taxable'] == 'Y') {
            $taxArray[] = new Square\Types\OrderLineItemAppliedTax([
                'uid' => $prefix . $tax['taxField'] . '-' . ($lineid + 1),
                'taxUid' => $tax['taxField']
            ]);
        }
    }

    return $taxArray;
}

function buildTestAppliedTaxArray($taxitem = '', $lineid = 0) : array {
    global $taxRates;
    $taxArray = array();
    $prefix = $taxitem;
    if ($prefix != '')
        $prefix .= '-';

    foreach ($taxRates as $tax) {
        if ($tax['rate'] > 0 && array_key_exists($taxitem, $tax['taxItems']) && $tax['taxItems'][$taxitem]['taxable'] == 'Y') {
            $taxArray[] = [
                'percentage' => $tax['rate'],
                'taxUid' => $tax['taxField'],
                'taxName' => $tax['label'],
            ];
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

function buildTestOrderTaxArray() : array {
    global $taxRates;
    $taxArray = array();

    foreach ($taxRates as $tax) {
        if ($tax['rate'] > 0) {

            $taxArray[] = [
                'uid' => $tax['taxField'],
                'name' => $tax['label'],
                'percentage' => $tax['rate'],
            ];
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
        if (array_key_exists('taxItemsDisplay', $taxConfig[$taxField])) {
            $taxConfig[$taxField]['taxItemsDisplay'] .= ',' . $row['item'] . '=' . $row['taxable'];
        } else {
            $taxConfig[$taxField]['taxItemsDisplay'] = ',' . $row['item'] . '=' . $row['taxable'];
        }
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

function computeTax($item) : array {
    // item has taxable Y/N and taxes for each tax rage
    $taxes = array();
    $taxes['totalTax'] = 0;
    $taxes['totalTax_base'] = 0;
    foreach ($item['taxes'] as $tax) {
        $taxField  = $tax['taxUid'];
        $amt = round($item['basePriceMoney'] * $tax['percentage'] / 100.0, 2);
        $taxes[$taxField] = $amt;
        $taxes[$taxField . '_base'] = $item['basePriceMoney'];
        $taxes['totalTax'] += $amt;
        $taxes['totalTax_base'] += $item['basePriceMoney'];
    }

    return $taxes;
}

function computeTotalTax(&$items) {
    $taxableAmounts = array();
    $taxes = array();
    $rates = array();
    $maxItem = array();
    $maxes = array();
    // compute the total of each line item taxes as computed by computeTax
    foreach ($items as $item) {
        foreach ($item['taxes'] as $tax) {
            $taxField  = $tax['taxUid'];
            if (array_key_exists($taxField, $taxableAmounts)) {
                $taxableAmounts[$taxField] += $item['basePriceMoney'];
                $taxes[$taxField] += $item['taxAmounts'][$taxField];
                if ($item['basePriceMoney'] > $maxes[$taxField]) {
                    $maxItem[$taxField] = $item;
                    $maxes[$taxField] = $item['basePriceMoney'];
                }
            } else {
                $taxableAmounts[$taxField] = $item['basePriceMoney'];
                $taxes[$taxField] = $item['taxAmounts'][$taxField];
                $rates[$taxField] = $tax['percentage'];
                $maxItem[$taxField] = $item;
                $maxes[$taxField] = $item['basePriceMoney'];
            }
        }
    }

    // now recompute the total tax and fudge the
    foreach ($taxes as $taxField => $tax) {
        $totalTax = $taxableAmounts[$taxField] *  $rates[$taxField] / 100;
        if ($totalTax != $tax) { // fudge last item in list to make the pennies add up
            $item = $maxItem[$taxField];
            $item['taxes'][$taxField] += $tax[$taxField] - $taxes[$taxField];
        }
    }

    return $taxes;
}
