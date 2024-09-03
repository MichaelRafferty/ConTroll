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
    drawEditPersonBlock($con, $useUSPS, $policies, 'membership');
?>
    <div class="row mt-3">
        <div class='col-sm-auto'>
            <button class='btn btn-sm btn-secondary' onclick='membership.gotoStep(1, true);'>Return to Age Verification</button>
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
    drawEditPersonBlock($con, $useUSPS, $policies, $source, true);
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
            <button class='btn btn-sm btn-secondary' onclick='membership.gotoStep(2, true);'>Return to Personal Information Verification</button>
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
            Select from the buttons above to add memberships and other items.
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

// draw change email address modal
function drawChangeEmailModal() {
?>
    <div id='changeEmailModal' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Change Email' aria-hidden='true' style='--bs-modal-width: 90%;'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header bg-primary text-bg-primary'>
                    <div class='modal-title' id='changeEmailTitle'>
                        <strong>Change Email Address</strong>
                    </div>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                    <div class='container-fluid' id="changeEmailBody">
                        <div class='row mt-3 mb-3'>
                            <div class='col-sm-12'>
                                <h1 class='size-h3 text-primary' id="changeEmailH1">Change Email Address For </h1>
                            </div>
                        </div>
                        <div class='row'>
                            <div class='col-sm-auto'>Enter the new email address:</div>
                            <div class='col-sm-auto'><input type='text' size='64', maxlength='254' id='changeEmailNewEmailAddr', name='newEmailAddr'></div>
                        </div>
                        <div class='row mt-2' id='changeEmailVerifyMe' hidden>
                            <div class='col-sm-auto'>This is an email address you manage, do you wish to change to this same email address?</div>
                            <div class='col-sm-auto'>
                                <button class='btn btn-sm btn-primary' type='button' onclick='portal.checkNewEmail(1);'>Yes, use the same email
                                address</button>
                            </div>
                        </div>
                        <div class="row"><div class="col-sm-1">&nbsp;</div></div>
                        <?php outputCustomText('main/changeEmail');?>
                        <div class='row'>
                            <div class='col-sm-12' id='ceMessageDiv'></div>
                        </div>
                    </div>
                </div>
                <div class='modal-footer'>
                    <button class='btn btn-sm btn-secondary' data-bs-dismiss='modal' tabindex='10101'>Cancel</button>
                    <button class='btn btn-sm btn-primary' id='changeEmailSubmitBtn' onClick='portal.checkNewEmail(0)' tabindex='10102' disabled>Change Email
                    Address</button>
                </div>
            </div>
        </div>
    </div>
<?php
}

//// membership items on portal screen
// drawLegend - draw a legend row for the membership block of the portal home page

