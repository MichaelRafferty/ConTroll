<?php
global $db_ini;

require_once "../lib/base.php";

$check_auth = google_init("ajax");
$perm = "artshow";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);


if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

$con=get_con();
$conid=$con['id'];
$conf = get_conf('con');

if(!isset($_POST) || count($_POST) == 0) { ajaxError("No Data"); }
if(!isset($_POST['perid'])) { ajaxError("Need a new ID"); }
if(!isset($_POST['artid'])) { ajaxError("Need a new ID"); }

$new = false;
$more = false;
$mailin = false;
$mailin_count = 0;
$query = "";
if($_POST['detailsId'] != '') {
  $query = "UPDATE artshow SET ";
  $query_end = " WHERE id='".sql_safe($_POST['detailsId'])."';";

  $query_info = "conid='".$con['id']."'";
} else {
  $query = "INSERT INTO artshow SET ";
  $query_end = ";";

  $countR = fetch_safe_assoc(dbQuery("SELECT count(*) as c from artshow where conid=$conid"));
  $count = $countR['c'];
  if(isset($_POST['mailin']) and ($_POST['mailin'] != 'mailin')) {
    $artkey = 100+$count;
  } else {
    $mailin=true;
    $artkey = 200+$count;
  }

  if(isset($_POST['mailin']) and ($_POST['mailin'] != 'special')) {
    $new=true;
    $counterQ = "UPDATE artshow_reg SET ";
    $counterQ_end = "WHERE conid=$conid;";
  }


  $query_info = "conid='".$con['id']."'";
  $query_info .= ", art_key=$artkey";
}


foreach ($_POST as $key => $value) {
  if($value != "") {
    switch($key) {
      case 'perid':
      case 'artid':
        if($query_info != "") { $query_info .= ", "; }
        $query_info .= "$key='".sql_safe($value)."'";
        break;
      case 'agent_request':
        if($query_info != "") { $query_info .= ", "; }
        $query_info .= "$key='".sql_safe($value)."'";
        break;
      case 'key':
        if($query_info != "") { $query_info .= ", "; }
        $query_info .= "art_key='".sql_safe($value)."'";
        break;
      case 'asp_count':
        if($query_info != "") { $query_info .= ", "; }
        $query_info .= "a_panels='".sql_safe($value)."'";
        if($new) {
            if($mailin) {
                $mailin_count += $value;
            } else {
                if($more) { $counterQ .= ", "; }
                $counterQ .= "cur_art = cur_art + $value";
                $more = true;
            }
        }
        break;
      case 'ast_count':
        if($query_info != "") { $query_info .= ", "; }
        $query_info .= "a_tables='".sql_safe($value)."'";
        if($new) {
            if($more) { $counterQ .= ", "; }
            $counterQ .= "cur_table = cur_table + " . sql_safe($value);
            $more = true;
        }
        break;
      case 'asp':
        if($query_info != "") { $query_info .= ", "; }
        $query_info .= "a_panel_list='".sql_safe(str_replace(' ', '', $value))."'";
        break;
      case 'ast':
        if($query_info != "") { $query_info .= ", "; }
        $query_info .= "a_table_list='".sql_safe(str_replace(' ', '', $value))."'";
        break;
      case 'psp_count':
        if($query_info != "") { $query_info .= ", "; }
        $query_info .= "p_panels='".sql_safe($value)."'";
        if($new) {
            if($mailin) {
                $mailin_count += $value;
            } else {
                if($more) { $counterQ .= ", "; }
                $counterQ .= "cur_print = cur_print + ". sql_safe(str_replace(' ', '', $value));
                $more = true;
            }
        }
        break;
      case 'pst_count':
        if($query_info != "") { $query_info .= ", "; }
        $query_info .= "p_tables='".sql_safe(str_replace(' ', '', $value))."'";
        break;
      case 'psp':
        if($query_info != "") { $query_info .= ", "; }
        $query_info .= "p_panel_list='".sql_safe(str_replace(' ', '', $value))."'";
        break;
      case 'pst':
        if($query_info != "") { $query_info .= ", "; }
        $query_info .= "p_table_list='".sql_safe(str_replace(' ', '', $value))."'";
        break;
      case 'mailin':
        if($query_info != "") { $query_info .= ", "; }
        if($value=='special') {$value='attending';}
        $query_info .= "attending='".sql_safe(str_replace(' ', '', $value))."'";
        break;
      case 'desc':
        if($query_info != "") { $query_info .= ", "; }
        $query_info .= "description='".sql_safe(str_replace(' ', '', $value))."'";
      case 'detailsId': break; // do nothing
      default: break; // do nothing
    }
  }
}
//$string = str_replace(' ', '', $string);

if($mailin and $mailin_count > 0) {
    if($more) { $counterQ .= ", "; }
    $counterQ .= "cur_mailin = cur_mailin + ". $mailin_count;

}

$query = $query . $query_info . $query_end;
if($new) { $counter = $counterQ . " " . $counterQ_end; }
if($new) { $response['counter'] = $counter; }
$response['query'] = $query;

dbQuery($query);
if($new) { dbQuery($counter);}

ajaxSuccess($response);
?>
