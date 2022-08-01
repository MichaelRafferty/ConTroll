<?php
require_once "lib/base.php";
require_once "lib/ajax_functions.php";

$response = array("post" => $_POST, "get" => $_GET);


$perm="data_entry";
$con = get_con();
$conid=$con['id'];
$check_auth=false;
if(isset($_POST) && isset($_POST['user']) && isset($_POST['passwd'])) {
    $check_auth = check_atcon($_POST['user'], $_POST['passwd'], $perm, $conid);
}

if($check_auth == false) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

if(!(isset($_POST['transid']) && isset($_POST['perid']) 
        && isset($_POST['type']) && isset($_POST['age']))) {
    $response['error'] = "Missing Data";
    ajaxSuccess($response);
    exit();
}

$transid = sql_safe($_POST['transid']);
$perid = sql_safe($_POST['perid']);
$userid = sql_safe($_POST['user']);
$action = sql_safe($_POST['type']);
$age = sql_safe($_POST['age']);
$origid = sql_safe($_POST['origId']);

$atconR = dbQuery("SELECT id FROM atcon WHERE transid=$transid;");
$atcon = fetch_safe_assoc($atconR);
$atconid = $atcon['id'];

$memR = dbQuery("SELECT  label, memCategory, memAge, id, price from memList where conid=$conid+1;");
$memIds = array('yearahead' => array(), 'rollover'=>array(), 'volunteer'=>array());
$prices = array('yearahead' => array(), 'rollover'=>array(), 'volunteer'=>array());

while ($mem = fetch_safe_assoc($memR)) {
    if($mem['label'] == 'Rollover') {
        $memIds['rollover']=array('adult'=>$mem['id'], 'youth'=>$mem['id'], 'child'=>$mem['id'], 'all'=>$mem['id']);
        $prices['rollover']=array('adult'=>$mem['price'], 'youth'=>$mem['price'], 'child'=>$mem['price'], 'all'=>$mem['price']);
    } else if($mem['label'] == 'Volunteer') {
        $memIds['volunteer']=array('adult'=>$mem['id'], 'youth'=>$mem['id'], 'child'=>$mem['id'], 'all'=>$mem['id']);
        $prices['volunteer']=array('adult'=>$mem['price'], 'youth'=>$mem['price'], 'child'=>$mem['price'], 'all'=>$mem['price']);
    } else if($mem['memCategory']=='yearahead') {
        $memIds['yearahead'][$mem['memAge']]=$mem['id'];
        $prices['yearahead'][$mem['memAge']]=$mem['price'];
    }

}

$memid = $memIds[$action][$age];
$price = $prices[$action][$age];

$newconid = $conid + 1;

$regQ = "INSERT IGNORE INTO reg (conid, perid, memId, price, create_trans) VALUES"
    . " ($newconid, $perid, $memid, $price, $transid);";


$badgeId = dbInsert($regQ);

$actionQ = "INSERT IGNORE INTO atcon_badge (atconId, badgeId, action, comment) VALUES"
    . " ($atconid, $badgeId, '$action', '$age'),"
    . " ($atconid, $origid, 'notes', '$action'),"
    . " ($atconid, $badgeId, 'attach', '$age');";


$response['action'] = dbInsert($actionQ);
$response['reg'] = $badgeId;
$response['price'] = $price;


ajaxSuccess($response);
?>
