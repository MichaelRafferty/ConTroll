<?php
// portalForms:  Forms used by the portal for person and membership work

//// Step 1 - Age Bracket
// drawGetAgeBracket - age bracket selection for filtering memberships
function drawGetAgeBracket($updateName, $condata) {
    $readableStartDate = date_format(date_create($condata['startdate']), 'l M j, Y');
    ?>
    <div class="row mt-2">
        <div class="col-sm-12">
            <h3 class='text-primary'>Please verify the age of <?php echo $updateName; ?> as of <?php echo $readableStartDate; ?></h3>
        </div>
        <div class="row mt-1" id="ageButtons"></div>
        <div class="row mt-2">
            <div class="col-sm-12">Please click on the proper age bracket above to continue to the next step.</div>
        </div>
    </div>
    <?php
}

//// Step 2 - Person
// drawVerifyPersonInfo - non modal version of validate person information
function drawVerifyPersonInfo($policies) {
    $usps = get_conf('usps');
    $useUSPS = false;
    if (($usps != null) && array_key_exists('secret', $usps) && ($usps['secret'] != ''))
        $useUSPS = true;
    $con = get_conf('con');
?>
<?php
    drawEditPersonBlock($con, $useUSPS, $policies);
?>
    <div class="row mt-3">
        <div class='col-sm-auto'>
            <button class='btn btn-sm btn-secondary' onclick='membership.gotoStep(1, true);'>Return to step 1: Age Verification</button>
        </div>
        <div class="col-sm-auto">
            <button class="btn btn-sm btn-primary" onclick="membership.verifyAddress();">Verify Address and move to next step</button>
        </div>
    </div>
<?php
}

// draw_editPerson - draw the verify/update form for the Person
function draw_editPersonModal($source, $policies) {
    $usps = get_conf('usps');
    $useUSPS = false;
    if (($usps != null) && array_key_exists('secret', $usps) && ($usps['secret'] != ''))
        $useUSPS = true;
    $con = get_conf('con');
    switch ($source) {
        case 'login':
            $closeClick = 'login.newpersonClose()';
            break;
        case 'portal':
            $closeClick = 'portal.checkEditPersonClose()';
            break;
        default:
            $closeClick = 'badClose()';
    }
?>
    <div id='editPersonModal' class='modal modal-x1 fade' tabindex='-1' aria-labelledby='Edit Person' aria-hidden='true' style='--bs-modal-width: 96%;'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header bg-primary text-bg-primary'>
                    <div class='modal-title' id='editPersonTitle'>
                        <strong>Edit Person</strong>
                    </div>
                    <button type='button' class='btn-close' onclick="<?php echo $closeClick; ?>;" aria-label='Close'></button>
                </div>
                <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                    <div class='container-fluid'>
                        <form id='editPerson' class='form-floating' action='javascript:void(0);'>
                            <input type='hidden' name='id' id='epPersonId'/>
                            <input type='hidden' name='type' id='epPersonType'/>
<?php
    drawEditPersonBlock($con, $useUSPS, $policies, true);
?>
                        </form>
                        <div class='row'>
                            <div class="col-sm-12" id='epMessageDiv'></div>
                        </div>
                    </div>
                </div>
                <div class='modal-footer'>
                    <button class='btn btn-sm btn-secondary' onclick='<?php echo $closeClick; ?>;' tabindex='10001'>Cancel</button>
                    <button class='btn btn-sm btn-primary' id='editPersonSubmitBtn' onClick="portal.editPersonSubmit()" tabindex='10002'>Update Person</button>
                </div>
            </div>
        </div>
    </div>
<?php
}

