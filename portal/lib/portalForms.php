<?php
// portalForms:  Forms used by the portal for person and membership work
// drawVerifyPersonInfo - non modal version of validate person information
function drawVerifyPersonInfo($policies, $ageByDate, $ageList, $countryOptions) : void {
    $usps = get_conf('usps');
    $useUSPS = false;
    if (($usps != null) && array_key_exists('secret', $usps) && ($usps['secret'] != ''))
        $useUSPS = true;
    $con = get_conf('con');
?>
<?php
    drawEditPersonBlock($con, $countryOptions, $useUSPS, $policies, 'add', false, false, $ageByDate, [], $ageList)
?>
<?php
}

// draw_editPerson - draw the verify/update form for the Person
function draw_editPersonModal($source, $policies, $ageList, $ageByDate, $countryOptions, $interests = null) : void {
    $usps = get_conf('usps');
    $useUSPS = false;
    if (($usps != null) && array_key_exists('secret', $usps) && ($usps['secret'] != ''))
        $useUSPS = true;
    $con = get_conf('con');
    $closeClick = match ($source) {
        'login' => 'login.newpersonClose()',
        'portal' => 'portal.checkEditPersonClose()',
        default => 'badClose()',
    };
?>
    <div id='editPersonModal' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Edit Person' aria-hidden='true' style='--bs-modal-width: 96%;'>
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
    drawEditPersonBlock($con, $countryOptions, $useUSPS, $policies, $source, true, false, $ageByDate, [], $ageList);
    if ($interests) { ?>
                        </form>
                        <div class='row'>
                        <div class='col-sm-12'>
                            <hr/>
                        </div>
                    </div>
<?php
        drawVerifyInterestsBlock($interests, false);
    } else {
        echo "</form>\n";
    }
?>

                        <div class='row'>
                            <div class="col-sm-12" id='epMessageDiv'></div>
                        </div>
                    </div>
                </div>
                <div class='modal-footer'>
                    <button class='btn btn-sm btn-secondary' onclick='<?php echo $closeClick; ?>;' tabindex='10001'>Cancel</button>
                    <button class='btn btn-sm btn-primary' id='editPersonSubmitBtn' onClick="portal.editPersonSubmit(false)" tabindex='10002'>Update
                        Person</button>
                    <button class='btn btn-sm btn-warning' id='editPersonOverrideBtn' onClick="portal.editPersonSubmit(true)" tabindex='10003' hidden>
                        Override Warnings and Update Person
                    </button>
                </div>
            </div>
        </div>
    </div>
<?php
}
// drawVerifyInterestsBlock - non modal version of validate interests
function drawVerifyInterestsBlock($interests) : void {
?>
    <form id='editInterests' class='form-floating' action='javascript:void(0);'>
        <?php
        drawInterestList($interests);
        ?>
    </form>
<?php
}

