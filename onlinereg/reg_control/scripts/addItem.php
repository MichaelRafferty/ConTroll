<?php
global $ini;
if (!$ini)
    $ini = parse_ini_file(__DIR__ . "/../../../config/reg_conf.ini", true);
if ($ini['reg']['https'] <> 0) {
    if(!isset($_SERVER['HTTPS']) or $_SERVER["HTTPS"] != "on") {
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: https://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]);
        exit();
    }
}

require_once "../lib/base.php";
require_once "../lib/ajax_functions.php";

$check_auth = google_init("ajax");
$perm = "art_control";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);


if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

$conf = get_conf('con');

$con = get_con();
$conid=$con['id'];

$artshowQ = "SELECT id FROM artshow WHERE art_key='"
    . sql_safe($_POST['artist']) . "' AND conid=$conid;";
$artshow = fetch_safe_array(dbQuery($artshowQ));

$pin = $artshow[0];


$query = "SELECT max(item_key) FROM artItems where artshow='$pin';";
$keyR = fetch_safe_array(dbQuery($query));
$maxKey=$keyR[0];
if($maxKey == '') { $maxKey=1; }
else { $maxKey +=1; }

$newItem = "INSERT INTO artItems SET conid=$conid, ";
if(!isset($_POST['type'])) {
  ajaxSuccess(array('error'=>'Bad Type'));
  exit();
}

switch($_POST['type']) {
  case 'nfs':
    if(!isset($_POST['title']) || !isset($_POST['price'])) {
      ajaxSuccess(array('error'=>'Bad Data'));
      exit();
    }
    $newItem .= "type='nfs', title='".sql_safe($_POST['title'])."', ";
    $newItem .= "min_price='".sql_safe($_POST['price'])."', ";
    $newItem .= "artshow='$pin', item_key='$maxKey';";
    break;
  case 'art':
    if(!isset($_POST['title']) || !isset($_POST['price'])) {
      ajaxSuccess(array('error'=>'Bad Data'));
      exit();
    }

    if(isset($_POST['qsale']) && intval($_POST['price']) >= intval($_POST['qsale'])) {
      ajaxSuccess(array('error'=>'ERROR: Quicksale price must be higher than the minimum bid/insurance price!'));
      exit();
    }

    $newItem .= "type='art', title='".sql_safe($_POST['title'])."', ";
    $newItem .= "min_price='".sql_safe($_POST['price'])."', ";
    if(isset($_POST['qsale'])) { $newItem .= "sale_price='".sql_safe($_POST['qsale'])."', "; }
    $newItem .= "artshow='$pin', item_key='$maxKey';";
    break;
  case 'print':
    if(!isset($_POST['title']) || !isset($_POST['qsale']) || !isset($_POST['qty'])) {
      ajaxSuccess(array('error'=>'Bad Data'));
      exit();
    }
    $newItem .= "type='print', title='".sql_safe($_POST['title'])."', ";
    $newItem .= "min_price='".sql_safe($_POST['qsale'])."', ";
    $newItem .= "sale_price='".sql_safe($_POST['qsale'])."', ";
    $newItem .= "quantity='".sql_safe($_POST['qty'])."', ";
    $newItem .= "original_qty='".sql_safe($_POST['qty'])."', ";
    $newItem .= "artshow='$pin', item_key='$maxKey';";
    break;
  default:
    ajaxSuccess(array('error'=>'Bad Type'));
    exit();
}

$newI = dbInsert($newItem);

    $artQ = "SELECT I.item_key, I.title, I.type, I.status, I.location" .
        ", I.quantity, I.original_qty, I.min_price, I.sale_price" .
        ", I.final_price, A.art_key" .
        ", concat_ws(',', A.a_panel_list, A.a_table_list, A.p_panel_list, A.p_table_list) as loc_list" .
        ", AR.art_name as artist" .
        ", concat_ws(' ', P.first_name, P.middle_name, P.last_name, P.suffix) as name" .
        ", concat_ws(' ', B.first_name, B.middle_name, B.last_name, B.suffix, B.id) as bidder" .
        " FROM artItems as I JOIN artshow as A ON A.id=I.artshow" .
        " JOIN artist as AR ON AR.id=A.artid" .
        " JOIN perinfo as P ON P.id=A.perid" .
        " LEFT OUTER JOIN perinfo as B on B.id=I.bidder" .
        " WHERE I.conid=$conid" .
        " ORDER BY A.art_key, I.item_key;";

$items=array();

    $artR = dbQuery($artQ);
    while($artItem = fetch_safe_assoc($artR)) {
        array_push($items, $artItem);
    }

$response['newI'] = $newI;
$response['art'] = $items;

ajaxSuccess($response);
?>