// drawEditPersonBlock - just output the block to edit the person
function drawEditPersonBlock($con, $useUSPS, $policies, $modal=false) {
    $reg = get_conf('reg');
    $portal_conf = get_conf('portal');
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
?>
        <h<?php echo $modal ? '1 class="size-h3"' : '3 class="text-primary"'?> id='epHeader'>
            Personal Information for this new person
        </h<?php echo $modal ? '1' : '3'?>>
        <div class='row' style='width:100%;'>
            <div class='col-sm-12'>
                <p class='text-body'>Note: Please provide your legal name that will match a valid form of ID. Your legal name will not
                    be publicly visible. If you don't provide one, it will default to your First, Middle, Last Names and Suffix.</p>
                <p class="text-body">Items marked with <span class="text-danger">&bigstar;</span> are required fields.</p>
            </div>
        </div>
        <?php if ($useUSPS) echo '<div class="row"><div class="col-sm-8 p-0 m-0"><div class="container-fluid">' . PHP_EOL; ?>
        <div class="row">
            <div class="col-sm-auto">
                <label for="fname" class="form-label-sm">
                    <span class="text-dark" style="font-size: 10pt;"><?php echo $firstStar; ?>First Name</span>
                </label><br/>
                <input class="form-control-sm" type="text" name="fname" id='fname' size="22" maxlength="32" tabindex="100"/>
            </div>
            <div class="col-sm-auto">
                <label for="mname" class="form-label-sm">
                    <span class="text-dark" style="font-size: 10pt;">Middle Name</span>
                </label><br/>
                <input class="form-control-sm" type="text" name="mname" id='mname' size="8" maxlength="32" tabindex="110"/>
            </div>
            <div class="col-sm-auto">
                <label for="lname" class="form-label-sm">
                    <span class="text-dark" style="font-size: 10pt;"><?php echo $allStar; ?>Last Name</span>
                </label><br/>
                <input class="form-control-sm" type="text" name="lname" id='lname' size="22" maxlength="32" tabindex="120"/>
            </div>
            <div class="col-sm-auto">
                <label for="suffix" class="form-label-sm">
                    <span class="text-dark" style="font-size: 10pt;">Suffix</span>
                </label><br/>
                <input class="form-control-sm" type="text" name="suffix" id='suffix' size="4" maxlength="4" tabindex="130"/>
            </div>
        </div>
        <div class='row'>
            <div class='col-sm-12'>
                <label for='legalname' class='form-label-sm'>
                    <span class='text-dark' style='font-size: 10pt;'>Legal Name: for checking against your ID. It will only be visible to Registration Staff.
                </label><br/>
                <input class='form-control-sm' type='text' name='legalname' id='legalname' size=64 maxlength='64'
                       placeholder='Defaults to First Name Middle Name Last Name, Suffix' tabindex='140'/>
            </div>
        </div>
        <div class='row'>
            <div class='col-sm-12'>
                <label for='pronouns' class='form-label-sm'>
                        <span class='text-dark' style='font-size: 10pt;'>Pronouns
                </label><br/>
                <input class='form-control-sm' type='text' name='pronouns' id='pronouns' size=64 maxlength='64'
                       placeholder='Optional pronouns' tabindex='140'/>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <label for="addr" class="form-label-sm">
                    <span class="text-dark" style="font-size: 10pt;"><?php echo $addrStar; ?>Address</span>
                </label><br/>
                <input class="form-control-sm" type="text" name='addr' id='addr' size=64 maxlength="64" tabindex='150'/>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <label for="addr2" class="form-label-sm">
                    <span class="text-dark" style="font-size: 10pt;">Company/2nd Address line</span>
                </label><br/>
                <input class="form-control-sm" type="text" name='addr2' id='addr2' size=64 maxlength="64" tabindex='160'/>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-auto">
                <label for="city" class="form-label-sm">
                    <span class="text-dark" style="font-size: 10pt;"><?php echo $addrStar; ?>City</span>
                </label><br/>
                <input class="form-control-sm" type="text" name="city" id='city' size="22" maxlength="32" tabindex="170"/>
            </div>
            <div class="col-sm-auto">
                <label for="state" class="form-label-sm">
                    <span class="text-dark" style="font-size: 10pt;"><?php echo $addrStar; ?>State: US/CAN 2 letter abv.</span>
                </label><br/>
                <input class="form-control-sm" type="text" name="state" id='state' size="16" maxlength="16" tabindex="180"/>
            </div>
            <div class="col-sm-auto">
                <label for="zip" class="form-label-sm">
                    <span class="text-dark" style="font-size: 10pt;"><?php echo $addrStar; ?>Zip</span>
                </label><br/>
                <input class="form-control-sm" type="text" name="zip" id='zip' size="10" maxlength="10" tabindex="190"/>
            </div>
            <div class="col-sm-auto">
                <label for="country" class="form-label-sm">
                    <span class="text-dark" style="font-size: 10pt;">Country</span>
                </label><br/>
                <select name='country' tabindex='200' id='country' onchange="portal.countryChange();">
                    <?php
                    $fh = fopen(__DIR__ . '/../../lib/countryCodes.csv', 'r');
                    while (($data = fgetcsv($fh, 1000, ',', '"')) != false) {
                        echo '<option value="' . escape_quotes($data[1]) . '">' . $data[0] . '</option>';
                    }
                    fclose($fh);
                    ?>
                </select>
            </div>
        </div>
        <?php if ($useUSPS) echo '</div></div><div class="col-sm-4" id="uspsblock"></div></div>' . PHP_EOL; ?>
        <div class='row'>
            <div class='col-sm-12'>
                <hr/>
            </div>
        </div>
        <div class='row'>
            <div class='col-sm-12'>
                <p class='text-body'>Contact Information
                    (<a href="<?php echo escape_quotes($con['privacypolicy']); ?>" target='_blank'><?php echo $con['privacytext']; ?></a>).
                </p>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-auto">
                <label for="email1" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;"><span
                                class='text-danger'>&bigstar;</span>Email</span></label><br/>
                <input class="form-control-sm" type="email" name="email1" id='email1' size="35" maxlength="254" tabindex="210"/>
            </div>
            <div class="col-sm-auto">
                <label for="email2" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;"><span
                                class='text-danger'>&bigstar;</span>Confirm Email</span></label><br/>
                <input class="form-control-sm" type="email" name="email2" id='email2' size="35" maxlength="254" tabindex="220"/>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-auto">
                <label for="phone" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">Phone</span></label><br/>
                <input class="form-control-sm" type="text" name="phone" id='phone' size="20" maxlength="15" tabindex="230"/>
            </div>
            <div class="col-sm-auto">
                <label for="badgename" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">Badge Name (optional)</span></label><br/>
                <input class="form-control-sm" type="text" name="badgename" id='badgename' size="35" maxlength="32" placeholder='defaults to first and last name'
                       tabindex="240"/>
            </div>
        </div>
        <div class='row'>
            <div class='col-sm-12'>
                <hr/>
            </div>
        </div>
        <div class='row'>
            <div class='col-sm-12'>
                <p class='text-body'>
                    <a href="<?php echo escape_quotes($con['policy']); ?>" target='_blank'>Click here for
                        the <?php echo $con['policytext']; ?></a>.
                </p>
            </div>
        </div>
<?php       if (!(array_key_exists('showVolunteerPolicy',$portal_conf) && $portal_conf['showVolunteerPolicy'] == 0)) { ?>
        <div class="row">
            <div class="col-sm-12">
                <p class="text-body"><?php echo $con['conname']; ?> is entirely run by volunteers.
                    If you're interested in helping us run the convention please email
                    <a href="mailto:<?php echo escape_quotes($con['volunteers']); ?>"><?php echo $con['volunteers']; ?></a>.
                </p>
            </div>
        </div>
<?php       } ?>
    </form>
    <form id='editPolicies' class='form-floating' action='javascript:void(0);'>
<?php
    drawPoliciesBlock($policies);
}

