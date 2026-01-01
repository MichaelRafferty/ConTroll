<?php
// Registration  Portal - paymentHistory.php - show the payment history for the portal
require_once("lib/base.php");
require_once('lib/getAccountData.php');
require_once('lib/sessionManagement.php');
require_once('../lib/webauthn.php');
require_once('../lib/email__load_methods.php');
require_once("../lib/portalForms.php");
require_once("../lib/paymentPlans.php");
require_once("../lib/coupon.php");

global $config_vars;

$con = get_conf('con');
$conid = $con['id'];
$portal_conf = get_conf('portal');
$condata = get_con();

if (getConfValue('portal', 'suspended') == 1) {
    // the portal is now closed, redirect the user back as a logout and let them get the closed screen
    header('location:' . $portal_conf['portalsite'] . '?logout');
    exit();
}

if (isSessionVar('id') && isSessionVar('idType')) {
    // check for being resolved/baned
    $resolveUpdates = isResolvedBanned();
    if ($resolveUpdates != null && array_key_exists('logout', $resolveUpdates) && $resolveUpdates['logout'] == 1) {
        header('location:' . $portal_conf['portalsite']);
        exit();
    }
    $loginType = getSessionVar('idType');
    $loginId = getSessionVar('id');
    $expiration = getSessionVar('tokenExpiration');
    $refresh = time() > $expiration;
} else {
    header('location:' . $portal_conf['portalsite']);
    exit();
}

$currency = getConfValue('con', 'currency', 'USD');
$locale = getLocale();

$config_vars = array();
$config_vars['label'] = $con['label'];
$config_vars['debug'] = getConfValue('debug', 'portal', 0);
$config_vars['uri'] = $portal_conf['portalsite'];
$config_vars['loadPlans'] = true;
$config_vars['id'] = $loginId;
$config_vars['idType'] = $loginType;
$config_vars['conid'] = $conid;
$config_vars['locale'] = $locale;
$config_vars['currency'] = $currency;

$cdn = getTabulatorIncludes();

// this section is for 'in-session' management
// build info array about the account holder
$info = getPersonInfo($conid, null, null, true);
if ($info === false) {
    echo 'Invalid Login, seek assistance';
    clearSession();
    portalPageFoot();
    exit();
}
$dolfmt = new NumberFormatter($locale, NumberFormatter::CURRENCY);

$tokenType = getSessionVar('tokenType');

if ($refresh) {
    portalPageInit('paymentHistory', $info,
            /* css */ array(),
            /* js  */ array(),
            true // refresh
    );
    if (getSessionVar('tokenType') == 'passkey') {
        $config_vars['refresh'] = 'passkey';
        portalPageFoot();
        ?>
        <script type="text/javascript">
            var config = <?php echo json_encode($config_vars); ?>;
            show_message("Passkey session has expired, a refresh of your is being requested.", 'warn');
        </script>
        <?php
    } else {
        echo "refresh needed<br/>\n";
        echo refreshSession();
    }
    exit();
}

$memberships = getAccountRegistrations($loginId, $loginType, $conid, 'all');

// get the payment plans
$paymentPlansData = getPaymentPlans(true);
$activePaymentPlans = 0;
if (array_key_exists('payorPlans', $paymentPlansData)) {
    $payorPlan = $paymentPlansData['payorPlans'];
    foreach ($payorPlan as $p) {
        if ($p['status'] == 'active') {
            $activePaymentPlans++;
        }
    }
} else {
    $payorPlan = [];
}
if (array_key_exists('plans', $paymentPlansData)) {
    $paymentPlans = $paymentPlansData['plans'];
} else {
    $paymentPlans = [];
}

// get valid coupons
$numCoupons = num_coupons();
$now = date_format(date_create('now'), 'Y-m-d H:i:s');

// compute total due so we can display it up top as well...
$totalDue = 0;
$totalUnpaid = 0;
$totalPaid = 0;
$numExpired = 0;
$disablePay = '';

