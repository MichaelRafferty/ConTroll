<?php
// check if the current year exists for exhibitor setuo, or try to build it from last year if it doesn't.

function exhibitorCheckOrBuildYear($conid) : string {
    // check to see if this conid exists in the exhibitor setup tables

    $checkQ = <<<EOS
SELECT count(*) AS num
FROM exhibitsRegionYears eY
JOIN exhibitsRegions eR ON eY.exhibitsRegion = eR.id
JOIN exhibitsRegionTypes eRT ON eRT.regionType = eR.regionType
WHERE eRT.active = 'Y' AND eY.conid = ?
EOS;

    $checkR = dbSafeQuery($checkQ, 'i', array($conid));
    if ($checkR === false || $checkR->num_rows == 0) {
        return "Error: Unable to run exhibits check query";
    }

    $numYearRows = $checkR->fetch_row()[0];
    $checkR->free();

    if ($numYearRows > 0)
        return "Exhibits already exists for $conid, skipping build";  // set up of this year at least started

    // ok, lets see if last year exists, so we can build this year
    $checkR = dbSafeQuery($checkQ, 'i', array($conid - 1));
    if ($checkR === false || $checkR->num_rows == 0) {
        return 'Error: Unable to run exhibits check query for conid-1';
    }

    $numYearRows = $checkR->fetch_row()[0];
    $checkR->free();

    if ($numYearRows == 0)
        return '';  // no last year either

    // we have a conid - 1, create the values conid for the three tables: exhibitsRegionYears, exhibitsSpaces, exhibitsSpacePrices
    $eRYQ = <<<EOS
SELECT id, conid, exhibitsRegion, ownerName, ownerEmail, IFNULL(includedMemId, -1) AS includedMemId, 
       IFNULL(additionalMemId, -1) AS additionalMemId, totalUnitsAvailable, atconIdBase, mailinFee, mailinIdBase, sortorder
FROM exhibitsRegionYears
WHERE conid = ?;
EOS;
    $eRYI = <<<EOS
INSERT INTO exhibitsRegionYears(conid, exhibitsRegion, ownerName, ownerEmail, includedMemId, 
       additionalMemId, totalUnitsAvailable, atconIdBase, mailinFee, mailinIdBase, sortorder)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);
EOS;

    $eSQ = <<<EOS
SELECT id, exhibitsRegionYear, shortname, name, description, unitsAvailable, unitsAvailableMailin, sortorder
FROM exhibitsSpaces
WHERE exhibitsRegionYear = ?;
EOS;

    $eSI = <<<EOS
INSERT INTO exhibitsSpaces(exhibitsRegionYear, shortname, name, description, unitsAvailable, unitsAvailableMailin, sortorder)
VALUES (?,?,?,?,?,?,?);
EOS;

    $eSPQ = <<<EOS
SELECT id, spaceId, code, description, units, price, includedMemberships, additionalMemberships, requestable, sortorder
FROM exhibitsSpacePrices
WHERE spaceId = ?;
EOS;

    $eSPI = <<<EOS
