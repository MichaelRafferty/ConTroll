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
function drawPoliciesBlock($policies, $tabIndexStart) {
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
            $description = preg_replace('/<a href=[^>]*/', '$0 tabindex="' . ($tabindex + 11) . '"', $description, 1);
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
                <input type='checkbox' <?php echo $checked; ?> name='p_<?php echo $name;?>' id='p_<?php echo $name;?>' value='Y'
                       tabindex="<?php echo $tabindex; $tabindex += 10;?>"/>
                <span id="l_<?php echo $name;?>" name="l_<?php echo $name;?>"><?php echo $prompt; ?></span>
            </label>
            <?php if ($description != '') { ?>
            <span class="small"><a href='javascript:void(0)' onClick='$("#<?php echo $name;?>Tip").toggle()'>
                    <img src="/lib/infoicon.png"  alt="click this info icon for more information" style="max-height: 25px;"
                         tabindex="<?php echo $tabindex; $tabindex += 10;?>"/>
                </a></span>
        <div id='<?php echo $name;?>Tip' class='padded highlight' style='display:none'>
            <p class='text-body'><?php echo $description; ?>
                <span class='small'><a href='javascript:void(0)' onClick='$("#<?php echo $name;?>Tip").toggle()'>
                      <img src='/lib/closeicon.png' alt='click this close icon to close the more information window' style='max-height: 25px;'
                           tabindex="<?php echo $tabindex; $tabindex += 10;?>"/>
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
            $new = 'N';
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

            // ok the options if old is blank, there likely isn't an entry in the database, New if missing is a 'N';
            if ($oldResponse == '') {
                $valueArray = array (
                    $personType == 'p' ? $personId : null,
                    $conid,
                    $personType == 'n' ? $personId : null,
                    $policy['policy'],
                    $new,
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