<?php
require_once('../lib/base.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$response = array('post' => $_POST, 'get' => $_GET);
$vendor = 0;

if(isset($_SESSION['id'])) {
    $vendor = $_SESSION['id'];
} else {
    $response['status']='error';
    $response['message']='Authentication Failure';
    ajaxSuccess($response);
    exit();
}

// name
// email
// website
// description
// publicity
// addr
// addr2
// city
// state
// zip

$updateQ = <<<EOS
UPDATE vendors
SET name=?, email=?, website=?, description=?, publicity=?, addr=?, addr2=?, city=?, state=?, zip=?
WHERE id=?
EOS;
$publicity = $_POST['publicity'] == 'on';
$updateArr = array(
        $_POST['name'],
        $_POST['email'],
        $_POST['website'],
        $_POST['description'],
        $publicity,
        $_POST['addr'],
        $_POST['addr2'],
        $_POST['city'],
        $_POST['state'],
        $_POST['zip'],
        $vendor
    );
$numrows = dbSafeCmd($updateQ, 'ssssisssssi', $updateArr);
if ($numrows == 1)
    $response['success'] = "Profile Updated";
else if ($numrows == 0)
    $response['success'] = "Nothing to update";
else
    $response['error'] = 'Error encounted updating profile';

ajaxSuccess($response);
?>