foreach ($memberships as $key => $membership) {
    $label = ($membership['conid'] != $conid ? $membership['conid'] . ' ' : '') . $membership['label'];
    if ($membership['status'] == 'unpaid') {
        $totalUnpaid++;
        $due = round($membership['price'] - ($membership['paid'] + $membership['couponDiscount']), 2);
        $totalDue += $due;

        $status = 'Balance due: ' . $dolfmt->formatCurrency((float) $due, $currency);

        if ($membership['startdate'] > $now || $membership['enddate'] < $now || $membership['online'] == 'N') {
            $label = "<span class='text-danger'><b>Expired: </b>$label</span>";
            $numExpired++;
        }
    }
    if ($membership['status'] == 'plan') {
        $totalUnpaid++;
    }
    if ($membership['status'] == 'paid') {
        $totalPaid++;
    }
    $memberships[$key]['displayLabel'] = $label;
}
if ($numExpired > 0) {
    $disablePay = ' disabled';
}

portalPageInit('paymentHistory', $info,
    /* css */ array($cdn['tabcss'],
        $cdn['tabbs5'],
    ),
    /* js  */ array( //$cdn['luxon'],
        $cdn['tabjs'],
        'jslib/paymentPlans.js',
        'js/paymentHistory.js',
    ),
    false // refresh
);
?>
<script type='text/javascript'>
    var config = <?php echo json_encode($config_vars); ?>;
    var paymentPlanList = <?php echo json_encode($paymentPlans); ?>;
    var payorPlans = <?php echo json_encode($payorPlan); ?>;
    var membershipsPurchased = <?php echo json_encode($memberships); ?>;
</script>
<?php
draw_recieptModal();
$noPayments = true;

$totalDueFormatted = '';
if ($totalDue > 0) {
    $noPayments = false;
    $totalDueFormatted = 'Total due: ' . $dolfmt->formatCurrency((float)$totalDue, $currency);
    $payHtml = <<<EOS
    $totalDueFormatted <button class="btn btn-sm btn-primary pt-1 pb-1 ms-1 me-2" name="payBalanceBTNs" onclick="paymentHistory.gotoPayment();" $disablePay>
            Make a Payment
        </button>
EOS;
} else {
    $payHtml = '';
}

