<?php
// update changed exhibits setup data and then
// retrieve exhibits setup data for admin tab exhibitss
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
            // trim all fields
            foreach ($row as $field => $value) {
                if ($value != null) {
                    $data[$index][$field] = trim($value);
                }
            }
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
INSERT INTO exhibitsRegionTypes(regionType, portalType, requestApprovalRequired, purchaseApprovalRequired, purchaseAreaTotals, mailinAllowed, sortorder, active)
VALUES(?,?,?,?,?,?,?,?);
EOS;
        $updsql = <<<EOS
UPDATE exhibitsRegionTypes
SET regionType = ?, portalType = ?, requestApprovalRequired = ?, purchaseApprovalRequired = ?, purchaseAreaTotals = ?, mailinAllowed = ?, sortorder = ?, active = ?
WHERE regionType = ?;
EOS;

        // now the updates, do the updates first in case we need to insert a new row with the same older key
        foreach ($data as $row ) {
            if (array_key_exists('to_delete', $row)) {
                if ($row['to_delete'] == 1)
                    continue;
            }
            if (array_key_exists($keyfield, $row)) { // if key is there, it's an update
                $numrows = dbSafeCmd($updsql, 'ssssssiss', array($row['regionType'], $row['portalType'], $row['requestApprovalRequired'], $row['purchaseApprovalRequired'], $row['purchaseAreaTotals'],
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
                $numrows = dbSafeInsert($inssql, 'ssssssis', array($row['regionType'], $row['portalType'], $row['requestApprovalRequired'], $row['purchaseApprovalRequired'], $row['purchaseAreaTotals'],
                    $row['mailinAllowed'], $row['sortorder'], $row['active']));
                if ($numrows !== false)
                    $inserted++;
            }
        }
        $response['message'] = "$tablename updated: $inserted added, $updated changed, $deleted removed.";
        break;

    case 'regions':
        if ($delete_keys != '') {
            $delsql = "DELETE FROM exhibitsRegions WHERE id in ( $delete_keys );";
            web_error_log("Delete sql = /$delsql/");
            $deleted += dbCmd($delsql);
        }
        $inssql = <<<EOS
INSERT INTO exhibitsRegions(regionType, shortname, name, description, sortorder)
VALUES(?,?,?,?,?);
EOS;
        $updsql = <<<EOS
UPDATE exhibitsRegions
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
            $delsql = "DELETE FROM exhibitsRegionYears WHERE id IN ( $delete_keys );";
            web_error_log("Delete sql = /$delsql/");
            $deleted += dbCmd($delsql);
        }
        $inssql = <<<EOS
INSERT INTO exhibitsRegionYears(conid, exhibitsRegion, ownerName, ownerEmail, includedMemId, additionalMemId, totalUnitsAvailable, sortorder)
VALUES(?,?,?,?,?,?,?,?);
EOS;
        $updsql = <<<EOS
UPDATE exhibitsRegionYears
SET exhibitsRegion = ?, ownerName = ?, ownerEmail = ?, includedMemId = ?, additionalMemId = ?, totalUnitsAvailable = ?, sortorder = ?
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
                $numrows = dbSafeCmd($updsql, 'sssiiiii', array($row['exhibitsRegion'], $row['ownerName'], $row['ownerEmail'],
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
                $numrows = dbSafeInsert($inssql, 'iissiiii', array($conid, $row['exhibitsRegion'], $row['ownerName'], $row['ownerEmail'],
                    $includedMemId, $additionalMemId, $totalUnitsAvailable, $row['sortorder']));
                if ($numrows !== false)
                    $inserted++;
            }
        }
        $response['message'] = "$tablename updated: $inserted added, $updated changed, $deleted removed.";
        break;

    case 'exhibitsSpaces':
        if ($delete_keys != '') {
            $delsql = "DELETE FROM exhibitsSpaces WHERE id IN ( $delete_keys );";
            web_error_log("Delete sql = /$delsql/");
            $deleted += dbCmd($delsql);
        }
        $inssql = <<<EOS
INSERT INTO exhibitsSpaces(exhibitsRegionYear, shortname, name, description, unitsAvailable, unitsAvailableMailin, sortorder)
VALUES(?,?,?,?,?,?,?);
EOS;
        $updsql = <<<EOS
UPDATE exhibitsSpaces
SET exhibitsRegionYear = ?, shortname = ?, name = ?, description = ?, unitsAvailable = ?, unitsAvailableMailin = ?, sortorder = ?
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
                $numrows = dbSafeCmd($updsql, 'isssiiii', array($row['exhibitsRegionYear'], $row['shortname'], $row['name'], $description,
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
                $numrows = dbSafeInsert($inssql, 'isssiii', array($row['exhibitsRegionYear'], $row['shortname'], $row['name'], $description,
                    $unitsAvailable, $unitsAvailableMailin, $row['sortorder']));
                if ($numrows !== false)
                    $inserted++;
            }
        }
        $response['message'] = "$tablename updated: $inserted added, $updated changed, $deleted removed.";
        break;

    case 'exhibitsSpacePrices':
        if ($delete_keys != '') {
            $delsql = "DELETE FROM exhibitsSpacePrices WHERE id IN ( $delete_keys );";
            web_error_log("Delete sql = /$delsql/");
            $deleted += dbCmd($delsql);
        }
        $inssql = <<<EOS
INSERT INTO exhibitsSpacePrices(spaceId, code, description, units, price, includedMemberships, additionalMemberships,  requestable, sortorder)
VALUES(?,?,?,?,?,?,?,?,?);
EOS;
        $updsql = <<<EOS
UPDATE exhibitsSpacePrices
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

                if (array_key_exists('code', $row)) {
                    $code = $row['code'];
                } else {
                    $code = 0;
                }
                if ($code == '')
                    $code = 0;

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

// exhibits types
if ($gettype == 'all' || str_contains($gettype,'types')) {
    $exhibitsRegionTypesQ = <<<EOS
SELECT ert.*, ert.regionType AS regionTypeKey, COUNT(er.regionType) uses 
FROM exhibitsRegionTypes ert
LEFT OUTER JOIN exhibitsRegions er ON (er.regionType = ert.regionType)
GROUP BY ert.regionType, ert.sortorder
ORDER BY ert.sortorder;
EOS;

    $exhibitsRegionTypes = array();
    $exhibitsRegionTypesR = dbQuery($exhibitsRegionTypesQ);
    while ($type = $exhibitsRegionTypesR->fetch_assoc()) {
        array_push($exhibitsRegionTypes, $type);
    }
    $exhibitsRegionTypesR->free();
    $response['exhibitsRegionTypes'] = $exhibitsRegionTypes;
}

// get all the exhibits regions
if ($gettype == 'all' || str_contains($gettype, 'regions')) {
    $exhibitsRegionsQ = <<<EOS
SELECT er.*, er.id AS regionKey, COUNT(ery.exhibitsRegion) uses
FROM exhibitsRegions er
LEFT OUTER JOIN exhibitsRegionYears ery ON (er.id = ery.exhibitsRegion)
GROUP BY er.id, er.sortorder
ORDER BY er.sortorder;
EOS;

    $exhibitsRegions = array();
    $exhibitsRegionsR = dbQuery($exhibitsRegionsQ);
    while ($space = $exhibitsRegionsR->fetch_assoc()) {
        array_push($exhibitsRegions, $space);
    }
    $exhibitsRegionsR->free();
    $response['exhibitsRegions'] = $exhibitsRegions;
}

// get the exhibits regions configured for this year
if ($gettype == 'all' || str_contains($gettype, 'years')) {
    $exhibitsRegionYearsQ = <<<EOS
SELECT ery.*, ery.id AS regionYearKey, COUNT(es.exhibitsRegionYear) uses, er.shortname
FROM exhibitsRegionYears ery
JOIN exhibitsRegions er ON (ery.exhibitsRegion = er.id)
LEFT OUTER JOIN exhibitsSpaces es ON (es.exhibitsRegionYear = ery.id)
WHERE ery.conid = ?
GROUP BY ery.id, ery.sortorder
ORDER BY ery.sortorder;
EOS;
    
    $exhibitsRegionYears = array();
    $exhibitsRegionYearsR = dbSafeQuery($exhibitsRegionYearsQ, 'i', array($conid));
    while ($year = $exhibitsRegionYearsR->fetch_assoc()) {
        array_push($exhibitsRegionYears, $year);
    }
    $exhibitsRegionYearsR->free();
    $response['exhibitsRegionYears'] = $exhibitsRegionYears;
}

// get the exhibits spaces
if ($gettype == 'all' || str_contains($gettype, 'spaces')) {
    $exhibitsSpacesQ = <<<EOS
SELECT es.*, es.id AS spaceKey, COUNT(esp.id) uses 
FROM exhibitsRegionYears ery
JOIN exhibitsSpaces es ON (es.exhibitsRegionYear = ery.id)
LEFT OUTER JOIN exhibitsSpacePrices esp ON (esp.spaceId = es.id)
WHERE ery.conid = ?
GROUP BY es.id, es.sortorder
ORDER BY sortOrder;
EOS;

    $exhibitsSpaces = array();
    $exhibitsSpacesR = dbSafeQuery($exhibitsSpacesQ, 'i', array($conid));
    while ($area = $exhibitsSpacesR->fetch_assoc()) {
        array_push($exhibitsSpaces, $area);
    }
    $exhibitsSpacesR->free();
    $response['exhibitsSpaces'] = $exhibitsSpaces;
}

// now the prices for those spaces
if ($gettype == 'all' || str_contains($gettype, 'prices')) {
    $exhibitsSpacePricesQ = <<<EOS
SELECT esp.*, esp.id AS priceKey, COUNT(vspur.item_requested) + COUNT(vsapp.item_approved) + COUNT(vspur.item_purchased) AS uses
FROM exhibitsRegionYears ery
JOIN exhibitsSpaces es ON (es.exhibitsRegionYear = ery.id)
JOIN exhibitsSpacePrices esp ON (es.id = esp.spaceId)
LEFT OUTER JOIN exhibitorSpaces vsreq ON (esp.id = vsreq.item_requested)
LEFT OUTER JOIN exhibitorSpaces vsapp ON (esp.id = vsapp.item_approved)
LEFT OUTER JOIN exhibitorSpaces vspur ON (esp.id = vspur.item_purchased)
WHERE ery.conid = ?
GROUP BY esp.id, esp.sortOrder
ORDER BY esp.sortOrder;
EOS;

    $exhibitsSpacePrices = array();
    $exhibitsSpacePricesR = dbSafeQuery($exhibitsSpacePricesQ, 'i', array($conid));
    while ($price = $exhibitsSpacePricesR->fetch_assoc()) {
        array_push($exhibitsSpacePrices, $price);
    }
    $exhibitsSpacePricesR->free();
    $response['exhibitsSpacePrices'] = $exhibitsSpacePrices;
}

if ($gettype == 'all') {
// mow the memList items for the pulldown of membership types
    $memListQ = <<<EOS
SELECT * 
FROM memList
WHERE conid = ?
AND memCategory IN ('artist','dealer','exhibits','fan','virtual')
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