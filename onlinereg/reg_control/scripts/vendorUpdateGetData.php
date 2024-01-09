<?php
// update changed vendor setup data and then
// retrieve vendor setup data for admin tab vendors
global $db_ini;

require_once '../lib/base.php';

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$check_auth = google_init('ajax');
$perm = 'admin';

$response = array('post' => $_POST, 'get' => $_GET, 'perm' => $perm);

if ($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = 'Authentication Failed';
    ajaxSuccess($response);
    exit();
}

$con = get_conf('con');
$conid = $con['id'];

if (!isset($_POST) || !isset($_POST['gettype'])) {
    $response['error'] = 'Missing Information';
    ajaxSuccess($response);
    exit();
}

$gettype = $_POST['gettype'];
$response['gettype'] = $gettype;
$inserted = 0;
$updated = 0;
$deleted = 0;
$delete_keys = '';
$first = true;

if (array_key_exists('tablename', $_POST)) {
    $tablename = $_POST['tablename'];
    $response['tablename'] = $tablename;
    $keyfield = $_POST['indexcol'];
} else {
    $tablename = 'none';
    $keyfield = 'none';
}
$data = [];

if ($tablename != 'none') {
    try {
        $data = json_decode($_POST['tabledata'], true, 512, JSON_THROW_ON_ERROR);
    } catch (Exception $e) {
        $msg = 'Caught exception on json_decode: ' . $e->getMessage() . PHP_EOL . 'JSON error: ' . json_last_error_msg() . PHP_EOL;
        $response['error'] = $msg;
        error_log($msg);
        ajaxSuccess($response);
        exit();
    }

    $sort_order = 10;
    foreach ($data as $index => $row ) {
        if (array_key_exists('to_delete', $row) && $row['to_delete'] == 1) {
            $delete_keys .= ($first ? "'" : ",'") . sql_safe($row[$keyfield]) . "'";
            $first = false;
        } else {
            if (array_key_exists('sortorder', $row))
                $roworder = $row['sortorder'];
            else
                $roworder = 500;

            if ($roworder >= 0 && $roworder < 900) {
                $data[$index]['sortorder'] = $sort_order;
                $sort_order += 10;
            }
        }
    }

}
switch ($tablename) {
    case 'none';
        break;
    case 'regionTypes':
        if ($delete_keys != '') {
            $delsql = "DELETE FROM regionTypes WHERE regionType in ( $delete_keys );";
            web_error_log("Delete sql = /$delsql/");
            $deleted += dbCmd($delsql);
        }
        $inssql = <<<EOS
INSERT INTO vendorRegionTypes(regionType, requestApprovalRequired, purchaseApprovalRequired, purchaseAreaTotals, mailinAllowed, sortorder, active)
VALUES(?,?,?,?,?,?,?);
EOS;
        $updsql = <<<EOS
UPDATE vendorRegionTypes
SET regionType = ?, requestApprovalRequired = ?, purchaseApprovalRequired = ?, purchaseAreaTotals = ?, mailinAllowed = ?, sortorder = ?, active = ?
WHERE regionType = ?;
EOS;

        // now the updates, do the updates first in case we need to insert a new row with the same older key
        foreach ($data as $row ) {
            if (array_key_exists('to_delete', $row)) {
                if ($row['to_delete'] == 1)
                    continue;
            }
            if (array_key_exists($keyfield, $row)) { // if key is there, it's an update
                $numrows = dbSafeCmd($updsql, 'sssssiss', array($row['regionType'], $row['requestApprovalRequired'], $row['purchaseApprovalRequired'], $row['purchaseAreaTotals'],
                    $row['mailinAllowed'], $row['sortorder'], $row['active'],$row[$keyfield]));
                $updated += $numrows;
            }
        }

        // now the inserts, do the inserts last in case we need to insert a new row with the same older key
        foreach ($data as $row) {
            if (array_key_exists('to_delete', $row)) {
                if ($row['to_delete'] == 1)
                    continue;
            }
            if (!array_key_exists($keyfield, $row)) { // if key is not there, it is an insert
                $numrows = dbSafeInsert($inssql, 'sssssis', array($row['regionType'], $row['requestApprovalRequired'], $row['purchaseApprovalRequired'], $row['purchaseAreaTotals'],
                    $row['mailinAllowed'], $row['sortorder'], $row['active']));
                if ($numrows !== false)
                    $inserted++;
            }
        }
        $response['message'] = "$tablename updated: $inserted added, $updated changed, $deleted removed.";
        break;

    case 'regions':
        if ($delete_keys != '') {
            $delsql = "DELETE FROM vendorRegions WHERE id in ( $delete_keys );";
            web_error_log("Delete sql = /$delsql/");
            $deleted += dbCmd($delsql);
        }
        $inssql = <<<EOS
INSERT INTO vendorRegions(regionType, shortname, name, description, sortorder)
VALUES(?,?,?,?,?);
EOS;
        $updsql = <<<EOS
UPDATE vendorRegions
SET regionType = ?, shortname = ?, name = ?, description = ?, sortorder = ?
WHERE id = ?;
EOS;

        // now the updates, do the updates first in case we need to insert a new row with the same older key
        foreach ($data as $row ) {
            if (array_key_exists('to_delete', $row)) {
                if ($row['to_delete'] == 1)
                    continue;
            }
            if (array_key_exists($keyfield, $row)) { // if key is there, it's an update
                if (array_key_exists('description', $row)) {
                    $description = $row['description'];
                    if ($description != null && trim($description) == '')
                        $description = null;
                } else {
                    $description = null;
                }
                $numrows = dbSafeCmd($updsql, 'ssssii', array($row['regionType'], $row['shortname'], $row['name'], $description, $row['sortorder'], $row[$keyfield]));
                $updated += $numrows;
            }
        }

        // now the inserts, do the inserts last in case we need to insert a new row with the same older key
        foreach ($data as $row) {
            if (array_key_exists('to_delete', $row)) {
                if ($row['to_delete'] == 1)
                    continue;
            }
            if (!array_key_exists($keyfield, $row)) { // if key is not there, it is an insert
                if (array_key_exists('description', $row)) {
                    $description = $row['description'];
                    if ($description != null && trim($description) == '')
                        $description = null;
                } else {
                    $description = null;
                }
                $numrows = dbSafeInsert($inssql, 'ssssi', array($row['regionType'], $row['shortname'], $row['name'],  $description, $row['sortorder']));
                if ($numrows !== false)
                    $inserted++;
            }
        }
        $response['message'] = "$tablename updated: $inserted added, $updated changed, $deleted removed.";
        break;

    case 'regionYears':
        if ($delete_keys != '') {
            $delsql = "DELETE FROM vendorRegionYears WHERE id IN ( $delete_keys );";
            web_error_log("Delete sql = /$delsql/");
            $deleted += dbCmd($delsql);
        }
        $inssql = <<<EOS
INSERT INTO vendorRegionYears(conid, vendorRegion, ownerName, ownerEmail, includedMemId, additionalMemId, totalUnitsAvailable, sortorder)
VALUES(?,?,?,?,?,?,?,?);
EOS;
        $updsql = <<<EOS
UPDATE vendorRegionYears
SET vendorRegion = ?, ownerName = ?, ownerEmail = ?, includedMemId = ?, additionalMemId = ?, totalUnitsAvailable = ?, sortorder = ?
WHERE id = ?;
EOS;

        // now the updates, do the updates first in case we need to insert a new row with the same older key
        foreach ($data as $row ) {
            if (array_key_exists('to_delete', $row)) {
                if ($row['to_delete'] == 1)
                    continue;
            }
            if (array_key_exists($keyfield, $row)) { // if key is there, it's an update
                if (array_key_exists('includedMemId', $row)) {
                    $includedMemId = $row['includedMemId'];
                } else {
                    $includedMemId = null;
                }
                if (array_key_exists('additionalMemId', $row)) {
                    $additionalMemId = $row['additionalMemId'];
                } else {
                    $additionalMemId = null;
                }
                if (array_key_exists('totalUnitsAvailable', $row)) {
                    $totalUnitsAvailable = $row['totalUnitsAvailable'];
                } else {
                    $totalUnitsAvailable = 0;
                }
                $numrows = dbSafeCmd($updsql, 'sssiiiii', array($row['vendorRegion'], $row['ownerName'], $row['ownerEmail'],
                    $row['includedMemId'], $row['additionalMemId'], $totalUnitsAvailable, $row['sortorder'], $row[$keyfield]));
                $updated += $numrows;
            }
        }

        // now the inserts, do the inserts last in case we need to insert a new row with the same older key
        foreach ($data as $row) {
            if (array_key_exists('to_delete', $row)) {
                if ($row['to_delete'] == 1)
                    continue;
            }
            if (!array_key_exists($keyfield, $row)) { // if key is not there, it is an insert
                if (array_key_exists('includedMemId', $row)) {
                    $includedMemId = $row['includedMemId'];
                } else {
                    $includedMemId = null;
                }
                if (array_key_exists('additionalMemId', $row)) {
                    $additionalMemId = $row['additionalMemId'];
                } else {
                    $additionalMemId = null;
                }
                if (array_key_exists('totalUnitsAvailable', $row)) {
                    $totalUnitsAvailable = $row['totalUnitsAvailable'];
                } else {
                    $totalUnitsAvailable = 0;
                }
                $numrows = dbSafeInsert($inssql, 'iissiiii', array($conid, $row['vendorRegion'], $row['ownerName'], $row['ownerEmail'],
                    $includedMemId, $additionalMemId, $totalUnitsAvailable, $row['sortorder']));
                if ($numrows !== false)
                    $inserted++;
            }
        }
        $response['message'] = "$tablename updated: $inserted added, $updated changed, $deleted removed.";
        break;

    case 'vendorSpaces':
        if ($delete_keys != '') {
            $delsql = "DELETE FROM vendorSpaces WHERE id IN ( $delete_keys );";
            web_error_log("Delete sql = /$delsql/");
            $deleted += dbCmd($delsql);
        }
        $inssql = <<<EOS
INSERT INTO vendorSpaces(vendorRegionYear, shortname, name, description, unitsAvailable, unitsAvailableMailin, sortorder)
VALUES(?,?,?,?,?,?,?);
EOS;
        $updsql = <<<EOS
UPDATE vendorSpaces
SET vendorRegionYear = ?, shortname = ?, name = ?, description = ?, unitsAvailable = ?, unitsAvailableMailin = ?, sortorder = ?
WHERE id = ?;
EOS;

        // now the updates, do the updates first in case we need to insert a new row with the same older key
        foreach ($data as $row ) {
            if (array_key_exists('to_delete', $row)) {
                if ($row['to_delete'] == 1)
                    continue;
            }
            if (array_key_exists($keyfield, $row)) { // if key is there, it's an update
                if (array_key_exists('unitsAvailable', $row)) {
                    $unitsAvailable = $row['unitsAvailable'];
                } else {
                    $unitsAvailable = 0;
                }
                if (array_key_exists('unitsAvailableMailin', $row)) {
                    $unitsAvailableMailin = $row['unitsAvailableMailin'];
                } else {
                    $unitsAvailableMailin = 0;
                }
                if (array_key_exists('description', $row)) {
                    $description = $row['description'];
                    if ($description != null && trim($description) == '')
                        $description = null;
                } else {
                    $description = null;
                }
                $numrows = dbSafeCmd($updsql, 'isssiiii', array($row['vendorRegionYear'], $row['shortname'], $row['name'], $description,
                    $unitsAvailable, $unitsAvailableMailin, $row['sortorder'], $row[$keyfield]));
                $updated += $numrows;
            }
        }

        // now the inserts, do the inserts last in case we need to insert a new row with the same older key
        foreach ($data as $row) {
            if (array_key_exists('to_delete', $row)) {
                if ($row['to_delete'] == 1)
                    continue;
            }
            if (!array_key_exists($keyfield, $row)) { // if key is not there, it is an insert
                if (array_key_exists('unitsAvailable', $row)) {
                    $unitsAvailable = $row['unitsAvailable'];
                } else {
                    $unitsAvailable = 0;
                }
                if (array_key_exists('unitsAvailableMailin', $row)) {
                    $unitsAvailableMailin = $row['unitsAvailableMailin'];
                } else {
                    $unitsAvailableMailin = 0;
                }
                if (array_key_exists('description', $row)) {
                    $description = $row['description'];
                    if ($description != null && trim($description) == '')
                        $description = null;
                } else {
                    $description = null;
                }
                $numrows = dbSafeInsert($inssql, 'isssiii', array($row['vendorRegionYear'], $row['shortname'], $row['name'], $description,
                    $unitsAvailable, $unitsAvailableMailin, $row['sortorder']));
                if ($numrows !== false)
                    $inserted++;
            }
        }
        $response['message'] = "$tablename updated: $inserted added, $updated changed, $deleted removed.";
        break;

    case 'vendorSpacePrices':
        if ($delete_keys != '') {
            $delsql = "DELETE FROM vendorSpacePrices WHERE id IN ( $delete_keys );";
            web_error_log("Delete sql = /$delsql/");
            $deleted += dbCmd($delsql);
        }
        $inssql = <<<EOS
INSERT INTO vendorSpacePrices(spaceId, code, description, units, price, includedMemberships, additionalMemberships,  requestable, sortorder)
VALUES(?,?,?,?,?,?,?,?,?);
EOS;
        $updsql = <<<EOS
UPDATE vendorSpacePrices
SET spaceId = ?, code = ?, description = ?, units = ?, price = ?, includedMemberships = ?, additionalMemberships = ?, requestable = ?, sortorder = ?
WHERE id = ?;
EOS;

        // now the updates, do the updates first in case we need to insert a new row with the same older key
        foreach ($data as $row ) {
            if (array_key_exists('to_delete', $row)) {
                if ($row['to_delete'] == 1)
                    continue;
            }
            if (array_key_exists($keyfield, $row)) { // if key is there, it's an update
                if (array_key_exists('units', $row)) {
                    $units = $row['units'];
                } else {
                    $units = 0;
                }
                if (array_key_exists('price', $row)) {
                    $price = $row['price'];
                } else {
                    $price = 0;
                }
                if (array_key_exists('includedMemberships', $row)) {
                    $includedMemberships = $row['includedMemberships'];
                } else {
                    $includedMemberships = 0;
                }
                if (array_key_exists('additionalMemberships', $row)) {
                    $additionalMemberships = $row['additionalMemberships'];
                } else {
                    $additionalMemberships = 0;
                }

                $numrows = dbSafeCmd($updsql, 'issddiiiii', array($row['spaceId'], $row['code'], $row['description'], $units, $price, $includedMemberships, $additionalMemberships,
                    $row['requestable'], $row['sortorder'], $row[$keyfield]));
                $updated += $numrows;
            }
        }

        // now the inserts, do the inserts last in case we need to insert a new row with the same older key
        foreach ($data as $row) {
            if (array_key_exists('to_delete', $row)) {
                if ($row['to_delete'] == 1)
                    continue;
            }
            if (!array_key_exists($keyfield, $row)) { // if key is not there, it is an insert
                if (array_key_exists('units', $row)) {
                    $units = $row['units'];
                } else {
                    $units = 0;
                }
                if (array_key_exists('price', $row)) {
                    $price = $row['price'];
                } else {
                    $price = 0;
                }
                if (array_key_exists('includedMemberships', $row)) {
                    $includedMemberships = $row['includedMemberships'];
                } else {
                    $includedMemberships = 0;
                }
                if (array_key_exists('additionalMemberships', $row)) {
                    $additionalMemberships = $row['additionalMemberships'];
                } else {
                    $additionalMemberships = 0;
                }

                $numrows = dbSafeInsert($inssql, 'issddiiii', array($row['spaceId'], $row['code'], $row['description'], $units, $price, $includedMemberships, $additionalMemberships,
                    $row['requestable'], $row['sortorder']));
                if ($numrows !== false)
                    $inserted++;
            }
        }
        $response['message'] = "$tablename updated: $inserted added, $updated changed, $deleted removed.";
        break;
    default:
        $response['message'] = "Cannot yet handle updating $tablename";
        $response['error'] = '';
        ajaxSuccess($response);
        exit();
}

