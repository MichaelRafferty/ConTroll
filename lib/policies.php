<?php
// policies - anything to do with the PHP side of policies so it can be used by multiple modules
// getPolicies - get the raw interest data (not for a member)
function getPolicies() {
    $policies = null;

    $policyQ = <<<EOS
SELECT *
FROM policies
WHERE active = 'Y'
ORDER BY sortOrder;
EOS;
    $policyR = dbQuery($policyQ);
    if ($policyR !== false) {
        $policies = array ();
        while ($policy = $policyR->fetch_assoc()) {
            $policies[] = $policy;
        }
        $policyR->free();
        if (count($policies) == 0) {
            $policies = null;
        }
    }
    return $policies;
}

//drawPoliciesBlock - draw the inner block for policy editing
function drawPoliciesBlock($policies, $tabIndexStart, $idPrefix = '') {
    if ($policies === null || count($policies) == 0) {
        return;
    }
    $tabindex = $tabIndexStart;
    foreach ($policies as $policy) {
        $name = $policy['policy'];
        $prompt = replaceVariables($policy['prompt']);
        $description = replaceVariables($policy['description']);
        /* fix prompt for an optional <a tag tab index */
        if (preg_match("/<a href/", $prompt)) {
            $prompt = preg_replace("/<a href=[^>]*/", '$0 tabindex="' . ($tabindex + 1) . '"', $prompt, 1);
        }
        if (preg_match('/<a href/', $description)) {
            $description = preg_replace('/<a href=[^>]*/', '$0 tabindex="' . ($tabindex + 3) . '"', $description, 1);
        }
        if ($policy['required'] == 'Y') {
            $prompt = "<span class='text-danger'>&bigstar;</span>" . $prompt;
        }
        if ($policy['defaultValue'] == 'Y') {
            $checked = 'checked';
        } else {
            $checked = '';
        }
?>
<div class='row'>
    <div class='col-sm-12'>
        <p class='text-body'>
            <label>
                <input type='checkbox' <?php echo $checked; ?> name='p_<?php echo $idPrefix . $name;?>' id='p_<?php echo $idPrefix . $name;?>' value='Y'
                       tabindex="<?php echo $tabindex; $tabindex += 2;?>"/>
                <span id="l_<?php echo $idPrefix . $name;?>" name="l_<?php echo $idPrefix . $name;?>"><?php echo $prompt; ?></span>
            </label>
            <?php if ($description != '') { ?>
            <span class="small"><a href='javascript:void(0)' onClick='$("#<?php echo $idPrefix . $name;?>Tip").toggle()'>
                    <img src="/lib/infoicon.png"  alt="click this info icon for more information" style="max-height: 25px;"
                         tabindex="<?php echo $tabindex; $tabindex += 1;?>"/>
                </a></span>
        <div id='<?php echo $idPrefix . $name;?>Tip' class='padded highlight' style='display:none'>
            <p class='text-body'><?php echo $description; ?>
                <span class='small'><a href='javascript:void(0)' onClick='$("#<?php echo $idPrefix . $name;?>Tip").toggle()'>
                      <img src='/lib/closeicon.png' alt='click this close icon to close the more information window' style='max-height: 25px;'
                           tabindex="<?php echo $tabindex; $tabindex += 2;?>"/>
                    </a></span>
            </p>
        </div>
        <?php } ?>
        </p>
    </div>
</div>
<?php
    }
}

//drawPoliciesCell - draw the simpler cell for comparing policies
function drawPoliciesCell($policies) {
    if ($policies == null) // if there are no policies, nothing to draw
        return;

    foreach ($policies as $policy) {
        $name = $policy['policy'];
        $prompt = replaceVariables($policy['prompt']);
        $description = replaceVariables($policy['description']);
        /* fix prompt for an optional <a tag tab index */
        if ($policy['required'] == 'Y') {
            $prompt = "<span class='text-danger'>&bigstar;</span>" . $prompt;
        }
        if ($policy['defaultValue'] == 'Y') {
            $checked = 'checked';
        }
        else {
            $checked = '';
        }
        ?>
                <label>
                    <input type='checkbox' <?php echo $checked; ?> name='p_<?php echo $name; ?>' id='p_<?php echo $name; ?>' value='Y'/>
                    <span id="l_<?php echo $name; ?>" name="l_<?php echo $name; ?>"><?php echo $prompt; ?></span>
                </label>
                <br/>
<?php
    }
}

