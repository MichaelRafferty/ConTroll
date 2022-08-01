<?php
require_once "../lib/base.php";

$check_auth = google_init("ajax");
$perm = "art_control";
$conf = get_conf('con');
$con=get_con();
$conid=$con['id'];



$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);


if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

if(!isset($_POST['key'])) { ajaxError("No Data"); }
$key=explode("_", $_POST['key']);

$artshowQ ="SELECT id FROM artshow WHERE conid=$conid AND art_key='".sql_safe($key[0])."';";
$artshow = fetch_safe_array(dbquery($artshowQ));

$updateQ = "";
if(isset($_POST['action']) && $_POST['action']=='delete') {
  if($conf['mode'] == 'online') {
    $updateQ = "DELETE FROM artItems WHERE conid=$conid AND item_key='"
      . sql_safe($key[1]) . "' AND artshow=" . $artshow[0] . ";";
  } else {
    ajaxSuccess(array('error'=>"may not be on con register when removing items"));
    exit();
  }
} else {
  $updateQ = "UPDATE artItems SET title='" . sql_safe($_POST['title']) . "'"
    . ", min_price='" . sql_safe($_POST['min_price']) . "'"
    . ", sale_price='" . sql_safe($_POST['sale_price']) . "'"
    . ", quantity='" . sql_safe($_POST['quantity']) . "'"
    . ", original_qty='" . sql_safe($_POST['original_qty']) . "'"
    . ", status='" . sql_safe($_POST['status']) . "'"
    . ", location='" . sql_safe($_POST['location']) . "'"
    . " WHERE conid=$conid AND item_key='" . sql_safe($key[1]) . "'"
    . " AND artshow=". $artshow[0] . ";";
}

$response['updateQ'] = $updateQ;

dbQuery($updateQ);
    $artQ = "SELECT I.item_key, I.title, I.type, I.status, I.location" .
        ", I.quantity, I.original_qty, I.min_price, I.sale_price" .
        ", I.final_price, A.art_key" .
        ", concat_ws(',', A.a_panel_list, A.a_table_list, A.p_panel_list, A.p_table_list) as loc_list" .
        ", V.name as artist" .
        ", concat_ws(' ', P.first_name, P.middle_name, P.last_name, P.suffix) as name" .
        ", concat_ws(' ', B.first_name, B.middle_name, B.last_name, B.suffix, B.id) as bidder" .
        " FROM artItems as I JOIN artshow as A ON A.id=I.artshow" .
        " JOIN artist as AR ON AR.id=A.artid" .
        " JOIN perinfo as P ON P.id=A.perid" .
        " JOIN vendors as V on V.id=AR.vendor" .
        " LEFT OUTER JOIN perinfo as B on B.id=I.bidder" .
        " WHERE I.conid=$conid;";

$items=array();

    $artR = dbQuery($artQ);
    while($artItem = fetch_safe_assoc($artR)) {
        array_push($items, $artItem);
    }

$response['art']  =$items;
$response['mode'] = $conf['mode'];



ajaxSuccess($response);
?>
