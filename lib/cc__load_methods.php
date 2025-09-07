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

function cc__metaval($value) :string {
    if ($value === null)
        return 'null';
    if ($value == '')
        return 'N/A';
    return strval($value);
}
// common build the notes fields for the credit card build order routines, builds notes and metadata
// Registration order line items
function cc_regNotes($badge, $planNameSrc, $transid, $custid, $regid, $rowno) : array {
    // notes for alexia: 'reg.01'~memid~perid~newperid~transid~memCategory~glnum
    // metadata 10: reg.01,memid,perid,newperid,planname,transid,custId,glnum, regid, rowno

    $version = 'reg.01';
    if (array_key_exists('perid', $badge))
        $perid = cc__metaval($badge['perid']);
    else
        $perid = 'missing';

    if (array_key_exists('newperid', $badge))
        $newperid = cc__metaval($badge['newperid']);
    else
        $newperid = 'missing';

    if (array_key_exists('memCategory', $badge))
        $memCategory = cc__metaval($badge['memCategory']);
    else
        $memCategory = 'missing';

    if (array_key_exists('glNum', $badge)) {
        $glNum = $badge['glNum'];
    } else {
        $glNum = '';
    }

    if ($planNameSrc != '') {
        $planName = $badge['inPlan'] !== null ? cc__metaval($planNameSrc) : 'NotInPlan';
    } else {
        $planName = 'missing';
    }

    $reg['note'] = implode('~', array($version,$badge['memId'],$perid,$newperid,$transid,$memCategory,$glNum));
    $reg['metadata'] = array(
        'version' => $version,
        'memId' => cc__metaval($badge['memId']),
        'perid' => $perid,
        'newperid' => $newperid,
        'planName' => $planName,
        'transid' => cc__metaval($transid ),
        'custId' => cc__metaval($custid),
        'glNum' => cc__metaval($glNum),
        'regId' => cc__metaval($regid),
        'rowno' => cc__metaval($rowno),
    );

    return $reg;
}

// Payments on Payment Plan items
function cc_planNotes($ep, $transId) : array {
    // note: 'pplan.01',planId~planName~payorPerid~payorNewPerid~transid
    // meta: 8: version, planId, planName, payorPeri, payorNewPerid, transId, currentPayment, BalanceDue
    $version = 'pplan.01';
    $planName = $ep['name'] !== null ? cc__metaval($ep['name']) : 'missing';
    $planId = $ep['id'] !== null ? cc__metaval($ep['id']) : 'missing';
    $payorPerid = $ep['perid'] !== null ? cc__metaval($ep['perid']) : 'missing';
    $payorNewPerid = $ep['newperid'] !== null ? cc__metaval($ep['newperid']) : 'missing';

    $plan['note'] = implode("~", array($version, $planId, $planName, $payorPerid, $payorNewPerid, $transId));
    $plan['metadata'] = array(
        'version' => $version,
        'planId' => $planId,
        'planName' => $planName,
        'payorPerid' => $payorPerid,
        'payorNewPerid' => $payorNewPerid,
        'transId' => cc__metaval($transId),
        'currentPayment' => cc__metaval($ep['currentPayment']),
        'balanceDue' => cc__metaval($ep['balanceDue']),
    );
    return $plan;
}

// Exhibitor Space Payments
function cc_spaceNotes($space, $transid, $incCount, $addCount) : array {
    // Note: sp.01,regionName,exhibitorId,incMbrAllowed,AddUsed,transid,glnum
    // MetaData: 9: sp.01,regionName,exhibitorId~exhibitorNum~incMbrAllowed/incMbrUsed~addMbrAllowed/addUsed~spaceId~transid~glnum

    $version = 'sp.01';
    if (array_key_exists('glNum', $space)) {
        $glNum = $space['glNum'];
    } else {
        $glNum = '';
    }

    $space['note'] = implode("~", array($version, $space['regionName'], $space['exhibitorId'],
        $space['includedMemberships'],$addCount, $transid, $glNum));

    $space['metadata'] = array(
        'version' => $version,
        'regionName' => cc__metaval($space['regionName']),
        'exhibitorId' => cc__metaval($space['exhibitorId']),
        'exhibitorNumber' => cc__metaval($space['exhibitorNumber']),
        'includedMemberships' => strval($space['includedMemberships']) . '/' . strval($incCount),
        'additionalMemberships' => strval($space['additionalMemberships']) . '/' . strval($addCount),
        'spaceId' => cc__metaval($space['id']),
        'transId' => cc__metaval($transid),
        'glNum' => cc__metaval($glNum),
    );

    return $space;
}

// exhibitor mail in fee
function cc_mailFeeNotes($fee, $transid) : array {
    // 'fee.01',name~feeGL
    $version = 'mail.01';
    if (array_key_exists('glNum', $fee))
        $glNum = $fee['glNum'];
    else
        $glNum = '';

    $fee['note'] = implode("~", array($version, $glNum));
    $fee['metadata'] = array(
        'version' => $version,
        'regionName' => cc__metaval($fee['name']),
        'transId' => cc__metaval($transid),
        'glNum' => cc__metaval($glNum),
    );
    return $fee;
    }

// plan deferement amounts
function cc_newPlanNotes($planName, $planId, $nonPlanAmt, $downPmt, $balanceDue,$loginPerid, $loginNewperid, $transid) : array {
    // planName~planId~nonPlanAmt~downPmt~balanceDue~perid~newperid~transid
    $version = 'plan.01';
    $newPlan['note'] = implode('~', array($version,$planName, $planId, $nonPlanAmt, $downPmt, $balanceDue, $loginPerid, $loginNewperid, $transid));
    $newPlan['metadata'] = array(
        'version' => $version,
        'planName' => cc__metaval($planName),
        'planId' => cc__metaval($planId),
        'nonPlanAmt' => cc__metaval($nonPlanAmt),
        'downPmt' => cc__metaval($downPmt),
        'balanceDue' => cc__metaval($balanceDue),
        'loginPerid' => cc__metaval($loginPerid),
        'loginNewperid' => cc__metaval($loginNewperid),
        'transId' => cc__metaval($transid),
    );
    return $newPlan;
}

// art Sales
function cc_artSalesNotes($art, $payorId, $transid) : array {
    // perid, payorid, exhId, exhNum, artId, type,  artSalesId, priceType, transid, glnum (placeholder))
    // default perid to payorId if null (non bid on item)
    $version = 'art.01';
    $perid = $art['perid'];
    if ($perid == null)
        $perid = $payorId;
    if ($perid == null)
        $perid = '';
    $art['note'] = implode('~', array($version, $perid, $art['exhibitorId'], $art['id'], $art['type'], $transid, ''));
    $art['metadata'] = array(
        'version' => $version,
        'perId' => cc__metaval($perid),
        'exhibitorId' => cc__metaval($art['exhibitorId']),
        'exhibitorNumber' => cc__metaval($art['exhibitorNumber']),
        'artId' => cc__metaval($art['id']),
        'type' => cc__metaval($art['type']),
        'artSalesId' =>  cc__metaval($art['artSalesId']),
        'priceType' => cc__metaval($art['priceType']),
        'transId' => cc__metaval($transid),
        'glNum' => 'Future'
    );
    return $art;
}
