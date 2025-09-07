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

// common build the notes fields for the credit card build order routines, builds notes and metadata
// Registration order line items
function cc_regNotes($badge, $planNameSrc, $transid, $custid, $regid, $rowno) : array {
    // notes for alexia: 'reg.01'memid~perid~newperid~transid~glnum
    // metadata 10: reg.01,memid,perid,newperid,planname,transid,custId,glnum, regid, rowno

    $version = 'reg.01';
    if (array_key_exists('perid', $badge))
        $perid = $badge['perid'];
    else
        $perid = '';

    if (array_key_exists('newperid', $badge))
        $newperid = $badge['newperid'];
    else
        $newperid = '';

    if (array_key_exists('glNum', $badge))
        $glNum = $badge['glNum'];
    else
        $glNum = '';

    if ($planNameSrc != '') {
        $planName = $badge['inPlan'] ? $planNameSrc : 'NotInPlan';
    } else {
        $planName = '';
    }

    $reg['note'] = implode('~', array($version,$badge['memId'],$perid,$newperid,$transid,$glNum));
    $reg['metadata'] = array(
        'version' => $version,
        'memId' => $$badge['memId'],
        'perid' => $perid,
        'newperid' => $newperid,
        'planName' => $planName,
        'transid' => $transid,
        'custId' => $custid,
        'glNum' => $glNum,
        'regId' => $regid,
        'rowno' => $rowno,
    );

    return $reg;
}

// Payments on Payment Plan items
function cc_planNotes($ep, $transId) : array {
    // note: 'pplan.01',planId~planName~payorPerid~payorNewPerid~transid
    // meta: 8: version, planId, planName, payorPeri, payorNewPerid, transId, currentPayment, BalanceDue
    $version = 'pplan.01';
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

    $plan['note'] = implode("~", array($version, $planId, $planName, $payorPerid, $payorNewPerid, $transId));
    $plan['metadata'] = array(
        'version' => $version,
        'planId' => $planId,
        'planName' => $planName,
        'payorPerid' => $payorPerid,
        'payorNewPerid' => $payorNewPerid,
        'transId' => $transId,
        'currentPayment' => $ep['currentPayment'],
        'balanceDue' => $ep['balanceDue'],
    );
    return $plan;
}

// Exhibitor Space Payments
function cc_spaceNotes($space, $transid, $incCount, $addCount) : array {
    // Note: sp.01,regionName,exhibitorId,incMbrAllowed,AddUsed,transid,glnum
    // MetaData: 9: sp.01,regionName,exhibitorId~exhibitorNum~incMbrAllowed/incMbrUsed~addMbrAllowed/addUsed~spaceId~transid~glnum

    $version = 'sp.01';
    if (array_key_exists('glNum', $space) && $space['glNum'] != '')
        $glNum = $space['glNum'];
    else
        $glNum = '';

    $space['note'] = implode("~", array($version, $space['regionName'], $space['exhibitorId'],
        $space['includedMemberships'],$addCount, $transid, $glNum));

    $space['metadata'] = array(
        'version' => $version,
        'regionName' => $space['regionName'],
        'exhibitorId' => $space['exhibitorId'],
        'exhibitorNumber' => $space['exhibitorNumber'],
        'includedMemberships' => $space['includedMemberships'] . '/' . $incCount,
        'additionalMemberships' => $space['additionalMemberships'] . '/' . $addCount,
        'spaceId' => $space['id'],
        'transId' => $transid,
        'glNum' => $glNum,
    );

    return $space;
}

// exhibitor mail in fee
function cc_mailFeeNotes($fee, $transid) : array {
    // 'fee.01',name~feeGL
    $version = 'mail.01';
    if (array_key_exists('glNum', $fee) && $fee['glNum'] != '')
        $glNum = $fee['glNum'];
    else
        $glNum = '';

    $fee['notes'] = implode("~", array($version, $glNum));
    $fee['metadata'] = array(
        'version' => $version,
        'regionName' => $fee['name'],
        'transId' => $transid,
        'glNum' => $glNum,
    );
    return $fee;
    }

// plan deferement amounts
function cc_newPlanNotes($planName, $planId, $nonPlanAmt, $downPmt, $balanceDue,$loginPerid, $loginNewperid, $transid) : string {
    // planName~planId~nonPlanAmt~downPmt~balanceDue~perid~newperid~transid
    return implode('~', array($planName, $planId, $nonPlanAmt, $downPmt, $balanceDue, $loginPerid, $loginNewperid, $transid));
}

// art Sales
function cc_artSalesNotes($art, $payorId, $transid) : string {
    // perid, payorid, exhId, exhNum, artId, type,  artSalesId, priceType, transid)
    // default perid to payorId if null (non bid on item)
    $perid = $art['perid'];
    if ($perid == null)
        $perid = $payorId;
    return implode('~', array($perid, $payorId, $art['exhibitorId'], $art['exhibitorNumber'], $art['id'], $art['type'],
        $art['artSalesId'], $art['priceType'], $transid));
}
