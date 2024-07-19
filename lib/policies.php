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

//drawInterestList - draw the inner block for interest editing
function drawPoliciesBlock($policies) {
    foreach ($policies as $policy) {
        $name = $policy['policy'];
        $prompt = replaceVariables($policy['prompt']);
        $description = replaceVariables($policy['description']);
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
                <input type='checkbox' <?php echo $checked; ?> name='p_<?php echo $name;?>' id='p_<?php echo $name;?>' value='Y'/>
                <span id="l_<?php echo $name;?>"><?php echo $prompt; ?></span>
            </label>
            <?php if ($description != '') { ?>
            <span class="small"><a href='javascript:void(0)' onClick='$("#<?php echo $name;?>Tip").toggle()'>(more info)</a></span>
        <div id='<?php echo $name;?>Tip' class='padded highlight' style='display:none'>
            <p class='text-body'><?php echo $description; ?>
                <span class='small'><a href='javascript:void(0)' onClick='$("#contactTip").toggle()'>(close)</a></span>
            </p>
        </div>
        <?php } ?>
        </p>
    </div>
</div>
<?php
    }
}