<?php
// exhibitorYears and exhibiorApprovals related functions for create/retrieval

// exhibitorBuildYears - build exhibitorYears and exhibitorApprovals for this year
function exhibitorBuildYears($exhibitor, $contactName = NULL, $contactEmail = NULL, $contactPhone = NULL, $contactPassword = NULL, $mailin = 'N'): bool|string {
    $con = get_conf('con');
    $conid = $con['id'];
    $need_new = 0;
    $confirm = 0;

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
    // no last year or passed contact parameters, need to insert new version
    if ($last_year <= 0) {
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

        // Next is exhibitorRegionYears,  from exhibitsRegionYears
        $exRYinsq = <<<EOS
INSERT INTO exhibitorRegionYears(exhibitorYearId, exhibitsRegionYearId, sortorder) {
SELECT ey.id AS exhibitorYearId, eRY.id, eRY.sortorder
FROM exhibitorYears ey
JOIN exhibitsRegionYears eRY ON (ey.conid = eRY.conid)
WHERE ey.exhibitorId = ? AND ey.conid = ?
ORDER BY eRY.sortorder;
EOS;
        $numrows = dbSafeCmd($exRYinsq, 'ii', array($exhibitor, $conid));
    } else {
        // no passed parameters but prior year exists
        $yinsq = <<<EOS
INSERT INTO exhibitorYears(conid, exhibitorId, contactName, contactEmail, contactPhone, contactPassword, mailin, need_new, confirm)
SELECT ? as conid, exhibitorId, contactName, contactEmail, contactPhone, contactPassword, mailin, need_new, confirm
FROM exhibitorYears
WHERE conid = ? AND exhibitorId = ?
EOS;
        $newyrid = dbSafeInsert($yinsq, 'iii', array($conid, $last_year, $exhibitor));

        // Next is exhibitorRegionYears,  from exhibitsRegionYears
        $exRYinsq = <<<EOS
INSERT INTO exhibitorRegionYears(exhibitorYearId, exhibitsRegionYearId, agentPerid, sortorder) {
SELECT ey.id AS exhibitorYearId, eRY.id, eRYold.agentPerid, eRY.sortorder
FROM exhibitorYears eYold
JOIN exhibitorRegionYears eRYold ON eYold.id = eRYold.exhibitorYearId
JOIN exhibitorYears ey
JOIN exhibitsRegionYears eRY ON (ey.conid = eRY.conid)
WHERE ey.exhibitorId = ? AND ey.conid = ? AND eYold.conid = ?
ORDER BY eRY.sortorder;
EOS;
        $numrows = dbSafeCmd($exRYinsq, 'iii', array($exhibitor, $conid, $last_year));
    }

    // now build new approval records for this year

    // load prior approvals - for checking ones that are 'once'
    $priorQ = <<<EOS
SELECT exhibitsRegionYearId, count(*) approvedCnt
FROM exhibitorApprovals
WHERE exhibitorId = ? AND approval = 'approved'
GROUP BY exhibitsRegionYearId;
EOS;
    $priorR = dbSafeQuery($priorQ, 'i', array($exhibitor));
    $priors = [];
    while ($priorL = $priorR->fetch_assoc()) {
        $priors[$priorL['exhibitsRegionYearId']] = $priorL['approvedCnt'];
    }
    $priorR->free();
    // now build exhibitorApprovals from exhibitorYear and exhibitsRegionYears
    $appQ = <<<EOS
SELECT ery.id as exhibitsRegionYearId, et.requestApprovalRequired
FROM exhibitsRegionYears ery
JOIN exhibitsRegions er ON (er.id = ery.exhibitsRegion)
JOIN exhibitsRegionTypes et ON (et.regionType = er.regionType)
WHERE ery.conid = ? AND et.active = 'Y'
EOS;
    $insQ = <<<EOS
INSERT INTO exhibitorApprovals(exhibitorId, exhibitsRegionYearId, approval, updateBy)
VALUES (?,?,?,?);
EOS;
    $instypes = 'iisi';

    $appR = dbSafeQuery($appQ, 'i', array($conid));
    while ($appL = $appR->fetch_assoc()) {
        switch ($appL['requestApprovalRequired']) {
            case 'None':
                $approval = 'approved';
                break;
            case 'Once':
                if ($priors[$appL['exhibitsRegionYearId']] > 0) {
                    $approval = 'approved';
                    break;
                }
            // if count fall into annual (default) as it's not approved.
            default:
                $approval = 'none';
        }
        $newid = dbSafeInsert($insQ, $instypes, array($exhibitor, $appL['exhibitsRegionYearId'], $approval, 2));
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
    $priorQ = <<<EOS
SELECT exhibitsRegionYearId, count(*) approvedCnt
FROM exhibitorApprovals
WHERE exhibitorId = ? AND approval = 'approved'
GROUP BY exhibitsRegionYearId;
EOS;
    $priorR = dbSafeQuery($priorQ, 'i', array($exhibitor));
    $priors = [];
    while ($priorL = $priorR->fetch_assoc()) {
        $priors[$priorL['exhibitsRegionYearId']] = $priorL['approvedCnt'];
    }
    $priorR->free();
    // now build exhibitorApprovals from exhibitorYear and exhibitsRegionYears
    $appQ = <<<EOS
SELECT ery.id as exhibitsRegionYearId, et.requestApprovalRequired
FROM exhibitsRegionYears ery
JOIN exhibitsRegions er ON (er.id = ery.exhibitsRegion)
JOIN exhibitsRegionTypes et ON (et.regionType = er.regionType)
LEFT OUTER JOIN exhibitorApprovals eA ON eA.exhibitsRegionYearId = ery.id AND  eA.exhibitorId = ?
WHERE ery.conid = ? AND et.active = 'Y' AND eA.id IS NULL
EOS;
    $insQ = <<<EOS
INSERT INTO exhibitorApprovals(exhibitorId, exhibitsRegionYearId, approval, updateBy)
VALUES (?,?,?,?);
EOS;
    $instypes = 'iisi';

    $appR = dbSafeQuery($appQ, 'ii', array($exhibitor, $conid));
    while ($appL = $appR->fetch_assoc()) {
        switch ($appL['requestApprovalRequired']) {
            case 'None':
                $approval = 'approved';
                break;
            case 'Once':
                if ($priors[$appL['exhibitsRegionYearId']] > 0) {
                    $approval = 'approved';
                    break;
                }
            // if count fall into annual (default) as it's not approved.
            default:
                $approval = 'none';
        }
        $newid = dbSafeInsert($insQ, $instypes, array($exhibitor, $appL['exhibitsRegionYearId'], $approval, 2));
    }
    $appR->free();

    // now for the exhibitorRegionYears
    $exRYinsq = <<<EOS
INSERT INTO exhibitorRegionYears(exhibitorYearId, exhibitsRegionYearId, sortorder) {
SELECT ey.id AS exhibitorYearId, eRY.id, eRY.sortorder
FROM exhibitorYears ey
JOIN exhibitsRegionYears eRY ON (ey.conid = eRY.conid)
LEFT OUTER JOIN exhibitorRegionYears exRY ON exRY.exhibitsRegionYearId = eRY.id
WHERE ey.exhibitorId = ? AND ey.conid = ? AND exRY.id IS NULL
ORDER BY eRY.sortorder;
EOS;
    $numrows = dbSafeCmd($exRYinsq, 'ii', array($exhibitor, $conid));

    // now build spaces for this year that don't exist
    $insSpQ = <<<EOS
INSERT INTO exhibitorSpaces(exhibitorYearId, spaceId)
SELECT ?, es.id
FROM exhibitsSpaces es
JOIN exhibitsRegionYears ery ON es.exhibitsRegionYear = ery.id
LEFT OUTER JOIN exhibitorSpaces eS ON eS.spaceId = es.id AND eS.exhibitorYearId = ?
WHERE ery.conid = ? AND eS.id is null;
EOS;
    $numRows = dbSafeCmd($insSpQ, 'iii', array($yearId, $yearId, $conid));
    return;
}
