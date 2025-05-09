<?php
// exhibitorYears and exhibiorRegionYears related functions for create/retrieval

// exhibitorBuildYears - build exhibitorYears and exhibitorRegionYears for this year
function exhibitorBuildYears($exhibitor, $contactName = NULL, $contactEmail = NULL, $contactPhone = NULL, $contactPassword = NULL, $mailin = 'N'): bool|string {
    $con = get_conf('con');
    $conid = $con['id'];
    $need_new = 0;
    $confirm = 0;
    $newyrid = 'Error: No path to set exhibitor year identifier';

    // first get the last (if any) contact info for this exhibitor, only check if not directly passed
    if ($contactName == NULL) {
        $ydsql = <<<EOS
SELECT MAX(conid)
FROM exhibitorYears
WHERE exhibitorId = ?;
EOS;
        $ydR = dbSafeQuery($ydsql, 'i', array($exhibitor));
        if ($ydR->num_rows !== 1) {
            $last_year = 0;
        } else {
            $last_year = $ydR->fetch_row()[0];
        }
        $ydR->free();
    } else {
        $last_year = 0;
    }
    if ($last_year <= 0) {  // no last year or passed contact parameters, need to insert new version
        if ($contactName == NULL) { // get default information from vendor
            $eyDefQ = <<<EOS
SELECT exhibitorName, exhibitorEmail, exhibitorPhone, password, need_new, confirm
FROM exhibitors
WHERE id = ?;
EOS;
            $eyDefR = dbSafeQuery($eyDefQ, 'i', array($exhibitor));
            if ($eyDefR === false || $eyDefR->num_rows != 1) {
                return "Exhibitor not found";
            }
            $eyDefL = $eyDefR->fetch_assoc();
            $contactName = $eyDefL['exhibitorName'];
            $contactEmail = $eyDefL['exhibitorEmail'];
            $contactPhone = $eyDefL['exhibitorPhone'];
            $contactPassword = $eyDefL['password'];
            $need_new = $eyDefL['need_new'];
            $confirm = $eyDefL['confirm'];
            $eyDefR->free();
        } else {
            $contactPassword = password_hash(trim($contactPassword), PASSWORD_DEFAULT);
        }
        $eyinsq = <<<EOS
INSERT INTO exhibitorYears(conid, exhibitorId, contactName, contactEmail, contactPhone, contactPassword, mailin, need_new, confirm)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?);
EOS;
        $typestr = 'iisssssii';
        $paramArray = array(
            $conid,
            $exhibitor,
            trim($contactName),
            trim($contactEmail),
            trim($contactPhone),
            $contactPassword,
            $mailin,
            $need_new,
            $confirm
        );
        $newyrid = dbSafeInsert($eyinsq, $typestr, $paramArray);
    } else if ($last_year < $conid) {
        // build from last year
        $eyinsQ = <<<EOS
INSERT INTO exhibitorYears (conid, exhibitorId, contactName, contactEmail, contactPhone, contactPassword, mailin, need_new, confirm)
SELECT ?, exhibitorId, contactName, contactEmail, contactPhone, contactPassword, mailin, need_new, confirm
FROM exhibitorYears
WHERE conid = ? AND exhibitorId = ?;
EOS;
        $newyrid = dbSafeInsert($eyinsQ, 'iii', array($conid, $last_year, $exhibitor));
    } else {
        // with the new partial exhibits region fill out, we need to return the eyID in all cases
        $eyselQ = <<<EOS
SELECT id
FROM exhibitorYears
WHERE conid = ? AND exhibitorId = ?;
EOS;
        $eyselR = dbSafeQuery($eyselQ, 'ii', array ($conid, $exhibitor));
        if ($eyselR === false || $eyselR->num_rows != 1) {
            return 'Exhibitor year not found';
        }
        $newyrid = $eyselR->fetch_row()[0];
        $eyselR->free();
    }

    // build a exhibitorRegionYears from exhibitsRegionYears and any past data
    // load prior approvals - for checking ones that are 'once'
    $priorQ = <<<EOS
WITH mostrecentPerid AS (
    SELECT exhibitsRegion, MAX(ey.conid) AS conid
    FROM exhibitorRegionYears exRY
    JOIN exhibitorYears ey ON exRY.exhibitorYearId = ey.id
    JOIN exhibitsRegionYears eRY ON eRY.id = exRY.exhibitsRegionYearId
    WHERE ey.exhibitorId = ?
    GROUP BY exhibitsRegion
), perid AS (
    SELECT p.exhibitsRegion, exRY.agentPerid, exRY.agentNewperson
    FROM exhibitorRegionYears exRY
    JOIN exhibitorYears ey ON exRY.exhibitorYearId = ey.id
    JOIN exhibitsRegionYears eRY ON eRY.id = exRY.exhibitsRegionYearId
    JOIN mostrecentPerid p ON p.conid = ey.conid AND p.exhibitsRegion = eRY.exhibitsRegion
    WHERE ey.exhibitorId = ?
), app AS (
    SELECT ery.exhibitsRegion, count(*) AS approvedCnt, MAX(updateDate) AS updateDate, MAX(updateBy) AS updateBy
    FROM exhibitorRegionYears exRY
    JOIN exhibitorYears eY on exRY.exhibitorYearId = eY.id
    JOIN exhibitsRegionYears ery ON exRY.exhibitsRegionYearId = ery.id
    WHERE exhibitorId = ? AND approval = 'approved'
    GROUP BY exhibitsRegion
)
SELECT app.exhibitsRegion, approvedCnt, updateDate, updateBy, agentPerid, agentNewperson
    FROM app
    LEFT OUTER JOIN perid p ON p.exhibitsRegion = app.exhibitsRegion;
