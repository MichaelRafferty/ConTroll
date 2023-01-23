<?php
if(!isset($_SERVER['HTTPS']) or $_SERVER["HTTPS"] != "on") {
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: https://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]);
    exit();
}

require_once "../lib/ajax_functions.php";
require_once "../lib/db_functions.php";
require_once "../lib/log.php";
require_once("../lib/cc__load_methods.php");


db_connect();

require_once "../lib/email.php";
require_once "../../../aws-api/aws-autoloader.php";

use Aws\Ses\SesClient;
use Aws\Ses\Exception\SesException;

global $con;

$con = get_con();
$conid = $con['id'];
$ini = get_conf('artshow');

$ccauth = get_conf('cc');
load_cc_procs();

$log = get_conf('log');
logInit($log['artshow']);


$amazonCred = get_conf('email');
$awsClient = SesClient::factory(array(
    'key'=>$amazonCred['aws_access_key_id'],
    'secret'=>$amazonCred['aws_secret_access_key'],
    'region'=>'us-east-1',
    'version'=>'2010-12-01'
    ));

$email = "no send attempt or a failure";

session_start();
if(!isset($_SESSION['id'])) { ajaxSuccess(array('status'=>'error', 'message'=>'Session Failure')); exit; }

$venId = $_SESSION['id'];

$response = array("post" => $_POST, "get" => $_GET);

$vendorR = dbQuery("SELECT * from vendors where id=$venId;");
$vendor = fetch_safe_assoc($vendorR);

$body = "Vendor " . $vendor['name'] . " Paid an Invoice for $ " . $_POST['total']
    . " covering " . $_POST['table_count'] . " tables and " . $_POST['mem_cnt'] . " memberships.  The information they included with the payment is below.\n\n";

$total = $_POST['total'];

foreach ($_POST as $key => $value) {
    switch ($key) {
        case 'vendor':
        case 'total':
        case 'nonce':
        case 'nds-pmd':
            break;
        default:
            $body .= "$key: $value\n";
    };
}

$alley_priceQ = "SELECT type, price_full as price from vendor_reg where conid=$conid and type='alley';";
$alley_priceR = dbQuery($alley_priceQ);
$prices = array();

while($price = fetch_safe_assoc($alley_priceR)) {
    $prices[$price['type']] = $price['price'];
}


$memRow = fetch_safe_assoc(dbQuery("SELECT id, price FROM memList WHERE conid=$conid and label='Vendor';"));

$request = array(
    'alley' => $_POST['table_count'],
    'custid' => $_POST['vendor'],
    'memberships' => $_POST['mem_cnt'],
    'prices' => $prices,
    'memPrice' => $memRow['price'],
    'total' => $total,
    'nonce' => $_POST['nonce']);

$transid = dbInsert("INSERT INTO transaction (conid, price, paid, notes) VALUES ($conid, $total, 0, 'Artist Alley Purchase');");

$request['transid'] = $transid;

/* */
$response['request'] = $request;
$rtn = cc_vendor_purchase($request);
$response['purchase_plan']=$rtn;
  if ($rtn === null) {
    ajaxSuccess(array('status'=>'error', 'data'=>'Credit card not approved'));
    exit();
  }
/* * / ajaxSuccess($response); exit(); /* */

$num_fields = sizeof($rtn['txnfields']);
$val = array();
for ($i = 0; $i < $num_fields; $i++) {
    $val[$i] = '?';
}


$txnQ = "INSERT INTO payments(time," . implode(',', $rtn['txnfields']) . ') VALUES(current_time(),' . implode(',', $val) . ');';
$txnT = implode('', $rtn['tnxtypes']);
$txnid = dbSafeInsert($txnQ, $txnT, $rtn['tnxdata']);
$approved_amt =  $rtn['amount'];


$txnUpdate = "UPDATE transaction SET ";
if($approved_amt == $total) {
    $txnUpdate .= "complete_date=current_timestamp(), ";
}
$txnUpdate .= "paid=? WHERE id=?;";
$txnU = dbSafeCmd($txnUpdate, "di", array($approved_amt, $transid) );