$memershipButtonColors = array(
        'full' => array('color' => 'border-success', 'style' => ''),
        'wsfs' => array('color' => 'border-dark', 'style' => ''),
        'minor' => array('color' => '', 'style' => 'border-color: #ff9000 !important;'),
        'oneday' => array('color' => '', 'style' => 'border-color: #ffe000 !important;'),
        'virtual' => array('color' => '', 'style' => 'border-color: #cc00cc !important;'),
        'yearahead' => array('color' => '', 'style' => 'border-color: #00fdff !important'),
        'addon' => array('color' => '', 'style' => 'border-color: #adb5bd !important'),
        'other' => array('color' => '', 'style' => ''),
);
function drawPortalLegend() {
    global $memershipButtonColors;
    $conf = get_conf('con');
    $conid = $conf['id'];

    // figure which legend item exist - we need categories and types from memList
    $mlQ = <<<EOS
SELECT 'yearahead' AS name, count(*) AS occurs
FROM memList WHERE memCategory = 'yearahead' AND conid = ?
UNION SELECT 'minor' AS name, count(*) AS occurs
FROM memList WHERE memAge in ('kit', 'child') AND conid = ?
UNION SELECT 'oneday' AS name, count(*) AS occurs
FROM memList WHERE memType = 'oneday' AND conid = ?
UNION SELECT 'virtual' AS name, count(*) AS occurs
FROM memList WHERE memType = 'virtual' AND conid = ?
UNION SELECT 'addon' AS name, count(*) AS occurs
FROM memList WHERE memCategory in ('addon', 'add-on', 'donation') AND conid = ?
UNION SELECT 'wsfs' AS name, count(*) AS occurs
FROM memList WHERE memType ='wsfs' AND conid = ?;
EOS;
    $mlR = dbSafeQuery($mlQ, 'iiiiii', array($conid, $conid, $conid, $conid, $conid, $conid));
    $yearahead = false;
    $minor = false;
    $oneday = false;
    $virtual = false;
    $addon = false;
    $wsfs = false;
    if ($mlR !== false) {
        while ($mlL = $mlR->fetch_assoc()) {
            switch ($mlL['name']) {
                case 'yearahead':
                    $yearahead = $mlL['occurs'] > 0;
                    break;
                case 'minor':
                    $minor = $mlL['occurs'] > 0;;
                    break;
                case 'oneday':
                    $oneday = $mlL['occurs'] > 0;;
                    break;
                case 'virtual':
                    $virtual = $mlL['occurs'] > 0;;
                    break;
                case 'addon':
                    $addon = $mlL['occurs'] > 0;;
                    break;
                case 'wsfs':
                    $wsfs = $mlL['occurs'] > 0;;
                    break;
            }
        }
    }
?>
    <div class="row mt-2">
        <div class='col-sm-12 ms-0 me-0 align-center'>
            <hr style='height:4px;width:95%;margin:auto;margin-top:18px;margin-bottom:10px;color:#333333;background-color:#333333;'/>
        </div>
    </div>
    <div class="row mt-2">
        <div class="col-sm-auto"><b>Legend:</b></div>
        <?php if ($wsfs) { ?>
        <div class='col-sm-auto'>
            <button class="btn btn-light border border-5 <?php echo $memershipButtonColors['wsfs']['color']; ?>"
                    style="pointer-events:none; <?php echo $memershipButtonColors['wsfs']['style']; ?>" tabindex='-1'>
                WSFS
            </button>
        </div>
        <?php } ?>
        <div class="col-sm-auto">
            <button class="btn btn-light border border-5 <?php echo $memershipButtonColors['full']['color']; ?>"
                    style="pointer-events:none; <?php echo $memershipButtonColors['full']['style']; ?>" tabindex="-1">
                Full Attending
            </button>
        </div>
        <?php if ($minor) { ?>
        <div class='col-sm-auto'>
            <button class="btn btn-light border border-5 <?php echo $memershipButtonColors['minor']['color']; ?>"
                    style="pointer-events:none; <?php echo $memershipButtonColors['minor']['style']; ?>" tabindex='-1'>
                Requires Adult
            </button>
        </div>
        <?php }
        if ($oneday) { ?>
        <div class='col-sm-auto'>
            <button class="btn btn-light border border-5 <?php echo $memershipButtonColors['oneday']['color']; ?>"
                    style="pointer-events:none; <?php echo $memershipButtonColors['oneday']['style']; ?>" tabindex='-1'>
                One Day Attending
            </button>
        </div>
        <?php }
        if ($virtual) { ?>
        <div class='col-sm-auto'>
            <button class="btn btn-light border border-5 <?php echo $memershipButtonColors['virtual']['color']; ?>"
                    style="pointer-events:none; <?php echo $memershipButtonColors['virtual']['style']; ?>" tabindex='-1'>
                Virtual
            </>
        </div>
        <?php }
        if ($yearahead) { ?>
        <div class='col-sm-auto'>
        <button class="btn btn-light border border-5 <?php echo $memershipButtonColors['yearahead']['color']; ?>"
                    style="pointer-events:none; <?php echo $memershipButtonColors['yearahead']['style']; ?>" tabindex='-1'>
                Year Ahead
            </button>
        </div>
        <?php }
        if ($addon) { ?>
        <div class='col-sm-auto'>
            <button class="btn btn-light border border-5 <?php echo $memershipButtonColors['addon']['color']; ?>"
                    style="pointer-events:none; <?php echo $memershipButtonColors['addon']['style']; ?>" tabindex='-1'>
                Extras
            </button>
        </div>
        <?php } ?>
        <div class='col-sm-auto'>
            <button class="btn btn-light border border-5 <?php echo $memershipButtonColors['other']['color']; ?>"
                    style="pointer-events:none; <?php echo $memershipButtonColors['other']['style']; ?>" tabindex='-1'>
                All Others
            </button>
        </div>
    </div>
<?php
}
// drawManagedRow: draw the memberships and buttons for a managed person or yourself
function drawPersonRow($personId, $personType, $person, $memberships, $showInterests, $showHR, $now) {
        global $memershipButtonColors;

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
    if (array_key_exists('missingPolicies', $person) && $person['missingPolicies'] == 0) {
        $profileClass = 'btn-primary';
    } else {
        $profileClass = 'btn-warning need-policies';
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
    $personArgs = json_encode(array('id' => $person['id'] , 'type' => $person['personType'], 'fullname' => $person['fullname'],
                                'first_name' => $person['first_name'], 'last_name' => $person['last_name'],
                                'email_addr' => $person['email_addr']));
    $personArgs = str_replace('"', '\\u0022', $personArgs);
    $personArgs = str_replace("'", '\\u0027', $personArgs);
    ?>
    <div class="row mt-1">
        <div class='col-sm-1' style='text-align: right;'><?php echo $person['personType'] == 'n' ? 'Pending' : $person['id']; ?></div>
        <div class='col-sm-3'><strong><?php echo $person['fullname']; ?></strong></div>
        <div class="col-sm-2"><?php echo $badge_name; ?></div>
        <div class='col-sm-6 p-1'>
                <button class='btn btn-sm btn-primary p-1' style='--bs-btn-font-size: 80%;'
                data-id="<?php echo $person['id']?>", data-type="<?php echo $person['personType']; ?>"
                onclick="portal.changeEmail('<?php echo $personArgs; ?>');">
                Change <?php echo $fn; ?>Email
            </button>
            <button class='btn btn-sm <?php echo $profileClass; ?> p-1' style='--bs-btn-font-size: 80%;'
                data-id="<?php echo $person['id']?>", data-type="<?php echo $person['personType']; ?>"
                onclick="portal.editPerson(<?php echo $person['id'] . ",'" . $person['personType'] . "'"; ?>);">
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
            $type = 'other';

            if ($membership['type'] == 'wsfs')
                $type = 'wsfs';
            else if ($membership['category'] == 'yearahead')
                $type = 'yearahead';
            else if ($membership['memAge'] == 'child' || $membership['memAge'] == 'kit')
                $type = 'minor';
            else if ($membership['type'] == 'oneday')
                $type = 'oneday';
            else if ($membership['type'] == 'virtual')
                $type = 'virtual';
            else if ($membership['type'] == 'full')
                $type = 'full';
            else if ($membership['category'] == 'addon' || $membership['category'] == 'add-on'|| $membership['category'] == 'donation')
                $type = 'addon';

            $borderColor = $memershipButtonColors[$type]['color'];
            $borderStyle = $memershipButtonColors[$type]['style'];

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
           if ($membership['memAge'] == 'all') {
               $ageRow =  '';
           } else {
               $ageRow = '<br/><b>' . $membership['ageShort'] . '</b> (' . $membership['ageLabel'] . ')';
           }
           $expired = $membership['status'] == 'unpaid' &&
                ($membership['startdate'] > $now || $membership['enddate'] < $now || $membership['startdate'] > $now || $membership['online'] == 'N');
           ?>
        <div class="col-sm-3 ps-1 pe-1 m-0"><button class="btn btn-light border border-5 mt-1 <?php echo $borderColor; ?>"
            style="width: 100%; pointer-events:none; <?php echo $borderStyle; ?>" <?php echo $disabled; ?> tabindex="-1"><b><?php
                if ($expired)
                    echo '<span class="text-danger">Expired: ';
                echo $membership['shortname'] . "</b> (" . $membership['status']. ")";
                if ($expired)
                    echo '</span>';
                echo $ageRow . $row3; ?></button></div>
<?php
        }
        echo "</div>\n";
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
                        <strong>Pay Via Credit Card</strong>
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
                        <div class='row mt-2'>
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

// draw_receiptModal - modal to display a receipt
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