// vendor types
if ($gettype == 'all' || str_contains($gettype,'types')) {
    $vendorRegionTypesQ = <<<EOS
SELECT vrt.*, vrt.regionType AS regionTypeKey, COUNT(vr.regionType) uses 
FROM vendorRegionTypes vrt
LEFT OUTER JOIN vendorRegions vr ON (vr.regionType = vrt.regionType)
GROUP BY vrt.regionType, vrt.sortorder
ORDER BY vrt.sortorder;
EOS;

    $vendorRegionTypes = array();
    $vendorRegionTypesR = dbQuery($vendorRegionTypesQ);
    while ($type = $vendorRegionTypesR->fetch_assoc()) {
        array_push($vendorRegionTypes, $type);
    }
    $vendorRegionTypesR->free();
    $response['vendorRegionTypes'] = $vendorRegionTypes;
}

// get all the vendor regions
if ($gettype == 'all' || str_contains($gettype, 'regions')) {
    $vendorRegionsQ = <<<EOS
SELECT vr.*, vr.id AS regionKey, COUNT(vry.vendorRegion) uses
FROM vendorRegions vr
LEFT OUTER JOIN vendorRegionYears vry ON (vr.id = vry.vendorRegion)
GROUP BY vr.id, vr.sortorder
ORDER BY vr.sortorder;
EOS;

    $vendorRegions = array();
    $vendorRegionsR = dbQuery($vendorRegionsQ);
    while ($space = $vendorRegionsR->fetch_assoc()) {
        array_push($vendorRegions, $space);
    }
    $vendorRegionsR->free();
    $response['vendorRegions'] = $vendorRegions;
}

