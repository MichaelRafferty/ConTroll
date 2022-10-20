<?php
require_once(__DIR__ . "/../../lib/db_functions.php");
require_once(__DIR__ . "/../../lib/log.php");
$ini = get_conf('reg');
if ($ini['https'] <> 0) {     
    if(!isset($_SERVER['HTTPS']) or $_SERVER["HTTPS"] != "on") {
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: https://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]);
        exit();
    }
}
db_connect();
$con = get_con();
$ini = get_conf('reg');
$conid=$con['id'];

$log = get_conf('log');
logInit($log['cancel']);

$badgeQ = "SELECT id, label from memList where memCategory='cancel';";
$badgeR = dbQuery($badgeQ);
$badge = array();

while($badgeA = fetch_safe_assoc($badgeR)) {
    $badge[$badgeA['label']] = $badgeA['id'];
}

?>
<html>
<head>
<title>Balticon Cancelation Page</title>
</head>
<body>
<img src="images/<?php echo $con['name']; ?>.jpg" width='100%' height='auto' alt="Baltimore Science Fiction Society . Balticon . May 25-28, 2018" height="206" width="1173"></img>
<h1><?php echo $con['label']; ?> Registration Cancelation Page</h1>
<?php
  if($ini['test']==1) {
    ?>
    <h2 class='warn'>This Page is for test purposes only</h2>
    <?php
  }
  if(($ini['suspended'] != 1) or ($ini['close'] != 1)) {
  ?>
    <p><?php echo $con['label']; ?> has not been canceled.  Please contact <a href='mailto:registration@balticon.org'>registration@balticon.org</a> if you have concerns about your membership.</p>
  <?php
  } else { // canceled
    //var_dump($_POST);
    $response = $_POST;
    
    $infoQ = "SELECT paid FROM transaction where id='" . sql_safe($_POST['tid']) . "';";
    
    $infoR = dbQuery($infoQ);
    if($infoR->num_rows < 1) { ?> <p>I'm sorry we were unable to find that transaction. Please try resubmitting the prior page or contact  <a href='mailto:registration@balticon.org'>registration@balticon.org</a></p> <?php }
    else { 
        // need to test for an handle case where some action has already been taken.

        $info_assoc = fetch_safe_assoc($infoR);
        $response['amount'] = $info_assoc['paid'];
    }

    logWrite($response);

    switch($_POST['choice']) {
        case 'donate':
            $updateQ = "UPDATE reg SET memId = " . $badge['Donated'] . " WHERE create_trans='". sql_safe($_POST['tid']) ."';";
            //echo $updateQ;
            dbQuery($updateQ);
            ?><p>Thank you for your donation to the Baltimore Science Fiction Society in the amount of $<?php echo $response['amount']; ?>!  Your generosity is appreciated!</p><?php
            break;
        case 'rollover':
            $updateQ = "UPDATE reg SET memId = " . $badge['Request Rollover'] . " WHERE create_trans='". sql_safe($_POST['tid']) ."';";
            //echo $updateQ;
            dbQuery($updateQ);
            ?><p>Thank you for rolling your membership over to Balticon 55.  We look forward to seeing you in 2021!</p><?php // put something in here about the donation if any
            break;
        case 'refund':
            $updateQ = "UPDATE reg SET memId = " . $badge['Request Refund'] . " WHERE create_trans='". sql_safe($_POST['tid']) ."';";
            //echo $updateQ;
            dbQuery($updateQ);
            ?><p>Thank you for your response. We will process the refund as soon as possible. Please remember that Balticon is a 100% volunteer run organization, and we thank you in advance for your patience.  Please contact <a href='refunds@balticon.org'>refunds@balticon.org</a> if you have any concerns.</p>
            Name: <?php echo $_POST['name']; ?><br/>
            Address Line 1: <?php echo $_POST['addr1']; ?><br/>
            Address Line 2: <?php echo $_POST['addr2']; ?><br/>
            City/State/Zip: <?php echo $_POST['city']; ?>
            <?php echo $_POST['state']; ?>
            <?php echo $_POST['zip']; ?><br/>
            <?php // for online implement the API refund and change message
            
            break;
        default:
            ?><p>Thank you for your response. Our registration team will contact you directly to assist.</p><?php
    }

    ?><p>We regret that we had to cancel Balticon 54 this year.  Once the social distancing requirements are lifted, please check out <a href="http://bsfs.org/bsfscldr.htm">The Baltimore Science Fiction Society Event Calendar</a> to see if there are any events you are interested in.</p>
  <?php
  }
?>
</body>
</html>