// draw change email address modal
function drawChangeEmailModal() : void {
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
                            <div class='col-sm-auto'><label for="changeEmailNewEmailAddr">Enter the new email address:</label></div>
                            <div class='col-sm-auto'><input type='text' size='64' maxlength='254' id='changeEmailNewEmailAddr' name='newEmailAddr'></div>
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

$membershipButtonColors = array(
        'full' => array('color' => 'border-success', 'style' => ''),
        'wsfs' => array('color' => 'border-dark', 'style' => ''),
        'minor' => array('color' => '', 'style' => 'border-color: #ff9000 !important;'),
        'oneday' => array('color' => '', 'style' => 'border-color: #ffe000 !important;'),
        'virtual' => array('color' => '', 'style' => 'border-color: #cc00cc !important;'),
        'yearahead' => array('color' => '', 'style' => 'border-color: #00fdff !important'),
        'addon' => array('color' => '', 'style' => 'border-color: #adb5bd !important'),
        'other' => array('color' => '', 'style' => ''),
        'black' => array('color' => '', 'style' => 'border-color: #000000 !important'),
);

$portal_conf = get_conf('portal');
$memberColor = null;
if (array_key_exists('memberbadgecolors', $portal_conf)) {
    $memberColor = $portal_conf['memberbadgecolors'];

    if ($memberColor != '')
        $membershipButtonColors['black']['style'] = 'border-color: ' . $memberColor . '!important';
}

function drawPortalLegend() : void {
    global $membershipButtonColors;
    $conf = get_conf('con');
    $conid = $conf['id'];
    $portal_conf = get_conf('portal');

    if (array_key_exists('memberbadgecolors', $portal_conf) || (array_key_exists('suppresslegend', $portal_conf) && $portal_conf['suppresslegend'] == 1))
        return;

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
                    $minor = $mlL['occurs'] > 0;
                    break;
                case 'oneday':
                    $oneday = $mlL['occurs'] > 0;
                    break;
                case 'virtual':
                    $virtual = $mlL['occurs'] > 0;
                    break;
                case 'addon':
                    $addon = $mlL['occurs'] > 0;
                    break;
                case 'wsfs':
                    $wsfs = $mlL['occurs'] > 0;
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
            <button class="btn btn-light border border-5 <?php echo $membershipButtonColors['wsfs']['color']; ?>"
                    style="pointer-events:none; <?php echo $membershipButtonColors['wsfs']['style']; ?>" tabindex='-1'>
                WSFS
            </button>
        </div>
        <?php } ?>
        <div class="col-sm-auto">
            <button class="btn btn-light border border-5 <?php echo $membershipButtonColors['full']['color']; ?>"
                    style="pointer-events:none; <?php echo $membershipButtonColors['full']['style']; ?>" tabindex="-1">
                Full Attending
            </button>
        </div>
        <?php if ($minor) { ?>
        <div class='col-sm-auto'>
            <button class="btn btn-light border border-5 <?php echo $membershipButtonColors['minor']['color']; ?>"
                    style="pointer-events:none; <?php echo $membershipButtonColors['minor']['style']; ?>" tabindex='-1'>
                Requires Adult
            </button>
        </div>
        <?php }
        if ($oneday) { ?>
        <div class='col-sm-auto'>
            <button class="btn btn-light border border-5 <?php echo $membershipButtonColors['oneday']['color']; ?>"
                    style="pointer-events:none; <?php echo $membershipButtonColors['oneday']['style']; ?>" tabindex='-1'>
                One Day Attending
            </button>
        </div>
        <?php }
        if ($virtual) { ?>
        <div class='col-sm-auto'>
            <button class="btn btn-light border border-5 <?php echo $membershipButtonColors['virtual']['color']; ?>"
                    style="pointer-events:none; <?php echo $membershipButtonColors['virtual']['style']; ?>" tabindex='-1'>
                Virtual
            </>
        </div>
        <?php }
        if ($yearahead) { ?>
        <div class='col-sm-auto'>
        <button class="btn btn-light border border-5 <?php echo $membershipButtonColors['yearahead']['color']; ?>"
                    style="pointer-events:none; <?php echo $membershipButtonColors['yearahead']['style']; ?>" tabindex='-1'>
                Year Ahead
            </button>
        </div>
        <?php }
        if ($addon) { ?>
        <div class='col-sm-auto'>
            <button class="btn btn-light border border-5 <?php echo $membershipButtonColors['addon']['color']; ?>"
                    style="pointer-events:none; <?php echo $membershipButtonColors['addon']['style']; ?>" tabindex='-1'>
                Extras
            </button>
        </div>
        <?php } ?>
        <div class='col-sm-auto'>
            <button class="btn btn-light border border-5 <?php echo $membershipButtonColors['other']['color']; ?>"
                    style="pointer-events:none; <?php echo $membershipButtonColors['other']['style']; ?>" tabindex='-1'>
                All Others
            </button>
        </div>
    </div>
<?php
}
// drawPersonTab: draw memberships and profile for a managed person or yourself
function drawPersonTab($personId, $personType, $person, $conid, $ageList, $memberships, $policies, $interests, $now, $ageByDate, $manager) : void {
    global $membershipButtonColors;
    $portal_conf = get_conf('portal');

    $hr = <<<EOS
<div class="row mt-2">
        <div class='col-sm-12 ms-0 me-0 align-center'>
            <hr style='height:4px;width:95%;margin:auto;margin-top:18px;margin-bottom:10px;color:#333333;background-color:#333333;'/>
        </div>
    </div>
EOS;


    $badgename = $person['badgename'];
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
    if ($person['currentAgeType'] == null || $person['currentAgeType'] == '' ||
            ($person['currentAgeConId'] != $conid && $ageList[$person['currentAgeType']]['verify'] == 'Y'))
        $profileClass .= ' need-age';

    $personArgs = json_encode(array('id' => $person['id'] , 'type' => $person['personType'], 'fullName' => $person['fullName'],
                                'first_name' => $person['first_name'], 'last_name' => $person['last_name'],
                                'email_addr' => $person['email_addr']));
    $personArgs = str_replace('"', '\\u0022', $personArgs);
    $personArgs = str_replace("'", '\\u0027', $personArgs);
    $id = $person['id'];
    $personType = $person['personType'];
    $fullName = $person['fullName'];
    $ageType = $person['currentAgeType'];
    $ageLabel = $ageType == '' ? 'unknown' : ($ageList[$ageType]['shortname'] . ' [' . $ageList[$ageType]['label'] . ']');
    $legalName = $person['legalName'];
    $pronouns = $person['pronouns'];
    $fullAddress = $person['address'];
    if ($person['addr_2'] != '')
        $fullAddress .= '<br/>' . $person['addr_2'];
    $fullAddress .= '<br/>' . $person['city'] . ', ' . $person['state'] . ' ' . $person['zip'];
    $country = $person['country'];
    $phone = $person['phone'];
    $email = $person['email_addr'];
    $label = $personType ==  'p' ? 'Membership Number' : 'Temp Membership Number';

    // Purchases block
    $mpbDisabled = '';
    $mpbSuffix = '';
    if ($person['missingPolicies'] > 0) {
        $mpbDisabled = ' disabled';
        $mpbSuffix = '<br/><span style="color: red;"><b>Missing Required Policies</b></span>';
    }

    $mpbId = 'mpBtn' . $personType . $id;
    echo <<<EOS
    <div class="row mt-1">
        <div class="col-sm-2">
            <button class='btn btn-sm btn-primary p-1 h-100 w-100' id="$mpbId" onclick="portal.addMembership($id, '$personType');"$mpbDisabled>
            Add Items to Cart$mpbSuffix
            </button>
        </div>
        <div class="col-sm-auto">
            <span class="h2">$fn Purchases</span>
        </div>
    </div>        
EOS;
    outputCustomText('tab/top');

// now the membership block

    $first = true;
    if ($memberships != null && count($memberships) > 0) {
        foreach ($memberships as $membership) {
            $memPersonId = $personType . $personId;
            if (array_key_exists('regNewperid', $membership)) {
                $memPersonId = 'n' . $membership['regNewperid'];
                if ($personType == 'n' && $membership['regNewperid'] != $personId)
                    continue;
            }
            if (array_key_exists('regPerid', $membership)) {
                $memPersonId = 'p' . $membership['regPerid'];
                if ($personType == 'p' && $membership['regPerid'] != $personId)
                    continue;
            }
            if ($first) {
                echo "<div class='row mt-3'>\n";
                $first = false;
            }
            $disabled = '';
            if (array_key_exists('memberbadgecolors', $portal_conf)) {
                $type = 'black';
            } else {
                $type = 'other';
                $memType = $membership['memType'];
                $memCategory = $membership['memCategory'];
                $memAge = $membership['memAge'];

                if ($memType == 'wsfs')
                    $type = 'wsfs';
                else if ($memCategory == 'yearahead')
                    $type = 'yearahead';
                else if ($memAge == 'child' || $memAge == 'kit')
                    $type = 'minor';
                else if ($memType == 'oneday')
                    $type = 'oneday';
                else if ($memType == 'virtual')
                    $type = 'virtual';
                else if ($memType == 'full')
                    $type = 'full';
                else if ($memCategory == 'addon' || $memCategory == 'add-on'|| $memCategory == 'donation')
                    $type = 'addon';
            }

            $borderColor = $membershipButtonColors[$type]['color'];
            $borderStyle = $membershipButtonColors[$type]['style'];

           if ($membership['status'] == 'upgraded')
                $disabled = ' disabled';

           if ($membership['completePerid'] != null) {
               $compareId = $membership['completePerid'];
               $compareType = 'p';
           } else if ($membership['completeNewperid'] != null) {
               $compareId = $membership['completeNewperid'];
               $compareType = 'n';
           } else if ($membership['createPerid'] != null) {
               $compareId = $membership['createPerid'];
               $compareType = 'p';
           } else if ($membership['createNewperid'] != null) {
               $compareId = $membership['createNewperid'];
               $compareType = 'n';
           } else {
               $compareId = '';
               $compareType = '';
           }
           $status = $membership['status'];
           if (($compareId != $personId || $compareType != $personType) && $membership['actPrice'] >= 0) {
               if ($status == 'unpaid')
                   $row3 = '<br/>Added by ' . $membership['purchaserName'];
               else
                   $row3 = '<br/>Purchased by ' . $membership['purchaserName'];
           } else {
               $row3 = '';
           }
           if ($memAge == 'all') {
               $ageRow =  '';
           } else {
               $ageRow = '<br/><b>' . $membership['ageShort'] . '</b> [' . $membership['ageLabel'] . ']';
           }
           $expired = $membership['status'] == 'unpaid' && ($membership['actPaid'] + $membership['actCouponDiscount']) == 0 &&
                ($membership['startdate'] > $now || $membership['enddate'] < $now);
           $shortname = $membership['shortname'];
           if ($expired) {
               $expiredPrefix = '<span class="text-danger">Expired: ';
               $expiredSuffix = '</span>';
           } else {
               $expiredPrefix = '';
               $expiredSuffix = '';
           }
            echo <<<EOS
    <div class="col-sm-3  ps-1 pe-1 m-0">
        <button class="btn btn-light border border-5 p-1 m-0 mt-1 mb-1 $borderColor w-100" 
            style="pointer-events:none; $borderStyle;" $disabled tabindex="-1"><b>$expiredPrefix$shortname</b>$expiredSuffix ($status)
            $ageRow
            $row3
        </button>
    </div>
    EOS;
            }
        if ($first == false) {
            echo "</div>\n";
            drawPortalLegend();
        }
    }

    // now for this person's profile
    $privacyLink = getConfValue('con', "privacypolicy", '');
    $privacyText = getConfValue('con', 'privacytext', '');
    echo <<<EOS
$hr
<div class="row mt-1">
    <div class="col-sm-2">
        <button class='btn btn-sm $profileClass p-1' data-id="$id" data-type="$personType" onclick="portal.editPerson($id, '$personType');">
            Edit $fn Profile
        </button>
    </div>
    <div class="col-sm-auto">
        <span class="h4">Age category of $fullName as of $ageByDate: $ageLabel</span>
    </div>
</div>
EOS;
    if ($privacyLink != '' && $privacyText == '')
        $privacyText = 'See our privacy policy for how we use and share information';
    if ($privacyLink != '') {
        echo <<<EOS
<div class="row mt-1">
    <div class="col-sm-2"></div>
    <div class="col-sm-10">
            (<a href="$privacyLink" target="_blank">$privacyText</a>).
    </div>
</div>
EOS;
    }

// draw non editable profile
    echo <<<EOS
<div class='row mt-2'>
    <div class="col-sm-2">$label:</div>
    <div class="col-sm-auto"><b>$id</b></div>
</div>
<div class='row mt'>
    <div class="col-sm-2">Name:</div>
    <div class="col-sm-auto"><b>$fullName</b></div>
</div>
<div class='row'>
    <div class="col-sm-2">Legal Name:</div>
    <div class="col-sm-auto"><b>$legalName</b></div>
</div>
<div class='row'>
    <div class="col-sm-2">Badge Name:</div>
    <div class="col-sm-auto"><b>$badgename</b></div>
</div>
<div class='row'>
    <div class="col-sm-2">Pronouns:</div>
    <div class="col-sm-auto"><b>$pronouns</b></div>
</div>
<div class='row'>
    <div class="col-sm-2">Address::</div>
    <div class="col-sm-auto"><b>$fullAddress</b></div>
</div>
<div class='row'>
    <div class="col-sm-2">Country:</div>
    <div class="col-sm-auto"><b>$country</b></div>
</div>
<div class='row'>
    <div class="col-sm-2">Phone:</div>
    <div class="col-sm-auto"><b>$phone</b></div>
</div>
<div class='row'>
    <div class="col-sm-2">
        <button class='btn btn-sm $profileClass p-1' data-id="$id" data-type="$personType" onclick="portal.changeEmail('$personArgs');">  
            Change $fn Email
        </button>
    </div>
    <div class="col-sm-auto"><b>$email</b></div>
</div>
EOS;

    // now the policy block
    if ($policies && count($policies) > 0) {
        // add some space before the policies
        echo <<<EOS
<div class='row'>
    <div class="com-sm-auto">&nbsp;</div>
</div>
EOS;

        drawPoliciesDisplay($policies, $person['policies'], $id);
    }
    // now the interest block
    if ($interests && count($interests) > 0) {
        echo <<<EOS
$hr
<div class='row mt-1'>
    <div class="col-sm-2">
        <button class='btn btn-sm, btn-primary p-1' onclick="portal.editInterests($id, '$personType');">Edit $fn Interests</button>
    </div>
    <div class="col-sm-auto"><span class="h3">Additional Interests or Needs</span></div>
</div>
EOS;
        drawInterestsDisplay($interests, $person['interests'], $id);
    }
}