//// step 3 - Interests
// drawVerifyInterestsBLock - non modal version of validate interests
function drawVerifyInterestsBlock($interests) {
    ?>
    <form id='editInterests' class='form-floating' action='javascript:void(0);'>
        <?php
        drawInterestList($interests);
        ?>
    </form>
    <div class="row mt-3">
        <div class='col-sm-auto'>
            <button class='btn btn-sm btn-secondary' onclick='membership.gotoStep(2, true);'>Return to step 2: Personal Information Verification</button>
        </div>
        <div class="col-sm-auto">
            <button class="btn btn-sm btn-primary" onclick="membership.saveInterests();">Save Interests and move to next step</button>
        </div>
    </div>
    <?php
}

//// step 4 memberships
// drawGetNewMemberships - membership selection
function drawGetNewMemberships() {
    ?>
    <div class='row mt-1' id='membershipButtons'></div>
    <div class="row mt-2">
        <div class="col-sm-12">
            Select from the buttons above to add memberships.
        </div>
    </div>
    <?php
}

// draw variable price membership set modal
function drawVariablePriceModal() {
?>
    <div id='variablePriceModal' class='modal modal-lg fade' tabindex='-1' aria-labelledby='Variable Price' aria-hidden='true'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header bg-primary text-bg-primary'>
                    <div class='modal-title' id='variablePriceTitle'>
                        <strong>How Much?</strong>
                    </div>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                    <div class='container-fluid' id="variablePriceBody">
                    </div>
                    <div class='row'>
                        <div class='col-sm-12' id='vpMessageDiv'></div>
                    </div>
                </div>
                <div class='modal-footer'>
                    <button class='btn btn-sm btn-secondary' data-bs-dismiss='modal' tabindex='10101'>Cancel</button>
                    <button class='btn btn-sm btn-primary' id='vpSubmitButton' onClick='membership.vpSubmit()' tabindex='10102'>Set Amount</button>
                </div>
            </div>
        </div>
    </div>
<?php
}

