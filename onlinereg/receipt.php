<?php
require_once("lib/base.php");

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

$owner = dbSafeQuery($ownerQ, 'i', array($transid))->fetch_assoc();
if ($owner == null) {
    $owner = array('first_name' => '', 'last_name' => '');
}
ol_page_init($condata['label'] . ' Registration Complete');
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

  if($transid==0 or !isset($owner['complete_date']) or ($owner['complete_date'] == null)) {
        ?>
        <p>
            Somehow you managed to get here without information on your purchase.
            If you don't know how that happened please contact
            <a href="mailto:<?php echo escape_quotes($con['regemail']); ?>"><?php echo $con['regemail']; ?></a> and we appologize for the confusion.
        </p>

        <?php } else {
      if ($owner['payid'] != null) { ?>
Your transaction number is <?php echo $transid; ?> and receipt number is
        <?php echo $owner['payid']; if ($owner['url'] != '') echo ' (<a href="' . escape_quotes($owner['url']) . '">' . $owner['url'] . "</a>)";
      } else {
          ?>
          Your transaction number is <?php echo $transid; ?> and as this transaction has no charge, your receipt has been emailed to you. <?php
          } ?>.<br />
        <p>
            In response to your request memberships have been created for <ul>
                <?php

$badgeQ = <<<EOS
SELECT NP.first_name, NP.last_name, M.label
FROM transaction T
JOIN reg R ON  (R.create_trans=T.id)
JOIN newperson NP ON (NP.id = R.newperid)
JOIN memLabel M ON (R.memID = M.id)
WHERE  T.id= ?
EOS;

$badgeR = dbSafeQuery($badgeQ, 'i', array($transid));

while($badge = $badgeR->fetch_assoc()) {
                ?>
                <li>
                    <?php echo $badge['first_name'] . " " . $badge['last_name']
    . " (" . $badge['label'] . ")"; ?>
                </li>
                <?php
}
                ?>
            </ul>
        </p>
        <?php if ($owner['url'] != '') { ?>
        <p>
            You should have an email from <?php echo $con['regadminemail']; ?> containing the credit card transaction receipt url and details of the memberships listed above. If you haven't received it in a few minutes please check your spam folder.
        </p>
        <?php } else { ?>
        <p>
            You should have an email from <?php echo $con['regadminemail']; ?> containing the transaction receipt and details of the memberships listed above. If you haven't received them in a few minutes please check your spam folder.
        </p>
        <?php } ?>
        <p>
            Please contact <a href="mailto:<?php echo escape_quotes($con['regadminemail']); ?>">
                <?php echo $con['regadminemail']; ?>
            </a><?php if ($con['regadminemail'] <> $con['regemail']) {?> or <a href="mailto:<?php echo escape_quotes($con['regemail']); ?>">
                <?php echo $con['regadminemail']; ?>
            </a><?php }?> with any questions and we look forward to seeing you at <?php echo $condata['label']; ?>
        </p>
        <?php } ?>
    </div>
    For hotel information and directions please see <a href="<?php echo escape_quotes($con['hotelwebsite']); ?>">
        the <?php echo $con['conname']; ?> hotel page
    </a>

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
