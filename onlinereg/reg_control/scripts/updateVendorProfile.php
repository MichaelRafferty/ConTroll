<?php
global $db_ini;

require_once '../lib/base.php';
$check_auth = google_init('ajax');
$perm = 'vendor';

$response = array('post' => $_POST, 'get' => $_GET, 'perm' => $perm);

if ($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = 'Authentication Failed';
    ajaxSuccess($response);
    exit();
}

$con = get_con();
$conid = $con['id'];

if (!array_key_exists('vendorId', $_POST)) {
    $response['error'] = 'Invalid calling sequence, no vendor';
    ajaxSuccess($response);
    exit();
}
$vendor = $_POST['vendorId'];

if ($vendor > 0) {
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
        $response['error'] = 'Error encountered updating profile';
} else {
    $updateQ = <<<EOS
INSERT INTO vendors(name, email, website, description, publicity, addr, addr2, city, state, zip)
VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?);
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
        $_POST['zip']
    );
    $newid = dbSafeCmd($updateQ, 'ssssisssss', $updateArr);
    if ($newid)
        $response['success'] = 'Vendor Added';
    else
        $response['error'] = 'Error encountered adding vendor';
}

ajaxSuccess($response);
?>
