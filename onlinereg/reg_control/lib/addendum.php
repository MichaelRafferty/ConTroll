<?php

function getVendorAddendum($shortName, $longName, $fileSuffix): array {
    // load the contents of a vendor configuration addendum file into a string and return that strin
    if ($shortName == '')
        return array('', '');

    $vendor = get_conf('vendor');
    $fieldName = str_replace(' ', '_', $shortName) . $fileSuffix;
    if (!array_key_exists($fieldName, $vendor))
        return array('', '');

    $fileName = $vendor[$fieldName] . '.txt';
    $filePath = __DIR__ . '/../../../config/' . $fileName;
    if (!is_readable($filePath))
        $addendumTxt = '';
    else {
        $addendumTxt = file_get_contents($filePath);
        if ($addendumTxt === false)
            $addendumTxt = '';
    }

    $fileName = $vendor[$fieldName] . '.html';
    $filePath = __DIR__ . '../../config/' . $fileName;
    if (!is_readable($filePath))
        $addendumHTML = '';
    else {
        $addendumHTML = file_get_contents($filePath);
        if ($addendumHTML === false)
            $addendumHTML = '';
    }
    return array($addendumHTML, $addendumTxt);
}

// future - create variable substitution for addendums