// draw_editInterests on portal screen - draw the update interests form for the person
function draw_editInterestsModal($interests) : void {
    if ($interests != null) {
    ?>
    <div id='editInterestModal' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Edit Interests' aria-hidden='true' style='--bs-modal-width: 96%;'>
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
function draw_PaymentDueModal() : void {
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
                    <div class='container-fluid'>
                        <div class='row'>
                            <div class='col-sm-12' id='payDueMessageDiv'></div>
                        </div>
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
function draw_makePaymentModal() : void {
    $testsite = getConfValue('portal', 'test') == 1;
    $cc = get_conf('cc');
    $con = get_conf('con');
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
    if ($testsite) {
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
                            <div class='col-sm-auto'>
                                <p>Is the purchase button not working? Try clicking in the last field of the payment form, then press tab.
                                    If that doesn't fix it, please try a different browser or device or contact
                                    <?php echo $con['regadminemail']; ?> for other payment options.
                                    We are aware that our payment processor (Square) does not play well with certain browser configurations,
                                    but cannot fix it. We apologize for the inconvenience.
                                </p>
                            </div>
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
function drawPaymentPlans($person, $paymentPlans, $activeOnly = false) : void {
    $currency = getConfValue('con', 'currency', 'USD');
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
        $id = $payorPlan['id'];
        $planid = $payorPlan['planId'];
        $plan = $plans[$planid];

        if ($activeOnly) {
            if ($payorPlan['status'] != 'active')
                continue;
            $onclick = "paymentPlans.payPlan($id);";
        } else {
            $onclick = "paymentHistory.gotoPayment();";
        }

        if ($payorPlan['status'] == 'active') {
            $nextPayColor = '';
            $data = computeNextPaymentDue($payorPlan, $plans, $dolfmt, $currency);
            $nextPayDue = $data['nextPayDue'];
            $minAmt = $data['minAmt'];
            $nextPayTimestamp = $data['nextPayTimestamp'];
            if ($nextPayTimestamp < $now) { // past due
                $nextPayColor = ' bg-danger text-white';
                $col1 = "<button class='btn btn-sm btn-danger pt-0 pb-0' onclick='paymentPlans.payPlan($id);'>Make Past Due Pmt</button>";
                $minAmt = $data['minAmt'];
            } else if ($nextPayTimestamp < $now + 7 * 24 * 3600) { // are we within 7 days of a payment
                $nextPayColor = ' bg-warning';
                $col1 = "<button class='btn btn-sm btn-primary pt-0 pb-0' onclick='$onclick'>Make Pmt</button>";
            } else {
                $col1 = "<button class='btn btn-sm btn-secondary pt-0 pb-0' onclick='$onclick'>Make Pmt</button>";
            }
        } else {
            $col1 = $payorPlan['status'];
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
            <div class='col-sm-1'><?php echo $data['lastPaidDate']; ?></div>
            <div class='col-sm-1'><?php echo $payByDate; ?></div>
            <div class="col-sm-1"><?php echo $plan['name'];?></div>
            <div class="col-sm-1"><?php echo $payorPlan['payType'];?></div>
            <div class="col-sm-1" style='text-align: right;'><?php echo $initialAmt;?></div>
            <div class="col-sm-1" style='text-align: right;'><?php echo $data['numPmts'] . " of " . $payorPlan['numPayments'];?></div>
            <div class="col-sm-1"><?php echo $dateCreated;?></div>
        </div>

<?php
    }
}

// draw_receiptModal - modal to display a receipt
function draw_recieptModal() : void {
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
                <div id="portalReceipt-text" hidden></div>
                <div id="portalReceipt-tables" hidden></div>
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

// draw_receiptModal - modal to display a receipt
function draw_addMembershipsConfirmModal() : void {
    ?>
    <div id='portalAddConfirm' class='modal modal-lg fade' tabindex='-1' aria-labelledby='Registration Add Memberships Now' aria-hidden='true'
         style='--bs-modal-width:
80%;'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header bg-primary text-bg-primary'>
                    <div class='modal-title'>
                        <strong id='addConfirmTitle'>Registration Portal - Add Memberships/Purchases Now?</strong>
                    </div>
                </div>
                <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                    <div class="row p-3">
                        <div class="col-sm-12" id='addConfirm-div'></div>
                    </div>
                </div>
                <div class='modal-footer'>1
                    <button class='btn btn-sm btn-secondary' onClick="addConfirmResponse(false)">Not Now</button>
                    <button class='btn btn-sm btn-primary' id='addConfirmBtn' onClick='addConfirmResponse(true)'>Purchase Memberships</button>
                </div>
            </div>
        </div>
    </div>
    <?php
}
