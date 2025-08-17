<?php
// interests - anything to do with the PHP side of interests so it can be used by multiple modules
// getInterests - get the raw interest data (not for a member)
function getInterests() {
    $interests = null;
    $iQ = <<<EOS
SELECT interest, description, sortOrder
FROM interests
WHERE active = 'Y'
ORDER BY sortOrder ASC;
EOS;
    $iR = dbQuery($iQ);
    if ($iQ !== false) {
        $interests = [];
        while ($row = $iR->fetch_assoc()) {
            $interests[] = $row;
        }
        $iR->free();
        if (count($interests) == 0) {
            $interests = null;
        }
    }
    return $interests;
}

//drawInterestList - draw the inner block for interest editing
function drawInterestList($interests, $modal = false, $tabIndexStart = 800) {
    if ($interests == null) // null? no interests, nothing to draw
        return;
    $tabindex = $tabIndexStart;
    $filter = getConfValue('portal', 'customtext', 'production');
    loadCustomText('profile', 'all', $filter, true);
    $header = returnCustomText('interests/header', 'profile/all/');
    $footer = returnCustomText('interests/footer', 'profile/all/');
    if ($header != '') {
?>
    <div class='row'>
        <div class='col-sm-auto'>
            <?php  echo $header . PHP_EOL; ?>
        </div>
    </div>
<?php
    }
    foreach ($interests as $interest) {
        $desc = replaceVariables($interest['description']);
?>
        <div class='row mt-1'>
            <div class='col-sm-auto'>
                <input type='checkbox' id='i_<?php echo $interest['interest'];?>' name='<?php echo $interest['interest'];?>'
                    tabindex="<?php echo $tabindex; $tabindex += 1;?>">
            </div>
            <div class='col-sm-auto'>
                <label for='i_<?php echo $interest['interest'];?>'><?php echo $desc; ?></label>
            </div>
        </div>
<?php
    }
    if ($footer != '') {
?>
            <div class='row'>
        <div class='col-sm-auto'>
            <?php  echo $footer . PHP_EOL; ?>
        </div>
    </div>
<?php
    }
}

// updateMemberInterests - update/insert the interests
function updateMemberInterests($conid, $personId, $personType, $loginId, $loginType) {
    $interests = getInterests();
    if ($interests == null) {
        return 0; // none updated because there are no interests configured
    }

    $newInterests = json_decode($_POST['newInterests'], true);
    $existingInterests = json_decode($_POST['existingInterests'], true);
    if ($existingInterests == null)
        $existingInterests = array ();

// find the differences in the interests to update the record

    if ($personType == 'p') {
        $pfield = 'perid';
    }
    else if ($personType == 'n') {
        $pfield = 'newperid';
    }
    // when you update the interests, force a re-notify of the change
    $updInterest = <<<EOS
UPDATE memberInterests
SET interested = ?, updateBy = ?, notifyDate = null, csvDate = null, updateDate = NOW()
WHERE id = ?;
EOS;
    $insInterest = <<<EOS
INSERT INTO memberInterests($pfield, conid, interest, interested, updateBy)
VALUES (?, ?, ?, ?, ?);
EOS;

    $rows_upd = 0;
    foreach ($interests as $interest) {
        $interestName = $interest['interest'];
        $newVal = array_key_exists($interestName, $newInterests) ? 'Y' : 'N';
        if (array_key_exists($interestName, $existingInterests)) {
            // this is an update, there is a record already in the memberInterests table for this interest.
            $existing = $existingInterests[$interestName];
            if (array_key_exists('interested', $existing)) {
                $oldVal = $existing['interested'];
            }
            else {
                $oldVal = '';
            }
            // only update if changed
            if ($newVal != $oldVal) {
                $upd = 0;
                if ($existing['id'] != null) {
                    $upd = dbSafeCmd($updInterest, 'sii', array ($newVal, $loginId, $existing['id']));
                }
                if ($upd === false || $upd === 0) {
                    $newkey = dbSafeInsert($insInterest, 'iissi', array ($personId, $conid, $interestName, $newVal, $loginId));
                    if ($newkey !== false && $newkey > 0)
                        $rows_upd++;
                }
                else {
                    $rows_upd++;
                }
            }
        }
        else {
            // row doesn't exist in existing interests
            $newkey = dbSafeInsert($insInterest, 'iissi', array ($personId, $conid, $interestName, $newVal, $loginId));
            if ($newkey !== false && $newkey > 0)
                $rows_upd++;
        }
    }
    return $rows_upd;
}

