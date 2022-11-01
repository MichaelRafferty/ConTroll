<?php
global $db_ini;

require_once "../lib/base.php";

$check_auth = google_init("ajax");
$perm = "admin";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);


if($check_auth == false || (!checkAuth($check_auth['sub'], $perm) &&
                            !checkAuth($check_auth['sub'], 'atcon'))) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

$con = get_conf('con');
$conid=$con['id'];

//ajax_request_action: "update_nextcondata",
//tabledata: next_condata.getData(),
//tablename: "conlist",
//indexcol: "id"
$response['error'] = 'Dump';
var_error_log($_POST);


$action=$_POST['ajax_request_action'];

switch ($action) {
    case 'update_nextcondata':
        $data = $_POST['tabledata'][0];
        $sql = <<<EOS
INSERT INTO conlist(id, name, label, startdate, enddate, create_date)
VALUES(?,?,?,?,?,NOW())
ON DUPLICATE KEY UPDATE name=?, label=?, startdate=?, enddate=?;
EOS;
        $num_rows = dbSafeInsert($sql, "issssssss", array(
            $data['id'],
            $data['name'],
            $data['label'],
            $data['startdate'],
            $data['enddate'],
            $data['name'],
            $data['label'],
            $data['startdate'],
            $data['enddate']
        ));
        if ($num_rows > 0) {
            $response['success'] =  "Convention " . $data['id'] . " updated.";
        } else {
            $response['success'] = "Nothing to change";
        }
        break;
    default:
        $response['error'] = "Invalid Request";
}

ajaxSuccess($response);
?>
