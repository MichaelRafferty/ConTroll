<?php
<?php
global $db_ini;

require_once "../lib/base.php";
require_once "../lib/email.php";


$check_auth = google_init("ajax");
$perm = "admin";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);

if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}
load_email_procs();

$test = true;
$email = "mike@bsfs.org";

if(!$_POST || !$_POST['action']) {
    $response['error'] = "missing trigger";
    ajaxSuccess($response);
    exit();
}

if($_POST['action'] == "test") {
    if($_POST['email']) { $email = $_POST['email']; }
} else if($_POST['action']=="full") {
    $test=false;
}

$response['test'] = $test;

$con = get_conf("con");
$reg = get_conf("reg");
$conid=$con['id'];

$emailQ = "SELECT DISTINCT P.email_addr as email FROM reg as R JOIN perinfo as P on P.id=R.perid where R.conid=$conid and R.paid=R.price and P.email_addr like '%@%' and P.contact_ok='Y'";
$emailR = dbQuery($emailQ);
$response['numEmails'] = $emailR->num_rows;

$email_array=array();
$data_array=array();

if($test) {
    $email_array = array($email);
} else {
    while($addr = fetch_safe_assoc($emailR)) {
       array_push($email_array, $addr['email']);
    }
}

foreach ($email_array as $email) {
    $return_arr = send_email($con['regadminemail'], trim($email), /* cc */ null, $condata['label']. " Welcome Email",  preConEmail_last_TEXT($reg['test']), preConEmail_last_HTML($reg['test']));


    if ($return_arr[''] == 'success') {
        array_push($data_array, array($email, "success"));
    } else {
        array_push($data_array, array($email, $return_arr['email_error']));
        $success = 'error';
    }

sleep(1);
}

$response['status'] = $success;
$response['error'] = $data_array;
$response['email_array'] = $email_array;

ajaxSuccess($response);
?>