// update policies in memberPolicies and return number updated
function updateMemberPolicies($conid, $personId, $personType, $loginId, $loginType) {
    // now update the policies
    $policies = getPolicies();
    if ($policies == null || count($policies) == 0) {
        return 0;
    }
    $iQ = <<<EOS
INSERT INTO memberPolicies(perid, conid, newperid, policy, response, updateBy)
VALUES (?,?,?,?,?,?);
EOS;
    $uQ = <<<EOS
UPDATE memberPolicies
SET response = ?, updateBy = ?
WHERE id = ?;
EOS;

    if (array_key_exists('oldPolicies', $_POST))
        $oldPoliciesArr = json_decode($_POST['oldPolicies'], true);
    else
        $oldPoliciesArr = array();
    if (array_key_exists('newPolicies', $_POST))
        $newPolicies = json_decode($_POST['newPolicies'], true);
    else
        $newPolicies = array();
// convert oldPolicies to an associative array with the p_ in the front of the indicies
    $oldPolicies = array();
    foreach ($oldPoliciesArr as $oldPolicy) {
        $oldPolicies['p_' . $oldPolicy['policy']] = $oldPolicy;
    }

    if ($policies != null) {
        $policy_upd = 0;
        foreach ($policies as $policy) {
            $oldResponse = '';
            $oldId = null;
            $new = '';
            $defaultValue = $policy['defaultValue'];
            if (array_key_exists('p_' . $policy['policy'], $oldPolicies)) {
                $old = $oldPolicies['p_' . $policy['policy']];
                if (array_key_exists('response', $old)) {
                    $oldResponse = $old['response'];
                    if ($oldResponse == null)
                        $oldResponse = '';
                }
                if (array_key_exists('id', $old)) {
                    $oldId = $old['id'];
                }
            }
            if (array_key_exists('p_' . $policy['policy'], $newPolicies))
                $new = $newPolicies['p_' . $policy['policy']];
            else
                $new = 'N'; // unchecked are 'N', and the array only returns checked ones.

            // ok the options if old is blank, there likely isn't an entry in the database, New if missing is a 'N';
            if ($oldResponse == '') {
                $valueArray = array (
                    $personType == 'p' ? $personId : null,
                    $conid,
                    $personType == 'n' ? $personId : null,
                    $policy['policy'],
                    $new == '' ? $defaultValue : $new,
                    $loginType == 'p' ? $loginId : null
                );
                $ins_key = dbSafeInsert($iQ, 'iiissi', $valueArray);
                if ($ins_key !== false) {
                    $policy_upd++;
                }
            } else if ($oldResponse != $new) {
                $policy_upd += dbSafeCmd($uQ, 'sii', array($new, $loginType == 'p' ? $loginId : null, $oldId));
            }
        }
    }
    return  $policy_upd;
}

// update policies in memberPolicies using the direct array and return number updated
function updateExisingMemberPolicies($policies, $conid, $perid, $loginId) {
    // now update the policies
    if ($policies == null || count($policies) == 0)
        return 0;

    $iQ = <<<EOS
INSERT INTO memberPolicies(perid, conid, policy, response, updateBy)
VALUES (?,?,?,?,?);
EOS;

    $uQ = <<<EOS
UPDATE memberPolicies
SET response = ?, updateBy = ?
WHERE id = ?;
EOS;

    $policy_upd = 0;
    foreach ($policies as $policy) {
        if (array_key_exists('policyId', $policy) && $policy['policyId'] > 0) {
            $policy_upd += dbSafeCmd($uQ, 'sii', array($policy['response'], $loginId, $policy['policyId']));
        } else {
            $newId = dbSafeInsert($iQ, 'iissi', array($perid, $conid, $policy['policy'], $policy['response'], $loginId));
            if ($newId !== false) {
                $policy_upd++;
            }
        }
    }
    return  $policy_upd;
}