// merge interests - merge interests from a new person and an existing person or two existing people
// Algorithm:
//  If newperson ones exist:
//      if perid ones exist:
//          update perid from newperson if newperson newer, and delete newperson
//      else if add perid to newperson ones
//  else
//  	do nothing
//  If At end of merge/new person if there are no perid based ones, make defaults

    function mergeInterests($conid, $remainingPerId, $sourceType, $sourceId, $loginId) {
        $interests = getInterests();
        if ($interests == null || count($interests) == 0) {
            return '';
        }

        // ok, there are interests to merge
        $sourceField = $sourceType == 'n' ? 'newperid' : 'perid';
        $sQ = <<<EOS
SELECT *
FROM memberInterests
WHERE $sourceField = ? AND conid = ?;
EOS;
        $rQ = <<<EOS
SELECT *
FROM memberInterests
WHERE perid = ? AND conid = ?;
EOS;
        $chgU = <<<EOS
UPDATE memberInterests
SET interested = ?, updateBy = ?, notifyDate = null, csvDate = null, updateDate = NOW()
WHERE id = ?;
EOS;
        $idU = <<<EOS
UPDATE memberInterests
SET perid = ?, updateBy = ?
WHERE id = ?;
EOS;
        $iP = <<<EOS
INSERT INTO memberInterests(perid, conid, interest, interested, updateBy)
VALUES (?, ?, ?, ?, ?);
EOS;
        $sD = <<<EOS
DELETE FROM memberInterests
WHERE id = ?;
EOS;

        $sourceInterests = [];
        $remainInterests = [];

        // source
        $sR = dbSafeQuery($sQ, 'ii', array($sourceId, $conid));
        if ($sR === false) {
            $message = "mergeInterests: Error retrieving source interests of $sourceType:$sourceId";
            error_log($message);
            return $message;
        }
        while ($sL = $sR->fetch_assoc()) {
            $sourceInterests[$sL['interest']] = $sL;
        }
        $sR->free();

        // remain
        $rR = dbSafeQuery($rQ, 'ii', array($remainingPerId, $conid));
        if ($rR === false) {
            $message = "mergeInterests: Error retrieving remaining interests of $remainingPerId";
            error_log($message);
            return $message;
        }
        while ($rL = $rR->fetch_assoc()) {
            $remainInterests[$rL['interest']] = $rL;
        }
        $rR->free();

        $numUpd = 0;
        $numDel = 0;
        $numIns = 0;

        foreach ($interests as  $interest) {
            $interestName = $interest['interest'];
            if (array_key_exists($interestName, $sourceInterests)) {
                $newRow = $sourceInterests[$interestName];
                if (array_key_exists($interestName, $remainInterests)) {
                    // the interest exists in both, check if it needs to be updated
                    $oldRow = $remainInterests[$interestName];
                    if ($oldRow['interested'] != $newRow['interested']) {
                        // they are not the same interested, update the remaining interests
                        $numUpd += dbSafeCmd($chgU, 'sii', array($newRow['interested'], $loginId, $oldRow['id']));
                    }
                    // now delete the 'source' interest
                    $numDel += dbSafeCmd($sD, 'i', array($newRow['id']));
                } else {
                    // the remaining place doesn't have the source interest, update the perid field of this id to the remaining id
                    $numUpd += dbSafeCmd($idU, 'iii', array($remainingPerId, $loginId, $newRow['id']));
                }
            } else {
                // not in the source, if not in the remain, insert the default value
                if (!array_key_exists($interestName, $remainInterests)) {
                    $newId = dbSafeInsert($iP, 'iissi', array($remainingPerId, $conid, $interestName, 'N', $loginId));
                    if ($newId !== false) {
                        $numIns++;
                    }
                }
            }
        }
        return '';
    }