if ($activePaymentPlans > 0) {
    ?>
    <div class='row mt-5'>
        <div class='col-sm-12'><h1 class="size-h3">Payment Plans for this account:</h1></div>
    </div>
    <?php
    outputCustomText('main/plan');
    drawPaymentPlans($info, $paymentPlansData, false);
?>
    <div class='row mt-4'>
        <div class='col-sm-12 p-0 m-0 align-center'>
            <hr style='height:4px;width:98%;margin:auto;margin-top:0px;margin-bottom:0px;color:#333333;background-color:#333333;'/>
        </div>
    </div>
<?php
}
?>
<div class='container-fluid p-0 m-0' id='paymentHistorySection'>
<?php
if (count($memberships) > 0) {
    $noPayments = false;
    if ($totalUnpaid > 0) {
        $showAll = 'disabled';
        $showUnpaid = 'disabled';
    } else {
        $showAll = 'disabled';
        $showUnpaid = 'hidden';
    }
?>
    <div class='row mt-4'>
        <div class='col-sm-auto'>
            <h1 class="size-h3">
                Purchased by this account: <?php echo $payHtml; ?>
                <div class="btn-group ms-2" data-toggle="buttons">
                <button class="btn btn-sm btn-info text-white me-0 ps-3" style="border-top-left-radius: 20px; border-bottom-left-radius: 20px;" id="btn-showAll"
                        type="button" onclick="paymentHistory.showAll();"
                    <?php echo $showAll;?>><b>Show All</b></button>
<?php
    if ($totalUnpaid > 0) {
?>
                <button class="btn btn-sm btn-info text-white m-0" style='border-top-right-radius: 20px; border-bottom-right-radius: 20px;' id="btn-showUnpaid"
                        type="button" onclick="paymentHistory.showUnpaid();"
                    <?php echo $showUnpaid; ?>><b>Show Unpaid Only</b></button>
<?php
    }
?>
                </div>
            </h1>
        </div>
    </div>
<?php
    outputCustomText('main/purchased');
    if ($numCoupons > 0) {
?>
    <div class='container-fluid' id='couponDiv' style="background-color: rgba(0,255,128,0.1)" hidden>
        <div class="row">
            <div class="col-sm-auto"><b>Coupon</b></div>
        </div>
        <div class="row">
            <div class="col-sm-12" id="couponDetailDiv"></div>
        </div>
        <div class='row'>
            <div class='col-sm-12'>
                <button class='btn btn-sm btn-secondary' onclick='coupon.ModalOpen(1);' id='changeCouponBTN'>Change/Remove Coupon</button>
            </div>
        </div>
        <div class='row mt-4'>
            <div class='col-sm-2'>
                Subtotal before coupon:
            </div>
            <div class='col-sm-1 text-end' id='subTotalColDiv'></div>
        </div>
        <div class='row'>
            <div class='col-sm-2'>
                Coupon Discount:
            </div>
            <div class='col-sm-1 text-end' id='couponDiscountDiv'></div>
        </div>
    </div>
<?php
    }
?>
    <div class='row'>
        <div class='col-sm-1' style='text-align: right;'><b>Trans ID</b></div>
        <div class='col-sm-2'><b>Date</b></div>
        <div class='col-sm-1'><b>Receipt</b></div>
    </div>
    <div class='row'>
        <div class='col-sm-1'></div>
        <div class='col-sm-2'><b>Status</b></div>
        <div class='col-sm-3'><b>Membership</b></div>
        <div class='col-sm-4'><b>Full Name / Badge Name</b></div>
    </div>
    <div class='row'>
        <div class='col-sm-12 p-0 m-0 align-center'>
            <hr style='height:4px;width:98%;margin:auto;margin-top:0px;margin-bottom:0px;color:#333333;background-color:#333333;'/>
        </div>
    </div>
<?php
// loop over the transactions outputting the memberships
    // first find all the transactions and set their status
    $currentId = -99999;
    $status = 'paid';
    foreach ($memberships as $membership) {
        if ($currentId != $membership['sortTrans']) {
            if ($currentId > -10000) {
                $trans['t-' . $currentId] = $status;
            }
            $currentId = $membership['sortTrans'];
            $status = 'paid';
        }
        if ($membership['status'] != $status) {
            if ($membership['status'] == 'unpaid')
                $status = 'unpaid';
            if ($membership['status'] == 'plan' && $status == 'paid')
                $status = 'plan';
        }
    }
    $trans['t-' . $currentId] = $status;
    $currentId = -99999;
    $color = true;
    echo '<div class="container-fluid p-0 m-0" name="t-' . $trans['t-' . $memberships[0]['sortTrans']] .'">' .  PHP_EOL;
    foreach ($memberships as $membership)  {
        if ($loginType == 'p' && array_key_exists('transPerid', $membership) && $membership['transPerid'] != $loginId)
            continue;
        if ($loginType == 'n' && array_key_exists('transNewPerid', $membership) && $membership['transNewPerid'] != $loginId)
            continue;

        if ($currentId != $membership['sortTrans']) {
            if ($currentId > -10000) {
                $bgcolor = $color ? ' bg-light' : '';
                $color = !$color
?>
        </div>
        <div class="container-fluid<?php echo $bgcolor; ?> p-0 m-0" name="t-<?php echo $trans['t-' . $membership['sortTrans']];?>">
        <div class='row'>
            <div class='col-sm-12 p-0 m-0 align-center'>
                <hr style='height:4px;width:98%;margin:auto;margin-top:0px;margin-bottom:0px;color:#333333;background-color:#333333;'/>
            </div>
        </div>
<?php
            }
            $currentId = $membership['sortTrans'];
            if ($membership['complete_trans']) {
                $receipt = "<button class='btn btn-sm btn-secondary p-1 pt-0 pb-0' style='--bs-btn-font-size: 80%;' " .
                    'onclick="paymentHistory.transReceipt(' . $membership['complete_trans'] . ');">Receipt</button>';
            } else {
                $receipt = '';
            }
            $transDate = date_format(date_create($membership['transDate']), 'Y-m-d');

?>
        <div class='row pt-1'>
            <div class='col-sm-1' style='text-align: right;'><?php echo $currentId; ?></div>
            <div class="col-sm-2"><?php echo $transDate; ?></div>
            <div class='col-sm-1'><?php echo $receipt; ?></div>
        </div>
<?php
        }
        if ($membership['status'] == 'unpaid') {
            $due = round($membership['price'] - ($membership['paid'] + $membership['couponDiscount']), 2);
            $status = '<b>Balance due: ' . $dolfmt->formatCurrency((float) $due, $currency) . '</b>';
        } else if ($membership['status'] == 'paid') {
            $status = 'paid: ' . $dolfmt->formatCurrency((float) $membership['paid'], $currency);
        } else if ($membership['status'] == 'plan') {
            $status = 'plan';
        }
?>
        <div class='row'>
            <div class='col-sm-1'></div>
            <div class='col-sm-2'><?php echo $status; ?></div>
            <div class='col-sm-3'><?php echo $membership['displayLabel']; ?></div>
            <div class="col-sm-6"><?php echo $membership['fullName'] . ' / ' . $membership['badgename'];?></div>
        </div>
<?php
    }
    echo "        </div>";
    if ($totalDue > 0) {
?>
    <div class='row'>
        <div class='col-sm-12 p-0 m-0 align-center'>
            <hr color="black" style='height:3px;width:98%;margin:auto;margin-top:10px;margin-bottom:2px;'/>
        </div>
        <div class='col-sm-12 p-0 m-0 align-center'>
            <hr color="black" style='height:3px;width:98%;margin:auto;margin-top:2px;margin-bottom:20px;'/>
        </div>
    </div>
<div class="row">
    <div class="col-sm-1"></div>
    <div class="col-sm-2"><b><?php echo $totalDueFormatted; ?></b></div>
    <div class="col-sm-4">
        <button class="btn btn-sm btn-primary pt-1 pb-1" id="payBalanceBTN" name='payBalanceBTNs' onclick="paymentHistory.gotoPayment();"<?php
            echo $disablePay;?>>
            Make a Payment
        </button>
    </div>
<?php
        if ($numExpired > 0) {
            if ($numExpired == 1)
                $expMsg = "one unpaid item  in your purchased list that is";
            else
                $expMsg = $numExpired . " unpaid items in your purchased list that are";
?>
    <div class="row mt-4">
        <div class="col-sm-12">
            <p>
                <span class="text-danger"><b>NOTE:</span> You have <?php echo $expMsg;?> no longer valid for purchase. This is bacause they
                either are no longer available for sale via the portal or the date for which they could have been purchased has passed.</b>
            </p>
            <p>
                You must use the "Add To/Edit Cart" for each person who has expired items in the list above and delete those items from the account.
                You can then replace them with items that are currently available for purchase. If you have issues with this please reach out to registration at
                the email address below.
            </p>
        </div>
    </div>
    <div class='row'>
        <div class='col-sm-12 p-0 m-0 align-center'>
            <hr style='height:4px;width:98%;margin:auto;margin-top:0px;margin-bottom:0px;color:#333333;background-color:#333333;'/>
        </div>
    </div>
<?php
        }
    }
}
if ($noPayments) {
?>
    <div class='row mt-4'>
        <div class='col-sm-12 align-center'>
            <h1 class="h3">You have not made any payments yet.</h1>
        </div>
    </div>
<?php
} else {
?>
</div>
<div class="container-fluid">
<?php
}
portalPageFoot();