EOS;
    $priorR = dbSafeQuery($priorQ, 'iii', array($exhibitor, $exhibitor, $exhibitor));
    $priors = [];
    while ($priorL = $priorR->fetch_assoc()) {
        $priors[$priorL['exhibitsRegion']] = $priorL;
    }
    $priorR->free();

    // now for the creation of exhibitorRegionYears taking into account the region approvals above

    $appQ = <<<EOS
SELECT ery.id as exhibitsRegionYearId, et.requestApprovalRequired, ey.id AS exhibitorYearId, ery.exhibitsRegion, exRY.id AS exRYid
FROM exhibitsRegionYears ery
JOIN exhibitsRegions er ON ery.exhibitsRegion = er.id
JOIN exhibitsRegionTypes et ON (et.regionType = er.regionType)
JOIN exhibitorYears ey on ery.conid = ey.conid
LEFT OUTER JOIN exhibitorRegionYears exRY ON ey.id = exRY.exhibitorYearId AND ery.id = exRY.exhibitsRegionYearId
WHERE ery.conid = ? AND et.active = 'Y' AND ey.exhibitorId = ?
EOS;
    $insQ = <<<EOS
INSERT INTO exhibitorRegionYears(exhibitorYearId, exhibitsRegionYearId, agentPerid,  agentNewperson, approval, updateDate, updateBy, sortorder)
VALUES (?, ?, ?, ?, ?, ?, ?, ?);
EOS;
    $instypes = 'iiiissii';

    $sortorder = 10;
    $now = date('Y-m-d H-i-s');
    $appR = dbSafeQuery($appQ, 'ii', array($conid, $exhibitor));
    while ($appL = $appR->fetch_assoc()) {
        if ($appL['exRYid'] != null) {
            // it already exists, don't add another
            $sortorder += 10;
            continue;
        }
        switch ($appL['requestApprovalRequired']) {
            case 'None':
                $approval = 'approved';
                break;
            case 'Once':
                if ($priors[$appL['exhibitsRegion']] > 0) {
                    $approval = 'approved';
                    break;
                }
            // if count fall into annual (default) as it's not approved.
            default:
                $approval = 'none';
        }

        $agentPerid = null;
        $agentNewperson = null;
        $updateBy = 2;
        $updatedDate = $now;
        if (array_key_exists($appL['exhibitsRegion'], $priors)) {
            $agentPerid = $priors[$appL['exhibitsRegion']]['agentPerid'];
            $agentNewperson = $priors[$appL['exhibitsRegion']]['agentNewperson'];
            if (array_key_exists('updateBy', $priors[$appL['exhibitsRegion']]))
                $updateBy = $priors[$appL['exhibitsRegion']]['updateBy'];
            if (array_key_exists('updateDate', $priors[$appL['exhibitsRegion']]))
                $updatedDate = $priors[$appL['exhibitsRegion']]['updateDate'];
            if ($updateBy == null) {
                $updateBy = 2;
                $updatedDate = $now;
            }
        }
        $newid = dbSafeInsert($insQ, $instypes, array($appL['exhibitorYearId'], $appL['exhibitsRegionYearId'], $agentPerid, $agentNewperson, $approval, $updatedDate, $updateBy, $sortorder));
        $sortorder += 10;
    }
    $appR->free();
    return $newyrid;
}


