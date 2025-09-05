<?php

// load the appropriate methods for processing credit cards based on the config file
function load_cc_procs() : void {
    $cc = get_conf('cc');

    switch ($cc['type']) {
        case 'square':
            require_once (__DIR__ . "/../Composer/vendor/autoload.php");
            require_once("cc_square.php");
            break;
        case 'test':
            if ((!array_key_exists('demo', $cc)) || $cc['demo'] != 1) { // allow demo override on test for cc
                if (($cc['env'] != 'sandbox') || getConfValue('reg','test') != 1) {
                    ajaxSuccess(array ('status' => 'error', 'data' => 'Something thinks this is a real charge method'));
                    exit();
                }
            }
            require_once("cc_test.php");
            break;
        case 'bypass':
            if (isDirectAllowed()) {
                require_once("cc_bypass.php");
                break;
            } else {
                echo "Bypass is not a valid credit card processor for this configuration\n";
                exit();
            }
        default:
            echo "No valid credit card processor defined\n";
            exit();
    }
}

// common build the notes fields for the credit card build order routines
// Registration order line items
function cc_regNotes($badge, $planName, $transid, $custid) : string {
    // memid~perid~newPerid~TransId~planName~custid~GL Num
    $notesFields = array($badge['memId']);
    if (array_key_exists('perid', $badge))
        $notesFields[] = $badge['perid'];
    else
        $notesFields[] = '';

    if (array_key_exists('perid', $badge))
        $notesFields[] = $badge['perid'];
    else
        $notesFields[] = '';
    if (array_key_exists('newperid', $badge))
        $notesFields[] = $badge['newperid'];
    else
        $notesFields[] = '';

    $notesFields[] = $transid;

    if ($planName != '') {
        $notesFields[] = $badge['inPlan'] ? $planName : 'NotInPlan';
    } else {
        $notesFields[] = '';
    }

    $notesFields[] = $custid;

    if (array_key_exists('glNum', $badge))
        $notesFields[] = $badge['glNum'];
    else
        $notesFields[] = '';

    return implode("~", $notesFields);
}

// Payments on Payment Plan items
function cc_planNotes($ep, $transId) : string {
    // planId~planName~payorPerid~payorNewPerid~transid
    $planName = $ep['name'];
    $planId = $ep['id'];
    if ($ep['perid'])
        $payorPerid = $ep['perid'];
    else
        $payorPerid = '';
    if ($ep['newperid'])
        $payorNewPerid = $ep['newperid'];
    else
        $payorNewPerid = '';

    return "$planId~$planName~$payorPerid~$payorNewPerid~$transId";
}

// Exhibitor Space Payments
function cc_spaceNotes($space, $transid, $incCount, $addCount) : string {
    // exhibitorId~exhibitorNum~regionName~incMbrAllowed~addMbrAllowed~incUsed~addUsed~spaceId~transid~glnum

    if (array_key_exists('glNum', $space) && $space['glNum'] != '')
        $glNum = $space['glNum'];
    else
        $glNum = '';

    return implode("~", array($space['exhibitorId'], $space['exhibitorNumber'], $space['regionName'],
        $space['includedMemberships'], $space['additionalMemberships'], $incCount,$addCount, $space['id'], $transid,
        $glNum));
}

// exhibitor mail in fee
function cc_mailFeeNotes($fee, $transid) : string {
    // name~feeGL
    if (array_key_exists('glNum', $fee) && $fee['glNum'] != '')
        $glNum = $fee['glNum'];
    else
        $glNum = '';

    return implode("~", array('Mail-in fee', $glNum));
    }

// plan deferement amounts
function cc_newPlanNotes($planName, $planId, $nonPlanAmt, $downPmt, $balanceDue,$loginPerid, $transid) : string {
    // planName~planId~nonPlanAmt~downPmt~balanceDue~perid~transid
    return implode('~', array($planName, $planId, $nonPlanAmt, $downPmt, $balanceDue,$loginPerid, $transid));
}

// art Sales
function cc_artSalesNotes($art, $payorId, $transid) : string {
    // perid, payorid, exhId, exhNum, artId, type,  artSalesId, priceType, transid)
    return implode('~', array($art['perid'], $payorid, $art['exhibitorId'], $art['exhibitorNumber'], $art['id'], $art['type'],
        $art['artSalesId'], $art['priceType'], $transid);
}
