<?php
require_once("base.php");

function sendCC($amount, $track, $source) {
  $ccauth = get_conf('cc');
  $cclink = get_conf('cc-connect');

  $resp = array();

  // DO CREDIT CARD
  $ccsale = array(
    'ssl_merchant_id'=>$ccauth['ssl_merchant_id'],
    'ssl_user_id'=>$ccauth['ssl_user_id'],
    'ssl_pin'=>$ccauth['ssl_pin'],
    'ssl_test_mode'=>$cclink['test_mode'],
    'ssl_transaction_type'=>'CCSALE',
    'ssl_show_form'=>'false',
    'ssl_amount'=>$amount,
    'ssl_description'=>"Balticon Artshow",
    'ssl_result_format'=>'ASCII',
  );

  $swipeccsale = array(
    'ssl_track_data'=>urlencode($track),
  );

  $ccsale = array_merge($ccsale, $swipeccsale);

  $args = "";
  foreach($ccsale as $key => $value) {
    if($args != "") { $args .= "&"; }
    $args.="$key=$value";
  }
  $url = "https://".$cclink['host'].$cclink['site'];
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

  $resp_array = array();
  $response = curl_exec($ch);

  $response_lines = preg_split("/\n/", $response);
  foreach($response_lines as $line) {
    $line_array = preg_split("/=/", $line);
    if($line_array[1]!="") { $resp_array[$line_array[0]]=$line_array[1]; }
  }

  $db_keys = array(
    "ssl_result_message",
    "ssl_amount",
    "ssl_txn_id",
    "ssl_txn_time",
    "ssl_approval_code",
    "ssl_status",
    "ssl_card_number",
    "ssl_description"
    );
  $info_keys = array(
    "ssl_card_number",
    "ssl_txn_id",
    "ssl_approval_code",
    "ssl_txn_time",
    "ssl_amount"
  );

  $ccdb = array_intersect_key($resp_array, array_flip($db_keys));
  $ccinfo = array_intersect_key($resp_array, array_flip($info_keys));
  $resp['ccinfo'] = $ccinfo;

  if(isset($resp_array['errorCode'])) {
    $resp['data']=$resp_array;
    $resp['error']=$resp_array['errorMessage'];
    $resp['success']=false;
    return($resp);
  }

  if(isset($resp_array['ssl_result_message']) &&
    ($resp_array['ssl_result_message']=='APPROVAL' or $resp_array['ssl_result_message']=='APPROVED' )) {

    $resp['paymentResponse'] = json_decode(recordCCSale($ccdb, $source), true);
    $resp['payment'] = $resp['paymentResponse']['payment'];
    $resp['success'] = true;

    $tempfile = tempnam(sys_get_temp_dir(), 'prnCtrl');
    if(!$tempfile) {
        $resp['error'] = "Print Failure: tempnam error: " . error_get_last();
        return($resp);
    }

    $temp = fopen($tempfile, "w");
    if(!$temp) {
        $resp['error'] = "Print Failure: Fopen error: " . error_get_last();
        return($resp);
    }
    
    fwrite($temp, buildSalesDraft($ccinfo));
    fclose($temp);

    shell_exec("lp -d receipt $tempfile");
    unlink($tempfile);
    
    return($resp);
  } else {
    $resp['data'] = $resp_array;
    $resp['error']="The credit card request was denied.  Please check for mis-typed data or use a different form of payment.";
    $resp['success'] = false;
    return($resp);
  }

  return($resp);
}

function recordCCSale($dbinfo, $source) {
    $method='POST';
    $data = "user=".$_SESSION['user']."&passwd=".$_SESSION['passwd'] . "&src=$source";
    foreach ($dbinfo as $key => $value) {
        $data .= "&$key=$value";
    }
    
#    echo callHome("recordCC.php", "POST", $data);
#    exit();
    return(callHome("recordCC.php", "POST", $data));
}

function buildSalesDraft($ccinfo) {
  $width = 30;

  $str = "Sales Draft";
  $pad = floor($width/2 + strlen($str)/2);
  $return = "\n" . sprintf("%${pad}s", $str) . "\n";

  $con = get_conf('con');

  $strArray = array(
    $con['label'],
    "Baltimore Science Fiction Society",
    "",
    "Street Address",
    "3310 E. Baltimore Street",
    "Baltimore, MD 21224-2220",
    "Mailing Address",
    "PO BOX 686",
    "Baltimore, MD 21203-0686",
    "Phone: 410-JOE-BSFS"
  );
  foreach($strArray as $str) {
    $pad = floor($width/2 + strlen($str)/2);
    $return .= sprintf("%${pad}s", $str) . "\n";
  }

  $return .= "Account  : " . $ccinfo['ssl_card_number'] . "\n";
  $return .= "Ref      : " . $ccinfo['ssl_txn_id'] . "\n";
  $return .= "Auth Code: " . $ccinfo['ssl_approval_code'] . "\n";
  $return .= "Date/Time: " . $ccinfo['ssl_txn_time'] . "\n";
  $return .= "\n";
  $return .= "Amount : $" . $ccinfo['ssl_amount'] . "\n";
  $return .= "I agree to pay above total amount\n";
  $return .= "According to card issuer agreement\n";
  $return .= "\n" . str_repeat('_', $width) . "\n\n\n";
  $return .= str_repeat('=', $width) . "\n\n\n\n\n\n\n\n\n";

  return $return;
}
?>
