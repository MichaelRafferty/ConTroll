<?php
// conroles - anything to do with the PHP side of conRoles so it can be used by multiple modules
// getConRoles - get the raw conROle data (not for a member)
function getConRoles() {
    $conRoles = null;
    if (getConfValue('con', 'conRoles', 0) == 1) {
        $cQ = <<<EOS
SELECT conRole, description, memLabel, sortOrder
FROM conRoles
WHERE active = 'Y'
ORDER BY sortOrder ASC;
EOS;
        $cR = dbQuery($cQ);
        if ($cQ !== false) {
            $conRoles = [];
            while ($row = $cR->fetch_assoc()) {
                $conRoles[] = $row;
            }
            $cR->free();
            if (count($conRoles) == 0) {
                $conRoles = null;
            }
        }
    } 
    return $conRoles;
}

//drawConRolesList - draw the inner block for role editing
function drawConRolesList($conroles, $modal = false, $tabIndexStart = 900) {
    if ($conroles == null || count($conroles) == 0) // null? no conroles, nothing to draw
        return;

    $tabindex = $tabIndexStart;
    loadCustomText('profile', 'all', getConfValue('portal', 'customtext', 'production'), true);
    $header = returnCustomText('conroles/header', 'profile/all/');
    $footer = returnCustomText('conroles/footer', 'profile/all/');
    if ($header != '') {
?>
    <div class='row'>
        <div class='col-sm-auto'>
            <?php  echo $header . PHP_EOL; ?>
        </div>
    </div>
<?php
    }
    foreach ($conroles as $conrole) {
        $desc = replaceVariables($conrole['description']);
?>
        <div class='row mt-1'>
            <div class='col-sm-auto'>
                <input type='checkbox' id='i_<?php echo $conrole['conrole'];?>' name='<?php echo $conrole['conrole'];?>'
                       tabindex="<?php echo $tabindex; $tabindex += 1;?>">
            </div>
            <div class='col-sm-auto'>
                <label for='i_<?php echo $conrole['conrole'];?>'><?php echo $desc; ?></label>
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

// drawConRolesDisplay: draw a read-only (display only) version of the policies and answers
function drawConRolesDisplay($conroles, $personConRoles, $id) {
    if ($conroles == null || count($conroles) == 0) // null? no conroles, nothing to draw
        return;

    loadCustomText('profile', 'all', getConfValue('portal', 'customtext', 'production'), true);
    $header = returnCustomText('conroles/header', 'profile/all/');
    $footer = returnCustomText('conroles/footer', 'profile/all/');
    if ($header != '') {
        ?>
        <div class='row mt-2'>
            <div class='col-sm-auto'>
                <?php  echo $header . PHP_EOL; ?>
            </div>
        </div>
        <?php
    }
    foreach ($conroles as $conrole) {
        $name = $conrole['conrole'];
        $description = replaceVariables($conrole['description']);
        if (array_key_exists($name, $personConRoles)) {
            $personConRole = $personConRoles[$name];
            $checked = $personConRole['conroleed'] == 'Y';
        } else {
            $checked = false;
        }

        if ($personConRole['readOnly'] == 0) {
            if ($checked)
                $box = '✅:';
            else
                $box = '❌:';
        } else {
            if ($checked)
                $box = '✔️:';
            else
                $box = '✖:';
        }
        ?>
        <div class='row'>
            <div class='col-sm-auto'>
                <?php echo $box; ?>
            </div>
            <div class='col-sm-auto'>
                <p class='text-body'>
                    <?php echo $description; ?>
                </p>
            </div>
<?php
        if ($checked && array_key_exists('notesPrompt', $conrole) && trim($conrole['notesPrompt']) != '') {
            $prompt = replaceVariables($conrole['notesPrompt']);
            $answer = '<i>(no answer entered)</i>';
            if (array_key_exists('notes', $personConRole) && trim($personConRole['notes']) != '') {
                $answer = trim($personConRole['notes']);
            }
            echo <<<EOS
        <div class='row'>
            <div class='col-sm-auto'>&emsp;&ensp;</div>
            <div class="col-sm-2">$prompt</div>
            <div class="col-sm-8">$answer</div>
        </div>
EOS;
        }
?>
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

// updateMemberConRoles - update/insert the conroles
function updateMemberConRoles($conid, $personId, $personType, $loginId, $loginType) {
    if ($personType != 'p')
        return 0; // roles only apply to perid's

    $conroles = getConRoles();
    if ($conroles == null) {
        return 0; // none updated because there are no conroles configured
    }

    $newConRoles = json_decode($_POST['newConRoles'], true);
    if (array_key_exists('existingConRoles', $_POST)) {
        $existingConRoles = json_decode($_POST['existingConRoles'], true);
        if ($existingConRoles == null)
            $existingConRoles = array ();
    }
    else
        $existingConRoles = array();

// find the differences in the conroles to update the record
    $updConRole = <<<EOS
UPDATE memberConRoles
SET assigned = ?, updateBy = ?, updateDate = NOW()
WHERE id = ?;
EOS;
    $insConRole = <<<EOS
INSERT INTO memberConRoles(perid, conid, conrole, assigned, updateBy)
VALUES (?, ?, ?, ?, ?);
EOS;

    $rows_upd = 0;
    foreach ($conroles as $conrole) {
        $conroleName = $conrole['conRole'];
        $newVal = array_key_exists($conroleName, $newConRoles) ? 'Y' : 'N';
        if (array_key_exists($conroleName, $existingConRoles)) {
            // this is an update, there is a record already in the memberConRoles table for this conrole.
            $existing = $existingConRoles[$conroleName];
            if (array_key_exists('conroleed', $existing)) {
                $oldVal = $existing['conRole'];
            }
            else {
                $oldVal = '';
            }
            // only update if changed
            if ($newVal != $oldVal) {
                $upd = 0;
                if ($existing['id'] != null) {
                    $upd = dbSafeCmd($updConRole, 'sii', array ($newVal, $loginId, $existing['id']));
                }
                if ($upd === false || $upd === 0) {
                    $newkey = dbSafeInsert($insConRole, 'iissi', array ($personId, $conid, $conroleName, $newVal, $loginId));
                    if ($newkey !== false && $newkey > 0)
                        $rows_upd++;
                }
                else {
                    $rows_upd++;
                }
            }
        }
        else {
            // row doesn't exist in existing conroles
            $newkey = dbSafeInsert($insConRole, 'iissi', array ($personId, $conid, $conroleName, $newVal, $loginId));
            if ($newkey !== false && $newkey > 0)
                $rows_upd++;
        }
    }
    return $rows_upd;
}

// merge conroles - merge conroles from a new person and an existing person or two existing people
// Algorithm:
//  If newperson ones exist:
//      if perid ones exist:
//          update perid from newperson if newperson newer, and delete newperson
//      else if add perid to newperson ones
//  else
//  	do nothing
//  If At end of merge/new person if there are no perid based ones, make defaults

    function mergeConRoles($conid, $remainingPerId, $sourceType, $sourceId, $loginId) {
        $conroles = getConRoles();
        if ($conroles == null || count($conroles) == 0) {
            return '';
        }

        // ok, there are conroles to merge
        $sourceField = $sourceType == 'n' ? 'newperid' : 'perid';
        $sQ = <<<EOS
SELECT *
FROM memberConRoles
WHERE $sourceField = ? AND conid = ?;
EOS;
        $rQ = <<<EOS
SELECT *
FROM memberConRoles
WHERE perid = ? AND conid = ?;
EOS;
        $chgU = <<<EOS
UPDATE memberConRoles
SET conroleed = ?, updateBy = ?, notifyDate = null, csvDate = null, updateDate = NOW()
WHERE id = ?;
EOS;
        $idU = <<<EOS
UPDATE memberConRoles
SET perid = ?, updateBy = ?
WHERE id = ?;
EOS;
        $iP = <<<EOS
INSERT INTO memberConRoles(perid, conid, conrole, conroleed, updateBy)
VALUES (?, ?, ?, ?, ?);
EOS;
        $sD = <<<EOS
DELETE FROM memberConRoles
WHERE id = ?;
EOS;

        $sourceConRoles = [];
        $remainConRoles = [];

        // source
        $sR = dbSafeQuery($sQ, 'ii', array($sourceId, $conid));
        if ($sR === false) {
            $message = "mergeConRoles: Error retrieving source conroles of $sourceType:$sourceId";
            error_log($message);
            return $message;
        }
        while ($sL = $sR->fetch_assoc()) {
            $sourceConRoles[$sL['conrole']] = $sL;
        }
        $sR->free();

        // remain
        $rR = dbSafeQuery($rQ, 'ii', array($remainingPerId, $conid));
        if ($rR === false) {
            $message = "mergeConRoles: Error retrieving remaining conroles of $remainingPerId";
            error_log($message);
            return $message;
        }
        while ($rL = $rR->fetch_assoc()) {
            $remainConRoles[$rL['conrole']] = $rL;
        }
        $rR->free();

        $numUpd = 0;
        $numDel = 0;
        $numIns = 0;

        foreach ($conroles as  $conrole) {
            $conroleName = $conrole['conrole'];
            if (array_key_exists($conroleName, $sourceConRoles)) {
                $newRow = $sourceConRoles[$conroleName];
                if (array_key_exists($conroleName, $remainConRoles)) {
                    // the conrole exists in both, check if it needs to be updated
                    $oldRow = $remainConRoles[$conroleName];
                    if ($oldRow['conroleed'] != $newRow['conroleed']) {
                        // they are not the same conroleed, update the remaining conroles
                        $numUpd += dbSafeCmd($chgU, 'sii', array($newRow['conroleed'], $loginId, $oldRow['id']));
                    }
                    // now delete the 'source' conrole
                    $numDel += dbSafeCmd($sD, 'i', array($newRow['id']));
                } else {
                    // the remaining place doesn't have the source conrole, update the perid field of this id to the remaining id
                    $numUpd += dbSafeCmd($idU, 'iii', array($remainingPerId, $loginId, $newRow['id']));
                }
            } else {
                // not in the source, if not in the remain, insert the default value
                if (!array_key_exists($conroleName, $remainConRoles)) {
                    $newId = dbSafeInsert($iP, 'iissi', array($remainingPerId, $conid, $conroleName, 'N', $loginId));
                    if ($newId !== false) {
                        $numIns++;
                    }
                }
            }
        }
        return '';
    }
