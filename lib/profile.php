<?php
// profile - anything to do with the PHP side of editing your name/address/... profile

// drawEditPersonBlock - just output the block to edit the person
function drawEditPersonBlock($con, $useUSPS, $policies, $class, $modal=false, $editEmail=false, $ageByDate = '',
                             $membershipTypes = [], $tabIndexStart = 100, $admin = false, $idPrefix = '') {
    $reg = get_conf('reg');
    if ($editEmail)
        $polConf = $reg;
    else
        $polConf = get_conf('portal');
    if (array_key_exists('required', $reg)) {
        $required = $reg['required'];
    } else {
        $required = 'addr';
    }
    $firstStar = '';
    $addrStar = '';
    $allStar = '';
    switch ($required) {
        // cascading list of required fields, each case adds more so the breaks fall into the next section

        case 'all':
            $allStar = '<span class="text-danger">&bigstar;</span>';
        case 'addr':
            $addrStar = '<span class="text-danger">&bigstar;</span>';
        case 'first':
            $firstStar = '<span class="text-danger">&bigstar;</span>';
    }
    $tabindex = $tabIndexStart;
    if ($editEmail == false) {
    ?>
    <h<?php echo $modal ? '1 class="size-h3"' : '3 class="text-primary"'?> id='epHeader'>
        Personal Information for this new person
    </h<?php echo $modal ? '1' : '3'?>>
<?php
    }

    if ($admin == false) {
?>
    <div class='row' style='width:100%;'>
        <div class='col-sm-12'>
            <p class='text-body'>Note: Please provide your legal name that will match a valid form of ID. Your legal name will not
                be publicly visible. If you don't provide one, it will default to your First, Middle, Last Names and Suffix.</p>
            <p class="text-body">Items marked with <span class="text-danger">&bigstar;</span> are required fields.</p>
        </div>
    </div>
<?php
    }

    if ($useUSPS) echo '<div class="row"><div class="col-sm-8 p-0 m-0"><div class="container-fluid">' . PHP_EOL;
?>
    <div class="row">
        <div class="col-sm-auto">
            <label for="<?php echo $idPrefix . "fname"; ?>" class="form-label-sm">
                <span class="text-dark" style="font-size: 10pt;"><?php echo $firstStar; ?>First Name</span>
            </label><br/>
            <input class="form-control-sm" type="text" name="fname" id='<?php echo $idPrefix . 'fname';?>' size="22" maxlength="32"
                   tabindex="<?php echo $tabindex; $tabindex += 10;?>"/>
        </div>
        <div class="col-sm-auto">
            <label for="<?php echo $idPrefix . 'mname'; ?>" class="form-label-sm">
                <span class="text-dark" style="font-size: 10pt;">Middle Name</span>
            </label><br/>
            <input class="form-control-sm" type="text" name="mname" id='<?php echo $idPrefix . 'mname'; ?>' size="8" maxlength="32"
                   tabindex="<?php echo $tabindex; $tabindex += 10;?>"/>
        </div>
        <div class="col-sm-auto">
            <label for="<?php echo $idPrefix . 'lname'; ?>" class="form-label-sm">
                <span class="text-dark" style="font-size: 10pt;"><?php echo $allStar; ?>Last Name</span>
            </label><br/>
            <input class="form-control-sm" type="text" name="lname" id='<?php echo $idPrefix . 'lname'; ?>' size="22" maxlength="32"
                   tabindex="<?php echo $tabindex; $tabindex += 10;?>"/>
        </div>
        <div class="col-sm-auto">
            <label for="<?php echo $idPrefix . 'suffix'; ?>" class="form-label-sm">
                <span class="text-dark" style="font-size: 10pt;">Suffix</span>
            </label><br/>
            <input class="form-control-sm" type="text" name="suffix" id='<?php echo $idPrefix . 'suffix'; ?>' size="4" maxlength="4"
                   tabindex="<?php echo $tabindex; $tabindex += 10;?>"/>
        </div>
    </div>
    <div class='row'>
        <div class='col-sm-12'>
            <label for='<?php echo $idPrefix . 'legalname'; ?>' class='form-label-sm'>
                <span class='text-dark' style='font-size: 10pt;'>Legal Name: for checking against your ID. It will only be visible to registration staff.
            </label><br/>
            <input class='form-control-sm' type='text' name='legalname' id='<?php echo $idPrefix . 'legalname'; ?>' size=64 maxlength='64'
                   placeholder='Defaults to First Name Middle Name Last Name, Suffix'
                   tabindex="<?php echo $tabindex; $tabindex += 10;?>"/>
        </div>
    </div>
    <div class='row'>
        <div class='col-sm-auto me-2'>
            <label for='<?php echo $idPrefix . 'badgename'; ?>' class='form-label-sm'><span class='text-dark' style='font-size: 10pt;'>Badge Name (optional)
                        </span></label><br/>
            <input class='form-control-sm' type='text' name='badgename' id='<?php echo $idPrefix . 'badgename'; ?>' size='35' maxlength='32'
                   placeholder='defaults to first and last name' tabindex="<?php echo $tabindex;
            $tabindex += 10; ?>"/>
        </div>
    </div>
    <div class='row'>
        <div class='col-sm-12'>
            <label for='<?php echo $idPrefix . 'pronouns'; ?>' class='form-label-sm'>
                    <span class='text-dark' style='font-size: 10pt;'>Pronouns
            </label><br/>
            <input class='form-control-sm' type='text' name='pronouns' id='<?php echo $idPrefix . 'pronouns'; ?>' size=64 maxlength='64'
                   placeholder='Optional pronouns' tabindex="<?php echo $tabindex; $tabindex += 10;?>"/>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <label for="<?php echo $idPrefix . 'addr'; ?>" class="form-label-sm">
                <span class="text-dark" style="font-size: 10pt;"><?php echo $addrStar; ?>Address</span>
            </label><br/>
            <input class="form-control-sm" type="text" name='addr' id='<?php echo $idPrefix . 'addr'; ?>' size=64 maxlength="64"
                   tabindex="<?php echo $tabindex; $tabindex += 10;?>"/>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <label for="<?php echo $idPrefix . 'addr2'; ?>" class="form-label-sm">
                <span class="text-dark" style="font-size: 10pt;">Company/2nd Address line</span>
            </label><br/>
            <input class="form-control-sm" type="text" name='addr2' id='<?php echo $idPrefix . 'addr2'; ?>' size=64 maxlength="64"
                   tabindex="<?php echo $tabindex; $tabindex += 10;?>"/>
        </div>
    </div>
    <div class='row'>
        <div class='col-sm-auto'>
            <label for='<?php echo $idPrefix . 'country'; ?>' class='form-label-sm'>
                <span class='text-dark' style='font-size: 10pt;'>Country</span>
            </label><br/>
            <select name='country' id='<?php echo $idPrefix . 'country'; ?>' onchange="<?php echo $class; ?>.countryChange();"
                    tabindex="<?php echo $tabindex; $tabindex += 10;?>">
                <?php
                    $fh = fopen(__DIR__ . '/../lib/countryCodes.csv', 'r');
                    while (($data = fgetcsv($fh, 1000, ',', '"')) != false) {
                        echo '<option value="' . escape_quotes($data[1]) . '">' . $data[0] . '</option>';
                    }
                    fclose($fh);
                ?>
            </select>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-auto">
            <label for="<?php echo $idPrefix . 'city'; ?>" class="form-label-sm">
                <span class="text-dark" style="font-size: 10pt;"><?php echo $addrStar; ?>City</span>
            </label><br/>
            <input class="form-control-sm" type="text" name="city" id='<?php echo $idPrefix . 'city'; ?>' size="22" maxlength="32"
                   tabindex="<?php echo $tabindex; $tabindex += 10;?>"/>
        </div>
        <div class="col-sm-auto">
            <label for="<?php echo $idPrefix . 'state'; ?>" class="form-label-sm">
                <span class="text-dark" style="font-size: 10pt;"><?php echo $addrStar; ?>State: U.S./CAN 2-letter abv.</span>
            </label><br/>
            <input class="form-control-sm" type="text" name="state" id='<?php echo $idPrefix . 'state'; ?>' size="16" maxlength="16"
                   tabindex="<?php echo $tabindex; $tabindex += 10;?>"/>
        </div>
        <div class="col-sm-auto">
            <label for="<?php echo $idPrefix . 'zip'; ?>" class="form-label-sm">
                <span class="text-dark" style="font-size: 10pt;"><?php echo $addrStar; ?>Zip/Postal Code</span>
            </label><br/>
            <input class="form-control-sm" type="text" name="zip" id='<?php echo $idPrefix . 'zip'; ?>' size="10" maxlength="10"
                   tabindex="<?php echo $tabindex; $tabindex += 10;?>"/>
        </div>
    </div>
    <div class='row'>
        <div class='col-sm-auto'>
            <label for="<?php echo $idPrefix . 'phone'; ?>" class='form-label-sm'><span class='text-dark' style='font-size: 10pt;'>Phone</span></label><br/>
            <input class='form-control-sm' type='text' name='phone' id='<?php echo $idPrefix . 'phone'; ?>' size='20' maxlength='15'
                   tabindex="<?php echo $tabindex;
                       $tabindex += 10; ?>"/>
        </div>
    </div>
    <?php if ($useUSPS) echo '</div></div><div class="col-sm-4" id="uspsblock"></div></div>' . PHP_EOL; ?>
<?php
    if ($admin == false) {
?>
    <div class='row'>
        <div class='col-sm-12'>
            <hr/>
        </div>
    </div>
    <div class='row'>
        <div class='col-sm-12'>
            <p class='text-body'>Contact Information
                (<a href="<?php echo escape_quotes($con['privacypolicy']); ?>" target='_blank'
                    tabindex="<?php echo $tabindex; $tabindex += 10;?>"><?php echo $con['privacytext']; ?></a>).
            </p>
        </div>
    </div>
<?php
    }
    if ($editEmail) {
?>
    <div class='row'>
        <div class='col-sm-auto me-2'>
            <label for='<?php echo $idPrefix . 'email1'; ?>' class='form-label-sm'><span class='text-dark' style='font-size: 10pt;'><span
                            class='text-danger'>&bigstar;</span>Email</span></label><br/>
            <input class='form-control-sm' type='email' name='email1' id='<?php echo $idPrefix . 'email1'; ?>' size='35' maxlength='254'
                   tabindex="<?php echo $tabindex; $tabindex += 10;?>"/>
        </div>
        <div class='col-sm-auto'>
            <label for='<?php echo $idPrefix . 'email2'; ?>' class='form-label-sm'><span class='text-dark' style='font-size: 10pt;'><span
                            class='text-danger'>&bigstar;</span>Confirm Email</span></label><br/>
            <input class='form-control-sm' type='email' name='email2' id='<?php echo $idPrefix . 'email2'; ?>' size='35' maxlength='254'
                   tabindex="<?php echo $tabindex; $tabindex += 10;?>"/>
        </div>
    </div>
<?php
        if ($membershipTypes != null) {
            $yearahead = false;
            foreach ($membershipTypes as $type) {
                if $type['memCategory'] == 'yearahead') {
                    $yearahead = true;
                    $nyConData = get_con($condata['id']  + 1);
                    $startdateYA = new DateTime($nyConData['startdate']);
                    $agebydateYA = $startdateYA->format('F j, Y');
                    }
                    break;
                }
            }

?>
    <div class='row'>
        <div class='col-sm-12'>
            <hr/>
        </div>
    </div>
    <div class='row'>
        <div class='col-sm-12'>
            <p class='text-body'>
                Select membership type from the drop-down menu below.<br/>
                Eligibility for Child and Young Adult rates are based on age on <?php echo $ageByDate; ?>
                (the first day of the convention).<br/>
        <?PHP if ($yearahead) { ?>
                For Year Ahead membrerships, the rates are based on age or <?php echo $agebydateYA; ?>
               (the first day of the convention for next year).<br/>
        <?PHP } ?>
            </p>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <label for="<?php echo $idPrefix . 'memId'; ?>" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;"><span
                            class='text-danger'>&bigstar;</span>Membership Type</span></label><br/>
            <select id='<?php echo $idPrefix . 'memId'; ?>' name='memId' style="max-width:90%;" tabindex="<?php echo $tabindex; $tabindex += 10;?>"/>
                <?php foreach ($membershipTypes as $memType) { ?>
                    <option value='<?php echo $memType['id']; ?>'><?php echo $memType['label']; ?> ($<?php echo $memType['price']; ?>)</option>
                <?php } ?>
            </select>
        </div>
    </div>
        <?php
        }
    } else {
?>
    <div class="row">
        <div class="col-sm-auto">
            Email Address: <span id='email1'></span>
        </div>
        <div class="col-sm-auto">
            <p><strong>Note:</strong> Email Address is entered at the start of creating the account or edited using the Change Email Address button on the home
                page
                .</p>
        </div>
    </div>
<?php
    }