// exhibitorCheckMissingSpaces - check for missing approval and space records for newly created spaces
function exhibitorCheckMissingSpaces($exhibitor, $yearId) {
    $con = get_conf('con');
    $conid = $con['id'];

    // now build new approval records for this year that don't already exist
    // load prior approvals - for checking ones that are 'once'
    // build a exhibitorRegionYears from exhibitsRegionYears and any past data
    // load prior approvals - for checking ones that are 'once'
    $priorQ = <<<EOS
WITH mostrecentPerid AS (
    SELECT exhibitsRegion, MAX(ey.conid) AS conid
    FROM exhibitorRegionYears exRY
    JOIN exhibitorYears ey ON exRY.exhibitorYearId = ey.id
    JOIN exhibitsRegionYears eRY ON eRY.id = exRY.exhibitsRegionYearId
    WHERE ey.exhibitorId = ?
    GROUP BY exhibitsRegion
), perid AS (
    SELECT p.exhibitsRegion, exRY.agentPerid 
    FROM exhibitorRegionYears exRY
    JOIN exhibitorYears ey ON exRY.exhibitorYearId = ey.id
    JOIN exhibitsRegionYears eRY ON eRY.id = exRY.exhibitsRegionYearId
    JOIN mostrecentPerid p ON p.conid = ey.conid AND p.exhibitsRegion = eRY.exhibitsRegion
    WHERE ey.exhibitorId = ?
), app AS (
    SELECT ery.exhibitsRegion, count(*) AS approvedCnt, MAX(updateDate) AS updateDate, MAX(updateBy) AS updateBy
    FROM exhibitorRegionYears exRY
    JOIN exhibitorYears eY on exRY.exhibitorYearId = eY.id
    JOIN exhibitsRegionYears ery ON exRY.exhibitsRegionYearId = ery.id
    WHERE exhibitorId = ? AND approval = 'approved'
    GROUP BY exhibitsRegion
)
SELECT app.exhibitsRegion, approvedCnt, updateDate, updateBy, agentPerid
    FROM app
    LEFT OUTER JOIN perid p ON p.exhibitsRegion = app.exhibitsRegion;
EOS;
    $priorR = dbSafeQuery($priorQ, 'iii', array($exhibitor, $exhibitor, $exhibitor));
    $priors = [];
    while ($priorL = $priorR->fetch_assoc()) {
        $priors[$priorL['exhibitsRegion']] = $priorL;
    }
    $priorR->free();

    // now for the creation of exhibitorRegionYears taking into account the region approvals above

    $appQ = <<<EOS
SELECT ery.id as exhibitsRegionYearId, et.requestApprovalRequired, ey.id AS exhibitorYearId, ery.exhibitsRegion
FROM exhibitsRegionYears ery
JOIN exhibitsRegions er ON ery.exhibitsRegion = er.id
JOIN exhibitsRegionTypes et ON (et.regionType = er.regionType)
JOIN exhibitorYears ey on ery.conid = ey.conid
LEFT OUTER JOIN exhibitorRegionYears exRY ON ey.id = exRY.exhibitorYearId
WHERE ery.conid = ? AND et.active = 'Y' AND ey.exhibitorId = ? AND exRY.id IS NULL
EOS;
    $insQ = <<<EOS
INSERT INTO exhibitorRegionYears(exhibitorYearId, exhibitsRegionYearId, agentPerid, approval, updateDate, updateBy, sortorder)
VALUES (?, ?, ?, ?, ?, ?, ?);
EOS;
    $instypes = 'iiissii';

    $sortorder = 10;
    $now = date('Y-m-d H-i-s');
    $appR = dbSafeQuery($appQ, 'ii', array($conid, $exhibitor));
    while ($appL = $appR->fetch_assoc()) {
        switch ($appL['requestApprovalRequired']) {
            case 'None':
                $approval = 'approved';
                break;
            case 'Once':
                if ($priors[$appL['exhibitsRegion']] > 0) {
                    $approval = 'approved';
                    break;
                }
            // if count fall into annual (default) as it's not approved.
            default:
                $approval = 'none';
        }
        $agentPerid = null;
        $updateBy = 2;
        $updatedDate = $now;
        if (array_key_exists($appL['exhibitsRegion'], $priors)) {
            $agentPerid = $priors[$appL['exhibitsRegion']]['agentPerid'];
            $updateBy = $priors[$appL['exhibitsRegion']]['updateBy'];
            $updatedDate = $priors[$appL['exhibitsRegion']]['updateDate'];
            if ($updateBy == null) {
                $updateBy = 2;
                $updatedDate = $now;
            }
        }
        $newid = dbSafeInsert($insQ, $instypes, array($appL['exhibitorYearId'], $appL['exhibitsRegionYearId'], $agentPerid, $approval, $updatedDate, $updateBy, $sortorder));
        $sortorder += 10;
    }
    $appR->free();

    // now build spaces for this year that don't exist
    $insSpQ = <<<EOS
INSERT INTO exhibitorSpaces(exhibitorRegionYear, spaceId)
SELECT exRY.id, es.id
FROM exhibitsSpaces es
JOIN exhibitsRegionYears ery ON es.exhibitsRegionYear = ery.id
JOIN exhibitorRegionYears exRY ON ery.id = exRY.exhibitsRegionYearId
JOIN exhibitorYears ey on ery.conid = ey.conid
LEFT OUTER JOIN exhibitorSpaces eS ON eS.spaceId = es.id AND eS.exhibitorRegionYear = exRY.id
WHERE ery.conid = ? AND ey.exhibitorId = ? AND eS.id is null;
EOS;
    $numRows = dbSafeCmd($insSpQ, 'ii', array($conid, $exhibitor));
    return;
}