// get the vendor regions configured for this year
if ($gettype == 'all' || str_contains($gettype, 'years')) {
    $vendorRegionYearsQ = <<<EOS
SELECT vry.*, vry.id AS regionYearKey, COUNT(vs.vendorRegionYear) uses, vr.shortname
FROM vendorRegionYears vry
JOIN vendorRegions vr ON (vry.vendorRegion = vr.id)
LEFT OUTER JOIN vendorSpaces vs ON (vs.vendorRegionYear = vry.id)
WHERE vry.conid = ?
GROUP BY vry.id, vry.sortorder
ORDER BY vry.sortorder;
EOS;
    
    $vendorRegionYears = array();
    $vendorRegionYearsR = dbSafeQuery($vendorRegionYearsQ, 'i', array($conid));
    while ($year = $vendorRegionYearsR->fetch_assoc()) {
        array_push($vendorRegionYears, $year);
    }
    $vendorRegionYearsR->free();
    $response['vendorRegionYears'] = $vendorRegionYears;
}

// get the vendor spaces
if ($gettype == 'all' || str_contains($gettype, 'spaces')) {
    $vendorSpacesQ = <<<EOS
SELECT vs.*, vs.id AS spaceKey, COUNT(vsp.id) uses 
FROM vendorRegionYears vry
JOIN vendorSpaces vs ON (vs.vendorRegionYear = vry.id)
LEFT OUTER JOIN vendorSpacePrices vsp ON (vsp.spaceId = vs.id)
WHERE vry.conid = ?
GROUP BY vs.id, vs.sortorder
ORDER BY sortOrder;
EOS;

    $vendorSpaces = array();
    $vendorSpacesR = dbSafeQuery($vendorSpacesQ, 'i', array($conid));
    while ($area = $vendorSpacesR->fetch_assoc()) {
        array_push($vendorSpaces, $area);
    }
    $vendorSpacesR->free();
    $response['vendorSpaces'] = $vendorSpaces;
}