//// buttons on portal screen
function drawManagedPerson($personId, $personType, $person, $memberships, $showInterests, $showHR) {
    $badge_name = $person['badge_name'];
    if ($badge_name == '') {
        $badge_name = '<i>' . TRIM($person['first_name'] . ' ' . $person['last_name']) . '</i>';
    }
    $fn = '';
    if ($person['first_name'] != '') {
        $fn = $person['first_name'] . "'s ";
    }
    else if ($person['last_name'] != '') {
        $fn = $person['last_name'] . "'s ";
    }
    if ($showHR) {
?>
    <div class='row'>
        <div class='col-sm-12 ms-0 me-0 align-center'>
            <hr style='height:4px;width:95%;margin:auto;margin-top:18px;margin-bottom:10px;color:#333333;background-color:#333333;'/>
        </div>
    </div>
<?php
    }
    ?>
    <div class="row mt-1">
        <div class='col-sm-1' style='text-align: right;'><?php echo $person['personType'] == 'n' ? 'In Progress' : $person['id']; ?></div>
        <div class='col-sm-4'><strong><?php echo $person['fullname']; ?></strong></div>
        <div class="col-sm-2"><?php echo $badge_name; ?></div>
        <div class='col-sm-5 p-1'>
            <button class='btn btn-sm, btn-primary p-1' style='--bs-btn-font-size: 80%;' onclick="portal.editPerson(<?php echo $person['id'] . ",'" . $person['personType'] . "'"; ?>);">
                Edit <?php echo $fn; ?>Profile
            </button>
<?php if ($showInterests) { ?>
            <button class='btn btn-sm, btn-primary p-1' style='--bs-btn-font-size: 80%;' onclick="portal.editInterests(<?php echo $person['id'] . ",'" . $person['personType'] . "'"; ?>);">
                Edit <?php echo $fn; ?>Interests
            </button>
<?php } ?>
            <button class='btn btn-sm btn-primary p-1' style='--bs-btn-font-size: 80%;' onclick="portal.addMembership(<?php echo $person['id'] . ",'" . $person['personType'] . "'"; ?>);">
                Add To/Edit Cart
            </button>
        </div>
    </div>
    <?php
    if ($memberships != null && count($memberships) > 0) {
        echo "<div class='row'>\n";
        foreach ($memberships as $membership) {
            $disabled = '';
            $borderColor = '';
            $borderStyle = '';
            if ($membership['category'] == 'yearahead')
                $borderColor = 'border-muted';
            else if ($membership['memAge'] == 'child' || $membership['memAge'] == 'kit')
                $borderStyle = 'border-color: #dd9e14 !important; ';
            else if ($membership['type'] == 'oneday')
                $borderColor = 'border-warning';
            else if ($membership['type'] == 'full')
                $borderColor = 'border-success';
            else if ($membership['category'] == 'addon' || $membership['category'] == 'donation')
                $borderColor = 'border-dark';

           if ($membership['status'] == 'upgraded')
                $disabled = ' disabled';

           if ($membership['completePerid'] != NULL) {
               $compareId = $membership['completePerid'];
               $compareType = 'p';
           } else if ($membership['completeNewperid'] != NULL) {
               $compareId = $membership['completeNewperid'];
               $compareType = 'n';
           } else if ($membership['createPerid'] != NULL) {
               $compareId = $membership['createPerid'];
               $compareType = 'p';
           } else if ($membership['createNewperid'] != NULL) {
               $compareId = $membership['createNewperid'];
               $compareType = 'n';
           } else {
               $compareId = '';
               $compareType = '';
           }
           if ($compareId != $personId || $compareType != $personType) {
               $row3 = '<br/>Purchased by ' . $membership['purchaserName'];
           } else {
               $row3 = '';
           }
           ?>
        <div class="col-sm-3 ps-1 pe-1 m-0"><button class="btn btn-light border border-5 <?php echo $borderColor; ?>"
            style="width: 100%; pointer-events:none; <?php echo $borderStyle; ?>" <?php echo $disabled; ?> tabindex="-1"><b><?php echo $membership['shortname']
                    . "</b> (" .
                    $membership['status']
            . ")
            <br/>" .
            "<b>" . $membership['ageShort'] . "</b> (" . $membership['ageLabel'] . ')' . $row3; ?></button></div>
<?php
        }
        echo "</div>\n";;
    }
}

