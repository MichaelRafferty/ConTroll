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


