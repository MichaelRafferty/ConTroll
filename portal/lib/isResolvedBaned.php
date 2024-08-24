<?php
// isResolvedBanned
//  check if the current session owner has been resolved or banned since they logged in
//      also catches up race condition orphaned records if so
//  returns null if not applicable or an array
//      idType = new session idType
//      id = new sessiond id
//      logout = 1: force logout
//      anything else: count of rows updated for the logs

function isResolvedBanned() {
    $loginId = getSessionVar('id');
    $loginType = getSessionVar('idType');

    if ($loginType == 'n') {
        // check to see if they are now resolved
        $checkQ = <<<EOS
SELECT perid
FROM newperson
WHERE id = ?;
EOS;
        $checkR = dbSafeQuery($checkQ, 'i', array($loginId));
        if ($checkR === false || $checkR->num_rows != 1)
            return null; // not found, why?, get out

        $perid = $checkR->fetch_row()[0];
        $checkR->free();
        if ($perid == null || $perid == 0) {
            return null; // not resolved yet
        }

        // ok they are resolved, we need to fix the login session, the database values that might have been missed
        //      run the queries to catch up any missed entries
        //      return information to the calling system to let them change the javascript variables
        $updates = [];
        $updates['idType'] = 'p';
        $updates['id'] = $perid;

        // tables needing updating: reg, transaction, exhibitors, memberInterests, memberPolicies, payorPlans, perinfo
        $upQ = <<<EOS
UPDATE reg 
JOIN newperson n ON reg.newperid = n.id AND n.perid IS NOT NULL
SET reg.perid = n.perid
WHERE reg.perid 
EOS;
        $updates['reg'] = dbCmd($upQ);
        $upQ = <<<EOS
UPDATE transaction
JOIN newperson n ON transaction.newperid = n.id AND n.perid IS NOT NULL
SET transaction.perid = n.perid
WHERE transaction.perid IS NULL;
EOS;
        $updates['trans'] = dbCmd($upQ);
        $upQ = <<<EOS
UPDATE exhibitors
JOIN newperson n ON exhibitors.newperid = n.id AND n.perid IS NOT NULL
SET exhibitors.perid = n.perid
WHERE exhibitors.perid IS NULL;
EOS;
        $updates['exh'] = dbCmd($upQ);
        $upQ = <<<EOS
UPDATE memberInterests
JOIN newperson n ON memberInterests.newperid = n.id AND n.perid IS NOT NULL
SET memberInterests.perid = n.perid
WHERE memberInterests.perid IS NULL;
EOS;
        $updates['int'] = dbCmd($upQ);
        $upQ = <<<EOS
UPDATE memberPolicies
JOIN newperson n ON memberPolicies.newperid = n.id AND n.perid IS NOT NULL
SET memberPolicies.perid = n.perid
WHERE memberPolicies.perid IS NULL;
EOS;
        $updates['pol'] = dbCmd($upQ);
        $upQ = <<<EOS
UPDATE payorPlans
JOIN newperson n ON payorPlans.newperid = n.id AND n.perid IS NOT NULL
SET payorPlans.perid = n.perid
WHERE payorPlans.perid IS NULL;
EOS;
        $updates['plan'] = dbCmd($upQ);
        $upQ = <<<EOS
UPDATE perinfo
JOIN newperson n ON perinfo.managedByNew = n.id AND n.perid IS NOT NULL
SET perinfo.managedBy = n.perid, perinfo.managedByNew = null
WHERE perinfo.managedBy IS NULL AND perinfo.managedByNew IS NOT NULL;
EOS;
        $updates['per'] = dbCmd($upQ);

        // now update the session to show the new id
        setSessionVar('id', $perid);
        setSessionVar('idType', 'p');
        error_log("Warning: resolved session of $perid");
        var_error_log($updates);
        return $updates;
    }

// ok, if you get here, you're a 'p', check for Banned and if so, clear the associations, and the session
    $checkQ = <<<EOS
SELECT banned, managedBy
FROM perinfo
WHERE id = ?;
EOS;
    $checkR = dbSafeQuery($checkQ, 'i', array($loginId));
    if ($checkR === false || $checkR->num_rows != 1)
        return null; // not found, why?, get out

    $checkL = $checkR->fetch_assoc();
    $checkR->free();
    if ($checkL['banned'] != 'Y')
        return null;    // not banned nothing to do.

    $updates = [];
    // ok, banned, disassociate them from anyone they manage and clear their managed flag, and themselves from anyone managing them
    $uQ = <<<EOS
UPDATE perinfo
SET managedBy = null
WHERE managedBy = ? OR id = ?;
EOS;
    $updates['man'] = dbSafeCmd($uQ, 'ii', array($loginId, $loginId));

    // now log them out and return same
    $updates['logout'] = 1;
    clearSession();
    error_log("Warning: Forced logout of $loginId");
    return $updates;
}