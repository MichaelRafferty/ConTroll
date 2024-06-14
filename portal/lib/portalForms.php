<?php
// portalForms:  Forms used by the portal for person and membership work

// drawVerifyPersonInfo - non modal version of validate person information
function drawVerifyPersonInfo() {
    $usps = get_conf('usps');
    $useUSPS = false;
    if (($usps != null) && array_key_exists('secret', $usps) && ($usps['secret'] != ''))
        $useUSPS = true;
    $con = get_conf('con');
?>
<?php
    drawEditPersonBlock($con, $useUSPS);
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
function draw_editPersonModal() {
    $usps = get_conf('usps');
    $useUSPS = false;
    if (($usps != null) && array_key_exists('secret', $usps) && ($usps['secret'] != ''))
        $useUSPS = true;
    $con = get_conf('con');
?>
    <div id='editPersonModal' class='modal modal-x1 fade' tabindex='-1' aria-labelledby='Edit Person' aria-hidden='true' style='--bs-modal-width: 96%;'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header bg-primary text-bg-primary'>
                    <div class='modal-title' id='editPersonTitle'>
                        <strong>Edit Person</strong>
                    </div>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body' stype='padding: 4px; background-color: lightcyan;'>
                    <div class='container-fluid'>
                        <form id='editPerson' class='form-floating' action='javascript:void(0);'>
                            <input type='hidden' name='id' id='epPersonId'/>
                            <input type='hidden' name='type' id='epPersonType'/>
<?php
    drawEditPersonBlock($con, $useUSPS);
?>
                        </form>
                        <div class='row'>
                            <div class="col-sm-12" id='epMessageDiv'></div>
                            <div class='row'>
                                <div class='col-sm-12'>
                                    Still need interests and perhaps to paginate this as it looks a bit long.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class='modal-footer'>
                    <button class='btn btn-sm btn-secondary' data-bs-dismiss='modal' tabindex='10001'>Cancel</button>
                    <button class='btn btn-sm btn-primary' id='editPersonSubmitBtn' onClick="portal.editPersonSubmit()" tabindex='10002'>Update Person</button>
                </div>
            </div>
        </div>
    </div>
<?php
}

// drawEditPersonBlock - just output the block to edit the person
function drawEditPersonBlock($con, $useUSPS) {
?>
        <h3 class='text-primary' id='epHeader'>Personal Information for this new person</h3>
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
                <label for="fname" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;"><span
                                class='text-danger'>&bigstar;</span>First Name</span></label><br/>
                <input class="form-control-sm" type="text" name="fname" id='fname' size="22" maxlength="32" tabindex="100"/>
            </div>
            <div class="col-sm-auto">
                <label for="mname" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">Middle Name</span></label><br/>
                <input class="form-control-sm" type="text" name="mname" id='mname' size="8" maxlength="32" tabindex="110"/>
            </div>
            <div class="col-sm-auto">
                <label for="lname" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;"><span class='text-danger'>&bigstar;</span>Last Name</span></label><br/>
                <input class="form-control-sm" type="text" name="lname" id='lname' size="22" maxlength="32" tabindex="120"/>
            </div>
            <div class="col-sm-auto">
                <label for="suffix" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">Suffix</span></label><br/>
                <input class="form-control-sm" type="text" name="suffix" id='suffix' size="4" maxlength="4" tabindex="130"/>
            </div>
        </div>
        <div class='row'>
            <div class='col-sm-12'>
                <label for='legalname' class='form-label-sm'><span class='text-dark' style='font-size: 10pt;'>Legal Name: for checking against your ID. It will only be visible to Registration Staff.</label><br/>
                <input class='form-control-sm' type='text' name='legalname' id='legalname' size=64 maxlength='64'
                       placeholder='Defaults to First Name Middle Name Last Name, Suffix' tabindex='140'/>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <label for="addr" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;"><span
                                class='text-danger'>&bigstar;</span>Address</span></label><br/>
                <input class="form-control-sm" type="text" name='addr' id='addr' size=64 maxlength="64" tabindex='150'/>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <label for="addr2" class="form-label-sm"><span class="text-dark"
                                                               style="font-size: 10pt;">Company/2nd Address line</span></label><br/>
                <input class="form-control-sm" type="text" name='addr2' id='addr2' size=64 maxlength="64" tabindex='160'/>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-auto">
                <label for="city" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;"><span
                                class='text-danger'>&bigstar;</span>City</span></label><br/>
                <input class="form-control-sm" type="text" name="city" id='city' size="22" maxlength="32" tabindex="170"/>
            </div>
            <div class="col-sm-auto">
                <label for="state" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;"><span class='text-danger'>&bigstar;</span>State: US/CAN 2 letter abv.</span></label><br/>
                <input class="form-control-sm" type="text" name="state" id='state' size="16" maxlength="16" tabindex="180"/>
            </div>
            <div class="col-sm-auto">
                <label for="zip" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;"><span class='text-danger'>&bigstar;</span>Zip</span></label><br/>
                <input class="form-control-sm" type="text" name="zip" id='zip' size="10" maxlength="10" tabindex="190"/>
            </div>
            <div class="col-sm-auto">
                <label for="country" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">Country</span></label><br/>
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
            <div col='col-sm-12'>
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
        <div class="row">
            <div class="col-sm-12">
                <p class="text-body"><?php echo $con['conname']; ?> is entirely run by volunteers.
                    If you're interested in helping us run the convention please email
                    <a href="mailto:<?php echo escape_quotes($con['volunteers']); ?>"><?php echo $con['volunteers']; ?></a>.
                </p>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <p class="text-body">
                    <label>
                        <input type='checkbox' checked name='contact' id='contact' value='Y'/>
                        <?php echo $con['remindertext']; ?>
                    </label>
                    <span class='small'><a href='javascript:void(0)' onClick='$("#contactTip").toggle()'>(more info)</a></span>
                <div id='contactTip' class='padded highlight' style='display:none'>
                    <p class="text-body">
                        We will not sell your contact information or use it for any purpose other than contacting you about this
                        <?php echo $con['conname']; ?> or future <?php echo $con['conname']; ?>s.
                        <span class='small'><a href='javascript:void(0)' onClick='$("#contactTip").toggle()'>(close)</a></span>
                    </p>
                </div>
                </p>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <p class="text-body">
                    <label>
                        <input type='checkbox' checked name='share' id='share' value='Y'/>
                        May we include you in our <a target='_blank' href='<?php echo $con['server']; ?>/checkReg.php'>Check Registration page</a>?
                        This will others to check if you are registered displaying only your first initial, last name, and postal code.
                        If you choose to opt out, only you can check your registration via the portal.
                    </label>
                </p>
            </div>
        </div>
<?php
}

// drawGetAgeBracked - age bracket selection for filtering memberships
function drawGetAgeBracket($updateName, $condata) {
    $readableStartDate = date_format(date_create($condata['startdate']), "l M j, Y");
    ?>
    <div class="row mt-2">
        <div class="col-sm-12">
            <h4 class='text-primary'>Please verify the age of <?php echo $updateName; ?> as of <?php echo $readableStartDate;?></h4>
        </div>
        <div class="row mt-1" id="ageButtons"></div>
        <div class="row mt-2">
            <div class="col-sm-12">Please click on the proper age bracket above to continue to the next step.</div>
        </div>
    </div>
    <?php
}

// drawGetNewMemberships - membership selection
function drawGetNewMemberships() {
    ?>
    <div class='row mt-1' id='membershipButtons'></div>
    <div class="row mt-2">
        <div class="col-sm-12">
            Select from the buttons above to add memberships.
        </div>
    </div>
    <div class='row mt-3' id="step3submit">
        <div class='col-sm-auto'>
            <button class='btn btn-sm btn-secondary' onclick='membership.gotoStep(2, true);'>Return to step 2: Personal Information Verification</button>
        </div>
        <div class='col-sm-auto'>
            <button class='btn btn-sm btn-primary' id="saveCartBtn" onclick='membership.saveCart();'>Return to the home page</button>
        </div>
    </div>
    <?php
}

// drawCart - membership cart
function drawCart() {
    ?>
    </div>
    <div class="cart" id="cartContentsDiv"></div>
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
                <div class='modal-body' stype='padding: 4px; background-color: lightcyan;'>
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

function drawManagedPerson($person, $memberships, $showInterests) {
    ?>
    <div class="row mb-1">
        <div class='col-sm-1' style='text-align: right;'><?php echo ($person['personType'] == 'n' ? 'Temp ' : '') . $person['id']; ?></div>
        <div class='col-sm-4'><?php echo $person['fullname']; ?></div>
        <div class="col-sm-3"><?php echo $memberships; ?></div>
        <div class='col-sm-4 p-1'>
            <button class='btn btn-sm, btn-primary p-1' style='--bs-btn-font-size: 80%;' onclick="portal.editPerson(<?php echo $person['id'] . ",'" . $person['personType'] . "'"; ?>);">Edit Person</button>
<?php if ($showInterests) { ?>
            <button class='btn btn-sm, btn-primary p-1' style='--bs-btn-font-size: 80%;' onclick="portal.editInterests(<?php echo $person['id'] . ",'" . $person['personType'] . "'"; ?>);">Edit Interests</button>
<?php } ?>
            <button class='btn btn-sm btn-primary p-1' style='--bs-btn-font-size: 80%;' onclick="portal.addMembership(<?php echo $person['id'] . ",'" . $person['personType'] . "'"; ?>);">Add/Upgrade Memberships</button>
        </div>
    </div>
    <?php
}

// draw_editInterests - draw the update interests form for the person
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
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body' stype='padding: 4px; background-color: lightcyan;'>
                    <div class='container-fluid'>
                        <form id='editInterests' class='form-floating' action='javascript:void(0);'>
                            <input type='hidden' name='id' id='eiPersonId'/>
                            <input type='hidden' name='type' id='eiPersonType'/>
                            <div class="row">
                                <div class="col-sm-auto">
                                    <h3 class='text-primary' id='eiHeader'>Editing Interests for this new person</h3>
                                </div>
                            </div>
                            <?php
                            drawInterestList($interests);
                            ?>
                        </form>
                        <div class='row'>
                            <div class="col-sm-12" id='eiMessageDiv'></div>
                        </div>
                    </div>
                </div>
                <div class='modal-footer'>
                    <button class='btn btn-sm btn-secondary' data-bs-dismiss='modal' tabindex='10301'>Cancel</button>
                    <button class='btn btn-sm btn-primary' id='editInterestSubmitBtn' onClick="portal.editInterestSubmit()" tabindex='10302'>Update Interests</button>
                </div>
            </div>
        </div>
    </div>
    <?php
    }
}