// merge policies - merge polcies from a new person and an existing person or two existing people
// Algorithm:
//  If newperson ones exist:
//      if perid ones exist:
//          update perid from newperson if newperson newer, and delete newperson
//      else if add perid to newperson ones
//  else
//  	do nothing
//  If At end of merge/new person if there are no perid based ones, make defaults

function mergePolicies($conid, $remainingPerId, $sourceType, $sourceId, $loginId, $sourceValues = null) {
    $policies = getPolicies();
    if ($policies == null || count($policies) == 0) {
        return '';
    }

    // ok, there are policies to merge
    $sourceField = $sourceType == 'n' ? 'newperid' : 'perid';
    $sQ = <<<EOS
SELECT *
FROM memberPolicies
WHERE $sourceField = ? AND conid = ?;
EOS;
    $rQ = <<<EOS
SELECT *
FROM memberPolicies
WHERE perid = ? AND conid = ?;
EOS;
    $chgU = <<<EOS
UPDATE memberPolicies
SET response = ?, updateBy = ?
WHERE id = ?;
EOS;
    $idU = <<<EOS
UPDATE memberPolicies
SET perid = ?, updateBy = ?
WHERE id = ?;
EOS;
    $iP = <<<EOS
INSERT INTO memberPolicies(perid, conid, policy, response, updateBy)
VALUES (?, ?, ?, ?, ?);
EOS;
    $sD = <<<EOS
DELETE FROM memberPolicies
WHERE id = ?;
EOS;

    $sourcePolicies = [];
    $remainPolicies = [];

    // source
    $sR = dbSafeQuery($sQ, 'ii', array($sourceId, $conid));
    if ($sR === false) {
        $message = "mergePolicies: Error retrieving source policies of $sourceType:$sourceId";
        error_log($message);
        return $message;
    }
    while ($sL = $sR->fetch_assoc()) {
        $sourcePolicies[$sL['policy']] = $sL;
        if (array_key_exists($sL['policy'], $sourceValues)) {
            $sourcePolicies[$sL['policy']]['response'] = $sourceValues[$sL['policy']];
        }
    }
    $sR->free();

    // remain
    $rR = dbSafeQuery($rQ, 'ii', array($remainingPerId, $conid));
    if ($rR === false) {
        $message = "mergePolicies: Error retrieving remaining policies of $remainingPerId";
        error_log($message);
        return $message;
    }
    while ($rL = $rR->fetch_assoc()) {
        $remainPolicies[$rL['policy']] = $rL;
    }
    $rR->free();

    $numUpd = 0;
    $numDel = 0;
    $numIns = 0;

    foreach ($policies as $policy) {
        $policyName = $policy['policy'];
        if (array_key_exists($policyName, $sourcePolicies)) {
            $newRow = $sourcePolicies[$policyName];
            if (array_key_exists($policyName, $remainPolicies)) {
                // the policy exists in both, check if it needs to be updated
                $oldRow = $remainPolicies[$policyName];
                if ($oldRow['response' != $newRow['response']]) {
                    // they are not the same response, update the remaining policies
                    $numUpd += dbSafeCmd($chgU, 'sii', array($newRow['response'], $loginId, $oldRow['id']));
                }
                // now delete the 'source' policy
                $numDel += dbSafeCmd($sD, 'i', array($newRow['id']));
            } else {
                // the remaining place doesn't have the source policy, update the perid field of this id to the remaining id
                $numUpd += dbSafeCmd($idU, 'iii', array($remainingPerId, $newRow['id'], $loginId));
            }
        } else {
            // not in the source, if not in the remain, insert the default value
            if (!array_key_exists($policyName, $remainPolicies)) {
                $response = $policy['defaultValue'];
                if (array_key_exists($policyName,  $sourceValues))
                    $response = $sourceValues[$policyName];
                if ($sourceValues[$policyName] != $policy['policy']) {}
                $newId = dbSafeInsert($iP, 'iissi', array($remainingPerId, $conid, $policyName, $response, $loginId));
                if ($newId !== false) {
                    $numIns++;
                }
            }
        }
    }
    return '';
}