INSERT INTO exhibitsSpacePrices(spaceId, code, description, units, price, includedMemberships, additionalMemberships, requestable, sortorder)
VALUES (?,?,?,?,?,?,?,?,?);
EOS;


    // start with exhibitsRegionYears, we need the memId's and will default the rest of it from last year
    $eRYR = dbSafeQuery($eRYQ, 'i', array($conid - 1));
    if ($eRYR === false) {
        return "Error: Unable to fetch exhibitsRegionYears for year " . $conid - 1 . ", seek assistance.";
    }

    while ($eryRow = $eRYR->fetch_assoc()) {
        $newIncludedMemId = null;
        $newAdditionalMemId = null;
        if ($eryRow['includedMemId'] > 0) {
            $newIncludedMemId = findOrBuild($eryRow['includedMemId'], $conid);
        }
        if ($eryRow['additionalMemId'] > 0) {
            $newAdditionalMemId = findOrBuild($eryRow['additionalMemId'], $conid);
        }
        
        $eryRow['conid'] = $conid;
        $eryRow['includedMemId'] = $newIncludedMemId;
        $eryRow['additionalMemId'] = $newAdditionalMemId;
        $valueArray = array(
            $conid,
            $eryRow['exhibitsRegion'], $eryRow['ownerName'], $eryRow['ownerEmail'],
            $newIncludedMemId, $newAdditionalMemId,
            $eryRow['totalUnitsAvailable'], $eryRow['atconIdBase'], $eryRow['mailinFee'], $eryRow['mailinIdBase'], $eryRow['sortorder']
        );
        $newERYId = dbSafeInsert($eRYI, 'iissiiiidii', $valueArray);
        if ($newERYId === false) {
            error_log("Unable to insert new exhibitRegionYear");
            var_error_log($valueArray);
            continue;
        }
        
        // now exhibitsSpaces
        $eSR = dbSafeQuery($eSQ, 'i', array($eryRow['id']));
        if ($eSR === false) {
            error_log("unable to fetch exhibitSpaces for year " . $conid - 1 . " and exhibitsRegionYear " . $eryRow['id']);
            continue;
        }

        while ($esRow = $eSR->fetch_assoc()) {
            $valueArray = array(
                $newERYId, $esRow['shortname'], $esRow['name'], $esRow['description'], $esRow['unitsAvailable'],
                $esRow['unitsAvailableMailin'], $esRow['sortorder']
            );
            $newESId = dbSafeInsert($eSI, 'isssiii', $valueArray);
            if ($newESId === false) {
                error_log('Unable to insert new exhibitsSpaces');
                var_error_log($valueArray);
                continue;
            }

            // now for the prices for this space
            $eSPR = dbSafeQuery($eSPQ, 'i', array ($esRow['id']));
            if ($eSPR === false) {
                error_log('unable to fetch exhibitSpacePrices for year ' . $conid - 1 . ', exhibitsRegionYear ' . $eryRow['id'] . ', spaceId ', $esRow['id']);
                continue;
            }

            while ($espRow = $eSPR->fetch_assoc()) {
                $valueArray = array (
                    $newESId, $espRow['code'], $espRow['description'], $espRow['units'], $espRow['price'],
                    $espRow['includedMemberships'], $espRow['additionalMemberships'], $espRow['requestable'], $espRow['sortorder']
                );
                $newESPId = dbSafeInsert($eSPI, 'issddiiii', $valueArray);
                if ($newESPId === false) {
                    error_log('Unable to insert new exhibitsSpaces');
                    var_error_log($valueArray);
                }
            }
        }
    }
    $eRYR->free();

    return "Built exhibits configuration for $conid";
}

function findOrBuild($memId, $conId) : int | null {
    if ($memId == null) // no current one to match
        return null;

    $priorMemQ = <<<EOS
SELECT conid, sort_order, memCategory, memType, memAge, label, notes, price, 
       DATE_ADD(startdate, INTERVAL 1 YEAR) AS startdate, DATE_ADD(enddate, INTERVAL 1 YEAR) AS enddate, atcon, online
FROM memList
WHERE id = ? and conid = ?;
EOS;
    $curMemQ = <<<EOS
SELECT id
FROM memList
WHERE conid = ? AND memCategory = ? AND memType = ? AND memAge = ? AND label = ?;
EOS;

    // get the prior value
    $priorMemR = dbSafeQuery($priorMemQ, 'ii', array($memId, $conId - 1));
    if ($priorMemR ===  false || $priorMemR->num_rows != 1)
        return null;    // no prior value found
    $priorMem = $priorMemR->fetch_assoc();
    $priorMemR->free();

    $curMemR = dbSafeQuery($curMemQ, 'issss', array($conId, $priorMem['memCategory'], $priorMem['memType'], $priorMem['memAge'], $priorMem['label']));
    if ($curMemR === false)
        return null;    // query error, see logs

    if ($curMemR->num_rows > 0) {
        $newMemId = $curMemR->fetch_row()[0];
        $curMemR->free();
        return $newMemId;
    }

    // non exists, add it
    $memI = <<<EOS
INSERT INTO memList(conid, sort_order, memCategory, memType, memAge, label, notes, price, startdate, enddate, atcon, online)
VALUES (?,?,?,?,?,?,?,?,?,?,?,?);
EOS;
    $valueArray = array($conId,
        $priorMem['sort_order'], $priorMem['memCategory'], $priorMem['memType'], $priorMem['memAge'], $priorMem['label'],
         $priorMem['notes'], $priorMem['price'], $priorMem['startdate'], $priorMem['enddate'], $priorMem['atcon'], $priorMem['online']
    );

    $newId = dbSafeInsert($memI, 'iisssssdssss', $valueArray);
    if ($newId === false)
        return null;
    return $newId;
}