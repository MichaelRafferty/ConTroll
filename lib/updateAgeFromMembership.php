<?php
    /*
     * Copyright (c) 2026, Michael Rafferty
     * ConTroll™ is freely available for use under the GNU Affero General Public License, Version 3. See the ConTroll™ ReadMe file.
     *
     * updateAgeFromMembership: if the users age is null or out of date (eariler conid), update it from thir current memberships for this conid
     * If a conflict, return a message indicating the conflict.
     * Designed to work from PoS and portal.
     */

    function updateAgeFromMembership($personId, $personType, $conid): string {
        if ($personType == 'p') {
            $pQ = <<<EOS
SELECT currentAgeConid, currentAgeType
FROM perinfo
WHERE id = ?;
EOS;
            $mQ = <<<EOS
SELECT m.memAge
FROM reg r
JOIN memList m ON r.memId = m.id
WHERE r.conid = ? And r.perid = ?
EOS;
            $upQ = <<<EOS
UPDATE perinfo
SET currentAgeConid = ?, currentAgeType = ?
WHERE id = ?;
EOS;
        } else {
            $pQ = <<<EOS
SELECT currentAgeConid, currentAgeType
FROM newperson
WHERE id = ?;
EOS;
            $mQ = <<<EOS
SELECT m.memAge
FROM reg r
JOIN memList m ON r.memId = m.id
WHERE r.conid = ? And r.newperid = ?
EOS;
            $upQ = <<<EOS
UPDATE newperson
SET currentAgeConid = ?, currentAgeType = ?
WHERE id = ?;
EOS;
        }
        // get the age
        $pR = dbSafeQuery($pQ, 'i', array ($personId));
        if ($pR === false || $pR->num_rows != 1)
            return "Error: could not get current age for person $personId";

        $pA = $pR->fetch_assoc();
        $pR->free();
        $currentAgeConid = $pA['currentAgeConid'];
        $currentAgeType = $pA['currentAgeType'];

        $needAge = $currentAgeType == null || $currentAgeConid < $conid;

        $mR = dbSafeQuery($mQ, 'ii', array($conid, $personId));
        if ($mR === false || $mR->num_rows == 0)
            return ''; // nothing we can use to update

        $curAge = null;
        while ($mem = $mR->fetch_assoc()) {
            $newAge = $mem['memAge'];
            if ($newAge == 'all')
                continue;
            if ($curAge != null && $curAge != $newAge) {
                $mR->free();
                return 'Warning: person $personId has multiple membership ages';
            }
            $curAge = $newAge;
        }
        $mR->free();
        if ($curAge == null) // nothing we can use for updating
            return '';

        if ($needAge) {
            // set the age from the memberships for this person
            $numUpd = dbSafeCmd($upQ, 'isi', array($conid, $curAge, $personId));
            if ($numUpd == 1)
                return '';
            return "Unable to update the age for person $personId";
        }

        // validate the age
        if ($curAge != $currentAgeType)
            return "Warning: person $personId current age ($currentAgeType) does not match the membership type ($curAge)";

        return '';
    }