// draw_editInterests on portal screen - draw the update interests form for the person
function draw_editInterestsModal($interests) {
    if ($interests != null) {
    ?>
    <div id='editInterestModal' class='modal modal-x1 fade' tabindex='-1' aria-labelledby='Edit Interests' aria-hidden='true' style='--bs-modal-width: 96%;'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header bg-primary text-bg-primary'>
                    <div class='modal-title' id='editInterestsTitle'>
                        <strong>Edit Interests</strong>
                    </div>
                    <button type='button' class='btn-close' onclick='portal.checkEditInterestsClose();' aria-label='Close'></button>
                </div>
                <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                    <div class='container-fluid'>
                        <form id='editInterests' class='form-floating' action='javascript:void(0);'>
                            <input type='hidden' name='id' id='eiPersonId'/>
                            <input type='hidden' name='type' id='eiPersonType'/>
                            <div class="row">
                                <div class="col-sm-auto">
                                    <h1 class='text-primary size-h2' id='eiHeader'>Editing Interests for this new person</h1>
                                </div>
                            </div>
                            <?php
                            drawInterestList($interests, true);
                            ?>
                        </form>
                        <div class='row'>
                            <div class="col-sm-12" id='eiMessageDiv'></div>
                        </div>
                    </div>
                </div>
                <div class='modal-footer'>
                    <button class='btn btn-sm btn-secondary' onclick='portal.checkEditInterestsClose();' tabindex='10301'>Cancel</button>
                    <button class='btn btn-sm btn-primary' id='editInterestSubmitBtn' onClick="portal.editInterestSubmit()" tabindex='10302'>Update Interests</button>
                </div>
            </div>
        </div>
    </div>
    <?php
    }
}

//// payment items
// drawPaymentModal- main payment modal popup
function draw_PaymentDueModal() {
    ?>
    <div id='paymentDueModal' class='modal modal-xl fade' tabindex='-1' aria-labelledby='paymentsDue' aria-hidden='true' style='--bs-modal-width: 96%;'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header bg-primary text-bg-primary'>
                    <div class='modal-title' id='paymentDueTitle'>
                        <strong>Pay Balance Due</strong>
                    </div>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                    <div class='container-fluid' id="paymentDueBody">
                    </div>
                    <div class='row'>
                        <div class='col-sm-12' id='payDueMessageDiv'></div>
                    </div>
                </div>
                <div class='modal-footer'>
                    <button class='btn btn-sm btn-secondary' data-bs-dismiss='modal' tabindex='10101'>Cancel</button>
                    <button class='btn btn-sm btn-primary' id='payDueSubmitButton' onClick='portal.makePayment(null)' tabindex='10402'>Pay total amount due</button>
                </div>
            </div>
        </div>
    </div>
    <?php
}

