<?php
require_once("lib/base.php");
$ini = redirect_https();

$condata = get_con();
$ini = get_conf('reg');
$con = get_conf('con');

$transid = 0;
if(isset($_GET) && isset($_GET['trans']) && is_numeric($_GET['trans'])) {
    $transid = $_GET['trans'];
}

$ownerQ = "select NP.first_name, NP.last_name, T.complete_date, P.receipt_id as payid, P.receipt_url as url from newperson as NP, transaction as T, payments as P where P.transid=T.id and NP.id=T.newperid and T.id='" . sql_safe($transid) . "';";
$owner = fetch_safe_assoc(dbQuery($ownerQ));
ol_page_init($condata['label'] . ' Registration Complete');
?>
<body>
    <img src="images/<?php echo $ini['logoimage']; ?>" alt="<?php echo $altstring ;?>" width="50%" height="auto" />
    <h1>
        <?php echo $owner['first_name'] . " " . $owner['last_name']; ?>
thank you for registering for <?php echo $condata['label']; ?>
    </h1>
    <div>
        <?php
  if($ini['test']==1) {
        ?>
        <h2 class='warn'>This Page is for test purposes only</h2>
        <?php
  }

  if($transid==0 or !isset($owner['complete_date']) or ($owner['complete_date'] == null)) {
        ?>
        <p>
            Somehow you managed to get here without information on your purchase.  If you don't know how that happened please contact <a href='mailto:<?php echo $con['regemail']; ?>'>
                <?php echo $con['regemail']; ?>
            </a> and we appologize for the confusion.
        </p>

        <?php } else { ?>
Your Transaction number is <?php echo $transid; ?> and Receipt number is
        <?php echo $owner['payid']; if ($owner['url'] != '') echo " (<a href='" . $owner['url'] . "'>" . $owner['url'] . "</a>)"; ?>.<br />
        <p>
            In response to your request Badges have been created for <ul>
                <?php

$badgeQ = "select NP.first_name, NP.last_name, M.label from memList as M, newperson as NP, transaction as T, reg as R where M.id=R.memId and NP.id=R.newperid and R.create_trans=T.id and T.id='". sql_safe($transid) . "';";
$badgeR = dbQuery($badgeQ);

while($badge = fetch_safe_assoc($badgeR)) {
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
            You should have an email from <?php echo $con['regadminemail']; ?> containing the credit card transaction receipt url and details of the badges listed above. If you haven't received it in a few minutes please check your spam folder.
        </p>
        <?php } else { ?>
        <p>
            You should have two emails from <?php echo $con['regadminemail']; ?> containing the credit card transaction receipt and details of the badges listed above. If you haven't received them in a few minutes please check your spam folder.
        </p>
        <?php } ?>
        <p>
            Please contact <a href='mailto:<?php echo $con['regadminemail']; ?>'>
                <?php echo $con['regadminemail']; ?>
            </a><?php if ($con['regadminemail'] <> $con['regemail']) {?> or <a href='mailto:<?php echo $con['regemail']; ?>'>
                <?php echo $con['regadminemail']; ?>
            </a><?php }?> with any questions and we look forward to seeing you at <?php echo $condata['label']; ?>
        </p>
        <?php } ?>
    </div>
    For hotel information and directions please see <a href="<?php echo $con['hotelwebsite']; ?>">
        the <?php echo $con['conname']; ?> hotel page
    </a>

    <br />
    <hr />
    <a href="<?php echo $con['policy'];?>" target="_blank">
        Click here for the <?php echo $con['policytext']; ?>
    </a>.<br />
    For more information about <?php echo $con['conname']; ?> please email <a href="mailto:<?php echo $con['infoemail']; ?>">
        <?php echo $con['infoemail']; ?>
    </a>.<br />
    For questions about <?php echo $con['conname']; ?> Registration, email <a href="mailto:<?php echo $con['regemail']; ?>">
        <?php echo $con['regemail']; ?>
    </a>.<br />

</body>
</html>
