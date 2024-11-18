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
function drawInterestList($interests, $modal = false) {
    if ($interests == null) // null? no interests, nothing to draw
        return;
?>
    <div class='row'>
        <div class='col-sm-auto'>
            <h<?php echo $modal ? '2 class="size-h3"' : '3 class="text-primary"';?>>
                Additional Interests or Needs
            </h<?php echo $modal ? '2' : '3';?>>
        </div>
    </div>
    <div class='row mb-2'>
        <div class='col-sm-auto'>
            This form lets us know if you want to be contacted about specific things. We ask these questions to help us give you the experience you are after.
        </div>
    </div>
<?php
    foreach ($interests as $interest) {
        $desc = replaceVariables($interest['description']);
?>
        <div class='row mt-1'>
            <div class='col-sm-auto'>
                <input type='checkbox' id='i_<?php echo $interest['interest'];?>' name='<?php echo $interest['interest'];?>'>
            </div>
            <div class='col-sm-auto'>
                <label for='i_<?php echo $interest['interest'];?>'><?php echo $desc; ?></label>
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
    $updInterest = <<<EOS
UPDATE memberInterests
SET interested = ?, updateBy = ?
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