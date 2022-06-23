<?php
  require_once("lib/db_functions.php");
  $ini = get_conf('reg');
  if ($ini['https'] <> 0) {     
      if(!isset($_SERVER['HTTPS']) or $_SERVER["HTTPS"] != "on") {
          header("HTTP/1.1 301 Moved Permanently");
          header("Location: https://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]);
          exit();
      }
  }
  db_connect();
  $condata = get_con();
  $conid=$condata['id'];
  $con=get_conf('con');


$price = array('adult'=>56, 'youth'=>28, 'child'=>19, 'all'=>56);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link href='css/style.css' rel='stylesheet' type='text/css' />
    <link href='css/jquery-ui-1.13.1.css' rel='stylesheet' type='text/css' />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <script type='text/javascript' src='javascript/jquery-min-3.60.js'></script>
    <script type='text/javascript' src='javascript/jquery-ui.min-1.13.1.js'></script>
    <script type='text/javascript' src='javascript/store.js'></script>
<title><?php echo $condata['label']; ?> Cancelation Page</title>
</head>
<body style='padding: 5px'>
    <div class="container-fluid">
        <img class="img-fluid" src="images/<?php echo $ini['logoimage']; ?>" alt="<?php echo $altstring ;?>"/>
    </div>
<h1><?php echo $condata['label']; ?> Registration Cancelation Page</h1>
<?php
if($ini['test']==1) {
?>
    <h2 class='text-danger'><strong>This Page is for test purposes only</strong></h2>
    <?php
}
$ini['suspended'] = 1;
$ini['close'] = 1;
if(($ini['suspended'] != 1) or ($ini['close'] != 1)) {
  ?>
    <p><?php echo $condata['label']; ?> has not been canceled.  Please contact <a href="mailto:<?php echo $con['regemail']; ?>"><?php echo $con['regemail']; ?></a> if you have concerns about your membership.</p>
  <?php
  } else { // actually canceled
    ?>
    <h1>Membership Cancelation Page</h1>
    <p>Thank you for coming to our membership cancelation page. We regret that we had to cancel <?php echo $condata['label']; ?> and thank you for your patience with us during these difficult times and your support for <?PHP echo $con['conname']; ?> and the <?PHP echo $con['org']; ?>.</p>
    <p>This page handles the cancelation of convention memberships purchased at the prior convention and via online registration or mail in. If you're looking for information on refunds for the Art Show, Artist Alley, or Dealer Room please email <a href='mailto:<?php echo $con['refundemail']; ?>'><?php echo $con['refundemail']; ?></a>.</p> 
    <?php
    if(!isset($_GET) or !isset($_GET['email']) or !isset($_GET['tid'])) {
      ?><p>Please provide the transaction id from your receipt or the email from registration</p>
      <form>
        Email: <input type='text' name='email'/><br/>
        Transaction #: <input type='text' name='tid'/><br/>
        <input type='submit'/> <input type='reset'/><br/>
      </form>
      <?php
    } else { //info provided
        //var_dump($_GET);
        $checkQ = "SELECT first_name, last_name, email_addr, R.create_trans FROM perinfo as P, reg as R WHERE R.perid=P.id and R.conid=$conid and R.create_trans = " . sql_safe($_GET['tid']) . " and email_addr = '" . sql_safe($_GET['email']) . "';";
    
        //echo $checkQ;
        $checkR = dbQuery($checkQ);
        if($checkR->num_rows < 1) {
            ?><p>We were unable to find this trasaction, please contact <a href='mailto:registration@balticon.org'>registration@balticon.org</a> for assistance.</p><?php
        } else { // transaction found
            $transQ = "SELECT T.paid, M.label, M.memAge"
                    . ", P.first_name, P.last_name, P.badge_name"
                    . ", R.paid, Y.description, Y.amount as total"
                . " FROM transaction as T"
                    . " JOIN reg as R on R.create_trans=T.id"
                    . " JOIN perinfo as P on P.id=R.perid"
                    . " JOIN memList as M on M.id=R.memId"
                    . " JOIN payments as Y on Y.transid=T.id"
                . " WHERE T.id = " . sql_safe($_GET['tid']) 
                . " AND M.memCategory in ('standard', 'yearahead')" . ";";
            $transR = dbQuery($transQ);
            ?>
            <form method='POST' action='cancelTrans.php'>
            <input type='hidden' name='email' value='<?php echo $_GET['email'];?>'/>
            <input type='hidden' name='tid' value='<?php echo $_GET['tid']; ?>'/>
            
            <p>We found <?php echo $transR->num_rows; ?> memberships on transaction <?php echo $_GET['tid']; ?>.</p> <?php
            $T_was = 'unknown';
            $T_paid = 0;
            $T_difference = 0;
            while($trans = fetch_safe_assoc($transR)) {
                $T_paid = $trans['total'];
                $T_difference += $trans['paid'] - $price[$trans['memAge']];

                //var_dump($trans);
                ?>
                <p>
                Name: <?php echo $trans['first_name'] . " " . $trans['last_name']; ?><br/>
                Badge Name: <?php echo $trans['badge_name']; ?><br/>
                Type: <?php echo $trans['label']; ?><br/>
                </p>
                <?php
                if($trans['description'] == 'Balticon Online Registration') {   
                    if($T_was == 'atcon') { $T_was = 'weird'; } 
                    else if ($T_was != 'weird') { $T_was = 'online'; }
                } else {
                    if($T_was == 'online') { $T_was = 'weird'; } 
                    else if ($T_was != 'weird') { $T_was = 'atcon'; }
                }
            }
            ?><p>The total amount of the transaction was $<?php echo $T_paid; ?>. Please fill out the form below to tell us how you want your membership(s) processed.</p>
            <h3>Processing Method</h3>
            <p>Please select one of the items below to tell us what to do:</p>
            <input type='hidden' name='source' value='<?php echo $T_was;?>'A/>
            <DL>
                <DT><label><strong><input type='radio' id='donate_choice' name='choice' value='donate'/>Donate my membership(s)</strong></label></DT>
                <DD><label for='donate_choice'>
                Balticon is run by the Baltimore Science Fiction Society (BSFS) which depends on memberships from Balticon for nearly all its yearly budget. Please consider donating the cost of your membership(s) ($<?php echo $T_paid;?>) to the BSFS. As a 501(c)3 non-profit, all donations to BSFS are tax-deductible (please contact your tax professionals for full details).
                </label></DD>
                <DT><label><strong><input type='radio' id='rollover_choice' name='choice' value='rollover'/>Rollover my membership(s)</strong></label></DT>
                <DD><label for='rollover_choice'>
                A rollover will convert your existing Balticon 54 membership(s) to a Balticon 55 membership, at no additional cost to you.
                </label></DD>
                <DT><label><strong><input type='radio' id='refund_choice' name='choice' value='refund'/>Refund my membership(s)</strong></label></DT>
                <DD><label for='refund_choice'>
            <?php
            switch($T_was) { 
                case 'online':
                    ?>For online purchases, we can refund your purchase to your original method of payment.<?php
                    break;
                case 'atcon':
                    ?>For at con or mail in purchases, we can mail you a check to refund the full amount of your membership(s).<?php
                    break;
                case 'weird': 
                default:
                    ?>Something weird happened with the transaction, please contact <a href='mailto:registration@balticon.org'>registration@balticon.org</a> for assistance.<?php
                    break;
            } ?>
                If the refund needs to be done another way please contact <a href='mailto:registration@balticon.org'>registration@balticon.org</a>.
                </label></DD>
                <DT><label><strong><input type='radio' id='complex_choice' name='choice' value='complex'/>Other</strong></label></DT>
                <DD><label for='complex_choice'>
                If you are looking for something that's not represented here, please note it in the comment field and you will be contacted by  <a href='mailto:registration@balticon.org'>registration@balticon.org</a>.</label></DD>
            </DD>

        <br/>
        Comment:<br/><textarea cols=40 rows=5></textarea>
        <br/>
        <p>Please provide current contact information that we can use to process this request.</p>
        Name: <input type='text' name='name'  placeholder='Full Name'></input><br/>
        Email: <input type='text' name='email'  placeholder='Email Address'></input><br/>
        Phone: <input type='text' name='phone'  placeholder='Phone #'></input><br/>
        Address:<br/>
            <input type='text' name='addr1' size=40 placeholder='Address Line 1'></input><br/>
            <input type='text' name='addr2' size=40 placeholder='Address Line 2'></input><br/>
            <input type='text' name='city' placeholder='City / State / Zip'></input> <input type='text' size=2 name='state'></input> <input type='text' size=5 name='zip'></input><br/>
        <p>The information provided here will only be used to process your request.  Your request will be processed as staff is available.  All requests are due by June 15th, 2020.  We hope to have all rollovers, refunds, and donations processed by the end of the summer.  Please remember that Balticon is a 100% volunteer run organization, and we thank you in advance for your patience.  </p>
        <br/>
        <input type='submit'><input type='reset'>
        </form><?php
        }
    }
  }
?>
</body>
</html>