// now the prices for those spaces
if ($gettype == 'all' || str_contains($gettype, 'prices')) {
    $vendorSpacePricesQ = <<<EOS
SELECT vsp.*, vsp.id AS priceKey, COUNT(vspur.item_requested) + COUNT(vsapp.item_approved) + COUNT(vspur.item_purchased) AS uses
FROM vendorRegionYears vry
JOIN vendorSpaces vs ON (vs.vendorRegionYear = vry.id)
JOIN vendorSpacePrices vsp ON (vs.id = vsp.spaceId)
LEFT OUTER JOIN vendor_space vsreq ON (vsp.id = vsreq.item_requested)
LEFT OUTER JOIN vendor_space vsapp ON (vsp.id = vsapp.item_approved)
LEFT OUTER JOIN vendor_space vspur ON (vsp.id = vspur.item_purchased)
WHERE vry.conid = ?
GROUP BY vsp.id, vsp.sortOrder
ORDER BY vsp.sortOrder;
EOS;

    $vendorSpacePrices = array();
    $vendorSpacePricesR = dbSafeQuery($vendorSpacePricesQ, 'i', array($conid));
    while ($price = $vendorSpacePricesR->fetch_assoc()) {
        array_push($vendorSpacePrices, $price);
    }
    $vendorSpacePricesR->free();
    $response['vendorSpacePrices'] = $vendorSpacePrices;
}

if ($gettype == 'all') {
// mow the memList items for the pulldown of membership types
    $memListQ = <<<EOS
SELECT * 
FROM memList
WHERE conid = ?
AND memCategory IN ('artist','dealer','vendor','fan','virtual')
ORDER BY sort_order;
EOS;

    $memList = array();
    $memListR = dbSafeQuery($memListQ, 'i', array($conid));
    while ($mem = $memListR->fetch_assoc()) {
        array_push($memList, $mem);
    }
    $response['memList'] = $memList;
}

ajaxSuccess($response);