// draw_makePaymentModal - the modap popup to take a payment via credit card
function draw_makePaymentModal() {
    $ini = get_conf('reg');
    $cc = get_conf('cc');
    ?>
    <div id='makePaymentModal' class='modal modal-xl fade' tabindex='-1' aria-labelledby='makePayments' aria-hidden='true' style='--bs-modal-width: 96%;'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header bg-primary text-bg-primary'>
                    <div class='modal-title' id='makePaymentTitle'>
                        <strong>Pay Via Credit Cart</strong>
                    </div>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                    <div class="container-fluid" id="makePaymentBody"></div>
                    <div class="container-fluid" id="creditCardDiv">
                        <div class="row">
                            <div class="col-sm-auto">
                                We Accept<br/>
                                <img src='cards_accepted_64.png' alt='Visa, Mastercard, American Express, and Discover'/>
                            </div>

                        </div>
                        <div class='row'>
                            <div class='col-sm-12'>
<?php
    if ($ini['test'] == 1) {
?>
                            <span class='text-danger size-h2'><strong>This won't charge your credit card.<br/>It also won't get you real
                                    memberships
                                    .</strong></span>
<?php
    }
?>
                            </div>
                        </div>
                        <div class='row'>
                            <div class='col-sm-12'><?php echo draw_cc_html($cc); ?></div>
                        </div>
                        <div class='row'>
                            <div class='col-sm-12' id='makePayMessageDiv'></div>
                        </div>
                    </div>
                </div>
                <div class='modal-footer'>
                    <button class='btn btn-sm btn-secondary' data-bs-dismiss='modal' tabindex='10101'>Cancel</button>
                </div>
            </div>
        </div>
    </div>
    <?php
}

