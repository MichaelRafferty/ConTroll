<?php
require_once("lib/base.php");

$condata = get_con();
$ini = get_conf('reg');
$con = get_conf('con');
$startdate = new DateTime($condata['startdate']);
$enddate = new DateTime($condata['enddate']);
$daterange = $startdate->format("F j-") . $enddate->format("j, Y");
$altstring = $con['org'] . '. ' . $condata['label'] . ' . ' . $daterange;


$transid = 0;
if(isset($_GET) && isset($_GET['trans']) && is_numeric($_GET['trans'])) {
    $transid = $_GET['trans'];
}
if(isset($_POST) && isset($_POST['trans']) && is_numeric($_POST['trans'])) {
    $transid = $_POST['trans'];
}
$include_interested = true;
if ($transid < 0) {
    $transid = -$transid;
    $include_interested = false;
}
// temp code for testing, if 0 transid, skip the receipt portion and don't complain
if ($transid != 0 && $transid != 1) {
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
} else {
    // for testing recp additional html
    $owner = array('first_name' => 'first', 'last_name' => 'last', 'complete_date' => '2024-01-01 00:00', 'payid' => 'receipt id', 'url' => 'receipt_url');
    $transid = 1;
}
ol_page_init($condata['label'] . ' Registration Complete');
?>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-auto">
                <?php if (array_key_exists('logoimage', $ini) && $ini['logoimage'] != '') { ?>
                <img class="img-fluid" src="images/<?php echo $ini['logoimage']; ?>" alt="<?php echo escape_quotes($altstring);?>"/>
                <?php }
                      if(array_key_exists('logotext', $ini) && $ini['logotext'] != '') { ?>
                          <div style='display:inline-block' class='display-1'><?php echo $ini['logotext']; ?></div>
                <?php } ?>
            </div>
        </div>
        <div class='row'>
            <div class='col-sm-auto'>
            <h1>
                <?php echo $owner['first_name'] . " " . $owner['last_name']; ?> thank you for registering for <?php echo $condata['label']; ?>
            </h1>
            </div>
        </div>
        <?php
  if($ini['test']==1) {
        ?>
        <div class='row'>
            <div class='col-sm-auto'>
                <h2 class='warn'>This Page is for test purposes only</h2>
            </div>
        </div>
        <div class='row'>
            <div class='col-sm-auto'>
        <?php
  }

  if($transid==0 or !isset($owner['complete_date']) or ($owner['complete_date'] == null)) {
        ?>
                    Somehow you managed to get here without information on your purchase.
                    If you don't know how that happened please contact
                    <a href="mailto:<?php echo escape_quotes($con['regemail']); ?>"><?php echo $con['regemail']; ?></a> and we appologize for the confusion.
            </div>
        </div>
        <div class='row'>
            <div class='col-sm-auto'>
        <?php } else {
      if ($owner['payid'] != null) { ?>
Your transaction number is <?php echo $transid; ?> and receipt number is
        <?php echo $owner['payid']; if ($owner['url'] != '') echo ' (<a href="' . escape_quotes($owner['url']) . '">' . $owner['url'] . "</a>)";
      } else {
          ?>
Your transaction number is <?php echo $transid; ?> and as this transaction has no charge, your receipt has been emailed to you. <?php
          } ?>.
            </div>
        </div>
        <div class='row'>
            <div class='col-sm-auto'>
            In response to your request memberships have been created for
            </div>
        </div>
        <div class='row'>
            <div class='col-sm-auto'>
                <ul>
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
            </div>
        </div>
        <div class='row mt-2'>
            <div class='col-sm-auto'>
        <?php if ($owner['url'] != '') { ?>
            You should have an email from <?php echo $con['regadminemail']; ?> containing the credit card transaction receipt url and details of the memberships listed above. If you haven't received it in a few minutes please check your spam folder.
        <?php } else { ?>
            You should have an email from <?php echo $con['regadminemail']; ?> containing the transaction receipt and details of the memberships listed above. If you haven't received them in a few minutes please check your spam folder.
        <?php } ?>
            </div>
        </div>
        <div class='row mt-2'>
            <div class='col-sm-auto'>
            Please contact <a href="mailto:<?php echo escape_quotes($con['regadminemail']); ?>"><?php echo $con['regadminemail']; ?></a>
            <?php if ($con['regadminemail'] <> $con['regemail']) {?> or
                    <a href="mailto:<?php echo escape_quotes($con['regemail']); ?>"><?php echo $con['regemail']; ?></a>
            <?php }?> with any questions and we look forward to seeing you at <?php echo $condata['label']; ?>
        <?php } ?>
            </div>
        </div>
        <div class='row mt-2'>
            <div class='col-sm-auto'>
    For hotel information and directions please see <a href="<?php echo escape_quotes($con['hotelwebsite']); ?>">the <?php echo $con['conname']; ?> hotel page</a>
            </div>
        </div>
        <hr />
        <?php
        if ($include_interested)
            $key = 'receiptaddlhtml';
        else
            $key = 'receiptaddlhtmlpost';

        if (array_key_exists($key, $ini)) {
            $addlText = null;
            $addlFile = $ini[$key];
            if ($addlFile !== null && $addlFile != '') {
                if (str_starts_with($addlFile, '/')) {
                    if (is_readable($addlFile)) {
                        $addlText = file_get_contents($addlFile);
                    }
                } else {
                    if (is_readable('../config/' . $addlFile)) {
                        $addlText = file_get_contents('../config/' . $addlFile);
                    }
                }
            }
            if ($addlText !== null && $addlText != '') {
                if (str_contains($addlText, '<!--ADD-HIDDEN-FIELDS-->')) {
                    // build hidden fields for this person
                    $addlText = str_replace('<!--ADD-HIDDEN-FIELDS-->', "<input type='hidden' name='tid' value='$transid'/>", $addlText);
                }
                ?>
        <div class='row'>
            <div class='col-sm-auto'>
                <?php echo "$addlText" . PHP_EOL; ?>
            </div>
        </div>
        <hr/>
        <?php }
        } ?>
        <div class='row mt-2'>
            <div class='col-sm-auto'>
                <a href="<?php echo escape_quotes($con['policy']);?>" target="_blank">Click here for the <?php echo $con['policytext']; ?></a>.
            </div>
        </div>
        <div class='row'>
            <div class='col-sm-auto'>
                For more information about <?php echo $con['conname']; ?> please email
                <a href="mailto:<?php echo escape_quotes($con['infoemail']); ?>"><?php echo $con['infoemail']; ?></a>.
            </div>
        </div>
        <div class='row'>
            <div class='col-sm-auto'>
                For questions about <?php echo $con['conname']; ?> Registration, email
                <a href="mailto:<?php echo escape_quotes($con['regemail']); ?>"><?php echo $con['regemail']; ?></a>
            </div>
        </div>
    </div>
</body>
</html>
