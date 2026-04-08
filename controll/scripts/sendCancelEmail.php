<?php
require_once "../lib/base.php";
require_once "../lib/email.php";
require_once(__DIR__ . "/../../lib/email__load_methods.php");
require_once '../lib/sessionAuth.php';

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$perm = 'admin';
$response = array ('post' => $_POST, 'get' => $_GET, 'perm' => $perm);
$authToken = new authToken('script');
$response['tokenStatus'] = $authToken->checkToken();
if (!$authToken->isLoggedIn() || !$authToken->checkAuth($perm)) {
    $response['error'] = 'Authentication Failed';
    ajaxSuccess($response);
    exit();
}

load_email_procs();

$test = true;
$email = "raffem47@yahoo.com";
$tid=63513;

if(!$_POST || !$_POST['action']) {
    $response['error'] = "missing trigger";
    ajaxSuccess($response);
    exit();
}

if($_POST['action'] == "test") {
    if($_POST['tid']) { $tid = $_POST['tid']; }
} else if($_POST['action']=="full") {
    $test=false;
}

$con = get_conf("con");
$testsite = getConfValue('reg', 'test') == 1;
$conid=$con['id'];

$emailQ = <<<EOS
SELECT distinct P.email_addr AS email, create_trans AS tid
FROM memList M
JOIN reg R ON (R.memId=M.id)
JOIN perinfo P ON (P.id=R.perid)
JOIN payments Y ON (Y.transid=R.create_trans)
WHERE M.memCategory in ('standard', 'yearahead') and M.conid=? order by tid;
EOS;

$emailR = dbSafeQuery($emailQ, 'i', $conid);
$response['numEmails'] = $emailR->num_rows;

$email_array=array();
$data_array=array();

if($test) {
    $emailR = dbSafeQuery("select DISTINCT P.email_addr AS email, create_trans AS tid FROM reg R JOIN perinfo P ON (P.id=R.perid) WHERE create_trans=?;", 'i', array($tid));
    while ($email_value = $emailR->fetch_assoc()) {
        array_push($email_array, array('email'=>$email_value['email'], 'tid'=>$email_value['tid']));
    }
} else {
    while($addr = $emailR->fetch_assoc()) {
        array_push($email_array, array('email'=>$addr['email'], 'tid'=>$addr['tid']));
    }
}

$success = 'success';
foreach ($email_array as $email) {
    $return_arr = send_email($con['regadminemail'], trim($email['email']), /* cc */ null, $con['label']. " Membership Cancelation Instructions",  
        refundEmail_TEXT($testsite, $email['email'], $email['tid']), refundEmail_HTML($testsite, $email['email'], $email['tid']));


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
