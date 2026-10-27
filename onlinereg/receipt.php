<?php
require_once("lib/base.php");
require_once('../lib/paymentPlans.php');
require_once('../lib/tax.php');
require_once("../lib/receipt.php");

$condata = get_con();
$con = get_conf('con');
$startdate = new DateTime($condata['startdate']);
$enddate = new DateTime($condata['enddate']);
$daterange = $startdate->format("F j-") . $enddate->format("j, Y");
$altstring = $con['org'] . '. ' . $condata['label'] . ' . ' . $daterange;
$testsite = getConfValue('reg', 'test') == 1;

$transid = 0;
if(isset($_GET) && isset($_GET['trans']) && is_numeric($_GET['trans'])) {
    $transid = $_GET['trans'];
}
if(isset($_POST) && isset($_POST['trans']) && is_numeric($_POST['trans'])) {
    $transid = $_POST['trans'];
}

$ownerQ = <<<EOS
SELECT NP.first_name, NP.last_name, T.complete_date, P.receipt_id as payid, P.receipt_url as url 
FROM transaction T
JOIN newperson NP ON (NP.id=T.newperid)
LEFT OUTER JOIN payments P ON (P.transid=T.id)
WHERE T.id=?;
EOS;

$currency = getConfValue('con', 'currency', 'USD');
$onedaycoupons = getConfValue('con', 'onedaycoupons', 0);

$locale = getLocale();
$config_vars = array();
$config_vars['label'] = $con['label'];
$config_vars['required'] = getConfValue('reg', 'required', 'addr');
$config_vars['conid'] = $condata['id'];
$config_vars['debug'] = getConfValue('debug', 'onlinereg', 0);
$config_vars['locale'] = $locale;
$config_vars['currency'] = $currency;
$config_vars['onedaycoupons'] = $onedaycoupons;

$owner = dbSafeQuery($ownerQ, 'i', array($transid))->fetch_assoc();
if ($owner == null) {
    $owner = array('first_name' => '', 'last_name' => '');
}

$js = 'var config = ' . json_encode($config_vars) . ';' . PHP_EOL;
ol_page_init($condata['label'] . ' Registration Complete', $js);
?>
<body>
    <div class="container-fluid">
        <?php
            $logoImage = getConfValue('reg', 'logoimage');
            if ($logoImage != '') { ?>
        <img class="img-fluid" src="images/<?php echo $logoImage; ?>" alt="<?php echo escape_quotes($altstring);?>"/>
        <?php }
            $logoText = getConfValue('reg', 'logotext');
            if ($logoText != '') { ?>
        <div style='display:inline-block' class='display-1'><?php echo $logoText; ?></div>
        <?php } ?>
    </div>
    <h1>
        <?php echo $owner['first_name'] . " " . $owner['last_name']; ?> thank you for registering for <?php echo $condata['label']; ?>
    </h1>
    <div>
        <?php
  if($testsite) {
        ?>
        <h2 class='warn'>This Page is for test purposes only</h2>
        <?php
  }

  if($transid==0 || !isset($owner['complete_date']) or ($owner['complete_date'] == null)) {
        ?>
        <p>
            Somehow you managed to get here without information on your purchase.
            If you don't know how that happened please contact
            <a href="mailto:<?php echo escape_quotes($con['regemail']); ?>"><?php echo $con['regemail']; ?></a> and we appologize for the confusion.
        </p>

        <?php } else {

      $receipts = trans_receipt($transid);

      if ($owner['url'] != '') { ?>
        <p>
            You should have an email from <?php echo $con['regadminemail']; ?> containing the credit card transaction receipt url and details of the
            memberships purchased as shown in your receipt below.
        </p>
        <?php } else { ?>
        <p>
            You should have an email from <?php echo $con['regadminemail']; ?> containing the transaction receipt and details of the memberships in the
            receipt below.
        </p>
        <?php } ?>
        <p>
          If you haven't received the email in a few minutes please check your spam folder.
        </p>
        <p>
            Please contact <a href="mailto:<?php echo escape_quotes($con['regadminemail']); ?>">
                <?php echo $con['regadminemail']; ?>
            </a><?php if ($con['regadminemail'] <> $con['regemail']) {?> or <a href="mailto:<?php echo escape_quotes($con['regemail']); ?>">
                <?php echo $con['regadminemail']; ?>
            </a><?php }?> with any questions and we look forward to seeing you at <?php echo $condata['label']; ?>
        </p>

    </div>
    For hotel information and directions please see <a href="<?php echo escape_quotes($con['hotelwebsite']); ?>">
        the <?php echo $con['conname']; ?> hotel page
    </a>
    <?php
        echo "<p></p></p><p>This is your receipt:</p>\n";
        echo $receipts['receipt_html'] . "\n</div>\n";
    }
    ?>
    <br />
    <hr />
    <a href="<?php echo escape_quotes($con['policy']);?>" target="_blank">
        Click here for the <?php echo $con['policytext']; ?>
    </a>.<br />
    For more information about <?php echo $con['conname']; ?> please email <a href="mailto:<?php echo escape_quotes($con['infoemail']); ?>">
        <?php echo $con['infoemail']; ?>
    </a>.<br />
    For questions about <?php echo $con['conname']; ?> Registration, email <a href="mailto:<?php echo escape_quotes($con['regemail']); ?>">
        <?php echo $con['regemail']; ?>
    </a>.<br />

</body>
</html>