$tableUpdate = "UPDATE vendor_show SET purchased='" . sql_safe($_POST['table_count'])
    . "', price='" . sql_safe($_POST['table_sub']) . "', paid='" 
    . sql_safe($_POST['table_sub']) . "', transid='$transid'"
    . " WHERE vendor=$venId and type='alley' and conid=$conid;";
dbQuery($tableUpdate);
$regUpdate = "UPDATE vendor_reg SET registered=registered + ".sql_safe($_POST['table_count'])." WHERE conid=$conid and type='alley';";
dbQuery($regUpdate);


$reg1 = "";
$reg2 = "";

$memId = $memRow['id'];

if($_POST['alley_mem1_lname'] != '') {
  $newPeople = "INSERT INTO newperson (last_name, middle_name, first_name, badge_name, address, addr_2, city, state, zip, share_reg_ok, contact_ok) VALUES "
    . '(\'' . sql_safe($_POST['alley_mem1_lname']) . '\',\''
          . sql_safe($_POST['alley_mem1_mname']) . '\',\''
          . sql_safe($_POST['alley_mem1_fname']) . '\',\''
          . sql_safe($_POST['alley_mem1_bname']) . '\',\''
          . sql_safe($_POST['alley_mem1_address']) . '\',\''
          . sql_safe($_POST['alley_mem1_addr2']) . '\',\''
          . sql_safe($_POST['alley_mem1_city']) . '\',\''
          . sql_safe($_POST['alley_mem1_state']) . '\',\''
          . sql_safe($_POST['alley_mem1_zip']) . '\',\'Y\',\'N\');';
  $per1 = dbInsert($newPeople);

  $reg1 = "INSERT INTO reg (conid, newperid, price, paid, memId, create_trans) VALUES ($conid, $per1, ".$memRow['price'].", ".$memRow['price'].", $memId, $transid);";
}  

if($_POST['alley_mem2_lname'] != '') {
   $newPeople = "INSERT INTO newperson (last_name, middle_name, first_name, badge_name, address, addr_2, city, state, zip, share_reg_ok, contact_ok) VALUES "
    . '(\'' . sql_safe($_POST['alley_mem2_lname']) . '\',\''
          . sql_safe($_POST['alley_mem2_mname']) . '\',\''
          . sql_safe($_POST['alley_mem2_fname']) . '\',\''
          . sql_safe($_POST['alley_mem2_bname']) . '\',\''
          . sql_safe($_POST['alley_mem2_address']) . '\',\''
          . sql_safe($_POST['alley_mem2_addr2']) . '\',\''
          . sql_safe($_POST['alley_mem2_city']) . '\',\''
          . sql_safe($_POST['alley_mem2_state']) . '\',\''
          . sql_safe($_POST['alley_mem2_zip']) . '\',\'Y\',\'N\');';
  $per2 = dbInsert($newPeople);

  $reg2 = "INSERT INTO reg (conid, newperid, price, paid, memId, create_trans) VALUES ($conid, $per2, ".$memRow['price'].", ".$memRow['price'].", $memId, $transid);";
} 

$response['per1'] = $reg1;
$response['per2'] = $reg2;

  $body .= "Receipt Link to come";

  if($reg1 != "") { dbQuery($reg1); }
  if($reg2 != "") { dbQuery($reg2); }

$info = get_conf('vendor');

$email_msg = "no send attempt or a failure";
  try {
    $email_msg = $awsClient->sendEmail(array(
      'Source' => 'regadmin@bsfs.org',
      'Destination' => array(
        'ToAddresses' => array($_POST['email'], $info['alley'])
      ),
      'Message' => array(
        'Subject' => array(
          'Data' => $con['label']. " Online Vendor Purchase"
        ),
        'Body' => array(
          'Text' => array(
            'Data' => $body
          ) // HTML
        )
      )// ReplyToAddresses or ReturnPath
    ));
    $email_error = "none";
    $success = "success";
    $data = "success";
  } catch (AwsException $e) {
    $email_error = $e->getCode();
    $success="error";
    $data=$e->getMessage();
} 

ajaxSuccess(array(
  "status"=>$success,
//  "url"=>$url,
  "data"=>$data,
  "trans"=>$transid,
 // "email"=>$email_msg,
  "email_error"=>$email_error
));
?>