?>
    <div class='row'>
        <div class='col-sm-12'>
            <hr/>
        </div>
    </div>
    <?php
    if ($admin == false && ((!array_key_exists('showConPolicy',$polConf)) || $polConf['showConPolicy'] == 1)) {
        ?>
        <div class='row'>
            <div class='col-sm-12'>
                <p class='text-body'>
                    <a href="<?php echo escape_quotes($con['policy']); ?>" target='_blank'
                       tabindex="<?php echo $tabindex; $tabindex += 10;?>">Click here for
                        the <?php echo $con['policytext']; ?></a>.
                </p>
            </div>
        </div>
        <?php
    }
    if ($admin == false && ((!array_key_exists('showVolunteerPolicy',$polConf)) || $polConf['showVolunteerPolicy'] == 1)) {
        ?>
        <div class="row">
            <div class="col-sm-12">
                <p class="text-body"><?php echo $con['conname']; ?> is entirely run by volunteers.
                    If you're interested in helping us run the convention please email
                    <a href="mailto:<?php echo escape_quotes($con['volunteers']); ?>"
                       tabindex="<?php echo $tabindex; $tabindex += 10;?>"><?php echo $con['volunteers']; ?></a>.
                </p>
            </div>
        </div>
        <?php
    }
    if ($policies != null && count($policies) > 0) {
    ?>
    </form>
    <form id='<?php echo $idPrefix;?>editPolicies' class='form-floating' action='javascript:void(0);'>
    <?php
        drawPoliciesBlock($policies, $tabIndexStart + 500, $idPrefix);
    }
}