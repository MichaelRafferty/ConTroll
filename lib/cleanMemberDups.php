<?php
// clean up memberInterest and memberPolicy duplicates

function checkDups($type): array {
    $con = get_conf('con');
    $conid = $con['id'];

    switch ($type) {
        case 'interests':
            $chkQ = <<<EOS
SELECT conid, perid, interest, COUNT(*)
FROM memberInterests
WHERE perid IS NOT NULL AND conid = ?
GROUP BY conid, perid, interest
HAVING COUNT(*) > 1;
EOS;
            break;

        case 'policies':
            $chkQ = <<<EOS
SELECT conid, perid, policy, COUNT(*)
FROM memberPolicies
WHERE perid IS NOT NULL AND conid = ?
GROUP BY conid, perid, policy
HAVING COUNT(*) > 1;
EOS;
            break;

        default:
            return [];
    }

    $chkR = dbSafeQuery($chkQ, 'i', array($conid));
    if ($chkR === false)
        return [];

    $dups = [];
    while ($dup = $chkR->fetch_assoc()) {
        $dups[] = $dup;
    }
    $chkR->free();
    return $dups;
}

function dedupTable($type, $dups) : array {
    $rowsUpd = 0;
    $rowsDel = 0;

    switch ($type) {
        case 'interests':
            $field = 'interest';
            $result = 'interested';
            $intQ = <<<EOS
SELECT *
FROM memberInterests
WHERE conid = ? AND interest = ? AND perid = ?
ORDER BY updateDate DESC;
EOS;
            $updQ = <<<EOS
UDPATE memberInterests
SET notifyDate = ?, updateDate = ?
WHERE id = ?;
EOS;
            $delQ = <<<EOS
DELETE FROM memberInterests
WHERE id = ?;
EOS;
            break;

        case 'policies':
            $field = 'policy';
            $result = 'response';
            $intQ = <<<EOS
SELECT *
FROM memberPolicies
WHERE conid = ? AND policy = ? AND perid = ?
ORDER BY updateDate DESC;
EOS;
            $updQ = null;
            
            $delQ = <<<EOS
DELETE FROM memberPolicies
WHERE id = ?;
EOS;
            break;

        default:
            return [];
    }

    foreach ($dups AS $dup) {  // for each dup passed in
        $intR = dbSafeQuery($intQ, 'isi', array($dup['conid'], $dup[$field], $dup['perid']));
        if ($intR === false)
            continue;

        $rows = []; // get all rows for that dup
        while ($row = $intR->fetch_assoc()) {
            $rows[] = $row;
        }
        $intR->free();

        if (count($rows) < 2)
            continue;       // some how it changed between the two calls, not an issue, so go to the next one

        $saveRow = $rows[0];
        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            $rowChanged = false;
            // check if newer send dates and if so, use that instead, if the interested value is the same
            if ($type == 'inerests' && $row[$result] == $saveRow[$result]) {
                if ((!array_key_exists('notifyDate', $saveRow)) || $saveRow['notifyDate'] == null) {
                    $saveRow['notifyDate'] = $row['notifyDate'];
                    $rowChanged = true;
                }
                else if (array_key_exists('notifyDate', $row) && $row['notifyDate'] > $saveRow['notifyDate']) {
                    $saveRow['notifyDate'] = $row['notifyDate'];
                    $rowChanged = true;
                }

                if ((!array_key_exists('csvDate', $saveRow)) || $saveRow['csvDate'] == null) {
                    $saveRow['csvDate'] = $row['csvDate'];
                    $rowChanged = true;
                }
                else if ($row['csvDate'] > $saveRow['csvDate']) {
                    $saveRow['csvDate'] = $row['csvDate'];
                    $rowChanged = true;
                }
            }

            // now update the main row if it's changed
            if ($rowChanged) {
                $rowsUpd += dbSafeCmd($updQ, 'ssi', array ($saveRow['notifyDate'], $saveRow['csvDate'], $saveRow['id']));
            }

            for ($i = 1; $i < count($rows); $i++) {
                $rowsDel += dbSafeCmd($delQ, 'i', array ($rows[$i]['id']));
            }
        }
    }

    return array($rowsUpd, $rowsDel);
}