//// payment plan items
// drawPaymentPlans - show the status of the payment plans for this account
function drawPaymentPlans($person, $paymentPlans) {
    $con = get_conf('con');
    if (array_key_exists('currency', $con)) {
        $currency = $con['currency'];
    } else {
        $currency = 'USD';
    }

    $plans = $paymentPlans['plans'];
    $payorPlans = $paymentPlans['payorPlans'];

    $dolfmt = new NumberFormatter('', NumberFormatter::CURRENCY);
?>
    <div class='row mb-1 align-items-end'>
        <div class="col-sm-1"><b>Status</b></div>
        <div class='col-sm-1'><b>Next Pmt Due</b></div>
        <div class='col-sm-1' style='text-align: right;'><b>Minimum Pmt Amt</b></div>
        <div class='col-sm-1' style='text-align: right;'><b>Remaining Balance</b></div>
        <div class='col-sm-1'><b>Last Pmt Date</b></div>
        <div class='col-sm-1'><b>Pay By Date</b></div>
        <div class="col-sm-1"><b>Plan Name</b></div>
        <div class="col-sm-1"><b>Payment Type</b></div>
        <div class="col-sm-1" style='text-align: right;'><b>Initial Amount</b></div>
        <div class="col-sm-1" style='text-align: right;'><b>Payments</b></div>
        <div class="col-sm-1"><b>Date Created</b></div>
    </div>
<?php
    $now = time();
    foreach ($payorPlans as $payorPlan) {
        $planid = $payorPlan['planId'];
        $plan = $plans[$planid];
        $nextPayColor = '';
        if (array_key_exists('payments', $payorPlan)) {
            $payments = $payorPlan['payments'];
            $numPmts = count($payments);
            $lastPayment = $payments[$numPmts];
            $lastPaidDate = date_format(date_create($lastPayment['payDate']), 'Y-m-d');
            // numPmts + 1 because we are looking for when the next payment (not the one that just got paid) is due.
            $nextPayDueDate = date_add(date_create($payorPlan['createDate']), date_interval_create_from_date_string((($numPmts + 1) * $payorPlan['daysBetween']) - 1 . ' days'));
            $nextPayDue = date_format($nextPayDueDate, 'Y-m-d');
            $minAmt = $dolfmt->formatCurrency((float) $payorPlan['minPayment'] <= $payorPlan['balanceDue'] ? $payorPlan['minPayment'] : $payorPlan['balanceDue'], $currency);
        } else {
            $numPmts = '0';
            $lastPaidDate = 'None';
            $nextPayDueDate = date_add(date_create($payorPlan['createDate']), date_interval_create_from_date_string($payorPlan['daysBetween'] - 1 . ' days'));
            $nextPayDue = date_format($nextPayDueDate, 'Y-m-d');
            $minAmt = $dolfmt->formatCurrency((float) $payorPlan['minPayment'], $currency);
        }
        if ($payorPlan['status'] != 'active') {
            $nextPayDue = '';
            $minAmt = '';
            $col1 = $payorPlan['status'];
        } else {
            $id = $payorPlan['id'];
            $nextPayTimestamp = $nextPayDueDate->getTimestamp();
            if ($nextPayTimestamp < $now) { // past due
                $nextPayColor = ' bg-danger text-white';
                $col1 = "<button class='btn btn-sm btn-danger pt-0 pb-0' onclick='paymentPlans.payPlan($id);'>Make Past Due Pmt</button>";
                $numPmtsPastDue = 1 + ceil(($now - $nextPayTimestamp) / (24 * 3600 * $payorPlan['daysBetween']));
                $minAmt = $numPmtsPastDue * $payorPlan['minPayment'];
                if ($minAmt > $payorPlan['balanceDue'])
                    $minAmt = $payorPlan['balanceDue'];
                $minAmt = $dolfmt->formatCurrency((float) $minAmt, $currency);
            } else if ($nextPayTimestamp < $now + 7 * 24 * 3600) { // are we within 7 days of a payment
                $nextPayColor = ' bg-warning';
                $col1 = "<button class='btn btn-sm btn-primary pt-0 pb-0' onclick='paymentPlans.payPlan($id);'>Make Pmt</button>";
            } else {
                $col1 = "<button class='btn btn-sm btn-secondary pt-0 pb-0' onclick='paymentPlans.payPlan($id);'>Make Pmt</button>";
            }
        }
        $dateCreated = date_format(date_create($payorPlan['createDate']), 'Y-m-d');
        $payByDate = date_format(date_create($plan['payByDate']), 'Y-m-d');
        $balanceDue = $dolfmt->formatCurrency((float) $payorPlan['balanceDue'], $currency);
        $initialAmt = $dolfmt->formatCurrency((float) $payorPlan['initialAmt'], $currency);
?>
        <div class="row">
            <div class="col-sm-1"><?php echo $col1;?></div>
            <div class="col-sm-1<?php echo $nextPayColor;?>"><?php echo $nextPayDue;?></div>
            <div class="col-sm-1" style='text-align: right;'><?php echo $minAmt;?></div>
            <div class='col-sm-1' style='text-align: right;'><?php echo $balanceDue; ?></div>
            <div class='col-sm-1'><?php echo $lastPaidDate; ?></div>
            <div class='col-sm-1'><?php echo $payByDate; ?></div>
            <div class="col-sm-1"><?php echo $plan['name'];?></div>
            <div class="col-sm-1"><?php echo $payorPlan['payType'];?></div>
            <div class="col-sm-1" style='text-align: right;'><?php echo $initialAmt;?></div>
            <div class="col-sm-1" style='text-align: right;'><?php echo "$numPmts of " . $payorPlan['numPayments'];?></div>
            <div class="col-sm-1"><?php echo $dateCreated;?></div>
        </div>

<?php
    }
}

// draw_makePaymentModal - the modap popup to take a payment via credit card
function draw_recieptModal() {
?>
    <div id='portalReceipt' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Registration Portal Receipt' aria-hidden='true' style='--bs-modal-width:
    80%;'>
    <div class='modal-dialog'>
        <div class='modal-content'>
            <div class='modal-header bg-primary text-bg-primary'>
                <div class='modal-title'>
                    <strong id='portalReceiptTitle'>Registration Portal Receipt</strong>
                </div>
                <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
            </div>
            <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                <div id='portalReceipt-div'></div>
                <div id="portalReceipt-text" hidden="true"></div>
                <div id="portalReceipt-tables" hidden="true"></div>
            </div>
            <div class='modal-footer'>
                <button class='btn btn-sm btn-secondary' data-bs-dismiss='modal'>Close</button>
                <button class='btn btn-sm btn-primary' id='portalEmailReceipt' onClick='portal.emailReceipt("payor")'>Email Receipt</button>
            </div>
        </div>
    </div>
</div>
<?php
}