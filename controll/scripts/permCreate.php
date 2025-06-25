<?php
global $db_ini;
require_once "../lib/base.php";

$check_auth = google_init("ajax");
$perm = "admin";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);


if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

if (!array_key_exists('perid', $_POST)) {
    ajaxError("Calling Sequence Error");
    exit();
}
$perid = $_POST['perid'];

// check if perid valid
$checkQ = <<<EOS
SELECT COUNT(*) FROM perinfo where id = ?;
EOS;
$checkR = dbSafeQuery($checkQ, 'i', array($perid));
$checkL = $checkR->fetch_row();
$count = $checkL[0];

if ($count != 1) {
    $response['error'] = "Person doesn't exist";
    ajaxSuccess($response);
    exit();
}

// check if the user exists already in the user list
$checkQ = <<<EOS
SELECT COUNT(*) FROM user where perid = ?;
EOS;
$checkR = dbSafeQuery($checkQ, 'i', array($perid));
$checkL = $checkR->fetch_row();
$count = $checkL[0];

if ($count > 0) {
    $response['error'] = "User already exists";
    ajaxSuccess($response);
    exit();
}

$insertQ = <<<EOS
INSERT INTO user(perid, email, name,google_sub)
SELECT id, email_addr,
   CASE  
        WHEN last_name != '' THEN TRIM(REGEXP_REPLACE(CONCAT(last_name, ', ', CONCAT_WS(' ', first_name, middle_name, suffix)), '  *', ' ')) 
        ELSE TRIM(REGEXP_REPLACE(CONCAT_WS(' ', first_name, middle_name, suffix), '  *', ' '))  
    END AS name, "" AS google_sub
FROM perinfo
WHERE id = ?
EOS;

$newid = dbSafeInsert($insertQ, 'i', array($perid));
if ($newid > 0) {
    $response['success'] = "User $newid added";
} else {
    $response['error'] = "Error inserting user";
}

ajaxSuccess($response);
?>
